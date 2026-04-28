/**
 * @file pendingPayment — storage localStorage per pagamenti in attesa.
 * Estratto da composables/usePayment.js. Wrapper safe (try/catch su SSR).
 *
 * IMPORTANTE: file critico — gestisce identifiers ordini in attesa.
 * Non modificare la logica TTL senza test pagamento E2E verdi.
 */

export const PENDING_PAYMENT_KEY = 'sf_pending_payment';
export const PENDING_PAYMENT_TTL_MS = 24 * 60 * 60 * 1000; // 24 ore

export function safeLocalGet(key) {
	if (typeof window === 'undefined') return null;
	try {
		const raw = window.localStorage.getItem(key);
		return raw ? JSON.parse(raw) : null;
	} catch {
		return null;
	}
}

export function safeLocalSet(key, value) {
	if (typeof window === 'undefined') return;
	try {
		window.localStorage.setItem(key, JSON.stringify(value));
	} catch {
		/* storage pieno o disabilitato */
	}
}

export function safeLocalRemove(key) {
	if (typeof window === 'undefined') return;
	try {
		window.localStorage.removeItem(key);
	} catch {
		/* storage disabilitato */
	}
}

/**
 * Legge un eventuale pagamento in sospeso (non completato) dal localStorage.
 * Ritorna null se scaduto o assente.
 */
export function loadPendingPayment() {
	const data = safeLocalGet(PENDING_PAYMENT_KEY);
	if (!data) return null;
	if (data.expiresAt && data.expiresAt < Date.now()) {
		safeLocalRemove(PENDING_PAYMENT_KEY);
		return null;
	}
	return data;
}

export function clearPendingPayment() {
	safeLocalRemove(PENDING_PAYMENT_KEY);
}
