import type { Ref } from 'vue'
import type { User } from '~/types'
import {
	AUTH_UI_COOKIE,
	AUTH_UI_STORAGE,
	type AuthUiSnapshot,
	createEmptySnapshot,
	parseStoredSnapshot,
	snapshotFromUser,
	useAuthBootstrapState,
} from '~/utils/auth'

type AuthCookieRuntimeValue = AuthUiSnapshot | string | undefined
type SnapshotUser = Parameters<typeof snapshotFromUser>[0]
type LiveSanctumAuth = {
	isAuthenticated: Ref<boolean>
	user: Ref<User | SnapshotUser | null>
	init?: () => Promise<unknown>
}

const normalizeCookieSnapshot = (cookie: Ref<AuthCookieRuntimeValue>): AuthUiSnapshot => {
	const value = cookie.value
	return typeof value === 'string' ? parseStoredSnapshot(value) : value ?? createEmptySnapshot()
}

export const useAuthUiSnapshotPersistence = () => {
	const authCookie = useCookie<AuthUiSnapshot | undefined>(AUTH_UI_COOKIE, {
		sameSite: 'lax',
		path: '/',
		secure: !import.meta.dev,
	})
	const runtimeCookie = authCookie as Ref<AuthCookieRuntimeValue>
	const initialSnapshot = useState<AuthUiSnapshot>('auth-ui-initial-snapshot', createEmptySnapshot)
	const storedSnapshot = useState<AuthUiSnapshot>('auth-ui-stored-snapshot', createEmptySnapshot)

	if (typeof runtimeCookie.value === 'string') {
		authCookie.value = parseStoredSnapshot(runtimeCookie.value)
	}

	const persistSnapshot = (snapshot: AuthUiSnapshot) => {
		authCookie.value = snapshot
		initialSnapshot.value = snapshot
		storedSnapshot.value = snapshot

		if (import.meta.client) {
			window.localStorage.setItem(AUTH_UI_STORAGE, JSON.stringify(snapshot))
		}
	}

	const persistSnapshotFromUser = (user: User | SnapshotUser | null | undefined) => {
		if (user) {
			persistSnapshot(snapshotFromUser(user))
		}
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

export const useAuthUiState = () => {
	const { bootstrapReady, bootstrapStatus } = useAuthBootstrapState()
	const { authCookie, clearSnapshot, initialSnapshot, persistSnapshotFromUser } =
		useAuthUiSnapshotPersistence()
	const route = useRoute()
	const auth = shallowRef<LiveSanctumAuth | null>(null)
	const liveAuthInitPending = ref(false)
	const authenticatedState = ref<boolean | null>(null)
	const guestOnlyPrefixes = ['/autenticazione', '/login', '/registrazione', '/recupera-password', '/aggiorna-password']

	const cookieSnapshot = computed(() => normalizeCookieSnapshot(authCookie as Ref<AuthCookieRuntimeValue>))
	const hasAuthenticatedSnapshot = computed(() => cookieSnapshot.value.authenticated)
	const shouldAttachLiveAuth = computed(() => {
		if (!import.meta.client) return false
		return !guestOnlyPrefixes.some((prefix) => route.path.startsWith(prefix))
	})

	if (import.meta.client) {
		watchEffect(() => {
			if (!auth.value && shouldAttachLiveAuth.value) {
				auth.value = useSanctumAuth() as unknown as LiveSanctumAuth
			}
		})

		watch(
			() => [shouldAttachLiveAuth.value, hasAuthenticatedSnapshot.value, Boolean(auth.value?.isAuthenticated.value)],
			async ([shouldAttach, hasSnapshot, alreadyAuthenticated]) => {
				if (!shouldAttach || !hasSnapshot || alreadyAuthenticated || !auth.value || liveAuthInitPending.value) {
					return
				}

				liveAuthInitPending.value = true
				try {
					await auth.value.init?.()
				} finally {
					liveAuthInitPending.value = false
				}
			},
			{ immediate: true },
		)
	}

	const liveAuthenticated = computed(() => Boolean(auth.value?.isAuthenticated.value))
	const liveUser = computed(() => auth.value?.user.value ?? null)

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

				if (ready && status === 'resolved' && !cookieSnapshot.value.authenticated) {
					clearSnapshot()
				}
			},
			{ immediate: true },
		)

		watch(
			[liveAuthenticated, bootstrapStatus, hasAuthenticatedSnapshot],
			([authenticated, status, snapshotAuth]) => {
				if (authenticated || snapshotAuth) {
					authenticatedState.value = true
					return
				}

				if (status === 'resolved') {
					authenticatedState.value = false
				}
			},
			{ immediate: true },
		)
	}

	const uiSnapshot = computed<AuthUiSnapshot>(() => {
		if (liveAuthenticated.value && liveUser.value) {
			return snapshotFromUser(liveUser.value)
		}

		if (cookieSnapshot.value.authenticated) {
			return cookieSnapshot.value
		}

		if (bootstrapStatus.value !== 'resolved' && initialSnapshot.value.authenticated) {
			return initialSnapshot.value
		}

		return createEmptySnapshot()
	})

	const isAuthenticatedForUi = computed(() => authenticatedState.value ?? uiSnapshot.value.authenticated)
	const isAuthUiPending = computed(() => {
		if (authenticatedState.value !== null || cookieSnapshot.value.authenticated) {
			return false
		}

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

export const useAuthProviders = () => {
	const store = useAuthStore()
	const { providers, providersLoaded, providersLoading } = storeToRefs(store)

	return {
		authProviders: providers,
		authProvidersLoaded: providersLoaded,
		authProvidersLoading: providersLoading,
		refreshAuthProviders: store.refreshProviders,
	}
}
