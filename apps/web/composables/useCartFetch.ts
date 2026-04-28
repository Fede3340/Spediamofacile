/**
 * @file useCartFetch — Composable useCartFetch.
 */
// Endpoint switch auth/guest allineato a CartController.php + GuestCartController.php (Laravel).
/** Fetch carrello reattivo: endpoint auth vs guest in base allo stato Sanctum. */
export const useCartFetch = () => {
    if (import.meta.prerender) {
        const endpoint = computed(() => '/api/guest-cart');
        const cart = ref({ data: [] });
        const status = ref('idle');
        const error = ref(null);
        const refresh = async () => cart.value;
        return { endpoint, cart, refresh, status, error };
    }
    const route = useRoute();
    const { authCookie } = useAuthUiSnapshotPersistence();
    const liveCartPrefixes = ['/account', '/checkout', '/la-tua-spedizione', '/riepilogo', '/carrello'];
    const shouldAttachLiveCartAuth = computed(() => {
        if (authCookie.value?.authenticated)
            return true;
        return liveCartPrefixes.some((prefix) => route.path.startsWith(prefix));
    });
    const auth = shallowRef(null);
    if (import.meta.client) {
        watchEffect(() => {
            if (!auth.value && shouldAttachLiveCartAuth.value) {
                auth.value = useSanctumAuth();
            }
        });
    }
    const isAuthenticated = computed(() => Boolean(auth.value?.isAuthenticated?.value));
    // L'endpoint cambia in base allo stato di autenticazione:
    // - Utente loggato → usa /api/cart (dati salvati nel DB, legati all'utente)
    // - Ospite → usa /api/guest-cart (dati salvati nella sessione del browser)
    const endpoint = computed(() => (isAuthenticated.value ? '/api/cart' : '/api/guest-cart'));
    const { data: cart, refresh, status, error, } = useSanctumFetch(endpoint, {
        method: 'GET',
        key: 'cart',
        // Il carrello dipende da sessione/cookie utente e non va caricato
        // durante il prerender statico del sito pubblico.
        server: false,
        lazy: true,
        dedupe: 'defer',
        watch: [endpoint],
    });
    return { endpoint, cart, refresh, status, error };
};
