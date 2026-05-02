import { defineStore } from 'pinia'
import {
	PENDING_PAYMENT_KEY,
	PENDING_PAYMENT_TTL_MS,
	type PendingPaymentDraft,
	safeLocalSet,
} from '~/utils/cartHelpers'

type PaymentMethodKey = 'carta' | 'wallet' | 'bonifico'

export const PAYMENT_METHOD_OPTIONS = [
	{ key: 'carta', title: 'Carta', description: 'Visa, Mastercard, Amex', badge: 'Piu usato' },
	{ key: 'bonifico', title: 'Bonifico', description: '1-2 giorni lavorativi' },
	{ key: 'wallet', title: 'Wallet', description: 'Saldo prepagato' },
] as const

export const usePaymentStore = defineStore('payment', () => {
	const paymentMethod = ref<PaymentMethodKey>('carta')
	const termsAccepted = ref(false)
	const showConfirmModal = ref(false)
	const isProcessing = ref(false)
	const paymentStep = ref('')
	const paymentError = ref('')
	const paymentSuccess = ref(false)
	const successOrderId = ref<string | number | null>(null)

	const stripe = shallowRef<unknown>(null)
	const cardElement = shallowRef<unknown>(null)
	const cardElementContainer = ref<HTMLElement | null>(null)
	const cardMounted = ref(false)
	const cardComplete = ref(false)
	const cardError = ref('')
	const stripeLoading = ref(false)
	const stripeReady = ref(false)
	const stripeConfigured = ref(false)
	const cardPaymentsUnavailable = ref(false)
	const cardPaymentsNotice = ref('')
	const saveCardForFuture = ref(false)
	const useNewCard = ref(false)
	const hasSavedCard = ref(false)
	const defaultPayment = ref<Record<string, unknown> | null>(null)

	const canMakePayment = ref(false)
	const isAppleAvailable = computed(() => false)
	const isGoogleAvailable = computed(() => false)
	const paymentRequestContainer = ref<HTMLElement | null>(null)
	const paymentRequestError = ref('')

	function selectPaymentMethod(key: PaymentMethodKey) {
		paymentMethod.value = key
		paymentError.value = ''
	}

	function persistPaymentDraft(draft: PendingPaymentDraft) {
		if (!draft.orderId) return
		safeLocalSet(PENDING_PAYMENT_KEY, {
			...draft,
			createdAt: Date.now(),
			expiresAt: Date.now() + PENDING_PAYMENT_TTL_MS,
		})
	}

	function setStripeUnavailable(notice = 'Pagamento con carta non disponibile al momento. Usa bonifico o wallet.') {
		cardPaymentsUnavailable.value = true
		cardPaymentsNotice.value = notice
	}

	function markPaymentSuccess(orderId: string | number | null) {
		paymentSuccess.value = true
		successOrderId.value = orderId
	}

	function resetPayment() {
		paymentMethod.value = 'carta'
		termsAccepted.value = false
		showConfirmModal.value = false
		isProcessing.value = false
		paymentStep.value = ''
		paymentError.value = ''
		paymentSuccess.value = false
		successOrderId.value = null
		stripe.value = null
		cardElement.value = null
		cardElementContainer.value = null
		cardMounted.value = false
		cardComplete.value = false
		cardError.value = ''
		stripeLoading.value = false
		stripeReady.value = false
		stripeConfigured.value = false
		cardPaymentsUnavailable.value = false
		cardPaymentsNotice.value = ''
		saveCardForFuture.value = false
		useNewCard.value = false
		hasSavedCard.value = false
		defaultPayment.value = null
	}

	return {
		paymentMethod, termsAccepted, showConfirmModal, isProcessing,
		paymentStep, paymentError, paymentSuccess, successOrderId,
		stripe, cardElement, cardElementContainer, cardMounted, cardComplete, cardError,
		stripeLoading, stripeReady, stripeConfigured, cardPaymentsUnavailable, cardPaymentsNotice,
		saveCardForFuture, useNewCard, hasSavedCard, defaultPayment,
		canMakePayment, isAppleAvailable, isGoogleAvailable, paymentRequestContainer, paymentRequestError,
		selectPaymentMethod, persistPaymentDraft, setStripeUnavailable, markPaymentSuccess, resetPayment,
	}
})
