/**
 * authProvidersStore — Pinia store per disponibilità provider OAuth (Google/Facebook/Apple).
 *
 * Sostituisce il pattern useState() di useAuthProviders in useAuth.js con uno store
 * unico ispezionabile in Vue DevTools. Il composable useAuthProviders() resta come
 * thin wrapper retro-compat per gli 8+ caller esistenti.
 */
import { defineStore } from 'pinia';
const defaultProviders = () => ({
    google: false,
    facebook: false,
    apple: false,
});
export const useAuthProvidersStore = defineStore('authProviders', () => {
    const providers = ref(defaultProviders());
    const loaded = ref(false);
    const loading = ref(false);
    async function refresh() {
        if (loading.value)
            return providers.value;
        loading.value = true;
        try {
            const response = await $fetch('/api/auth/providers');
            providers.value = {
                ...defaultProviders(),
                ...response,
            };
            loaded.value = true;
        }
        catch {
            if (!loaded.value) {
                providers.value = defaultProviders();
            }
        }
        finally {
            loading.value = false;
        }
        return providers.value;
    }
    return {
        providers,
        loaded,
        loading,
        refresh,
    };
});
