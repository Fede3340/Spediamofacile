/**
 * usePaymentWallet — flusso pagamento con saldo wallet interno.
 *
 * Wallet = 2-step backend: /api/wallet/pay (debito saldo) →
 * /api/stripe/mark-order-completed (finalizzazione). NO Stripe SDK, NO 3DS.
 */
import type { PaymentFlowSharedDeps } from './types'

type WalletPayResponse = {
	success?: boolean
	message?: string
	error?: string
	data?: { id?: string | number }
}

export function usePaymentWallet(deps: PaymentFlowSharedDeps) {
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

	async function payWithWallet() {
		// Il totale mostrato in checkout nasce da useCart(): se la preview
		// coupon/referral diverge dal totale canonico ordine, il backend
		// rifiuta la finalizzazione (no drift silenzioso).
		const orderId = await resolvePayableOrderId()
		const isExisting = Boolean(cart.existingOrder?.value || cart.existingOrderId?.value)
		const submissionContext = buildSubmissionContext({ preferExisting: isExisting, generate: true })
		const submissionId = submissionContext.client_submission_id

		persistPaymentDraft({
			orderId,
			paymentMethod: 'wallet',
			submissionId,
			isExisting,
			amount: Number(cart.finalTotal?.value ?? 0),
		})

		paymentStep.value = 'Addebito saldo wallet...'
		const amountEur = Number(cart.finalTotal?.value ?? 0)
		const res = await callWithAuthRetry<WalletPayResponse>(
			() =>
				sanctum('/api/wallet/pay', {
					method: 'POST',
					body: {
						amount: amountEur,
						reference: `order-${orderId}`,
						description: `Pagamento ordine #${orderId}`,
					},
				}) as Promise<WalletPayResponse>,
			{ label: 'wallet pay' },
		)
		if (!res?.success || !res?.data?.id) {
			// Distingue saldo insufficiente da errore tecnico/rete.
			const serverMessage = res?.message || res?.error
			const isInsufficientFunds = typeof serverMessage === 'string'
				&& /saldo|insufficien/i.test(serverMessage)
			const fallback = isInsufficientFunds
				? 'Saldo wallet insufficiente per completare il pagamento.'
				: 'Errore durante l\'addebito dal wallet. Riprova tra poco o contatta l\'assistenza.'
			throw new Error(serverMessage || fallback)
		}
		const walletTransactionId = res.data.id

		paymentStep.value = 'Finalizzazione...'
		await callWithAuthRetry(
			() =>
				sanctum('/api/stripe/mark-order-completed', {
					method: 'POST',
					body: {
						order_id: orderId,
						payment_type: 'wallet',
						ext_id: `wallet-${walletTransactionId}`,
						is_existing_order: isExisting,
						...submissionContext,
					},
				}),
			{ label: 'wallet mark-completed' },
		)
		await onPaymentSuccess(orderId, 'wallet')
	}

	return { payWithWallet }
}
