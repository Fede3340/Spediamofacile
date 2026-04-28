/**
 * paymentHelpers — funzioni pure per il flusso pagamento (auth-retry, errori, draft).
 *
 * Estratte da `composables/usePayment.js` (split atomico Pinia 2026-04-26).
 * Tutte le funzioni qui dentro sono SSR-safe e non dipendono da Vue/Pinia.
 *
 * @typedef {object} ErrorWithStatus
 * @property {{status?: number}} [response]
 * @property {number} [statusCode]
 * @property {number} [status]
 * @property {{statusCode?: number}} [data]
 */

/**
 * True se l'errore HTTP indica sessione scaduta (401) o CSRF mismatch (419).
 *
 * @param {unknown} error
 * @returns {boolean}
 */
export function isAuthError(error) {
	const status = error?.response?.status ?? error?.statusCode ?? error?.status ?? error?.data?.statusCode
	return status === 401 || status === 419
}

/**
 * Auth-retry generico: se `fn` lancia 401/419 (sessione scaduta durante 3DS),
 * chiama `reauth()` e ritenta. Max `attempts` retry.
 *
 * @template T
 * @param {() => Promise<T>} fn
 * @param {() => Promise<void>} reauth
 * @param {{attempts, label?: string}} [options]
 * @returns {Promise<T>}
 */
export async function callWithAuthRetry(fn, reauth, { attempts = 2, label = 'payment call' } = {}) {
	let lastError = null
	for (let attempt = 0; attempt <= attempts; attempt++) {
		try { return await fn() }
		catch (error) {
			lastError = error
			if (isAuthError(error) && attempt < attempts) {
				console.warn(`[payment] ${label}: 401, tentativo re-auth #${attempt + 1}`)
				try { await reauth() }
				catch (authErr) { console.warn(`[payment] re-auth fallito:`, authErr?.message || authErr) }
				continue
			}
			throw error
		}
	}
	throw lastError
}

/**
 * Distingue saldo insufficiente da errore tecnico per messaggio contestuale all'utente.
 *
 * @param {unknown} serverMessage
 * @returns {boolean}
 */
export function detectInsufficientFunds(serverMessage) {
	return typeof serverMessage === 'string' && /saldo|insufficien/i.test(serverMessage)
}

/** Stile Stripe Card Element (font, colori, placeholder). */
export const STRIPE_CARD_STYLE = {
	base: {
		fontSize: '16px',
		lineHeight: '40px',
		color: '#0f172a',
		fontFamily: 'Inter, system-ui, sans-serif',
		'::placeholder': { color: '#94a3b8' },
	},
	invalid: { color: '#b91c1c' },
}
