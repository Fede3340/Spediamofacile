/**
 * Plugin client: handler globale 401 → riapre overlay login.
 *
 * Quando una qualsiasi chiamata API lato client ritorna 401 (sessione scaduta o
 * token revocato), invece di lasciare l'utente con un errore generico riapriamo
 * automaticamente il modal auth con redirect alla pagina corrente, così il flusso
 * riprende senza perdere il contesto (carrello, pagamento, ecc.).
 *
 * Si aggancia all'event hook globale di Nuxt `app:error` e all'hook `sanctum:error`
 * di nuxt-auth-sanctum quando disponibile.
 */
export default defineNuxtPlugin((nuxtApp) => {
	const asApiError = (error: unknown) => (
		error && typeof error === 'object'
			? error as { response?: { status?: number }; statusCode?: number; status?: number; data?: { statusCode?: number } }
			: {}
	)
	const handle401 = (error: unknown) => {
		const apiError = asApiError(error)
		const status = Number(
			apiError.response?.status
				?? apiError.statusCode
				?? apiError.status
				?? apiError.data?.statusCode
				?? 0,
		)
		if (status !== 401 && status !== 419) return

		// Evita reopen ripetuti se l'overlay è già aperto.
		const authModal = useAuthStore()
		if (authModal.isOpen) return
		const { openAuthModal } = authModal

		const route = useRoute()
		// Salva la rotta corrente come redirect post-login così l'utente
		// torna esattamente dove era (carrello, checkout, ecc.).
		openAuthModal({ tab: 'login', redirect: route.fullPath })
	}

	// Hook generico Nuxt: ogni errore raggiunge qui.
	nuxtApp.hook('app:error', handle401)
	// Hook specifico nuxt-auth-sanctum se disponibile.
	if (typeof nuxtApp.hook === 'function') {
		;(nuxtApp.hook as (name: string, callback: (error: unknown) => void) => void)('sanctum:error', handle401)
	}
})
