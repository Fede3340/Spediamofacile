/**
 * usePaymentBonifico — flusso pagamento bonifico bancario.
 *
 * Il bonifico e' il flusso piu' isolato: NO Stripe SDK, NO carta state,
 * NO 3DS. L'unico ruolo e' registrare l'ordine come "pagamento pendente"
 * e lasciare al backend l'invio email con coordinate IBAN.
 */
import type { PaymentFlowSharedDeps } from './types'

export function usePaymentBonifico(deps: PaymentFlowSharedDeps) {
	const {
		cart,
		paymentStep,
		sanctum,
		resolvePayableOrderId,
		buildSubmissionContext,
		persistPaymentDraft,
		callWithAuthRetry,
		onPaymentSuccess,
	} = deps

	async function payWithBonifico() {
		const orderId = await resolvePayableOrderId()
		const isExisting = Boolean(cart.existingOrder?.value || cart.existingOrderId?.value)
		const submissionContext = buildSubmissionContext({ preferExisting: isExisting, generate: true })
		const submissionId = submissionContext.client_submission_id

		persistPaymentDraft({
			orderId,
			paymentMethod: 'bonifico',
			submissionId,
			isExisting,
			amount: Number(cart.finalTotal?.value ?? 0),
		})

		paymentStep.value = 'Registrazione ordine...'
		await callWithAuthRetry(
			() =>
				sanctum('/api/stripe/mark-order-completed', {
					method: 'POST',
					body: {
						order_id: orderId,
						payment_type: 'bonifico',
						is_existing_order: isExisting,
						...submissionContext,
					},
				}),
			{ label: 'bonifico mark-completed' },
		)
		await onPaymentSuccess(orderId, 'bonifico')
	}

	return { payWithBonifico }
}
