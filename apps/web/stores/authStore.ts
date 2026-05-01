/**
 * authStore — store unificato del modulo autenticazione.
 *
 * Fonde i 2 store storici (Ondata 3 consolidamento Pinia):
 *  - authModalStore: stato modal login/register/forgot
 *  - authProvidersStore: disponibilita' provider OAuth (Google/Facebook/Apple)
 *
 * Dominio unico: tutto cio' che riguarda l'autenticazione client-side passa
 * da qui (modal + OAuth providers). Ispezionabile in Vue DevTools.
 */
import { defineStore } from 'pinia';
import { ref } from 'vue';

export type AuthModalTab = 'login' | 'register' | 'forgot';

type AuthModalOptions = {
	tab?: AuthModalTab;
	redirect?: string;
	entryMode?: string | null;
};

type AuthProviders = {
	google: boolean;
	facebook: boolean;
	apple: boolean;
};

const normalizeAuthRedirect = (redirect?: string) =>
	redirect && redirect.startsWith('/') ? redirect : '/';

const defaultProviders = (): AuthProviders => ({
	google: false,
	facebook: false,
	apple: false,
});

export const useAuthStore = defineStore('auth', () => {
	// ── Modal state (ex authModalStore) ──────────────────────────────────
	const isOpen = ref(false);
	const selectedTab = ref<AuthModalTab>('login');
	const redirectPath = ref('/');
	const entryMode = ref<string | null>(null);

	function openAuthModal(options: AuthModalOptions = {}) {
		selectedTab.value = options.tab ?? 'login';
		redirectPath.value = normalizeAuthRedirect(options.redirect);
		entryMode.value = options.entryMode ?? null;
		isOpen.value = true;
	}

	function closeAuthModal() {
		isOpen.value = false;
		entryMode.value = null;
	}

	function clearEntryMode() {
		entryMode.value = null;
	}

	// ── OAuth providers state (ex authProvidersStore) ────────────────────
	const providers = ref<AuthProviders>(defaultProviders());
	const providersLoaded = ref(false);
	const providersLoading = ref(false);

	async function refreshProviders() {
		if (providersLoading.value) return providers.value;
		providersLoading.value = true;
		try {
			const response = await $fetch<Partial<AuthProviders>>('/api/auth/providers');
			providers.value = { ...defaultProviders(), ...response };
			providersLoaded.value = true;
		} catch {
			if (!providersLoaded.value) {
				providers.value = defaultProviders();
			}
		} finally {
			providersLoading.value = false;
		}
		return providers.value;
	}

	return {
		// Modal
		isOpen,
		selectedTab,
		redirectPath,
		entryMode,
		openAuthModal,
		closeAuthModal,
		clearEntryMode,

		// Providers
		providers,
		providersLoaded,
		providersLoading,
		refreshProviders,
	};
});
