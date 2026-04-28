/**
 * Composable che imposta automaticamente il link rel="canonical"
 * basato sulla route corrente. Usa il dominio di produzione.
 *
 * OPZIONI:
 *  - path: override del percorso (utile per pagine dinamiche con slug risolto lato API)
 *  - keepParams: lista di query params SEO-relevant da preservare (default: [])
 *    Esempio: keepParams: ['page'] per paginazione elenchi articoli
 *  - excludeParams: lista esplicita di param da rimuovere (default UTM/tracking)
 *    Usato solo se keepParams non e' passato — quando keepParams e' definito,
 *    tutti gli altri param vengono strippati per default.
 *
 * CONVENZIONI:
 *  - URL sempre assoluto (https://spediamofacile.it/...)
 *  - Nessun trailing slash (tranne root '/')
 *  - Canonical stesso URL anche per og:url (gestito a livello di useSeoMeta)
 */
const DEFAULT_EXCLUDE = [
    'ref',
    'utm_source',
    'utm_medium',
    'utm_campaign',
    'utm_term',
    'utm_content',
    'fbclid',
    'gclid',
    'mc_cid',
    'mc_eid',
];
/**
 * Imposta rel="canonical" con URL assoluto coerente (no trailing slash, query canonicalizzata).
 */
export const useCanonical = (options = {}) => {
    const route = useRoute();
    const runtimeConfig = useRuntimeConfig();
    const baseUrl = runtimeConfig.public?.siteUrl || 'https://spediamofacile.it';
    const resolvePath = () => {
        const p = options.path;
        if (p == null)
            return route.path;
        if (typeof p === 'string')
            return p;
        if (typeof p === 'function')
            return p();
        if (typeof p.value === 'string')
            return p.value;
        return route.path;
    };
    const canonicalUrl = computed(() => {
        const rawPath = resolvePath();
        const path = rawPath === '/' ? '' : rawPath;
        const cleanPath = path.endsWith('/') && path !== '/' ? path.slice(0, -1) : path;
        let query = '';
        const hasKeep = Array.isArray(options.keepParams) && options.keepParams.length > 0;
        const hasExclude = Array.isArray(options.excludeParams) && options.excludeParams.length > 0;
        if (hasKeep || hasExclude) {
            const params = new URLSearchParams();
            const excludeList = hasExclude ? options.excludeParams : DEFAULT_EXCLUDE;
            for (const [key, raw] of Object.entries(route.query)) {
                if (hasKeep && !options.keepParams.includes(key))
                    continue;
                if (!hasKeep && excludeList.includes(key))
                    continue;
                if (raw == null)
                    continue;
                const value = Array.isArray(raw) ? raw[0] : raw;
                if (typeof value === 'string' && value !== '') {
                    params.set(key, value);
                }
            }
            const qs = params.toString();
            if (qs)
                query = `?${qs}`;
        }
        return `${baseUrl}${cleanPath}${query}`;
    });
    useHead({
        link: [
            { rel: 'canonical', href: canonicalUrl },
        ],
    });
    return { canonicalUrl };
};
