/**
 * useSession — sessione preventivo server-side (pacchi, prezzi, indirizzi, servizi).
 * Popolata da POST /api/session/first-step e /api/session/second-step.
 * Backend: laravel-spedizionefacile-main/app/Http/Controllers/SessionController.php
 */
export const useSession = (options = {}) => {
    const enabled = options?.enabled ?? true;
    const server = options?.server ?? false;
    const key = options?.key ?? 'session';
    const lazy = options?.lazy ?? false;
    const dedupe = options?.dedupe ?? 'defer';
    if (import.meta.prerender) {
        const session = ref(null);
        const status = ref('idle');
        const refresh = async () => session.value;
        return { session, refresh, status };
    }
    if (!enabled) {
        const session = ref(null);
        const status = ref('idle');
        const refresh = async () => session.value;
        return { session, refresh, status };
    }
    const { data: session, status, refresh, } = useSanctumFetch('/api/session', {
        method: 'GET',
        key,
        // Default client-only: nel funnel pubblico evitiamo fetch SSR inutili.
        // Il middleware puo richiedere esplicitamente server:true quando deve
        // risolvere un redirect prima dell'hydration.
        server,
        lazy,
        dedupe,
    });
    return { session, refresh, status };
};
