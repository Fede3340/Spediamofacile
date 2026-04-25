/**
 * useTurnstile — Helper per Cloudflare Turnstile CAPTCHA.
 *
 * Espone token, isReady, callback onVerify/onExpire/onError, reset, verify, payload.
 * In produzione IL BACKEND deve chiamare
 * https://challenges.cloudflare.com/turnstile/v0/siteverify con TURNSTILE_SECRET_KEY.
 */

interface TurnstilePayload {
	cf_turnstile_token?: string
}

export const useTurnstile = () => {
	const token = ref('')
	const lastError = ref<string | null>(null)

	const isReady = computed(() => Boolean(token.value && token.value.length > 0))

	const onVerify = (t: unknown): void => {
		token.value = typeof t === 'string' ? t : ''
		lastError.value = null
	}

	const onExpire = (): void => {
		token.value = ''
	}

	const onError = (err: unknown): void => {
		token.value = ''
		lastError.value = typeof err === 'string' ? err : 'Errore CAPTCHA. Riprova.'
	}

	const reset = (): void => {
		token.value = ''
		lastError.value = null
	}

	/**
	 * Verifica "stub" lato client: restituisce true se il token non e' vuoto.
	 * Il vero check e' lato backend con siteverify.
	 */
	const verify = (t?: string): boolean => {
		const val = t ?? token.value
		return typeof val === 'string' && val.length > 0
	}

	/** Payload da fondere con il body della POST: { cf_turnstile_token: '...' } */
	const payload = (): TurnstilePayload => {
		return token.value ? { cf_turnstile_token: token.value } : {}
	}

	return {
		token,
		isReady,
		lastError,
		onVerify,
		onExpire,
		onError,
		reset,
		verify,
		payload,
	}
}
