// composables/usePayment.js
// Boundary frontend del pagamento checkout.
// Possiede:
// - scelta metodo pagamento
// - bootstrap auth/Stripe
// - creazione o recupero ordine pagabile
// - submit finale carta / bonifico / wallet
// Non possiede:
// - il calcolo canonico del totale ordine backend (useCart espone un totale preview UI)
// - la logica di funnel e validazione cross-step (vive in useShipmentStepPageOrchestration)
// Nota importante:
// il pagamento wallet oggi e' un flusso in 2 step backend:
// 1. /api/wallet/pay
// 2. /api/stripe/mark-order-completed
// L'obiettivo di semplificazione futuro e' rendere questo boundary piu' lineare senza perdere idempotenza.
// Boundary critico Stripe + 3DS + idempotency-key. Pure helpers gia' estratti
// in utils/checkout + utils/pendingPayment. Lo split del composable in
// sub-composable per flusso (carta/wallet/bonifico) richiede E2E carta test
// 4242 4242 4242 4242 09/30 123 — vedi piano ondata 8b futura.

import { useCheckoutOrderContext } from '~/composables/useCheckoutOrderContext'
import { usePaymentBonifico } from '~/composables/payment/usePaymentBonifico'
import { usePaymentWallet } from '~/composables/payment/usePaymentWallet'
import { usePaymentStripe } from '~/composables/payment/usePaymentStripe'
import { buildCheckoutSuccessQuery, translateStripeError } from '~/utils/checkout'
import type { Ref } from 'vue'
import type {
  Stripe,
  StripeCardElement,
  StripeCardElementChangeEvent,
} from '@stripe/stripe-js'
import {
  PENDING_PAYMENT_KEY,
  PENDING_PAYMENT_TTL_MS,
  safeLocalSet,
  clearPendingPayment,
} from '~/utils/cartHelpers'

type PaymentMethodKey = 'carta' | 'bonifico' | 'wallet'
type CartLike = {
  finalTotal?: Ref<number>
  finalTotalFormatted?: Ref<string>
  billingPayload: Ref<Record<string, unknown> | null>
  existingOrder?: Ref<unknown | null>
  existingOrderId?: Ref<string | number | null>
  walletSufficient?: Ref<boolean>
  loadWalletBalance?: () => unknown
}
type StripeSettingsResponse = { publishable_key?: string }
type DefaultPaymentResponse = { card?: { id?: string } }
type PaymentDraft = {
  orderId: string | number
  paymentMethod: PaymentMethodKey
  submissionId?: string
  isExisting: boolean
  amount: number
}
type AuthRetryOptions = { attempts?: number; label?: string }

const asRecord = (value: unknown): Record<string, unknown> =>
  value && typeof value === 'object' ? value as Record<string, unknown> : {}
const getErrorMessage = (error: unknown): string => String(asRecord(error).message || '')
const getErrorStatus = (error: unknown): number => {
  const e = asRecord(error)
  const response = asRecord(e.response)
  const data = asRecord(e.data)
  return Number(response.status ?? e.statusCode ?? e.status ?? data.statusCode ?? 0)
}

/**
 * Composable principale di pagamento checkout.
 *
 *   finalTotal (Ref<number>), finalTotalFormatted (Ref<string>),
 *   getNumberTotal (Ref<number>), billingPayload (Ref<object>),
 *   existingOrder (Ref<object|null>), existingOrderId (Ref<string|number|null>),
 *   walletSufficient? (Ref<boolean>), loadWalletBalance? (Function).
 */
export function usePayment(cart: CartLike) {
  const route = useRoute()
  const router = useRouter()
  const sanctum = useSanctumClient()
  const { user, isAuthenticated, refreshIdentity } = useSanctumAuth()
  const { authCookie } = useAuthUiSnapshotPersistence()
  const { session } = useSession()
  const shipmentFlowStore = useShipmentStore()

  // ---------- CONFIG TAB METODI ----------
  const paymentMethodOptions = [
    { key: 'carta', title: 'Carta', description: 'Visa, Mastercard, Amex', badge: 'Più usato' },
    { key: 'bonifico', title: 'Bonifico', description: '1-2 giorni lavorativi' },
    { key: 'wallet', title: 'Wallet', description: 'Saldo prepagato' },
  ]

  // ---------- STATO PAGAMENTO ----------
  const paymentMethod = ref<PaymentMethodKey>('carta')
  const termsAccepted = ref(false)
  const showConfirmModal = ref(false)
  const isProcessing = ref(false)
  const paymentStep = ref('')
  const paymentError = ref('')
  const paymentSuccess = ref(false)
  const successOrderId = ref<string | number | null>(null)

  // ---------- STATO STRIPE ----------
  const stripe = ref<Stripe | null>(null)
  const cardElement = ref<StripeCardElement | null>(null)
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
  const defaultPayment = ref<DefaultPaymentResponse | null>(null)
  const {
    buildSubmissionContext,
    resolvePayableOrderId,
  } = useCheckoutOrderContext({
    cart,
    sanctum,
    shipmentFlowStore,
    session,
    paymentStep,
  })

  async function ensurePaymentAuthContext({ force = false } = {}) {
    const authSnapshot = authCookie.value && typeof authCookie.value === 'object'
      ? authCookie.value as { authenticated?: boolean }
      : null
    const hasUiSnapshot = Boolean(authSnapshot?.authenticated)

    if (force || !isAuthenticated.value) {
      try {
        await runAuthBootstrap({ force: force || hasUiSnapshot })
      } catch (error) {
        console.warn('[usePayment] auth bootstrap failed before payment:', getErrorMessage(error) || error)
      }
    }

    if (!isAuthenticated.value) {
      const synced = await waitForPostAuthSync(refreshIdentity)
      if (!synced) {
        throw new Error('Sessione non valida. Accedi di nuovo per completare il pagamento.')
      }
    }

    try {
      await $fetch('/sanctum/csrf-cookie', {
        method: 'GET',
        credentials: 'include',
      })
    } catch (error) {
      console.warn('[usePayment] csrf refresh failed before payment:', getErrorMessage(error) || error)
    }
  }

  /**
   * Esegue una chiamata API e in caso di 401 (sessione scaduta durante 3DS)
   * prova a rinnovare la sessione Sanctum e ritenta. Max 2 retry.
   * Se fallisce comunque, rilancia l'errore per mostrare messaggio chiaro all'utente.
   */
  async function callWithAuthRetry<T>(fn: () => Promise<T>, { attempts = 2, label = 'payment call' }: AuthRetryOptions = {}): Promise<T> {
    let lastError: unknown = null
    for (let attempt = 0; attempt <= attempts; attempt++) {
      try {
        return await fn()
      } catch (error) {
        lastError = error
        const status = getErrorStatus(error)
        const is401 = status === 401 || status === 419
        if (is401 && attempt < attempts) {
          console.warn(`[usePayment] ${label}: 401, tentativo re-auth #${attempt + 1}`)
          try {
            await ensurePaymentAuthContext({ force: true })
          } catch (authErr) {
            console.warn('[usePayment] re-auth fallito:', getErrorMessage(authErr) || authErr)
          }
          continue
        }
        throw error
      }
    }
    throw lastError
  }

  /**
   * Salva nel localStorage lo stato del pagamento in corso (prima di 3DS).
   * Permette di recuperare l'ordine dopo eventuale disconnessione durante la challenge.
   */
  function persistPaymentDraft(draft: PaymentDraft) {
    if (!draft?.orderId) return
    safeLocalSet(PENDING_PAYMENT_KEY, {
      ...draft,
      createdAt: Date.now(),
      expiresAt: Date.now() + PENDING_PAYMENT_TTL_MS,
    })
  }

  // ---------- INIT STRIPE ----------
  /**
   * Carica Stripe SDK, crea Card Element, monta nel container
   * (quando diventa disponibile via ref) e fetcha la carta salvata.
   * Idempotente: chiamata multipla è no-op dopo il primo successo.
   */
  async function initStripe() {
    if (stripeReady.value || stripeLoading.value) return
    stripeLoading.value = true
    try {
      const settings = await sanctum('/api/settings/stripe') as StripeSettingsResponse
      const publishableKey = settings?.publishable_key
      if (!publishableKey) throw new Error('Stripe publishable key mancante.')
      stripeConfigured.value = true

      const { loadStripe } = await import('@stripe/stripe-js')
      stripe.value = await loadStripe(publishableKey)
      if (!stripe.value) throw new Error('Caricamento Stripe SDK fallito.')

      const stripeClient = stripe.value
      const elements = stripeClient.elements({ locale: 'it' })
      cardElement.value = elements.create('card', { hidePostalCode: true })
      cardElement.value.on('change', (e: StripeCardElementChangeEvent) => {
        cardComplete.value = !!e.complete
        cardError.value = e.error?.message || ''
      })

      // Mount lazy: aspetta che il container v-if diventi disponibile.
      // Il watcher viene fermato su unmount per evitare remount duplicato
      // se l'utente naviga away e poi torna al pagamento (Stripe Elements
      // accetta un solo mount point; remount causa errori silenti).
      const stopCardMountWatch = watch(
        cardElementContainer,
        (el) => {
          if (el && cardElement.value && !cardMounted.value) {
            cardElement.value.mount(el)
            cardMounted.value = true
          }
        },
        { flush: 'post', immediate: true },
      )

      if (getCurrentScope()) {
        onScopeDispose(() => {
          stopCardMountWatch()
          if (cardElement.value && cardMounted.value) {
            try { cardElement.value.unmount() } catch { /* element already destroyed */ }
            cardMounted.value = false
          }
        })
      }

      // Carica eventuale carta salvata (silenzioso se 404).
      try {
        const saved = await sanctum('/api/stripe/default-payment-method') as DefaultPaymentResponse
        if (saved?.card) {
          defaultPayment.value = saved
          hasSavedCard.value = true
        }
      } catch {
        /* nessuna carta salvata, ok */
      }

      stripeReady.value = true
    } catch (err) {
      cardPaymentsUnavailable.value = true
      cardPaymentsNotice.value =
        'Pagamento con carta non disponibile al momento. Usa bonifico o wallet.'
      console.warn('[usePayment] initStripe failed:', getErrorMessage(err) || err)
    } finally {
      stripeLoading.value = false
    }
  }

  // ---------- SELEZIONE METODO ----------
  /**
   * Cambia metodo di pagamento attivo. Se wallet, prova a refreshare il saldo.
   */
  function selectPaymentMethod(key: PaymentMethodKey) {
    paymentMethod.value = key
    paymentError.value = ''
    if (key === 'wallet' && typeof cart.loadWalletBalance === 'function') {
      cart.loadWalletBalance()
    }
  }

  // ---------- VALIDAZIONE / GUARD ----------
  const canPay = computed(() => {
    if (isProcessing.value) return false
    if (!termsAccepted.value) return false
    if (!cart.billingPayload?.value) return false

    if (paymentMethod.value === 'carta') {
      if (cardPaymentsUnavailable.value) return false
      if (hasSavedCard.value && !useNewCard.value) return true
      return cardComplete.value
    }
    if (paymentMethod.value === 'wallet') {
      return cart.walletSufficient?.value ?? false
    }
    if (paymentMethod.value === 'bonifico') return true
    return false
  })

  const payButtonTooltip = computed(() => {
    if (!termsAccepted.value) return 'Accetta i termini per procedere'
    if (!cart.billingPayload?.value) return 'Completa i dati di fatturazione'
    if (paymentMethod.value === 'carta') {
      if (cardPaymentsUnavailable.value) return 'Pagamento con carta non disponibile'
      if (!hasSavedCard.value && !cardComplete.value) return 'Completa i dati della carta'
      if (hasSavedCard.value && useNewCard.value && !cardComplete.value)
        return 'Completa i dati della carta'
    }
    if (paymentMethod.value === 'wallet' && !cart.walletSufficient?.value) {
      return 'Saldo wallet insufficiente'
    }
    return ''
  })

  const paymentActionLabel = computed(() => {
    const total = cart.finalTotalFormatted?.value ?? ''
    if (paymentMethod.value === 'bonifico') return `Conferma ordine · ${total}`
    return `Paga · ${total}`
  })

  const shouldShowCardForm = computed(
    () =>
      paymentMethod.value === 'carta' &&
      stripeReady.value &&
      !cardPaymentsUnavailable.value &&
      (!hasSavedCard.value || useNewCard.value),
  )

  // ---------- DISPATCHER ----------
  /** Apre la modale di conferma finale (NON processa il pagamento). */
  function confirmPayment() {
    if (!canPay.value) return
    paymentError.value = ''
    showConfirmModal.value = true
  }

  /**
   * Processa il pagamento in base al metodo selezionato.
   * Wrappa ogni branch in try/catch e traduce gli errori Stripe.
   */
  async function proceedWithPayment() {
    if (isProcessing.value) return
    showConfirmModal.value = false
    isProcessing.value = true
    paymentError.value = ''
    try {
      await ensurePaymentAuthContext()
      if (paymentMethod.value === 'carta') await payWithCard()
      else if (paymentMethod.value === 'wallet') await payWithWallet()
      else if (paymentMethod.value === 'bonifico') await payWithBonifico()
      else throw new Error('Metodo di pagamento non supportato.')
    } catch (err) {
      paymentError.value =
        translateStripeError(err) || getErrorMessage(err) || 'Pagamento fallito. Riprova.'
      console.warn('[usePayment] proceedWithPayment failed:', err)
    } finally {
      isProcessing.value = false
      paymentStep.value = ''
    }
  }

  // ---------- CARTA (+ 3DS automatico) ----------
  // Flusso Stripe carta estratto in composables/payment/usePaymentStripe.ts

  // Lifecycle Stripe SDK (initStripe) resta nell'orchestratore per lazy mount.
  const { payWithCard } = usePaymentStripe({
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
    callWithAuthRetry,
    markOrderPaid,
    onPaymentSuccess,
  })

  /**
   * Notifica backend che l'ordine è stato pagato (per invio email + sync stato).
   * Gestisce automaticamente 401 durante 3DS: re-auth + retry (max 2 tentativi).
   * Se anche dopo il retry fallisce, lancia errore ma il draft resta in localStorage
   * così l'utente può ritrovare il suo ordine dopo il re-login.
   */
  async function markOrderPaid(orderId: string | number, extId: string | null, isExisting: boolean, submissionId?: string) {
    paymentStep.value = 'Finalizzazione...'
    const endpoint = isExisting ? '/api/stripe/existing-order-paid' : '/api/stripe/order-paid'
    await callWithAuthRetry(
      () =>
        sanctum(endpoint, {
          method: 'POST',
          body: {
            order_id: orderId,
            ext_id: extId,
            is_existing_order: isExisting,
            client_submission_id: submissionId,
          },
        }),
      { label: 'markOrderPaid' },
    )
  }

  // ---------- WALLET ----------
  // Flusso wallet estratto in composables/payment/usePaymentWallet.ts

  const { payWithWallet } = usePaymentWallet({
    cart,
    paymentStep,
    sanctum,
    resolvePayableOrderId,
    buildSubmissionContext,
    persistPaymentDraft,
    callWithAuthRetry,
    onPaymentSuccess,
  })

  // ---------- BONIFICO ----------
  // Flusso bonifico estratto in composables/payment/usePaymentBonifico.ts

  const { payWithBonifico } = usePaymentBonifico({
    cart,
    paymentStep,
    sanctum,
    resolvePayableOrderId,
    buildSubmissionContext,
    persistPaymentDraft,
    callWithAuthRetry,
    onPaymentSuccess,
  })

  // ---------- SUCCESS + ANALYTICS ----------
  /**
   * Marca il pagamento come riuscito: setta flag, pulisce cache cart,
   * e aggiorna la route con query di successo.
   *
   * NOTA: l'evento GA4 `purchase` era qui; GA4 è stato archiviato il 2026-04-20
   * (vedi `_archive/frontend-simplification-2026-04-20/npm-packages/ga4-duplicato/`).
   * Per tracking purchase usare `useFunnelAnalytics().trackPaymentSuccess()`
   * che invia a Plausible.
   */
  async function onPaymentSuccess(orderId: string | number, method: PaymentMethodKey) {
    paymentSuccess.value = true
    successOrderId.value = orderId

    // Pagamento riuscito: rimuovi il draft di recovery dal localStorage.
    clearPendingPayment()

    // Plausible riceve `payment_success` via useFunnelAnalytics.

    try {
      clearNuxtData?.('cart')
    } catch {
      /* nessuna cache da pulire, ok */
    }

    const nextQuery = buildCheckoutSuccessQuery(route.query, {
      orderIds: [orderId],
      paymentMethod: method,
    })
    router.replace({ path: route.path, query: nextQuery })
  }

  /**
   * Traccia apertura del checkout (da chiamare quando l'utente apre lo step pagamento).
   *
   * NOTA: l'evento GA4 `begin_checkout` era qui; GA4 archiviato il 2026-04-20.
   * Per tracking begin_checkout usare Plausible via useFunnelAnalytics.
   */
  function trackBeginCheckout() {

  }

  // ---------- APPLE/GOOGLE PAY (ARCHIVIATO 2026-04-20) ----------
  // Wallet express disattivati. Fallback inerti per compatibilita' template.
  // Riattivazione: _archive/apple-google-pay-2026-04-20/README_REATTIVAZIONE.md
  const canMakePayment = ref(false)
  const isAppleAvailable = computed(() => false)
  const isGoogleAvailable = computed(() => false)
  const paymentRequestContainer = ref(null)
  const paymentRequestError = ref('')
  const mountPaymentRequestButton = () => { /* no-op */ }

  // ---------- RETURN API ----------
  return {
    // Metodo + opzioni
    paymentMethod,
    paymentMethodOptions,
    selectPaymentMethod,

    // Stripe lifecycle
    stripeLoading,
    stripeReady,
    stripeConfigured,
    cardPaymentsUnavailable,
    cardPaymentsNotice,
    initStripe,
    cardElementContainer,
    cardMounted,
    cardComplete,
    cardError,
    shouldShowCardForm,
    useNewCard,
    saveCardForFuture,
    hasSavedCard,
    defaultPayment,

    // Apple/Google Pay (inerti, archiviati)
    canMakePayment,
    isAppleAvailable,
    isGoogleAvailable,
    paymentRequestContainer,
    paymentRequestError,
    mountPaymentRequestButton,

    // Conferma + esecuzione
    showConfirmModal,
    confirmPayment,
    proceedWithPayment,
    isProcessing,
    paymentError,
    paymentSuccess,
    successOrderId,
    paymentStep,
    paymentActionLabel,
    canPay,
    payButtonTooltip,
    termsAccepted,

    // Analytics
    trackBeginCheckout,
  }
}
