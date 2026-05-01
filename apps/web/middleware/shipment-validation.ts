import { isAuthenticatedSnapshotValue } from '~/utils/auth';
import {
	canAccessShipmentFlowRoute,
	deriveShipmentFlowStateFromUserStore,
	isShipmentFlowResumeException,
	pickMostAdvancedShipmentFlowState,
	resolveShipmentFlowState,
	SHIPMENT_FLOW_ROUTES,
	trimUserStoreToFlowState,
	type RouteLike,
	type TrimmableUserStore,
} from '~/utils/shipment';

type SessionLike = { data?: Record<string, unknown> };

const BLOCK_TOAST_KEY = 'shipment-flow-guard-toast';

const scheduleClientToast = (callback: () => void) => {
	if (typeof window === 'undefined') return;
	window.requestAnimationFrame(() => {
		window.setTimeout(callback, 0);
	});
};

const normalizeRouteQueryValue = (value: unknown) => {
	if (Array.isArray(value)) {
		const normalized = value.filter((item) => typeof item === 'string');
		return normalized.length ? normalized : undefined;
	}
	return typeof value === 'string' ? value : undefined;
};

const toShipmentRouteLike = (routeLike: unknown): RouteLike => {
	const route = (routeLike && typeof routeLike === 'object' ? routeLike : {}) as {
		path?: unknown;
		fullPath?: unknown;
		hash?: unknown;
		query?: Record<string, unknown>;
	};
	const query = Object.fromEntries(
		Object.entries(route?.query || {})
			.map(([key, value]) => [key, normalizeRouteQueryValue(value)])
			.filter(([, value]) => value !== undefined),
	);

	return {
		path: typeof route?.path === 'string' ? route.path : undefined,
		fullPath: typeof route?.fullPath === 'string' ? route.fullPath : undefined,
		hash: typeof route?.hash === 'string' ? route.hash : undefined,
		query,
	};
};

const isShipmentProtectedPath = (routeLike: RouteLike) => {
	const path = String(routeLike?.path || routeLike?.fullPath || '');
	return path.startsWith('/la-tua-spedizione');
};

export default defineNuxtRouteMiddleware(async (to, from) => {
	const targetRoute = toShipmentRouteLike(to);
	const sourceRoute = toShipmentRouteLike(from);

	if (import.meta.server) {
		if (!isShipmentProtectedPath(targetRoute) || isShipmentFlowResumeException(targetRoute)) {
			return;
		}

		const cookie = useRequestHeaders(['cookie'])?.cookie || '';
		const hasSessionCookie = cookie.includes('laravel_session') || cookie.includes('XSRF-TOKEN');

		if (!hasSessionCookie) {
			// Guest senza session cookie (prima navigazione o sessione pulita):
			// il controllo di accesso al flusso avviene comunque lato client usando
			// localFlowState da shipmentFlowStore/sessionStorage. Non rimbalziamo in SSR,
			// altrimenti un guest che ricarica /la-tua-spedizione/X finisce sempre
			// su step 1 anche quando ha i dati in sessionStorage.
			return;
		}

		try {
			const { session, refresh } = useSession({
				server: true,
				key: `session-flow:${to.fullPath}`,
			});
			await refresh().catch(() => session.value);

			const serverSession = session.value as SessionLike | null | undefined;
			const serverFlowState = resolveShipmentFlowState(serverSession?.data || {});
			if (canAccessShipmentFlowRoute(targetRoute, serverFlowState)) {
				return;
			}

			const redirectTarget = serverFlowState.last_valid_route || SHIPMENT_FLOW_ROUTES.packages;
			if (to.fullPath !== redirectTarget) {
				return navigateTo(redirectTarget, { replace: true });
			}
		} catch {
			// Degradiamo al controllo client esistente: meglio un fallback graduale
			// che bloccare deep-link validi quando il fetch SSR non e disponibile.
		}

		return;
	}

	const nuxtApp = useNuxtApp();
	const { init, user } = useSanctumAuth();
	const { authCookie } = useAuthUiSnapshotPersistence();
	const { session, refresh } = useSession();
	const shipmentFlowStore = useShipmentStore();
	const uiFeedback = useUiFeedback();
	const { openAdminGate: openGate } = useShipmentStore();
	const quoteTransitionLock = useState('shipment-flow-quote-transition-lock', () => false);

	// Allineiamo subito lo stato locale del funnel prima di derivare localFlowState.
	// Senza questa hydration anticipata il client puo' vedere uno store vuoto al primo
	// passaggio e degradare verso Colli anche quando esiste gia' un draft valido in sessionStorage.
	if (!shipmentFlowStore.hasPersistedHydration) {
		shipmentFlowStore.hydrateFromSession();
	}

	const localFlowState = deriveShipmentFlowStateFromUserStore(shipmentFlowStore);

	if (isShipmentFlowResumeException(targetRoute)) {
		return;
	}

	const currentSession = session.value as SessionLike | null | undefined;
	const hasCachedSessionData = Boolean(currentSession?.data);
	const isInternalShipmentNavigation = Boolean(from?.path)
		&& isShipmentProtectedPath(sourceRoute)
		&& isShipmentProtectedPath(targetRoute);
	const isShipmentQueryStepHop = Boolean(from?.fullPath)
		&& from.path === to.path
		&& to.path.startsWith('/la-tua-spedizione');
	const isQuoteAdvanceIntoServices = Boolean(from?.path)
		&& (from.path === '/' || from.path.startsWith('/preventivo'))
		&& to.path.startsWith('/la-tua-spedizione');
	const shouldBootstrapAuth = isAuthenticatedSnapshotValue(authCookie.value)
		&& to.path.startsWith('/carrello');
	const localFastPathAllowed = canAccessShipmentFlowRoute(targetRoute, localFlowState)
		&& (isInternalShipmentNavigation || isShipmentQueryStepHop || isQuoteAdvanceIntoServices || quoteTransitionLock.value);

	if (localFastPathAllowed) {
		return;
	}

	if (isQuoteAdvanceIntoServices && localFlowState.quote_ready) {
		return;
	}

	if (!(hasCachedSessionData && (isInternalShipmentNavigation || isShipmentQueryStepHop))) {
		await refresh().catch(() => session.value);

		if (shouldBootstrapAuth) {
			try {
				await init();
			} catch {
				// Se lo snapshot auth era stantio lasciamo proseguire il funnel normale.
			}
		}
	}

	const refreshedSession = session.value as SessionLike | null | undefined;
	const sessionData = refreshedSession?.data || {};
	const remoteFlowState = resolveShipmentFlowState(sessionData);
	const flowState = pickMostAdvancedShipmentFlowState(remoteFlowState, localFlowState);
	const hasAccess = canAccessShipmentFlowRoute(targetRoute, flowState);

	const authUser = user.value as { role?: string } | null;
	const userRole = String(authUser?.role || '').trim();
	if (userRole === 'Admin') {
		if (!hasAccess && !isShipmentFlowResumeException(targetRoute)) {
			openGate({
				targetPath: to.fullPath,
				lastValidRoute: flowState.last_valid_route || SHIPMENT_FLOW_ROUTES.packages,
				reason: 'admin-out-of-flow',
			});
		}
		return;
	}

	if (hasAccess) {
		return;
	}

	const remoteRank = Number(remoteFlowState.summary_ready) * 4
		|| Number(remoteFlowState.addresses_ready) * 3
		|| Number(remoteFlowState.services_ready) * 2
		|| Number(remoteFlowState.quote_ready);
	const localRank = Number(localFlowState.summary_ready) * 4
		|| Number(localFlowState.addresses_ready) * 3
		|| Number(localFlowState.services_ready) * 2
		|| Number(localFlowState.quote_ready);

	if (remoteRank >= localRank) {
		trimUserStoreToFlowState(shipmentFlowStore as TrimmableUserStore, flowState);
	}

	const redirectTarget = flowState.last_valid_route || SHIPMENT_FLOW_ROUTES.packages;
	const toastLock = useState(BLOCK_TOAST_KEY, () => false);
	const shouldShowRedirectToast = !nuxtApp.isHydrating
		&& Boolean(from?.path)
		&& to.fullPath !== redirectTarget;
	if (shouldShowRedirectToast && !toastLock.value) {
		toastLock.value = true;
		scheduleClientToast(() => {
			uiFeedback.info('Ultimo step valido', 'Ti abbiamo riportato all\'ultimo step valido del tuo flusso.', { timeout: 3200 });
		});
		setTimeout(() => {
			toastLock.value = false;
		}, 3500);
	}

	if (to.fullPath !== redirectTarget) {
		return navigateTo(redirectTarget, { replace: true });
	}
});
