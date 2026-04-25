import { isAuthenticatedSnapshotValue } from '~/utils/auth';
import {
	canAccessShipmentFlowRoute,
	deriveShipmentFlowStateFromUserStore,
	isShipmentFlowResumeException,
	pickMostAdvancedShipmentFlowState,
	resolveShipmentFlowState,
	SHIPMENT_FLOW_ROUTES,
	trimUserStoreToFlowState,
} from '~/utils/shipment';

/** @typedef {import('~/utils/shipment').RouteLike} RouteLike */

const BLOCK_TOAST_KEY = 'shipment-flow-guard-toast';

/**
 * @typedef {Object} AuthUserWithRole
 * @property {string|null} [role]
 */

/**
 * Pianifica l'esecuzione di un toast client-side dopo il prossimo repaint.
 * @param {() => void} callback
 * @returns {void}
 */
const scheduleClientToast = (callback) => {
	if (typeof window === 'undefined') return;
	window.requestAnimationFrame(() => {
		window.setTimeout(callback, 0);
	});
};

/**
 * Normalizza il valore di una query della route mantenendo solo stringhe valide.
 * @param {unknown} value
 * @returns {string | string[] | undefined}
 */
const normalizeRouteQueryValue = (value) => {
	if (Array.isArray(value)) {
		const normalized = value.filter((item) => typeof item === 'string');
		return normalized.length ? normalized : undefined;
	}
	return typeof value === 'string' ? value : undefined;
};

/**
 * Converte un oggetto route-like in una forma normalizzata compatibile con le utility shipment.
 * @param {unknown} routeLike
 * @returns {RouteLike}
 */
const toShipmentRouteLike = (routeLike) => {
	const route = routeLike;
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

/**
 * Verifica se il path appartiene al flusso protetto "la-tua-spedizione".
 * @param {RouteLike} routeLike
 * @returns {boolean}
 */
const isShipmentProtectedPath = (routeLike) => {
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

			const serverFlowState = resolveShipmentFlowState(session.value?.data || {});
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
	const shipmentFlowStore = useShipmentFlowStore();
	const uiFeedback = useUiFeedback();
	const { openGate } = useShipmentFlowAdminGate();
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

	const hasCachedSessionData = Boolean(session.value?.data);
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

	const sessionData = session.value?.data || {};
	const remoteFlowState = resolveShipmentFlowState(sessionData);
	const flowState = pickMostAdvancedShipmentFlowState(remoteFlowState, localFlowState);
	const hasAccess = canAccessShipmentFlowRoute(targetRoute, flowState);

	const userRole = String(user.value?.role || '').trim();
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
		trimUserStoreToFlowState(shipmentFlowStore, flowState);
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
