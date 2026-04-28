/**
 * @file auth — Utility auth.
 */
// === utils/auth.js — Helper autenticazione consolidati ===
// Consolidamento di:
//   - utils/authUiState.ts     (snapshot, cookie, extract/parse)
//   - utils/authHelpers.ts     (sanitizeAuthRedirect, humanizeSocialAuthError)
//   - utils/authRouting.ts     (buildAuthOverlayLocation, normalizeAuthTab, ...)
//   - utils/authBootstrap.ts   (runAuthBootstrap, readSsrAuthState, validateSsrAuthSession)
//   - utils/postAuthSync.ts    (waitForPostAuthSync)
// Tutti gli export originali sono preservati identici.
// ─────────────────────────────────────────────────────────────────
// SEZIONE 1 — ex utils/authUiState.ts
// ─────────────────────────────────────────────────────────────────
/**
 * @typedef {Object} AuthUiSnapshot
 * @property {boolean} authenticated
 * @property {string} name
 * @property {string} surname
 * @property {string} email
 * @property {string} createdAt
 * @property {string} userType
 * @property {string | null} role
 */
/**
 * @typedef {Object} AuthUiUser
 * @property {string | null} [name]
 * @property {string | null} [surname]
 * @property {string | null} [email]
 * @property {string | null} [created_at]
 * @property {string | null} [user_type]
 * @property {string | null} [role]
 */
/**
 * @typedef {'idle' | 'pending' | 'resolved' | 'failed'} AuthBootstrapStatus
 */
export const AUTH_UI_COOKIE = 'sf_auth_ui';
export const AUTH_UI_STORAGE = 'sf_auth_ui_cache';
/**
 * Crea uno snapshot auth vuoto (utente non autenticato).
 * @returns {AuthUiSnapshot}
 */
export const createEmptySnapshot = () => ({
    authenticated: false,
    name: '',
    surname: '',
    email: '',
    createdAt: '',
    userType: '',
    role: null,
});
/**
 * Costruisce uno snapshot a partire dai campi di un utente autenticato.
 * @param {AuthUiUser} user
 * @returns {AuthUiSnapshot}
 */
export const snapshotFromUser = (user) => ({
    authenticated: true,
    name: String(user.name || ''),
    surname: String(user.surname || ''),
    email: String(user.email || ''),
    createdAt: String(user.created_at || ''),
    userType: String(user.user_type || ''),
    role: user.role || null,
});
/**
 * Parsa uno snapshot salvato (JSON string) restituendo sempre un oggetto valido.
 * @param {string | null} value
 * @returns {AuthUiSnapshot}
 */
export const parseStoredSnapshot = (value) => {
    if (!value) {
        return createEmptySnapshot();
    }
    try {
        const parsed = JSON.parse(value);
        if (!parsed.authenticated) {
            return createEmptySnapshot();
        }
        return {
            authenticated: true,
            name: String(parsed.name || ''),
            surname: String(parsed.surname || ''),
            email: String(parsed.email || ''),
            createdAt: String(parsed.createdAt || ''),
            userType: String(parsed.userType || ''),
            role: parsed.role || null,
        };
    }
    catch {
        return createEmptySnapshot();
    }
};
/**
 * Estrae il valore di un cookie dalla stringa Cookie header (ritorna null se assente).
 * @param {string} cookieHeader
 * @param {string} name
 * @returns {string | null}
 */
export const extractCookieValue = (cookieHeader, name) => {
    const match = cookieHeader.match(new RegExp(`(?:^|; )${name}=([^;]*)`));
    return match?.[1] ? decodeURIComponent(match[1]) : null;
};
/**
 * Indica se nella Cookie header è presente almeno un cookie di sessione (_session).
 * @param {string} cookieHeader
 * @returns {boolean}
 */
export const hasAuthSessionCookie = (cookieHeader) => /(?:^|;\s*)(?:[^=;]+_session)=/.test(cookieHeader);
/**
 * Legge lo snapshot auth dai cookie presenti nella Cookie header.
 * @param {string} cookieHeader
 * @returns {AuthUiSnapshot}
 */
export const readAuthUiSnapshotFromCookieHeader = (cookieHeader) => parseStoredSnapshot(extractCookieValue(cookieHeader, AUTH_UI_COOKIE));
/**
 * Type-guard: true se il valore è uno snapshot autenticato.
 * @param {unknown} value
 * @returns {boolean}
 */
export const isAuthenticatedSnapshotValue = (value) => Boolean(value
    && typeof value === 'object'
    && value.authenticated);
// ─────────────────────────────────────────────────────────────────
// SEZIONE 2 — ex utils/authHelpers.ts
// ─────────────────────────────────────────────────────────────────
/**
 * Helper condivisi per la logica di autenticazione.
 *
 * Funzioni usate da useAuthOverlay (modale auth inline) e dalle pagine di auth
 * standalone (aggiorna-password, verifica-email). Centralizzate qui per evitare drift.
 *
 * NOTA: il vecchio composable useAutenticazione.js (pagina standalone /autenticazione)
 * e' stato archiviato il 2026-04-20 insieme alle pagine legacy, che ora redirezionano
 * tutte al modale overlay via buildLegacyAuthOverlayRedirect().
 */
// ── Redirect sanitization ──
/**
 * Valida e normalizza un path di redirect post-auth.
 * Previene open redirect rifiutando path non relativi.
 * @param {string | null} [redirect]
 * @param {string} [fallback]
 * @returns {string}
 */
export const sanitizeAuthRedirect = (redirect, fallback = '/account') => {
    if (!redirect || typeof redirect !== 'string')
        return fallback;
    if (redirect.startsWith('/') && !redirect.startsWith('//')) {
        const normalized = redirect !== '/' && redirect.endsWith('/') ? redirect.slice(0, -1) : redirect;
        const blockedPrefixes = [
            '/autenticazione',
            '/login',
            '/registrazione',
            '/recupera-password',
            '/aggiorna-password',
            '/verifica-email',
        ];
        if (blockedPrefixes.some((prefix) => normalized === prefix || normalized.startsWith(`${prefix}/`))) {
            return fallback;
        }
        return normalized;
    }
    return fallback;
};
// ── Social auth error messages ──
/** @type {Record<string, string>} */
const SOCIAL_ERROR_MAP = {
    google_email_missing: "Il tuo account Google non ha un\u2019email disponibile. Usa un altro account oppure registrati con email.",
    facebook_email_missing: "Il tuo account Facebook non ha un\u2019email disponibile. Usa un altro account oppure registrati con email.",
    apple_email_missing: "Il tuo account Apple non ha un\u2019email disponibile. Usa un altro account oppure registrati con email.",
    google_unavailable: 'Accesso con Google temporaneamente non disponibile. Completiamo prima la configurazione del provider.',
    facebook_unavailable: 'Accesso con Facebook temporaneamente non disponibile. Completiamo prima la configurazione del provider.',
    apple_unavailable: 'Accesso con Apple temporaneamente non disponibile. Completiamo prima la configurazione del provider.',
};
/**
 * Traduce un codice errore social auth in un messaggio leggibile per l'utente.
 * @param {string} rawError
 * @returns {string}
 */
export const humanizeSocialAuthError = (rawError) => {
    if (SOCIAL_ERROR_MAP[rawError])
        return SOCIAL_ERROR_MAP[rawError];
    if (rawError.startsWith('facebook_'))
        return "Errore durante l\u2019accesso con Facebook. Riprova.";
    if (rawError.startsWith('google_'))
        return "Errore durante l\u2019accesso con Google. Riprova.";
    if (rawError.startsWith('apple_'))
        return "Errore durante l\u2019accesso con Apple. Riprova.";
    return "Errore durante l\u2019accesso social. Riprova.";
};
// ─────────────────────────────────────────────────────────────────
// SEZIONE 3 — ex utils/authRouting.ts
// ─────────────────────────────────────────────────────────────────
/**
 * @typedef {'login' | 'register'} AuthOverlayTab
 */
/**
 * Indica se path coincide con prefix o ne è una sotto-route.
 * @param {string} path
 * @param {string} prefix
 * @returns {boolean}
 */
const isSameOrNestedPath = (path, prefix) => path === prefix || path.startsWith(`${prefix}/`);
/**
 * Normalizza un valore di query route: se è array prende il primo elemento.
 * @template T
 * @param {T | T[] | undefined | null} value
 * @returns {T | undefined}
 */
export const getRouteQueryValue = (value) => Array.isArray(value) ? value[0] : value ?? undefined;
/**
 * Normalizza il valore del tab dell'overlay auth (default login).
 * @param {unknown} value
 * @returns {AuthOverlayTab}
 */
export const normalizeAuthTab = (value) => value === 'register' || value === 'registrati' ? 'register' : 'login';
/**
 * Normalizza il path richiesto dall'utente post-auth applicando sanitize + trim slash.
 * @param {string | null} [path]
 * @param {string} [fallback]
 * @returns {string}
 */
export const normalizeRequestedPath = (path, fallback = '/') => {
    const sanitized = sanitizeAuthRedirect(path, fallback);
    return sanitized !== '/' && sanitized.endsWith('/') ? sanitized.slice(0, -1) : sanitized;
};
/**
 * Restituisce la pagina su cui montare l'overlay auth in base al path richiesto.
 * @param {string} requestedPath
 * @returns {string}
 */
export const resolveAuthOverlayHost = (requestedPath) => {
    if (isSameOrNestedPath(requestedPath, '/checkout'))
        return '/carrello';
    if (isSameOrNestedPath(requestedPath, '/account'))
        return '/';
    return requestedPath;
};
/**
 * Costruisce una location Nuxt { path, query } per aprire il modale auth nel punto corretto.
 * @param {Object} params
 * @param {boolean} [params.forgot]
 * @param {string | null} [params.requestedPath]
 * @param {AuthOverlayTab} [params.tab]
 * @returns {{ path: string, query: Record<string, string> }}
 */
export const buildAuthOverlayLocation = ({ forgot = false, requestedPath, tab = 'login', }) => {
    const redirect = normalizeRequestedPath(requestedPath, '/');
    const path = resolveAuthOverlayHost(redirect);
    return {
        path,
        query: {
            ...(forgot ? { auth_forgot: '1' } : {}),
            auth_modal: tab,
            redirect,
        },
    };
};
/**
 * Costruisce un redirect dal vecchio flusso auth standalone al modale overlay attuale.
 * @param {{ query?: Record<string, unknown> } | null | undefined} routeLike
 * @param {Object} [options]
 * @param {AuthOverlayTab} [options.defaultTab]
 * @param {boolean} [options.allowRequestedMode]
 * @param {boolean} [options.allowRequestedTab]
 * @param {boolean} [options.forceForgot]
 * @param {string} [options.fallbackPath]
 * @returns {{ path: string, query: Record<string, string> }}
 */
export const buildLegacyAuthOverlayRedirect = (routeLike, { defaultTab = 'login', allowRequestedMode = false, allowRequestedTab = false, forceForgot = false, fallbackPath = '/', } = {}) => {
    const query = routeLike?.query || {};
    const requestedRedirect = getRouteQueryValue(query.redirect);
    const requestedMode = getRouteQueryValue(query.mode);
    const requestedTab = getRouteQueryValue(query.tab);
    const targetTab = allowRequestedMode && requestedMode === 'register'
        ? 'register'
        : allowRequestedTab
            ? normalizeAuthTab(requestedTab)
            : defaultTab;
    return buildAuthOverlayLocation({
        forgot: forceForgot || (allowRequestedMode && requestedMode === 'forgot'),
        requestedPath: normalizeRequestedPath(requestedRedirect, fallbackPath),
        tab: targetTab,
    });
};
// ─────────────────────────────────────────────────────────────────
// SEZIONE 4 — ex utils/authBootstrap.ts
// ─────────────────────────────────────────────────────────────────
/**
 * Auth bootstrap centralizzato per middleware e plugin.
 *
 * Tre middleware (admin, app-auth, guest-auth) e il plugin sanctum-bootstrap
 * condividono la stessa logica di inizializzazione auth:
 *   1. Leggono useState('auth-bootstrap-ready') e useState('auth-bootstrap-status')
 *   2. Chiamano useSanctumAuth().init()
 *   3. Gestiscono 401/419 come "risolto" (utente non autenticato ma stato noto)
 *   4. Marcano bootstrap come pronto/fallito
 *
 * Questo modulo centralizza tutti questi pattern per evitare drift e duplicazione.
 */
// ── Tipi ──
/**
 * @typedef {Object} AuthBootstrapResult
 * @property {ReturnType<typeof useState<boolean>>} bootstrapReady
 * @property {ReturnType<typeof useState<'idle' | 'pending' | 'resolved' | 'failed'>>} bootstrapStatus
 */
// ── Stato condiviso ──
/**
 * Accede allo stato globale (useState) del bootstrap auth.
 * Tutti i consumatori usano le stesse chiavi, garantendo coerenza.
 * @returns {AuthBootstrapResult}
 */
export const useAuthBootstrapState = () => {
    const bootstrapReady = useState('auth-bootstrap-ready', () => false);
    const bootstrapStatus = useState('auth-bootstrap-status', () => 'idle');
    return { bootstrapReady, bootstrapStatus };
};
// ── Client-side bootstrap ──
/**
 * Esegue il bootstrap auth client-side: chiama init() e gestisce gli errori.
 *
 * Opzioni:
 *   - force: esegue anche se il bootstrap risulta già risolto (default: false)
 *   - skipIfNoSnapshot: salta init() se non c'è una snapshot auth (guest-auth pattern)
 *
 * Restituisce il risultato dello stato bootstrap dopo l'esecuzione.
 *
 * @param {Object} [options]
 * @param {boolean} [options.force]
 * @param {boolean} [options.skipIfNoSnapshot]
 * @param {boolean} [options.hasAuthenticatedSnapshot]
 * @returns {Promise<AuthBootstrapResult>}
 */
export const runAuthBootstrap = async (options) => {
    const { bootstrapReady, bootstrapStatus } = useAuthBootstrapState();
    const { init } = useSanctumAuth();
    const alreadyResolved = bootstrapReady.value && bootstrapStatus.value === 'resolved';
    if (alreadyResolved && !options?.force) {
        return { bootstrapReady, bootstrapStatus };
    }
    // guest-auth: evita /api/user se non esiste traccia di sessione autenticata
    if (options?.skipIfNoSnapshot && !options?.hasAuthenticatedSnapshot) {
        bootstrapStatus.value = 'resolved';
        bootstrapReady.value = true;
        return { bootstrapReady, bootstrapStatus };
    }
    bootstrapReady.value = false;
    bootstrapStatus.value = 'pending';
    try {
        await init();
        bootstrapStatus.value = 'resolved';
    }
    catch (error) {
        const err = error;
        const status = Number(err?.status ?? err?.response?.status ?? 0);
        if ([401, 419].includes(status)) {
            // Non autenticato o CSRF scaduto: stato noto, non è un errore fatale
            bootstrapStatus.value = 'resolved';
        }
        else {
            bootstrapStatus.value = 'failed';
        }
    }
    finally {
        bootstrapReady.value = true;
    }
    return { bootstrapReady, bootstrapStatus };
};
// ── SSR cookie check ──
/**
 * @typedef {Object} SsrAuthCheck
 * @property {string} cookie
 * @property {AuthUiSnapshot} authSnapshot
 * @property {boolean} hasSessionCookie
 * @property {boolean} isAuthenticated
 */
/**
 * @typedef {Object} SsrAuthValidation
 * @property {boolean} checked
 * @property {boolean} authenticated
 * @property {AuthUiUser | null} user
 */
/**
 * Legge lo stato auth dai cookie nella request SSR.
 * Usato da admin.js e app-auth.js per decidere se fare redirect
 * prima che il client abbia bootstrappato.
 * @returns {SsrAuthCheck}
 */
export const readSsrAuthState = () => {
    const cookie = useRequestHeaders(['cookie'])?.cookie || '';
    const authSnapshot = readAuthUiSnapshotFromCookieHeader(cookie);
    const sessionCookie = hasAuthSessionCookie(cookie);
    return {
        cookie,
        authSnapshot,
        hasSessionCookie: sessionCookie,
        // In SSR consideriamo valida l'auth solo se coesistono
        // una sessione server e una snapshot UI autenticata.
        // Evita falsi positivi con cookie di sessione stantii.
        isAuthenticated: Boolean(sessionCookie && authSnapshot.authenticated),
    };
};
/**
 * Valida la sessione SSR chiamando /api/user con i cookie propagati.
 * Il risultato viene memoizzato in useState('auth-ssr-validation').
 * @returns {Promise<SsrAuthValidation>}
 */
export const validateSsrAuthSession = async () => {
    const validationState = useState('auth-ssr-validation', () => ({
        checked: false,
        authenticated: false,
        user: null,
    }));
    if (validationState.value.checked) {
        return validationState.value;
    }
    if (import.meta.client) {
        validationState.value = {
            checked: true,
            authenticated: false,
            user: null,
        };
        return validationState.value;
    }
    const { cookie, hasSessionCookie } = readSsrAuthState();
    if (!hasSessionCookie) {
        validationState.value = {
            checked: true,
            authenticated: false,
            user: null,
        };
        return validationState.value;
    }
    const requestFetch = useRequestFetch();
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 1800);
    try {
        const user = await requestFetch('/api/user', {
            method: 'GET',
            headers: {
                accept: 'application/json',
                cookie,
                'x-requested-with': 'XMLHttpRequest',
            },
            signal: controller.signal,
        });
        validationState.value = {
            checked: true,
            authenticated: Boolean(user),
            user: user || null,
        };
    }
    catch {
        validationState.value = {
            checked: true,
            authenticated: false,
            user: null,
        };
    }
    finally {
        clearTimeout(timeout);
    }
    return validationState.value;
};
// ─────────────────────────────────────────────────────────────────
// SEZIONE 5 — ex utils/postAuthSync.ts
// ─────────────────────────────────────────────────────────────────
const POST_AUTH_RETRY_DELAYS = [0, 180, 420, 900];
/**
 * @param {number} ms
 * @returns {Promise<void>}
 */
const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
/**
 * Riprova a chiamare refreshIdentity con backoff per far assestare i cookie post-login.
 * @param {() => Promise<unknown>} refreshIdentity
 * @returns {Promise<boolean>}
 */
export const waitForPostAuthSync = async (refreshIdentity) => {
    for (const delay of POST_AUTH_RETRY_DELAYS) {
        if (delay > 0) {
            await wait(delay);
        }
        try {
            await refreshIdentity();
            return true;
        }
        catch {
            // Dopo login/registrazione i cookie possono assestarsi con un leggero ritardo.
        }
    }
    return false;
};
