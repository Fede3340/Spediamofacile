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
	const handle401 = (error) => {
		const status = Number(
			error?.response?.status
				?? error?.statusCode
				?? error?.status
				?? error?.data?.statusCode
				?? 0,
		)
		if (status !== 401 && status !== 419) return

		// Evita reopen ripetuti se l'overlay è già aperto.
		const authModal = useAuthModalStore()
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
		nuxtApp.hook('sanctum:error', handle401)
	}
})
