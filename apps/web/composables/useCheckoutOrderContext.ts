/**
 * @file useCheckoutOrderContext — Composable useCheckoutOrderContext.
 */
import { readClientSubmissionId } from '~/utils/shipment'
import {
  buildCheckoutSubmissionContext,
  buildCheckoutSubmissionSignature,
  readExistingOrderSubmissionId,
  readPendingShipmentDraft,
  syncPendingShipmentSubmissionId,
} from '~/utils/checkoutSubmissionContext'

// Boundary frontend canonico per "quale ordine sto pagando".
//
// Possiede soltanto:
// - riuso / generazione del client_submission_id
// - restore dell'ordine esistente
// - creazione ordine nuova se il checkout non ha ancora un order_id
//
// Non possiede:
// - logica Stripe
// - scelta metodo pagamento
// - finalizzazione wallet / bonifico / carta
export function useCheckoutOrderContext({
  cart,
  sanctum,
  shipmentFlowStore,
  session,
  paymentStep,
}) {
  const clientSubmissionId = ref(null)
  const checkoutSubmissionContext = ref(null)

  const normalizeOrderId = (value) => String(value ?? '').trim()

  function currentOrderMatchesRoute(order) {
    const routeOrderId = normalizeOrderId(cart.existingOrderId?.value)
    if (!routeOrderId) return true

    return normalizeOrderId(order?.id) === routeOrderId
  }

  function getPendingShipment() {
    return readPendingShipmentDraft(shipmentFlowStore)
  }

  function syncCheckoutSubmissionContext(submissionId) {
    const normalized = typeof submissionId === 'string' ? submissionId.trim() : ''
    if (!normalized) return

    clientSubmissionId.value = normalized
    syncPendingShipmentSubmissionId(shipmentFlowStore, normalized)
    checkoutSubmissionContext.value = {
      signature: buildCheckoutSubmissionSignature({
        existingOrderId: cart.existingOrderId?.value || cart.existingOrder?.value?.id || null,
        total: Number(cart.finalTotal?.value ?? cart.getNumberTotal?.value ?? 0),
        billingPayload: cart.billingPayload?.value || null,
      }),
      client_submission_id: normalized,
    }
  }

  async function restoreExistingOrder({ force = false } = {}) {
    const orderId = cart.existingOrderId?.value
    if (!orderId) return null

    const currentOrder = cart.existingOrder?.value
    const currentSubmissionId = readClientSubmissionId(currentOrder)
    if (currentOrder?.id && currentSubmissionId && currentOrderMatchesRoute(currentOrder) && !force) {
      syncCheckoutSubmissionContext(currentSubmissionId)
      return currentOrder
    }

    const res = await sanctum(`/api/orders/${orderId}`)
    const hydratedOrder = res?.data ?? res ?? null
    if (cart.existingOrder?.value !== undefined) {
      cart.existingOrder.value = hydratedOrder
    }

    const canonicalSubmissionId = readClientSubmissionId(hydratedOrder)
    if (canonicalSubmissionId) {
      syncCheckoutSubmissionContext(canonicalSubmissionId)
    }

    return hydratedOrder
  }

  function buildSubmissionContext({ preferExisting = true, generate = true } = {}) {
    const { clientSubmissionId: nextSubmissionId, context } = buildCheckoutSubmissionContext({
      preferExisting,
      generate,
      existingOrder: cart.existingOrder?.value || null,
      existingOrderId: cart.existingOrderId?.value || cart.existingOrder?.value?.id || null,
      pendingShipment: getPendingShipment(),
      sessionData: session.value?.data || null,
      cachedContext: checkoutSubmissionContext.value || null,
      localSubmissionId: clientSubmissionId.value,
      total: Number(cart.finalTotal?.value ?? cart.getNumberTotal?.value ?? 0),
      billingPayload: cart.billingPayload?.value || null,
    })

    clientSubmissionId.value = nextSubmissionId
    syncPendingShipmentSubmissionId(shipmentFlowStore, nextSubmissionId)
    checkoutSubmissionContext.value = context

    return {
      client_submission_id: nextSubmissionId,
      ...(cart.discountContext?.value ? { discount_context: cart.discountContext.value } : {}),
    }
  }

  async function createCheckoutOrder() {
    if (paymentStep?.value !== undefined) {
      paymentStep.value = 'Creazione ordine...'
    }

    const subtotalCents = Math.round(Number(cart.getNumberTotal?.value ?? 0) * 100)
    const submissionContext = buildSubmissionContext({ preferExisting: false, generate: true })
    const res = await sanctum('/api/stripe/create-order', {
      method: 'POST',
      body: {
        subtotal: subtotalCents,
        billing_data: cart.billingPayload.value,
        single_order_only: true,
        ...submissionContext,
      },
    })

    const orderIds = Array.isArray(res?.order_ids) ? res.order_ids.filter(Boolean) : []
    if (orderIds.length > 1) {
      throw new Error('Il pagamento di piu spedizioni separate va completato una spedizione alla volta.')
    }

    const orderId = orderIds[0] ?? res?.order_id
    if (!orderId) {
      throw new Error('Ordine non creato: id mancante nella risposta.')
    }
    if (res?.client_submission_id) {
      syncCheckoutSubmissionContext(res.client_submission_id)
    }

    return orderId
  }

  async function resolvePayableOrderId() {
    const existing = cart.existingOrder?.value
    if (existing?.id && currentOrderMatchesRoute(existing)) {
      const existingSubmissionId = readClientSubmissionId(existing)
      if (existingSubmissionId) {
        syncCheckoutSubmissionContext(existingSubmissionId)
        return existing.id
      }

      if (cart.existingOrderId?.value) {
        const hydratedExisting = await restoreExistingOrder({ force: true })
        if (hydratedExisting?.id) return hydratedExisting.id
      }

      return existing.id
    }

    if (cart.existingOrderId?.value) {
      const hydratedExisting = await restoreExistingOrder({ force: true })
      if (hydratedExisting?.id) return hydratedExisting.id
      throw new Error('Ordine non disponibile. Ricarica la pagina e riprova.')
    }

    return createCheckoutOrder()
  }

  return {
    buildSubmissionContext,
    resolvePayableOrderId,
    restoreExistingOrder,
    syncCheckoutSubmissionContext,
  }
}
