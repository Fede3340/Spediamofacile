/**
 * useAuth.js — Aggregatore auth: snapshot+persistence, UI state, social providers, overlay form.
 * NOTA: lo stato del modal auth vive in `useAuthModalStore` (Pinia).
 */

import {
	AUTH_UI_COOKIE,
	AUTH_UI_STORAGE,
	createEmptySnapshot,
	humanizeSocialAuthError,
	parseStoredSnapshot,
	sanitizeAuthRedirect,
	snapshotFromUser,
	useAuthBootstrapState,
	waitForPostAuthSync,
} from '~/utils/auth'

// ─────────────────────────────────────────────────────────────────────────────
// SEZIONE 1: Snapshot Persistence (ex useAuthUiSnapshotPersistence)
// Persistenza snapshot auth UI su cookie + localStorage, con useState per reattività SSR-safe.
// ─────────────────────────────────────────────────────────────────────────────

/**
 * @typedef {import('~/utils/authUiState').AuthUiSnapshot} AuthUiSnapshot
 * @typedef {import('~/utils/authUiState').AuthUiUser} AuthUiUser
 */

/** Persistenza snapshot auth UI su cookie + localStorage, con useState per reattività SSR-safe. */
export const useAuthUiSnapshotPersistence = () => {
	const authCookie = useCookie(AUTH_UI_COOKIE, {
		sameSite: 'lax',
		path: '/',
		// HTTPS-only in produzione (in dev http://localhost va in chiaro per non rompere il login).
		secure: !import.meta.dev,
	})
	const initialSnapshot = useState('auth-ui-initial-snapshot', createEmptySnapshot)
	const storedSnapshot = useState('auth-ui-stored-snapshot', createEmptySnapshot)

	// In alcuni bootstrap/client restore il cookie arriva come stringa JSON url-encoded
	// invece che come oggetto gia' deserializzato. Normalizziamo subito per evitare
	// che middleware e plugin leggano `authenticated` come undefined.
	if (typeof authCookie.value === 'string') {
		authCookie.value = parseStoredSnapshot(authCookie.value)
	}

	const persistSnapshot = (snapshot) => {
		authCookie.value = snapshot
		initialSnapshot.value = snapshot
		storedSnapshot.value = snapshot
		if (import.meta.client) {
			window.localStorage.setItem(AUTH_UI_STORAGE, JSON.stringify(snapshot))
		}
	}

	const persistSnapshotFromUser = (user) => {
		if (!user) {
			return
		}

		persistSnapshot(snapshotFromUser(user))
	}

	const clearSnapshot = () => {
		const snapshot = createEmptySnapshot()
		authCookie.value = undefined
		initialSnapshot.value = snapshot
		storedSnapshot.value = snapshot
		if (import.meta.client) {
			window.localStorage.removeItem(AUTH_UI_STORAGE)
		}
	}

	return {
		authCookie,
		clearSnapshot,
		initialSnapshot,
		persistSnapshot,
		persistSnapshotFromUser,
		storedSnapshot,
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// SEZIONE 2: UI State (ex useAuthUiState)
// Stato UI auth: snapshot SSR-safe + sync opzionale con Sanctum live (guest-only blacklist).
// ─────────────────────────────────────────────────────────────────────────────

/**
 * @typedef {import('~/utils/authUiState').AuthUiUser} AuthUiUser
 */

/** Stato UI auth: snapshot SSR-safe + sync opzionale con Sanctum live (guest-only blacklist). */
export const useAuthUiState = () => {
	const { bootstrapReady, bootstrapStatus } = useAuthBootstrapState()
	const { authCookie, clearSnapshot, initialSnapshot, persistSnapshotFromUser } =
		useAuthUiSnapshotPersistence()
	const route = useRoute()
	const guestOnlyPrefixes = ['/autenticazione', '/login', '/registrazione', '/recupera-password', '/aggiorna-password']
	const hasAuthenticatedSnapshot = computed(() => Boolean(authCookie.value?.authenticated))
	// BLACKLIST: Sanctum si attacca ovunque TRANNE sulle pagine guest-only.
	// Evita di tenere disattivo il live auth su pagine pubbliche dove l'utente
	// potrebbe gia' essere autenticato (es. homepage, servizi, contatti).
	const shouldAttachLiveAuth = computed(() => {
		if (!import.meta.client) return false
		if (guestOnlyPrefixes.some((prefix) => route.path.startsWith(prefix))) return false
		return true
	})
	const auth = shallowRef(null)
	const liveAuthInitPending = ref(false)

	if (import.meta.client) {
		watchEffect(() => {
			if (!auth.value && shouldAttachLiveAuth.value) {
				auth.value = useSanctumAuth()
			}
		})

		watch(
			() => [shouldAttachLiveAuth.value, hasAuthenticatedSnapshot.value, Boolean(auth.value?.isAuthenticated?.value)],
			async ([shouldAttach, hasAuthenticatedSnapshot, alreadyAuthenticated]) => {
				if (!shouldAttach || !hasAuthenticatedSnapshot || alreadyAuthenticated || !auth.value || liveAuthInitPending.value) {
					return
				}

				liveAuthInitPending.value = true
				try {
					await auth.value.init?.()
				} catch {
					// Se il cookie snapshot e' stantio lasciamo il composable degradare sulla snapshot stessa.
				} finally {
					liveAuthInitPending.value = false
				}
			},
			{ immediate: true },
		)
	}

	const liveAuthenticated = computed(() => Boolean(auth.value?.isAuthenticated?.value))
	const liveUser = computed(() => {
		const user = auth.value?.user?.value
		return user ?? null
	})

	if (import.meta.client) {
		watch(
			[liveAuthenticated, liveUser, bootstrapReady, bootstrapStatus, liveAuthInitPending],
			([authenticated, user, ready, status, initPending]) => {
				if (authenticated && user) {
					persistSnapshotFromUser(user)
					return
				}

				if (!auth.value || initPending || status === 'pending') {
					return
				}

				if (ready && status === 'resolved') {
					// Guard anti-logout spurio: se il cookie snapshot SSR-safe e' ancora
					// valido (authenticated=true), NON cancellare. Il backend e' fonte di
					// verita' finale ma il cookie preserva la UI finche' non c'e'
					// evidenza opposta (401 esplicito dal backend).
					if (authCookie.value?.authenticated) {
						return
					}
					clearSnapshot()
				}
			},
			{ immediate: true },
		)
	}

	const uiSnapshot = computed(() => {
		if (liveAuthenticated.value && liveUser.value) {
			return snapshotFromUser(liveUser.value)
		}

		// Su pagine pubbliche non agganciamo live auth per evitare fetch inutili,
		// ma dopo un login da modale il cookie SSR-safe deve aggiornare subito navbar e CTA.
		if (authCookie.value?.authenticated) {
			return authCookie.value
		}

		if (bootstrapStatus.value !== 'resolved') {
			if (initialSnapshot.value.authenticated) {
				return initialSnapshot.value
			}
		}

		return createEmptySnapshot()
	})

	// Stato stabile per evitare flicker: aggiorna solo quando abbiamo evidenza
	// forte (true=auth confermato, false=resolved senza auth). Negli stati
	// intermedi (pending, init) mantiene il valore precedente.
	const authenticatedState = ref(null)
	if (import.meta.client) {
		watch(
			[liveAuthenticated, bootstrapStatus, hasAuthenticatedSnapshot],
			([authenticated, status, snapshotAuth]) => {
				if (authenticated || snapshotAuth) {
					authenticatedState.value = true
					return
				}
				if (status === 'resolved' && !authenticated && !snapshotAuth) {
					authenticatedState.value = false
					return
				}
				// altrimenti mantieni il valore precedente (no flicker)
			},
			{ immediate: true },
		)
	}

	const isAuthenticatedForUi = computed(() => {
		if (authenticatedState.value !== null) return authenticatedState.value
		return uiSnapshot.value.authenticated
	})

	// Pending UI: true finche' non abbiamo una risposta stabile sull'auth
	// (bootstrap non risolto E nessuno snapshot cookie autoritativo).
	const isAuthUiPending = computed(() => {
		if (authenticatedState.value !== null) return false
		if (authCookie.value?.authenticated) return false
		return bootstrapStatus.value !== 'resolved'
	})

	const accountLabel = computed(() => {
		if (!uiSnapshot.value.authenticated) return 'Accedi'
		if (uiSnapshot.value.role === 'Admin') return 'Area Admin'
		return uiSnapshot.value.name ? `Ciao ${uiSnapshot.value.name}` : 'Il mio account'
	})
	const mobileAccountLabel = computed(() => {
		if (!uiSnapshot.value.authenticated) return 'Accedi o Registrati'
		if (uiSnapshot.value.role === 'Admin') return 'Area Admin'
		return 'Il mio account'
	})

	return {
		accountLabel,
		bootstrapReady,
		bootstrapStatus,
		isAuthenticatedForUi,
		isAuthUiPending,
		liveAuthenticated,
		liveUser,
		mobileAccountLabel,
		uiSnapshot,
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// SEZIONE 3: Providers Config (ex useAuthProviders)
// Tiene traccia della disponibilità dei provider social (Google/Facebook/Apple).
// ─────────────────────────────────────────────────────────────────────────────

/**
 * @typedef {{ google: boolean, facebook: boolean, apple: boolean }} AuthProvidersAvailability
 */

const defaultProviders = () => ({
	google: false,
	facebook: false,
	apple: false,
})

/**
 * Composable che tiene traccia della disponibilità dei provider social (Google/Facebook/Apple).
 * Thin wrapper retro-compat sullo store Pinia `authProvidersStore` (Vue DevTools-friendly).
 */
export const useAuthProviders = () => {
	const store = useAuthProvidersStore()
	const { providers, loaded, loading } = storeToRefs(store)

	return {
		authProviders: providers,
		authProvidersLoaded: loaded,
		authProvidersLoading: loading,
		refreshAuthProviders: store.refresh,
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// SEZIONE 4: Overlay Logic (ex useAuthOverlay)
// useAuthOverlay estratto in composables/useAuthOverlay.js
