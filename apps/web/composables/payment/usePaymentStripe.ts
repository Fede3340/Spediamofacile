/**
 * usePaymentStripe — flusso pagamento carta Stripe (3DS automatico).
 *
 * Gestisce sia carta salvata (handleCardAction) sia nuova (confirmCardPayment),
 * con 3DS challenge senza re-mount del Card Element.
 *
 * Il LIFECYCLE Stripe SDK (loadStripe, mount cardElement) RESTA
 * nell'orchestratore usePayment.ts perche' lazy-mount-aware. Qui riceviamo
 * `stripe` e `cardElement` gia' pronti come Ref<>.
 */
import type { Ref } from 'vue'
import type { Stripe, StripeCardElement } from '@stripe/stripe-js'
import type { PaymentFlowSharedDeps } from './types'

type UserProfile = { name?: string }

type DefaultPaymentResponse = { card?: { id?: string } }

type StripePaymentResponse = {
	payment_intent_id?: string | null
	status?: string
	client_secret?: string
	error?: string
}

type PaymentIntentResponse = {
	client_secret?: string
	error?: string
}

const asRecord = (value: unknown): Record<string, unknown> =>
	value && typeof value === 'object' ? value as Record<string, unknown> : {}
const getErrorMessage = (error: unknown): string => String(asRecord(error).message || '')

export type PaymentStripeDeps = PaymentFlowSharedDeps & {
	stripe: Ref<Stripe | null>
	cardElement: Ref<StripeCardElement | null>
	hasSavedCard: Ref<boolean>
	useNewCard: Ref<boolean>
	defaultPayment: Ref<DefaultPaymentResponse | null>
	saveCardForFuture: Ref<boolean>
	user: Ref<unknown | null>
	markOrderPaid: (orderId: string | number, extId: string | null, isExisting: boolean, submissionId?: string) => Promise<void>
}

export function usePaymentStripe(deps: PaymentStripeDeps) {
	const {
		stripe,
		cardElement,
		hasSavedCard,
		useNewCard,
		defaultPayment,
		saveCardForFuture,
		cart,
		user,
		paymentStep,
		sanctum,
		resolvePayableOrderId,
		buildSubmissionContext,
		persistPaymentDraft,
		markOrderPaid,
		onPaymentSuccess,
	} = deps

	async function payWithCard() {
		const stripeClient = stripe.value
		if (!stripeClient) throw new Error('Stripe non inizializzato.')
		const orderId = await resolvePayableOrderId()
		const isExisting = Boolean(cart.existingOrder?.value || cart.existingOrderId?.value)
		const submissionContext = buildSubmissionContext({ preferExisting: isExisting, generate: true })
		const submissionId = submissionContext.client_submission_id
		const useSaved = hasSavedCard.value && !useNewCard.value

		// Persist draft PRIMA del 3DS: se la sessione Sanctum scade durante
		// la challenge, l'utente recupera l'ordine al re-login.
		persistPaymentDraft({
			orderId,
			paymentMethod: 'carta',
			submissionId,
			isExisting,
			amount: Number(cart.finalTotal?.value ?? 0),
		})

		paymentStep.value = 'Conferma pagamento...'

		if (useSaved) {
			// ── CARTA SALVATA ──
			const endpoint = isExisting
				? '/api/stripe/existing-order-payment'
				: '/api/stripe/create-payment'
			const result = await sanctum(endpoint, {
				method: 'POST',
				body: {
					order_id: orderId,
					currency: 'eur',
					payment_method_id: defaultPayment.value?.card?.id,
					...submissionContext,
				},
			}) as StripePaymentResponse

			let finalIntentId = result?.payment_intent_id ?? null

			if (result?.status === 'requires_action' && result?.client_secret) {
				const { paymentIntent, error } = await stripeClient.handleCardAction(result.client_secret)
				if (error) throw error
				if (paymentIntent?.status !== 'succeeded') {
					throw new Error('Autenticazione 3D Secure non completata.')
				}
				finalIntentId = paymentIntent.id
			} else if (result?.status !== 'succeeded') {
				throw new Error('Pagamento non riuscito. Stato: ' + (result?.status || 'sconosciuto'))
			}

			await markOrderPaid(orderId, finalIntentId, isExisting, submissionId)
		} else {
			// ── CARTA NUOVA ──
			const card = cardElement.value
			if (!card) throw new Error('Campo carta non pronto.')
			const intentEndpoint = isExisting
				? '/api/stripe/existing-order-payment-intent'
				: '/api/stripe/create-payment-intent'
			const intent = await sanctum(intentEndpoint, {
				method: 'POST',
				body: { order_id: orderId, ...submissionContext },
			}) as PaymentIntentResponse
			if (!intent?.client_secret) {
				throw new Error(intent?.error || 'PaymentIntent non creato.')
			}

			const billingName =
				String(cart.billingPayload.value?.full_name || '') ||
				String(cart.billingPayload.value?.name || '') ||
				String(((user.value || {}) as UserProfile).name || '') ||
				''

			const confirmOpts = {
				payment_method: { card, billing_details: { name: billingName } },
			} as Parameters<Stripe['confirmCardPayment']>[1] & { setup_future_usage?: 'off_session' }
			if (saveCardForFuture.value) confirmOpts.setup_future_usage = 'off_session'

			const { paymentIntent, error } = await stripeClient.confirmCardPayment(
				intent.client_secret,
				confirmOpts,
			)
			if (error) throw error
			if (paymentIntent?.status !== 'succeeded') {
				throw new Error('Stato pagamento: ' + paymentIntent?.status)
			}

			await markOrderPaid(orderId, paymentIntent.id, isExisting, submissionId)

			// Salva carta come default (non bloccante).
			if (saveCardForFuture.value && paymentIntent.payment_method) {
				try {
					await sanctum('/api/stripe/set-default-payment-method', {
						method: 'POST',
						body: { payment_method: paymentIntent.payment_method },
					})
				} catch (e) {
					console.warn('[usePaymentStripe] save card failed (non bloccante):', getErrorMessage(e) || e)
				}
			}
		}

		await onPaymentSuccess(orderId, 'carta')
	}

	return { payWithCard }
}
