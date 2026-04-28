import { describe, it, expect } from 'vitest';
import {
	AUTH_UI_COOKIE,
	createEmptySnapshot,
	extractCookieValue,
	hasAuthSessionCookie,
	readAuthUiSnapshotFromCookieHeader,
} from '~/utils/auth';

describe('authUiState cookie helpers', () => {
	it('legge lo snapshot auth dal cookie header', () => {
		const snapshot = {
			authenticated: true,
			name: 'Admin',
			surname: 'SpediamoFacile',
			email: 'admin@spediamofacile.it',
			createdAt: '2026-04-03T19:00:00Z',
			userType: 'privato',
			role: 'Admin',
		};
		const cookieHeader = `foo=bar; ${AUTH_UI_COOKIE}=${encodeURIComponent(JSON.stringify(snapshot))}; theme=light`;

		expect(extractCookieValue(cookieHeader, AUTH_UI_COOKIE)).toBe(JSON.stringify(snapshot));
		expect(readAuthUiSnapshotFromCookieHeader(cookieHeader)).toEqual(snapshot);
	});

	it('riconosce i cookie di sessione Laravel', () => {
		expect(hasAuthSessionCookie('foo=bar; laravel_session=abc123')).toBe(true);
		expect(hasAuthSessionCookie('foo=bar; XSRF-TOKEN=xyz')).toBe(true);
		expect(hasAuthSessionCookie('foo=bar; theme=light')).toBe(false);
	});

	it('degrada a snapshot vuoto se il cookie auth manca o e corrotto', () => {
		expect(readAuthUiSnapshotFromCookieHeader('foo=bar')).toEqual(createEmptySnapshot());
		expect(readAuthUiSnapshotFromCookieHeader(`${AUTH_UI_COOKIE}=not-json`)).toEqual(createEmptySnapshot());
	});
});
