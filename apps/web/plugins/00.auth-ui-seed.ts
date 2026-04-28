import {
	AUTH_UI_COOKIE,
	AUTH_UI_STORAGE,
	createEmptySnapshot,
	parseStoredSnapshot,
} from '~/utils/auth'

/**
 * @typedef {import('~/types').AuthUiSnapshot} AuthUiSnapshot
 */

export default defineNuxtPlugin(() => {
	const authCookie = useCookie(AUTH_UI_COOKIE, {
		sameSite: 'lax',
		path: '/',
		// HTTPS-only in produzione (in dev http://localhost va in chiaro per non rompere il login).
		secure: !import.meta.dev,
	})
	const initialSnapshot = useState('auth-ui-initial-snapshot', createEmptySnapshot)
	const storedSnapshot = useState('auth-ui-stored-snapshot', createEmptySnapshot)

	const cookieSnapshot = typeof authCookie.value === 'string'
		? parseStoredSnapshot(authCookie.value)
		: (authCookie.value || createEmptySnapshot())

	if (cookieSnapshot.authenticated) {
		initialSnapshot.value = cookieSnapshot
	}

	if (import.meta.client) {
		const rawStoredSnapshot = window.localStorage.getItem(AUTH_UI_STORAGE)
		let parsedStoredSnapshot = null

		if (rawStoredSnapshot) {
			try {
				parsedStoredSnapshot = JSON.parse(rawStoredSnapshot)
			} catch {
				window.localStorage.removeItem(AUTH_UI_STORAGE)
			}
		}

		if (initialSnapshot.value.authenticated) {
			if (!cookieSnapshot.authenticated) {
				authCookie.value = initialSnapshot.value
			}
			storedSnapshot.value = initialSnapshot.value
			window.localStorage.setItem(AUTH_UI_STORAGE, JSON.stringify(initialSnapshot.value))
		} else if (parsedStoredSnapshot?.authenticated) {
			storedSnapshot.value = parsedStoredSnapshot
		} else {
			storedSnapshot.value = createEmptySnapshot()
			window.localStorage.removeItem(AUTH_UI_STORAGE)
		}
	}
})
