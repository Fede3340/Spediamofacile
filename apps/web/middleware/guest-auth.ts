import { isAuthenticatedSnapshotValue, runAuthBootstrap } from '~/utils/auth'

/**
 * Determina la destinazione di redirect per utenti già autenticati che accedono a pagine guest.
 * @param {Record<string, unknown>} query
 * @returns {string}
 */
const getGuestRedirectTarget = (query) => {
	const redirectValue = Array.isArray(query.redirect) ? query.redirect[0] : query.redirect
	return typeof redirectValue === 'string' && redirectValue.startsWith('/') ? redirectValue : '/account'
}

export default defineNuxtRouteMiddleware(async (to) => {
	if (import.meta.server) {
		return
	}

	const { authCookie, clearSnapshot, initialSnapshot, storedSnapshot } = useAuthUiSnapshotPersistence()
	const { isAuthenticated } = useSanctumAuth()
	const hasAuthenticatedSnapshot = Boolean(
		isAuthenticatedSnapshotValue(authCookie.value)
		|| initialSnapshot.value.authenticated
		|| storedSnapshot.value.authenticated,
	)

	await runAuthBootstrap({
		force: true,
		skipIfNoSnapshot: true,
		hasAuthenticatedSnapshot,
	})

	// Se la snapshot era stantia (401/419), pulisci il cookie
	if (hasAuthenticatedSnapshot && !isAuthenticated.value) {
		clearSnapshot()
	}

	if (isAuthenticated.value) {
		return navigateTo(getGuestRedirectTarget(to.query), { replace: true })
	}
})
