import { existsSync, readFileSync } from 'node:fs';
import { resolve } from 'node:path';

const firstValue = (...values: Array<string | undefined | null>) =>
	values.map((value) => value?.trim()).find((value): value is string => Boolean(value)) || '';

export const authOutputDir = resolve(process.cwd(), 'output/playwright/auth');

export const authStateFiles = {
	customer: resolve(authOutputDir, 'customer.json'),
	pro: resolve(authOutputDir, 'pro.json'),
	admin: resolve(authOutputDir, 'admin.json'),
} as const;

export const authSetupProfiles = [
	{
		name: 'cliente',
		email: 'cliente@spediamofacile.it',
		password: 'Cliente2026!',
		outputFile: authStateFiles.customer,
	},
	{
		name: 'partner-pro',
		email: 'pro@spediamofacile.it',
		password: 'Partner2026!',
		outputFile: authStateFiles.pro,
	},
	{
		name: 'admin',
		email: 'admin@spediamofacile.it',
		password: 'Admin2026!',
		outputFile: authStateFiles.admin,
	},
] as const;

const isStorageStateFresh = (filePath: string) => {
	if (!existsSync(filePath)) {
		return false;
	}

	try {
		const parsed = JSON.parse(readFileSync(filePath, 'utf8'));
		const cookies = Array.isArray(parsed?.cookies) ? parsed.cookies : [];
		const now = Math.floor(Date.now() / 1000);

		return cookies.some((cookie) => {
			if (typeof cookie?.expires !== 'number') {
				return false;
			}

			return cookie.expires === -1 || cookie.expires > now + 60;
		});
	} catch {
		return false;
	}
};

export const resolveE2EStorageState = (profile: 'account' | 'customer' | 'pro' | 'admin') => {
	const configured = firstValue(process.env.PLAYWRIGHT_STORAGE_STATE, process.env.TEST_STORAGE_STATE);
	if (configured) {
		return configured;
	}

	const candidates = profile === 'admin'
		? [authStateFiles.admin]
		: profile === 'pro'
			? [authStateFiles.pro]
			: [authStateFiles.customer];

	return candidates.find((candidate) => isStorageStateFresh(candidate)) || '';
};
