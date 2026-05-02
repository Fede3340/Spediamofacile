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
	response?: { status?: number; _data?: ApiErrorData };
};
type AuthResponse = { user?: User } & Record<string, unknown>;
type MessageResponse = { message?: string };

const isRecord = (value: unknown): value is Record<string, unknown> =>
	typeof value === 'object' && value !== null;
const asApiError = (error: unknown): ApiErrorLike => (isRecord(error) ? (error as ApiErrorLike) : {});
const getApiData = (error: unknown): ApiErrorData => {
	const e = asApiError(error);
	return e.response?._data || e.data || {};
};
const getApiStatus = (error: unknown): number => {
	const e = asApiError(error);
	return Number(e.response?.status || e.statusCode || e.status || 0);
};
const emptyCode = (): string[] => ['', '', '', '', '', ''];

/**
 * useAuthOverlay — orchestrazione AuthOverlayModal.vue (login/register/verify/social).
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
	const { trackAuthLogin, trackAuthRegister } = useFunnelAnalytics()

	const loginForm = ref({ email: '', password: '', remember: false })
	const registerForm = ref({
		name: '', surname: '', email: '', email_confirmation: '',
		prefix: '+39', telephone_number: '',
		password: '', password_confirmation: '',
		role: 'Cliente', user_type: 'privato', referred_by: '', privacy_accepted: false,
	})

	const isLoading = ref(false)
	const resendLoading = ref(false)
	const authError = ref('')
	const authSuccess = ref('')
	const socialError = ref('')
	const verificationMode = ref(false)
	const verificationLoading = ref(false)
	const verificationCode = ref<string[]>(emptyCode())
	const verificationError = ref('')
	const verificationSuccess = ref('')
	const showLoginPassword = ref(false)
	const showRegisterPassword = ref(false)
	const showRegisterPasswordConfirm = ref(false)

	const socialErrorTone = computed(() =>
		/temporaneamente non disponibile/i.test(socialError.value) ? 'muted' : 'error',
	)
	const currentRedirect = computed(() => sanitizeAuthRedirect(redirectPath.value || route.fullPath, '/'))
	const isBusy = computed(() => isLoading.value || resendLoading.value || verificationLoading.value)

	const clearFeedback = () => {
		authError.value = ''; authSuccess.value = ''; socialError.value = ''
		verificationError.value = ''; verificationSuccess.value = ''
	}
	const resetVerificationMode = () => {
		verificationMode.value = false
		verificationCode.value = emptyCode()
		verificationError.value = ''; verificationSuccess.value = ''
	}
	const closeModal = () => { if (!isBusy.value) closeAuthModal() }

	const extractFirstApiError = (error: unknown): string => {
		const data = getApiData(error)
		const explicit = data.message || data.error || asApiError(error).message
		if (explicit) return String(explicit)
		const errors = data.errors
		const firstKey = errors && typeof errors === 'object' ? Object.keys(errors)[0] : undefined
		const entry = firstKey ? errors![firstKey] : undefined
		const val = Array.isArray(entry) ? entry[0] : entry
		return val ? String(val) : 'Operazione non riuscita. Riprova.'
	}

	const buildSocialAuthUrl = (provider: AuthProvider): string => {
		const params = new URLSearchParams({
			frontend: window.location.origin,
			redirect: currentRedirect.value,
			intent: selectedTab.value === 'register' ? 'register' : 'login',
		})
		if (selectedTab.value === 'register') {
			const ref = registerForm.value.referred_by.trim()
			if (ref) params.set('ref', ref)
			params.set('user_type', registerForm.value.user_type)
		}
		return `/api/auth/${provider}/redirect?${params.toString()}`
	}

	const startSocialAuth = async (provider: AuthProvider) => {
		await refreshAuthProviders()
		if (!authProviders.value[provider]) {
			socialError.value = humanizeSocialAuthError(`${provider}_unavailable`)
			return
		}
		// Track intent (success arriva post-redirect OAuth fuori app).
		;(selectedTab.value === 'register' ? trackAuthRegister : trackAuthLogin)(provider)
		window.location.assign(buildSocialAuthUrl(provider))
	}

	const finalizeAuth = async (responseUser: AuthResponse | User | null | undefined) => {
		const redirectTarget = currentRedirect.value
		const snapshotUser = isRecord(responseUser) && 'user' in responseUser
			? (responseUser.user as User)
			: (responseUser as User | null | undefined)
		persistSnapshotFromUser(snapshotUser)
		await waitForPostAuthSync(refreshIdentity)
		try { await refreshNuxtData() } catch { /* la nav verso route protetta riallinea */ }
		closeAuthModal()
		clearFeedback(); resetVerificationMode()
		// CRITICAL: niente window.location.assign() — un hard reload svuota shipmentFlowStore
		// (Pinia) prima dell'idratazione, il middleware shipment-validation reindirizza
		// a step 1 e l'utente perde i dati form. Soft navigation preserva lo state.
		if (redirectTarget !== route.fullPath) {
			await navigateTo(redirectTarget, { replace: true })
		}
	}

	const openVerificationFromLogin = () => {
		verificationMode.value = true
		verificationCode.value = emptyCode()
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
				body: { ...loginForm.value },
			})
			trackAuthLogin('email')
			await finalizeAuth(response)
		} catch (error) {
			if (getApiStatus(error) === 403 && getApiData(error)?.requires_verification) {
				openVerificationFromLogin()
				return
			}
			authError.value = extractFirstApiError(error)
		} finally {
			isLoading.value = false
		}
	}

	const validateRegister = (): string | null => {
		const f = registerForm.value
		if (!f.name || !f.surname) return 'Inserisci nome e cognome.'
		if (!f.email || !f.email_confirmation) return 'Inserisci e conferma la tua email.'
		if (f.email !== f.email_confirmation) return 'Le email non coincidono.'
		if (!f.password || !f.password_confirmation) return 'Inserisci e conferma la password.'
		if (f.password !== f.password_confirmation) return 'Le password non coincidono.'
		return null
	}

	const handleRegister = async () => {
		if (isLoading.value) return
		clearFeedback()
		const validationError = validateRegister()
		if (validationError) { authError.value = validationError; return }
		isLoading.value = true
		try {
			await sanctum('/api/custom-register', { method: 'POST', body: registerForm.value })
			// Track qui: l'auto-login successivo può fallire per verification pending senza
			// che la registrazione in sé sia fallita.
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
				if (getApiStatus(error) === 403 && getApiData(error)?.requires_verification) {
					loginForm.value.email = registerForm.value.email
					loginForm.value.password = registerForm.value.password
					loginForm.value.remember = true
					selectedTab.value = 'register'
					openVerificationFromLogin()
					verificationSuccess.value = 'Registrazione completata. Inserisci il codice a 6 cifre per attivare l’account.'
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

	const focusVerificationInput = (index: number) => {
		document.querySelector<HTMLInputElement>(`[data-verification-index="${index}"]`)?.focus()
	}

	const handleVerificationInput = (index: number, event: Event) => {
		const target = event.target instanceof HTMLInputElement ? event.target : null
		if (!target) return
		verificationCode.value[index] = target.value.replace(/\D/g, '').slice(0, 1)
		if (verificationCode.value[index] && index < 5) focusVerificationInput(index + 1)
	}

	const handleVerificationKeydown = (index: number, event: KeyboardEvent) => {
		if (event.key === 'Backspace' && !verificationCode.value[index] && index > 0) {
			focusVerificationInput(index - 1)
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
				body: { ...loginForm.value, code },
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
			verificationError.value = 'Inserisci prima un’email valida.'
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

	const handleRouteAuthError = async () => {
		const routeError = route.query.auth_error
		const value = Array.isArray(routeError) ? routeError[0] : routeError
		if (!value) return
		selectedTab.value = 'login'
		openAuthModal({ redirect: route.fullPath, tab: 'login' })
		socialError.value = humanizeSocialAuthError(String(value))
		const nextQuery = { ...route.query }
		delete nextQuery.auth_error
		delete nextQuery.auth_modal
		await router.replace({ path: route.path, query: nextQuery, hash: route.hash })
	}

	watch(isOpen, async (open) => {
		if (!open) return
		clearFeedback()
		await refreshAuthProviders()
	})
	watch(() => route.query.auth_error, async () => { await handleRouteAuthError() }, { immediate: true })

	return {
		loginForm, registerForm,
		isLoading, resendLoading,
		authError, authSuccess, socialError,
		verificationMode, verificationLoading, verificationCode,
		verificationError, verificationSuccess,
		showLoginPassword, showRegisterPassword, showRegisterPasswordConfirm,
		socialErrorTone, isBusy,
		isOpen, selectedTab, entryMode, authProviders,
		clearFeedback, resetVerificationMode, closeModal,
		startSocialAuth, handleLogin, handleRegister,
		handleVerificationInput, handleVerificationKeydown,
		verifyCode, resendVerificationEmail,
	}
}
