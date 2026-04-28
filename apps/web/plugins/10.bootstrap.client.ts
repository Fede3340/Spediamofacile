import { isAuthenticatedSnapshotValue, runAuthBootstrap, useAuthBootstrapState } from '~/utils/auth'

/**
 * PLUGIN CONSOLIDATO — bootstrap client-side unificato.
 *
 * Nato da merge (2026-04-20) di 4 plugin client-only:
 *   - 00.sanctum-dynamic-url.client.ts  (fix baseUrl per tunnel Cloudflare)
 *   - 01.sanctum-bootstrap.client.ts    (bootstrap auth iniziale)
 *   - 01.shipment-flow-store-hydrate.client.ts  (hydrate shipment store)
 *   - 02.hydrated-class.client.ts       (classe .is-hydrated su <html>)
 *
 * PERCHE' UN UNICO FILE?
 *   Nuxt rispetta l'ordine di esecuzione interno di un plugin: `setup()` gira
 *   riga-per-riga in modo sequenziale, quindi chiamare 4 blocchi uno dopo
 *   l'altro equivale a 4 plugin separati con `enforce: 'pre'`. Meno file da
 *   caricare, meno overhead di bootstrap, nessun cambio di comportamento.
 *
 * ORDINE DI ESECUZIONE (critico):
 *   1. .is-hydrated  → per primo, sblocca transizioni CSS prima di qualsiasi
 *                      rerender (evita flicker durante hydration).
 *   2. sanctum URL   → PRIMA che nuxt-auth-sanctum inizializzi, altrimenti le
 *                      chiamate CSRF/login partono verso 127.0.0.1:8787 anche
 *                      quando siamo su tunnel Cloudflare.
 *   3. auth bootstrap → dopo che sanctum e' pronto, chiama /api/user se serve.
 *   4. shipment hydrate → hook app:mounted, indipendente dagli altri.
 *
 * Non tocca il plugin universale `00.auth-ui-seed.js` (che gira anche lato
 * server).
 */
export default defineNuxtPlugin({
	name: 'sf-bootstrap-client',
	enforce: 'pre', // Esegui PRIMA di nuxt-auth-sanctum e di gran parte dei plugin
	async setup(nuxtApp) {
		if (typeof window === 'undefined') return

		// ─────────────────────────────────────────────────────────────────────
		// 1. Aggiungi .is-hydrated a <html>
		// ─────────────────────────────────────────────────────────────────────
		// Scopo: eliminare artefatti visivi durante l'hydration (classi che
		// cambiano provocano transizioni visibili prima che il layout sia
		// stabile). Vedi main.css regola `html:not(.is-hydrated) * { transition: none }`.
		// Aggiungo subito appena il JS client parte.
		document.documentElement.classList.add('is-hydrated')

		// ─────────────────────────────────────────────────────────────────────
		// 2. Fixa URL sanctum per Cloudflare tunnel (PRE-sanctum init)
		// ─────────────────────────────────────────────────────────────────────
		// PROBLEMA:
		//   In nuxt.config.ts, sanctum.baseUrl e' impostato a "http://127.0.0.1:8787"
		//   per il funzionamento locale. Ma quando il sito e' condiviso tramite tunnel
		//   Cloudflare (es. https://abc123.trycloudflare.com), le chiamate API del browser
		//   andrebbero a http://127.0.0.1:8787 che e':
		//   1. Un'origine DIVERSA dalla pagina → i cookie di sessione NON vengono inviati
		//      dal browser (SameSite=Lax blocca i cookie cross-origin)
		//   2. Irraggiungibile da browser remoti (127.0.0.1 e' il LORO localhost, non il server)
		//
		// SOLUZIONE:
		//   Riscrivere baseUrl all'origine corrente del browser (window.location.origin).
		//   Cosi' tutte le chiamate API passano dallo stesso dominio della pagina:
		//   - Locale: http://127.0.0.1:8787 → nessun cambiamento
		//   - Cloudflare: https://abc123.trycloudflare.com → le API passano dal tunnel
		//
		// PERCHE' SOLO CLIENT?
		//   Durante il rendering lato server (SSR), Nuxt gira sullo stesso server di Laravel,
		//   quindi http://127.0.0.1:8787 funziona perfettamente. Il problema esiste SOLO
		//   nel browser dell'utente che potrebbe essere su un dominio diverso.
		const config = useRuntimeConfig()
		const browserOrigin = window.location.origin
		const { hostname, port } = window.location

		// In sviluppo puro Nuxt (3000/3001) NON dobbiamo riscrivere baseUrl verso
		// la stessa origine del frontend, altrimenti tutte le chiamate API finiscono
		// su Nuxt invece che su Laravel/proxy e generano 404.
		const isLocalNuxtDevOrigin =
			['127.0.0.1', 'localhost'].includes(hostname) &&
			['3000', '3001'].includes(port)

		if (!isLocalNuxtDevOrigin) {
			// Imposta il baseUrl di Sanctum all'origine corrente del browser.
			// Questo fa si' che le chiamate di autenticazione (login, logout, csrf, user)
			// passino sempre dallo stesso dominio della pagina.
			config.public.sanctum.baseUrl = browserOrigin

			// Imposta anche apiBase, usato da componenti e pagine che fanno fetch dirette
			// e non passano dal client Sanctum.
			config.public.apiBase = browserOrigin
		}

		// ─────────────────────────────────────────────────────────────────────
		// 3. Bootstrap auth iniziale
		// ─────────────────────────────────────────────────────────────────────
		// Bootstrap auth immediato lato client:
		// in nuova tab (middle click) non passa da page:loading:start, quindi
		// forziamo subito init() per riallineare lo stato utente con la sessione.
		//
		// IMPORTANTE: Questo blocco DEVE completare prima che le pagine protette
		// facciano richieste API, altrimenti otteniamo 401 (CSRF token non pronto).
		const { authCookie } = useAuthUiSnapshotPersistence()
		const route = useRoute()
		const { bootstrapReady, bootstrapStatus } = useAuthBootstrapState()

		// Su pagine pubbliche evitiamo /api/user inutili lato guest,
		// ma se abbiamo gia' uno snapshot auth valido bootstrappiamo subito
		// per evitare il flash "Accedi" -> "Ciao Nome" su reload o nuova tab.
		const requiresImmediateAuthBootstrap =
			route.path.startsWith('/account')
		const canSoftBootstrapAuth =
			route.path.startsWith('/carrello')
			|| route.path.startsWith('/checkout')

		const shouldBootstrapFromSnapshot = isAuthenticatedSnapshotValue(authCookie.value)
		const shouldRunInit = requiresImmediateAuthBootstrap || (canSoftBootstrapAuth && shouldBootstrapFromSnapshot)

		if (!shouldRunInit) {
			// Su pagine pubbliche senza sessione auth nota consideriamo il bootstrap
			// gia' "risolto": cosi' la UI non continua a fidarsi di snapshot locali
			// stantii che possono far lampeggiare navbar/account al primo render.
			bootstrapStatus.value = 'resolved'
			bootstrapReady.value = true
			nuxtApp.provide('authReady', bootstrapReady)
		} else {
			await runAuthBootstrap({ force: true })
			nuxtApp.provide('authReady', bootstrapReady)
		}

		// ─────────────────────────────────────────────────────────────────────
		// 4. Hydrate shipment flow store da sessione
		// ─────────────────────────────────────────────────────────────────────
		// Hook app:mounted: aspetta che il root component sia montato e poi
		// rileggere eventuali dati di flusso spedizione salvati in sessione.
		// Indipendente dagli altri blocchi: puo' partire in parallelo.
		nuxtApp.hook('app:mounted', () => {
			const shipmentFlowStore = useShipmentFlowStore()
			shipmentFlowStore.hydrateFromSession()
		})
	},
})
