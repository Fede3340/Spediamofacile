import { isAuthenticatedSnapshotValue, runAuthBootstrap, useAuthBootstrapState } from '~/utils/auth'

/**
 * Bootstrap client-side unificato (merge di 4 plugin client-only, 2026-04-20).
 *
 * Nuxt esegue setup() riga per riga in modo sequenziale, quindi 4 blocchi nello
 * stesso plugin equivalgono a 4 plugin separati con `enforce: 'pre'` ma con
 * meno overhead di bootstrap.
 *
 * Ordine critico:
 *   1. .is-hydrated  → per primo, sblocca transizioni CSS prima di rerender
 *                      (evita flicker durante hydration). Vedi main.css regola
 *                      `html:not(.is-hydrated) * { transition: none }`.
 *   2. sanctum URL   → PRIMA dell'init di nuxt-auth-sanctum, altrimenti CSRF/login
 *                      partono verso 127.0.0.1:8787 anche su tunnel Cloudflare.
 *   3. auth bootstrap → dopo che sanctum è pronto, chiama /api/user se serve.
 *   4. shipment hydrate → hook app:mounted, indipendente dagli altri.
 *
 * Non tocca il plugin universale `00.auth-ui-seed.js` (gira anche lato server).
 */
export default defineNuxtPlugin({
	name: 'sf-bootstrap-client',
	enforce: 'pre',
	async setup(nuxtApp) {
		if (typeof window === 'undefined') return

		document.documentElement.classList.add('is-hydrated')

		// Riscrive sanctum.baseUrl all'origine corrente del browser per supportare
		// tunnel Cloudflare: con baseUrl fissa a 127.0.0.1:8787 i cookie SameSite=Lax
		// non viaggerebbero cross-origin e l'host non sarebbe raggiungibile da remoto.
		// Solo lato client: in SSR Nuxt gira sullo stesso server di Laravel.
		const config = useRuntimeConfig()
		const browserOrigin = window.location.origin
		const { hostname, port } = window.location

		// In dev puro Nuxt (3000/3001) NON riscrivere baseUrl verso la stessa origine,
		// altrimenti le chiamate API finiscono su Nuxt invece che su Laravel/proxy → 404.
		const isLocalNuxtDevOrigin =
			['127.0.0.1', 'localhost'].includes(hostname) &&
			['3000', '3001'].includes(port)

		if (!isLocalNuxtDevOrigin) {
			config.public.sanctum.baseUrl = browserOrigin
			config.public.apiBase = browserOrigin
		}

		// Bootstrap auth immediato lato client: in nuova tab (middle click) non
		// passa da page:loading:start, quindi forziamo init() per riallineare lo
		// stato utente con la sessione PRIMA che le pagine protette facciano
		// richieste API (altrimenti 401 per CSRF token non pronto).
		const { authCookie } = useAuthUiSnapshotPersistence()
		const route = useRoute()
		const { bootstrapReady, bootstrapStatus } = useAuthBootstrapState()

		// Su pagine pubbliche evita /api/user inutili lato guest, ma se abbiamo
		// uno snapshot auth valido bootstrappa subito per evitare il flash
		// "Accedi" → "Ciao Nome" su reload o nuova tab.
		const requiresImmediateAuthBootstrap =
			route.path.startsWith('/account')
		const canSoftBootstrapAuth =
			route.path.startsWith('/carrello')
			|| route.path.startsWith('/checkout')

		const shouldBootstrapFromSnapshot = isAuthenticatedSnapshotValue(authCookie.value)
		const shouldRunInit = requiresImmediateAuthBootstrap || (canSoftBootstrapAuth && shouldBootstrapFromSnapshot)

		if (!shouldRunInit) {
			// Su pagine pubbliche senza sessione auth nota consideriamo il bootstrap
			// "risolto": evita che la UI si fidi di snapshot stantii e lampeggi
			// navbar/account al primo render.
			bootstrapStatus.value = 'resolved'
			bootstrapReady.value = true
			nuxtApp.provide('authReady', bootstrapReady)
		} else {
			await runAuthBootstrap({ force: true })
			nuxtApp.provide('authReady', bootstrapReady)
		}

		// Hook app:mounted: indipendente dagli altri blocchi, può partire in parallelo.
		nuxtApp.hook('app:mounted', () => {
			const shipmentFlowStore = useShipmentStore()
			shipmentFlowStore.hydrateFromSession()
		})
	},
})
