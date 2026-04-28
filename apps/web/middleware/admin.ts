import {
	buildAuthOverlayLocation,
	isAuthenticatedSnapshotValue,
	readSsrAuthState,
	runAuthBootstrap,
	validateSsrAuthSession,
	waitForPostAuthSync,
} from '~/utils/auth';

/**
 * @typedef {Object} AuthUserWithRole
 * @property {string|null} [role]
 */

/**
 * Costruisce l'URL di redirect al login admin.
 * @param {string} fullPath
 * @returns {ReturnType<typeof buildAuthOverlayLocation>}
 */
const buildAdminLoginRedirect = (fullPath) =>
	buildAuthOverlayLocation({ requestedPath: fullPath, tab: 'login' });

export default defineNuxtRouteMiddleware(async (to) => {
	// SSR: controlla cookie di sessione — se manca, redirect immediato
	if (import.meta.server) {
		const { authSnapshot } = readSsrAuthState();
		const validation = await validateSsrAuthSession();

		if (!validation.authenticated) {
			return navigateTo(buildAdminLoginRedirect(to.fullPath), { replace: true });
		}

		const resolvedRole = String(validation.user?.role || authSnapshot.role || '').trim();
		if (resolvedRole !== 'Admin') {
			return navigateTo('/account', { replace: true });
		}

		return;
	}

	const { user, refreshIdentity } = useSanctumAuth();
	const { authCookie, initialSnapshot, storedSnapshot, clearSnapshot } = useAuthUiSnapshotPersistence();
	const hasUiSnapshot = Boolean(
		isAuthenticatedSnapshotValue(authCookie.value)
		|| initialSnapshot.value.authenticated
		|| storedSnapshot.value.authenticated,
	);
	const { bootstrapStatus } = await runAuthBootstrap({ force: hasUiSnapshot });

	if (!user.value && hasUiSnapshot) {
		await waitForPostAuthSync(refreshIdentity);
	}

	// Se bootstrap fallisce o utente non autenticato → redirect al login
	if (bootstrapStatus.value === 'failed' || !user.value) {
		clearSnapshot();
		return navigateTo(buildAdminLoginRedirect(to.fullPath), { replace: true });
	}

	const role = String(user.value?.role || '').trim();
	if (role !== 'Admin') {
		return navigateTo('/account');
	}
});
