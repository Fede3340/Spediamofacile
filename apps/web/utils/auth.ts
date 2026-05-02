import type { Ref } from 'vue';

export type AuthUiSnapshot = {
    authenticated: boolean;
    name: string;
    surname: string;
    email: string;
    createdAt: string;
    userType: string;
    role: string | null;
};

type AuthUiUser = {
    name?: string | null;
    surname?: string | null;
    email?: string | null;
    created_at?: string | null;
    user_type?: string | null;
    role?: string | null;
};

type AuthBootstrapStatus = 'idle' | 'pending' | 'resolved' | 'failed';
type AuthOverlayTab = 'login' | 'register';
type AuthBootstrapResult = {
    bootstrapReady: Ref<boolean>;
    bootstrapStatus: Ref<AuthBootstrapStatus>;
};
type AuthOverlayLocation = { path: string; query: Record<string, string> };
type LegacyAuthRouteLike = { query?: Record<string, unknown> } | null | undefined;
type LegacyAuthRedirectOptions = {
    defaultTab?: AuthOverlayTab;
    allowRequestedMode?: boolean;
    allowRequestedTab?: boolean;
    forceForgot?: boolean;
    fallbackPath?: string;
};
type AuthBootstrapOptions = {
    force?: boolean;
    skipIfNoSnapshot?: boolean;
    hasAuthenticatedSnapshot?: boolean;
};
type AuthErrorLike = { status?: number | string; response?: { status?: number | string } };
type SsrAuthCheck = {
    cookie: string;
    authSnapshot: AuthUiSnapshot;
    hasSessionCookie: boolean;
    isAuthenticated: boolean;
};
type SsrAuthValidation = { checked: boolean; authenticated: boolean; user: AuthUiUser | null };

const isRecord = (value: unknown): value is Record<string, unknown> => typeof value === 'object' && value !== null;

// ── Auth UI snapshot / cookie ──
export const AUTH_UI_COOKIE = 'sf_auth_ui';
export const AUTH_UI_STORAGE = 'sf_auth_ui_cache';

export const createEmptySnapshot = (): AuthUiSnapshot => ({
    authenticated: false, name: '', surname: '', email: '', createdAt: '', userType: '', role: null,
});

export const snapshotFromUser = (user: AuthUiUser): AuthUiSnapshot => ({
    authenticated: true,
    name: String(user.name || ''),
    surname: String(user.surname || ''),
    email: String(user.email || ''),
    createdAt: String(user.created_at || ''),
    userType: String(user.user_type || ''),
    role: user.role || null,
});

export const parseStoredSnapshot = (value: string | null | undefined): AuthUiSnapshot => {
    if (!value) return createEmptySnapshot();
    try {
        const parsed: unknown = JSON.parse(value);
        if (!isRecord(parsed) || !parsed.authenticated) return createEmptySnapshot();
        return {
            authenticated: true,
            name: String(parsed.name || ''),
            surname: String(parsed.surname || ''),
            email: String(parsed.email || ''),
            createdAt: String(parsed.createdAt || ''),
            userType: String(parsed.userType || ''),
            role: typeof parsed.role === 'string' ? parsed.role : null,
        };
    } catch {
        return createEmptySnapshot();
    }
};

export const extractCookieValue = (cookieHeader: string, name: string): string | null => {
    const match = cookieHeader.match(new RegExp(`(?:^|; )${name}=([^;]*)`));
    return match?.[1] ? decodeURIComponent(match[1]) : null;
};

export const hasAuthSessionCookie = (cookieHeader: string): boolean => cookieHeader.split(';').some((cookie) => {
    const name = String(cookie || '').trim().split('=')[0] || '';
    return name === 'XSRF-TOKEN' || name.endsWith('_session');
});

export const readAuthUiSnapshotFromCookieHeader = (cookieHeader: string): AuthUiSnapshot =>
    parseStoredSnapshot(extractCookieValue(cookieHeader, AUTH_UI_COOKIE));

export const isAuthenticatedSnapshotValue = (value: unknown): value is AuthUiSnapshot =>
    Boolean(isRecord(value) && value.authenticated);

// ── Redirect sanitization + social auth errors ──
// Pagine standalone /autenticazione legacy redirezionano al modale via buildLegacyAuthOverlayRedirect().
const BLOCKED_REDIRECT_PREFIXES = ['/autenticazione', '/login', '/registrazione', '/recupera-password', '/aggiorna-password', '/verifica-email'];

export const sanitizeAuthRedirect = (redirect?: string | null, fallback = '/account'): string => {
    if (!redirect || typeof redirect !== 'string') return fallback;
    if (!redirect.startsWith('/') || redirect.startsWith('//')) return fallback;
    const normalized = redirect !== '/' && redirect.endsWith('/') ? redirect.slice(0, -1) : redirect;
    if (BLOCKED_REDIRECT_PREFIXES.some((p) => normalized === p || normalized.startsWith(`${p}/`))) return fallback;
    return normalized;
};

const SOCIAL_ERROR_MAP: Record<string, string> = {
    google_email_missing: "Il tuo account Google non ha un’email disponibile. Usa un altro account oppure registrati con email.",
    facebook_email_missing: "Il tuo account Facebook non ha un’email disponibile. Usa un altro account oppure registrati con email.",
    apple_email_missing: "Il tuo account Apple non ha un’email disponibile. Usa un altro account oppure registrati con email.",
    google_unavailable: 'Accesso con Google temporaneamente non disponibile. Completiamo prima la configurazione del provider.',
    facebook_unavailable: 'Accesso con Facebook temporaneamente non disponibile. Completiamo prima la configurazione del provider.',
    apple_unavailable: 'Accesso con Apple temporaneamente non disponibile. Completiamo prima la configurazione del provider.',
};

export const humanizeSocialAuthError = (rawError: string): string => {
    if (SOCIAL_ERROR_MAP[rawError]) return SOCIAL_ERROR_MAP[rawError];
    if (rawError.startsWith('facebook_')) return "Errore durante l’accesso con Facebook. Riprova.";
    if (rawError.startsWith('google_')) return "Errore durante l’accesso con Google. Riprova.";
    if (rawError.startsWith('apple_')) return "Errore durante l’accesso con Apple. Riprova.";
    return "Errore durante l’accesso social. Riprova.";
};

// ── Auth routing (overlay locations) ──
const isSameOrNestedPath = (path: string, prefix: string): boolean => path === prefix || path.startsWith(`${prefix}/`);

export const getRouteQueryValue = <T>(value: T | T[] | undefined | null): T | undefined =>
    Array.isArray(value) ? value[0] : value ?? undefined;

export const normalizeAuthTab = (value: unknown): AuthOverlayTab =>
    value === 'register' || value === 'registrati' ? 'register' : 'login';

export const normalizeRequestedPath = (path?: string | null, fallback = '/'): string => {
    const sanitized = sanitizeAuthRedirect(path, fallback);
    return sanitized !== '/' && sanitized.endsWith('/') ? sanitized.slice(0, -1) : sanitized;
};

export const resolveAuthOverlayHost = (requestedPath: string): string => {
    if (isSameOrNestedPath(requestedPath, '/checkout')) return '/carrello';
    if (isSameOrNestedPath(requestedPath, '/account')) return '/';
    return requestedPath;
};

export const buildAuthOverlayLocation = ({ forgot = false, requestedPath, tab = 'login' }: {
    forgot?: boolean;
    requestedPath?: string | null;
    tab?: AuthOverlayTab;
}): AuthOverlayLocation => {
    const redirect = normalizeRequestedPath(requestedPath, '/');
    return {
        path: resolveAuthOverlayHost(redirect),
        query: { ...(forgot ? { auth_forgot: '1' } : {}), auth_modal: tab, redirect },
    };
};

export const buildLegacyAuthOverlayRedirect = (
    routeLike: LegacyAuthRouteLike,
    { defaultTab = 'login', allowRequestedMode = false, allowRequestedTab = false, forceForgot = false, fallbackPath = '/' }: LegacyAuthRedirectOptions = {},
): AuthOverlayLocation => {
    const query = routeLike?.query || {};
    const requestedRedirect = getRouteQueryValue(query.redirect);
    const requestedMode = getRouteQueryValue(query.mode);
    const requestedTab = getRouteQueryValue(query.tab);
    const targetTab = allowRequestedMode && requestedMode === 'register' ? 'register'
        : allowRequestedTab ? normalizeAuthTab(requestedTab) : defaultTab;
    return buildAuthOverlayLocation({
        forgot: forceForgot || (allowRequestedMode && requestedMode === 'forgot'),
        requestedPath: normalizeRequestedPath(typeof requestedRedirect === 'string' ? requestedRedirect : null, fallbackPath),
        tab: targetTab,
    });
};

// ── Auth bootstrap (middleware + plugin) ──
// Stato globale condiviso da admin.js, app-auth.js, guest-auth.js, sanctum-bootstrap plugin.
export const useAuthBootstrapState = (): AuthBootstrapResult => ({
    bootstrapReady: useState<boolean>('auth-bootstrap-ready', () => false),
    bootstrapStatus: useState<AuthBootstrapStatus>('auth-bootstrap-status', () => 'idle'),
});

export const runAuthBootstrap = async (options: AuthBootstrapOptions = {}): Promise<AuthBootstrapResult> => {
    const state = useAuthBootstrapState();
    const { bootstrapReady, bootstrapStatus } = state;
    const { init } = useSanctumAuth();
    if (bootstrapReady.value && bootstrapStatus.value === 'resolved' && !options.force) return state;
    // guest-auth: evita /api/user se non esiste traccia di sessione autenticata
    if (options.skipIfNoSnapshot && !options.hasAuthenticatedSnapshot) {
        bootstrapStatus.value = 'resolved';
        bootstrapReady.value = true;
        return state;
    }
    bootstrapReady.value = false;
    bootstrapStatus.value = 'pending';
    try {
        await init();
        bootstrapStatus.value = 'resolved';
    } catch (error) {
        const err = error as AuthErrorLike;
        const status = Number(err?.status ?? err?.response?.status ?? 0);
        // 401/419: stato noto (non autenticato o CSRF scaduto), non fatale
        bootstrapStatus.value = [401, 419].includes(status) ? 'resolved' : 'failed';
    } finally {
        bootstrapReady.value = true;
    }
    return state;
};

export const readSsrAuthState = (): SsrAuthCheck => {
    const cookie = useRequestHeaders(['cookie'])?.cookie || '';
    const authSnapshot = readAuthUiSnapshotFromCookieHeader(cookie);
    const sessionCookie = hasAuthSessionCookie(cookie);
    // SSR: auth valida solo se coesistono sessione server + snapshot UI (evita falsi positivi).
    return { cookie, authSnapshot, hasSessionCookie: sessionCookie, isAuthenticated: Boolean(sessionCookie && authSnapshot.authenticated) };
};

export const validateSsrAuthSession = async (): Promise<SsrAuthValidation> => {
    const validationState = useState<SsrAuthValidation>('auth-ssr-validation', () => ({ checked: false, authenticated: false, user: null }));
    if (validationState.value.checked) return validationState.value;
    if (import.meta.client) {
        validationState.value = { checked: true, authenticated: false, user: null };
        return validationState.value;
    }
    const { cookie, hasSessionCookie } = readSsrAuthState();
    if (!hasSessionCookie) {
        validationState.value = { checked: true, authenticated: false, user: null };
        return validationState.value;
    }
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 1800);
    try {
        const user = await useRequestFetch()<AuthUiUser>('/api/user', {
            method: 'GET',
            headers: { accept: 'application/json', cookie, 'x-requested-with': 'XMLHttpRequest' },
            signal: controller.signal,
        });
        validationState.value = { checked: true, authenticated: Boolean(user), user: user || null };
    } catch {
        validationState.value = { checked: true, authenticated: false, user: null };
    } finally {
        clearTimeout(timeout);
    }
    return validationState.value;
};

// ── Post-auth sync (cookie settling backoff) ──
const POST_AUTH_RETRY_DELAYS = [0, 180, 420, 900];

export const waitForPostAuthSync = async (refreshIdentity: () => Promise<unknown>): Promise<boolean> => {
    for (const delay of POST_AUTH_RETRY_DELAYS) {
        if (delay > 0) await new Promise<void>((resolve) => setTimeout(resolve, delay));
        try {
            await refreshIdentity();
            return true;
        } catch {
            // Cookie post-login possono assestarsi con un leggero ritardo.
        }
    }
    return false;
};
