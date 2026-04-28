import {
	buildAuthOverlayLocation,
	isAuthenticatedSnapshotValue,
	readSsrAuthState,
	runAuthBootstrap,
	validateSsrAuthSession,
	waitForPostAuthSync,
} from '~/utils/auth';

/**
 * Normalizza un path rimuovendo eventuale slash finale (eccetto la root).
 * @param {string} path
 * @returns {string}
 */
const normalizeRequestedPath = (path) => (path !== '/' && path.endsWith('/') ? path.slice(0, -1) : path);

/**
 * Costruisce l'URL di redirect al modale di login.
 * @param {string} requestedPath
 * @returns {ReturnType<typeof buildAuthOverlayLocation>}
 */
const buildAuthRedirectTarget = (requestedPath) =>
	buildAuthOverlayLocation({ requestedPath, tab: 'login' });

export default defineNuxtRouteMiddleware(async (to) => {
	if (import.meta.server) {
		const { hasSessionCookie } = readSsrAuthState();
		const requestedPath = normalizeRequestedPath(to.fullPath);

		if (!hasSessionCookie) {
			return navigateTo(buildAuthRedirectTarget(requestedPath), { replace: true });
		}

		const { authenticated } = await validateSsrAuthSession();
		if (!authenticated) {
			return navigateTo(buildAuthRedirectTarget(requestedPath), { replace: true });
		}

		return;
	}

	const { isAuthenticated, refreshIdentity } = useSanctumAuth();
	const { authCookie, clearSnapshot, initialSnapshot, storedSnapshot } = useAuthUiSnapshotPersistence();

	if (isAuthenticated.value) {
		return;
	}

	// Lo snapshot UI è utile solo per forzare un bootstrap severo,
	// non per concedere l'accesso a una route protetta.
	const hasUiSnapshot = Boolean(
		isAuthenticatedSnapshotValue(authCookie.value)
		|| initialSnapshot.value.authenticated
		|| storedSnapshot.value.authenticated,
	);

	try {
		await Promise.race([
			runAuthBootstrap({ force: hasUiSnapshot }),
			new Promise((_, reject) => setTimeout(() => reject('timeout'), 5000)),
		]);
	} catch {
		// Se il bootstrap fallisce o va in timeout controlliamo lo stato reale subito dopo.
	}

	if (isAuthenticated.value) {
		return;
	}

	if (hasUiSnapshot) {
		const synced = await waitForPostAuthSync(refreshIdentity);
		if (synced && isAuthenticated.value) {
			return;
		}
	}

	if (hasUiSnapshot) {
		clearSnapshot();
	}

	const requestedPath = normalizeRequestedPath(to.fullPath);
	return navigateTo(buildAuthRedirectTarget(requestedPath), { replace: true });
});
