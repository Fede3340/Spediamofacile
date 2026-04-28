/**
 * authModalStore — Pinia store per overlay autenticazione (login/register/forgot).
 * Stato unico ispezionabile in Vue DevTools.
 */
import { defineStore } from 'pinia';

const normalizeAuthRedirect = (redirect) => {
    if (!redirect || typeof redirect !== 'string')
        return '/';
    return redirect.startsWith('/') ? redirect : '/';
};

export const useAuthModalStore = defineStore('authModal', () => {
    const isOpen = ref(false);
    const selectedTab = ref('login');
    const redirectPath = ref('/');
    const entryMode = ref(null);

    function openAuthModal(options = {}) {
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

    return {
        isOpen,
        selectedTab,
        redirectPath,
        entryMode,
        openAuthModal,
        closeAuthModal,
        clearEntryMode,
    };
});
