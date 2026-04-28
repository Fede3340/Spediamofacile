/**
 * @file useShellRouteState — Composable useShellRouteState.
 */
const AUTH_PAGE_PREFIXES = ['/autenticazione', '/login', '/registrazione', '/recupera-password', '/aggiorna-password', '/verifica-email'];
const AUTH_MINIMAL_SHELL_PREFIXES = ['/recupera-password', '/aggiorna-password'];
const QUOTE_FLOW_PREFIXES = ['/preventivo', '/la-tua-spedizione', '/riepilogo', '/checkout', '/carrello'];
const STANDALONE_MARKETING_HERO_PREFIXES = ['/servizi', '/guide', '/contatti', '/chi-siamo', '/faq'];
const startsWithAny = (path, prefixes) => prefixes.some((prefix) => path.startsWith(prefix));
/**
 * Computed flags che classificano la route corrente per decidere quale shell/hero mostrare.
 */
export const useShellRouteState = () => {
    const route = useRoute();
    const isAuthPageRoute = computed(() => startsWithAny(route.path, AUTH_PAGE_PREFIXES));
    const isAuthMinimalShellRoute = computed(() => startsWithAny(route.path, AUTH_MINIMAL_SHELL_PREFIXES));
    const isAccountRoute = computed(() => route.path.startsWith('/account'));
    const isQuoteFlowRoute = computed(() => startsWithAny(route.path, QUOTE_FLOW_PREFIXES));
    const isHomepageLikeRoute = computed(() => route.path === '/' || route.path === '/preview/home-hero');
    const isPreventivoRoute = computed(() => route.path === '/preventivo' || route.path.startsWith('/preventivo/'));
    const isStandaloneMarketingHeroRoute = computed(() => startsWithAny(route.path, STANDALONE_MARKETING_HERO_PREFIXES));
    return {
        isAccountRoute,
        isAuthMinimalShellRoute,
        isAuthPageRoute,
        isHomepageLikeRoute,
        isPreventivoRoute,
        isQuoteFlowRoute,
        isStandaloneMarketingHeroRoute,
    };
};
