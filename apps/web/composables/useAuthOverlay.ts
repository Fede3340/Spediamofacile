import type { User } from '~/types';

type AuthProvider = 'google' | 'facebook' | 'apple';
type SanctumClient = <T = unknown>(url: string, options?: { method?: string; body?: unknown }) => Promise<T>;
type ApiErrorData = {
	message?: string;
	error?: string;
	requires_verification?: boolean;
	errors?: Record<string, string | string[]>;
};
type ApiErrorLike = {
	message?: string;
	statusCode?: number;
	status?: number;
	data?: ApiErrorData;
	response?: {
		status?: number;
		_data?: ApiErrorData;
	};
};
type AuthResponse = { user?: User } & Record<string, unknown>;
type MessageResponse = { message?: string };

const isRecord = (value: unknown): value is Record<string, unknown> =>
	typeof value === 'object' && value !== null;

const asApiError = (error: unknown): ApiErrorLike => (isRecord(error) ? (error as ApiErrorLike) : {});
const getApiData = (error: unknown): ApiErrorData => {
	const apiError = asApiError(error);
	return apiError.response?._data || apiError.data || {};
};
const getApiStatus = (error: unknown) => {
	const apiError = asApiError(error);
	return Number(apiError.response?.status || apiError.statusCode || apiError.status || 0);
};

/**
 * @file useAuthOverlay — composable di orchestrazione per AuthOverlayModal.vue.
 * Estratto da composables/useAuth.js. Gestisce login, register, social, verifica email.
 */

// Usato da AuthOverlayModal.vue per mantenere il componente snello.
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Composable: useAuthOverlay
 * Logica completa per il modale di autenticazione (login, registrazione, verifica, social).
 * Usato da AuthOverlayModal.vue per mantenere il componente snello.
 */
export function useAuthOverlay() {
	const route = useRoute()
	const router = useRouter()
	const sanctum = useSanctumClient() as SanctumClient
	const { refreshIdentity } = useSanctumAuth()
	const { persistSnapshotFromUser } = useAuthUiSnapshotPersistence()
	const { authProviders, refreshAuthProviders } = useAuthProviders()
	const authModal = useAuthStore()
	const { isOpen, redirectPath, selectedTab, entryMode } = storeToRefs(authModal)
	const { openAuthModal, closeAuthModal } = authModal
	// Funnel analytics: track login/register success.
	const { trackAuthLogin, trackAuthRegister } = useFunnelAnalytics()

	// --- State ---
	const loginForm = ref({
		email: '',
		password: '',
		remember: false,
	})

	const registerForm = ref({
		name: '',
		surname: '',
		email: '',
		email_confirmation: '',
		prefix: '+39',
		telephone_number: '',
		password: '',
		password_confirmation: '',
		role: 'Cliente',
		user_type: 'privato',
		referred_by: '',
		privacy_accepted: false,
	})

	const isLoading = ref(false)
	const resendLoading = ref(false)
	const authError = ref('')
	const authSuccess = ref('')
	const socialError = ref('')
	const verificationMode = ref(false)
	const verificationLoading = ref(false)
	const verificationCode = ref(['', '', '', '', '', ''])
	const verificationError = ref('')
	const verificationSuccess = ref('')
	const showLoginPassword = ref(false)
	const showRegisterPassword = ref(false)
	const showRegisterPasswordConfirm = ref(false)

	// --- Computed ---
	const socialErrorTone = computed(() =>
		/temporaneamente non disponibile/i.test(socialError.value) ? 'muted' : 'error',
	)

	const currentRedirect = computed(() => sanitizeAuthRedirect(redirectPath.value || route.fullPath, '/'))
	const isBusy = computed(() => isLoading.value || resendLoading.value || verificationLoading.value)

	// --- Helpers ---
	const clearFeedback = () => {
		authError.value = ''
		authSuccess.value = ''
		socialError.value = ''
		verificationError.value = ''
		verificationSuccess.value = ''
	}

	const resetVerificationMode = () => {
		verificationMode.value = false
		verificationCode.value = ['', '', '', '', '', '']
		verificationError.value = ''
		verificationSuccess.value = ''
	}

	const resetModalState = () => {
		clearFeedback()
		resetVerificationMode()
	}

	const closeModal = () => {
		if (isBusy.value) return
		closeAuthModal()
	}

	const extractFirstApiError = (error: unknown) => {
		const data = getApiData(error)
		const explicit = data.message || data.error || asApiError(error).message
		if (explicit) return String(explicit)
		const errors = data?.errors
		if (errors && typeof errors === 'object') {
			const firstKey = Object.keys(errors)[0]
			if (!firstKey) return 'Operazione non riuscita. Riprova.'
			const firstEntry = errors[firstKey]
			const firstVal = Array.isArray(firstEntry) ? firstEntry[0] : firstEntry
			if (firstVal) return String(firstVal)
		}
		return 'Operazione non riuscita. Riprova.'
	}

	const humanizeSocialError = humanizeSocialAuthError

	// --- Social Auth ---
	const refreshProviderStatus = async () => {
		await refreshAuthProviders()
	}

	const buildSocialAuthUrl = (provider: AuthProvider) => {
		const params = new URLSearchParams({
			frontend: window.location.origin,
			redirect: currentRedirect.value,
			intent: selectedTab.value === 'register' ? 'register' : 'login',
		})
		if (selectedTab.value === 'register') {
			if (registerForm.value.referred_by.trim()) params.set('ref', registerForm.value.referred_by.trim())
			params.set('user_type', registerForm.value.user_type)
		}
		return `/api/auth/${provider}/redirect?${params.toString()}`
	}

	const startSocialAuth = async (provider: AuthProvider) => {
		await refreshProviderStatus()
		if (!authProviders.value[provider]) {
			socialError.value = humanizeSocialError(`${provider}_unavailable`)
			return
		}
		// Analytics: trackiamo l'intent, non il success (il success avviene dopo
		// il redirect OAuth che esce dall'app). Il tab attivo indica login vs register.
		if (selectedTab.value === 'register') {
			trackAuthRegister(provider)
		} else {
			trackAuthLogin(provider)
		}
		window.location.assign(buildSocialAuthUrl(provider))
	}

	// --- Auth Flow ---
	const finalizeAuth = async (responseUser: AuthResponse | User | null | undefined) => {
		const redirectTarget = currentRedirect.value
		const snapshotUser = isRecord(responseUser) && 'user' in responseUser ? responseUser.user as User : responseUser as User | null | undefined
		persistSnapshotFromUser(snapshotUser)
		await waitForPostAuthSync(refreshIdentity)
		try {
			await refreshNuxtData()
		} catch {
			// Se refreshNuxtData fallisce, la navigazione verso la route protetta riallinea comunque la UI.
		}
		closeAuthModal()
		resetModalState()
		// IMPORTANTE: niente window.location.assign() qui.
		// Un hard reload svuota il shipmentFlowStore (Pinia) PRIMA dell'idratazione da
		// sessionStorage. Il middleware shipment-validation trova localFlowState
		// con tutti i flag a false e reindirizza a step 1 colli — l'utente perde
		// tutti i dati form compilati (peso colli, indirizzi, servizi).
		// refreshIdentity() sopra + refreshNuxtData() hanno gia' sincronizzato
		// Sanctum. Una soft navigation preserva lo state Pinia.
		if (redirectTarget !== route.fullPath) {
			await navigateTo(redirectTarget, { replace: true })
		}
	}

	const openVerificationFromLogin = () => {
		verificationMode.value = true
		verificationCode.value = ['', '', '', '', '', '']
		verificationError.value = ''
		verificationSuccess.value = 'Inserisci il codice di verifica inviato alla tua email.'
		authSuccess.value = ''
	}

	const handleLogin = async () => {
		if (isLoading.value) return
		clearFeedback()
		if (!loginForm.value.email || !loginForm.value.password) {
			authError.value = 'Inserisci email e password per continuare.'
			return
		}
		isLoading.value = true
		try {
			const response = await sanctum<AuthResponse>('/api/custom-login', {
				method: 'POST',
				body: {
					email: loginForm.value.email,
					password: loginForm.value.password,
					remember: loginForm.value.remember,
				},
			})
			// Analytics: login email-password riuscito (no PII nel payload).
			trackAuthLogin('email')
			await finalizeAuth(response)
		} catch (error) {
			const statusCode = getApiStatus(error)
			const data = getApiData(error)
			if (statusCode === 403 && data?.requires_verification) {
				openVerificationFromLogin()
				return
			}
			authError.value = extractFirstApiError(error)
		} finally {
			isLoading.value = false
		}
	}

	const handleRegister = async () => {
		if (isLoading.value) return
		clearFeedback()
		if (!registerForm.value.name || !registerForm.value.surname) {
			authError.value = 'Inserisci nome e cognome.'
			return
		}
		if (!registerForm.value.email || !registerForm.value.email_confirmation) {
			authError.value = 'Inserisci e conferma la tua email.'
			return
		}
		if (registerForm.value.email !== registerForm.value.email_confirmation) {
			authError.value = 'Le email non coincidono.'
			return
		}
		if (!registerForm.value.password || !registerForm.value.password_confirmation) {
			authError.value = 'Inserisci e conferma la password.'
			return
		}
		if (registerForm.value.password !== registerForm.value.password_confirmation) {
			authError.value = 'Le password non coincidono.'
			return
		}
		isLoading.value = true
		try {
			await sanctum('/api/custom-register', { method: 'POST', body: registerForm.value })
			// Analytics: registrazione email riuscita. Trackiamo qui perché l'auto-login
			// successivo può fallire per verification pending senza che la registrazione
			// in sé sia fallita.
			trackAuthRegister('email')
			try {
				const loginResponse = await sanctum<AuthResponse>('/api/custom-login', {
					method: 'POST',
					body: {
						email: registerForm.value.email,
						password: registerForm.value.password,
						remember: true,
					},
				})
				await finalizeAuth(loginResponse)
				return
			} catch (error) {
				const statusCode = getApiStatus(error)
				const data = getApiData(error)
				if (statusCode === 403 && data?.requires_verification) {
					loginForm.value.email = registerForm.value.email
					loginForm.value.password = registerForm.value.password
					loginForm.value.remember = true
					selectedTab.value = 'register'
					openVerificationFromLogin()
					verificationSuccess.value = "Registrazione completata. Inserisci il codice a 6 cifre per attivare l\u2019account."
					return
				}
				throw error
			}
		} catch (error) {
			authError.value = extractFirstApiError(error)
		} finally {
			isLoading.value = false
		}
	}

	// --- Verification ---
	const handleVerificationInput = (index: number, event: Event) => {
		const target = event.target instanceof HTMLInputElement ? event.target : null
		if (!target) return
		verificationCode.value[index] = target.value.replace(/\D/g, '').slice(0, 1)
		if (verificationCode.value[index] && index < 5) {
			const next = document.querySelector<HTMLInputElement>(`[data-verification-index="${index + 1}"]`)
			next?.focus()
		}
	}

	const handleVerificationKeydown = (index: number, event: KeyboardEvent) => {
		if (event.key === 'Backspace' && !verificationCode.value[index] && index > 0) {
			const prev = document.querySelector<HTMLInputElement>(`[data-verification-index="${index - 1}"]`)
			prev?.focus()
		}
	}

	const verifyCode = async () => {
		const code = verificationCode.value.join('')
		if (code.length !== 6) {
			verificationError.value = 'Inserisci il codice completo a 6 cifre.'
			return
		}
		verificationLoading.value = true
		verificationError.value = ''
		try {
			const response = await sanctum<AuthResponse>('/api/verify-code', {
				method: 'POST',
				body: {
					email: loginForm.value.email,
					password: loginForm.value.password,
					remember: loginForm.value.remember,
					code,
				},
			})
			await finalizeAuth(response?.user || response)
		} catch (error) {
			verificationError.value = extractFirstApiError(error)
		} finally {
			verificationLoading.value = false
		}
	}

	const resendVerificationEmail = async () => {
		if (!loginForm.value.email) {
			verificationError.value = "Inserisci prima un\u2019email valida."
			return
		}
		resendLoading.value = true
		verificationError.value = ''
		try {
			const response = await sanctum<MessageResponse>('/api/resend-verification-email', {
				method: 'POST',
				body: { email: loginForm.value.email },
			})
			verificationSuccess.value = response?.message || 'Nuovo codice inviato.'
		} catch (error) {
			verificationError.value = extractFirstApiError(error)
		} finally {
			resendLoading.value = false
		}
	}

	// --- Route error watcher ---
	const clearAuthErrorQuery = async () => {
		const nextQuery = { ...route.query }
		delete nextQuery.auth_error
		delete nextQuery.auth_modal
		await router.replace({ path: route.path, query: nextQuery, hash: route.hash })
	}

	const handleRouteAuthError = async () => {
		const routeError = route.query.auth_error
		const authErrorValue = Array.isArray(routeError) ? routeError[0] : routeError
		if (!authErrorValue) return
		selectedTab.value = 'login'
		openAuthModal({ redirect: route.fullPath, tab: 'login' })
		socialError.value = humanizeSocialError(String(authErrorValue))
		await clearAuthErrorQuery()
	}

	// --- Watchers ---
	watch(isOpen, async (open) => {
		if (!open) return
		clearFeedback()
		await refreshProviderStatus()
	})

	watch(
		() => route.query.auth_error,
		async () => { await handleRouteAuthError() },
		{ immediate: true },
	)

	return {
		// State
		loginForm,
		registerForm,
		isLoading,
		resendLoading,
		authError,
		authSuccess,
		socialError,
		verificationMode,
		verificationLoading,
		verificationCode,
		verificationError,
		verificationSuccess,
		showLoginPassword,
		showRegisterPassword,
		showRegisterPasswordConfirm,
		// Computed
		socialErrorTone,
		isBusy,
		isOpen,
		selectedTab,
		entryMode,
		authProviders,
		// Methods
		clearFeedback,
		resetVerificationMode,
		closeModal,
		startSocialAuth,
		handleLogin,
		handleRegister,
		handleVerificationInput,
		handleVerificationKeydown,
		verifyCode,
		resendVerificationEmail,
	}
}
