/**
 * Tipi shared dei 3 flussi pagamento (carta/wallet/bonifico).
 *
 * Estratti dai sub-composable per evitare duplicazione e rendere chiaro
 * il contratto deps che la factory di ognuno riceve dall'orchestratore.
 */
import type { Ref } from 'vue'

export type SanctumClient = (url: string, options?: Record<string, unknown>) => Promise<unknown>

export type PaymentMethodKey = 'carta' | 'bonifico' | 'wallet'

export type CartLike = {
	finalTotal?: Ref<number>
	finalTotalFormatted?: Ref<string>
	billingPayload: Ref<Record<string, unknown> | null>
	existingOrder?: Ref<unknown | null>
	existingOrderId?: Ref<string | number | null>
	walletSufficient?: Ref<boolean>
	loadWalletBalance?: () => unknown
}

export type AuthRetryFn = <T>(
	fn: () => Promise<T>,
	options?: { attempts?: number; label?: string },
) => Promise<T>

export type SubmissionContext = {
	client_submission_id?: string
	[key: string]: unknown
}

export type PaymentDraft = {
	orderId: string | number
	paymentMethod: PaymentMethodKey
	submissionId?: string
	isExisting: boolean
	amount: number
}

/** Helper shared che ogni sub-flow riceve dall'orchestratore. */
export type PaymentFlowSharedDeps = {
	cart: CartLike
	paymentStep: Ref<string>
	sanctum: SanctumClient
	resolvePayableOrderId: () => Promise<string | number>
	buildSubmissionContext: (options: { preferExisting?: boolean; generate?: boolean }) => SubmissionContext
	persistPaymentDraft: (draft: PaymentDraft) => void
	callWithAuthRetry: AuthRetryFn
	onPaymentSuccess: (orderId: string | number, method: PaymentMethodKey) => Promise<void>
}
