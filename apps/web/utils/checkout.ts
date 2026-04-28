/**
 * @file checkout — Utility checkout.
 */
// === utils/checkout.js — Helper checkout / post-pagamento ===
// Consolidamento di:
//   - utils/checkoutSuccess.ts  (build/read/clear checkout success query, status)
//   - utils/stripeErrors.ts     (mappa errori Stripe IT, translateStripeError)
// Tutti gli export originali sono preservati identici.

// ─────────────────────────────────────────────────────────────────
// SEZIONE 1 — ex utils/checkoutSuccess.ts
// ─────────────────────────────────────────────────────────────────

/**
 * checkoutSuccess — helpers for persisting / reading / clearing checkout success
 * state via URL query parameters.
 *
 * After a successful payment the checkout page encodes order-ids and payment
 * method into the URL so the success screen survives full-page reloads.
 * `clearCheckoutSuccessQuery` removes those params once they've been consumed
 * to keep the URL clean.
 *
 * @typedef {import('vue-router').LocationQuery} LocationQuery
 */

const QUERY_KEY_SUCCESS = 'checkout_success'
const QUERY_KEY_ORDER_IDS = 'order_ids'
const QUERY_KEY_PAYMENT_METHOD = 'payment_method'

/**
 * Order statuses that are considered "successfully placed".
 * Used by components that need to decide whether an order is in a terminal
 * positive state.  "pending" is included because bonifico orders start in
 * pending and are still considered successful from the checkout perspective.
 *
 * @type {readonly string[]}
 */
export const SUCCESSFUL_ORDER_STATUSES = Object.freeze([
  'paid',
  'completed',
  'processing',
  'label_generated',
  'shipped',
  'in_transit',
  'out_for_delivery',
  'delivered',
  'pending',
])

/**
 * Merges checkout-success query params into an existing query object.
 * Returns a new LocationQuery that the caller can pass to `router.replace`.
 *
 * @param {LocationQuery} baseQuery
 * @param {{ orderIds: (string|number)[], paymentMethod: string }} opts
 * @returns {LocationQuery}
 */
export function buildCheckoutSuccessQuery(baseQuery, { orderIds, paymentMethod }) {
  return {
    ...baseQuery,
    [QUERY_KEY_SUCCESS]: '1',
    [QUERY_KEY_ORDER_IDS]: orderIds.map(String).join(','),
    [QUERY_KEY_PAYMENT_METHOD]: paymentMethod,
  }
}

/**
 * Reads the checkout-success state from the current route query.
 *
 * @param {LocationQuery} query
 * @returns {{ active: boolean, orderIds: string[], paymentMethod: string }}
 */
export function readCheckoutSuccessState(query) {
  const active = query[QUERY_KEY_SUCCESS] === '1'
  if (!active) {
    return { active: false, orderIds: [], paymentMethod: '' }
  }

  const rawIds = String(query[QUERY_KEY_ORDER_IDS] || '')
  const orderIds = rawIds
    .split(',')
    .map((id) => id.trim())
    .filter(Boolean)

  if (orderIds.length === 0) {
    return { active: false, orderIds: [], paymentMethod: '' }
  }

  const paymentMethod = String(query[QUERY_KEY_PAYMENT_METHOD] || '')

  return { active: true, orderIds, paymentMethod }
}

/**
 * Returns a new LocationQuery with checkout-success params stripped out.
 * The caller should pass this to `router.replace({ query })` to clean the URL.
 *
 * @param {LocationQuery} query
 * @returns {LocationQuery}
 */
export function clearCheckoutSuccessQuery(query) {
  const cleaned = { ...query }
  delete cleaned[QUERY_KEY_SUCCESS]
  delete cleaned[QUERY_KEY_ORDER_IDS]
  delete cleaned[QUERY_KEY_PAYMENT_METHOD]
  return cleaned
}

// ─────────────────────────────────────────────────────────────────
// SEZIONE 2 — ex utils/stripeErrors.ts
// ─────────────────────────────────────────────────────────────────

/**
 * stripeErrors — messaggi errore Stripe unificati in italiano.
 *
 * Prima di questo file, le mappe erano duplicate in usePaymentFlow,
 * usePaymentProcess e useWalletTopUp con testi leggermente diversi.
 * Ora una sola fonte di verità: modifica QUI le traduzioni.
 *
 * Stripe restituisce `err.code` (API error) o `err.decline_code`
 * (carta rifiutata). I codici non mappati ricadono sul message
 * originale (che può essere in EN).
 *
 * @type {Record<string, string>}
 */
export const STRIPE_ERRORS_IT = {
	card_declined: "Carta rifiutata. Verifica i dati o usa un'altra carta.",
	insufficient_funds: 'Fondi insufficienti sulla carta.',
	expired_card: 'Carta scaduta.',
	incorrect_cvc: 'Codice CVC non corretto.',
	incorrect_number: 'Numero carta non valido.',
	invalid_number: 'Numero carta non valido. Verifica il numero inserito.',
	invalid_cvc: 'Codice CVC non valido.',
	invalid_expiry_month: 'Mese di scadenza non valido.',
	invalid_expiry_year: 'Anno di scadenza non valido.',
	incomplete_number: 'Numero carta incompleto.',
	incomplete_expiry: 'Data di scadenza incompleta.',
	incomplete_cvc: 'Codice CVC incompleto.',
	processing_error: 'Errore di elaborazione. Riprova tra qualche istante.',
	authentication_required: 'La banca richiede una verifica aggiuntiva (3D Secure).',
	payment_intent_authentication_failure: 'Autenticazione 3D Secure non riuscita.',
	generic_decline: 'Pagamento rifiutato. Contatta la tua banca.',
	rate_limit: 'Troppe richieste in poco tempo. Attendi un istante e riprova.',
	card_not_supported: "Tipo di carta non supportato. Prova con un'altra carta.",
	lost_card: 'Carta segnalata come smarrita. Contatta la tua banca.',
	stolen_card: 'Carta segnalata come rubata. Contatta la tua banca.',
};

/**
 * Traduce un errore Stripe nel messaggio italiano corrispondente.
 * Fallback sul message originale o generico se il codice non è mappato.
 *
 * @param {*} err
 * @param {string} [fallback='Errore durante il pagamento. Riprova.']
 * @returns {string}
 */
export function translateStripeError(err, fallback = 'Errore durante il pagamento. Riprova.') {
	if (!err) return fallback;
	const code = err?.code || err?.decline_code;
	if (code && STRIPE_ERRORS_IT[code]) return STRIPE_ERRORS_IT[code];
	return (
		err?.response?._data?.error ||
		err?.response?._data?.message ||
		err?.data?.error ||
		err?.message ||
		fallback
	);
}
