import { mkdirSync } from 'node:fs';
import { test, expect, type Locator, type Page } from '@playwright/test';
import { authOutputDir, authSetupProfiles } from './utils/authState';

mkdirSync(authOutputDir, { recursive: true });
test.describe.configure({ mode: 'serial' });

type AuthSetupProfile = (typeof authSetupProfiles)[number];
type AuthTarget = {
	path: string;
	verify: (page: Page) => Promise<void>;
};

const authSetupTargets: Record<AuthSetupProfile['name'], AuthTarget> = {
	cliente: {
		path: '/account/profilo',
		verify: async (page) => {
			await expect.poll(() => new URL(page.url()).pathname, { timeout: 30000 }).toBe('/account/profilo');
			await expect(page.locator('main').getByRole('heading', { name: /il mio profilo/i })).toBeVisible({ timeout: 30000 });
			await expect(page.locator('#auth-modal-email')).toHaveCount(0);
		},
	},
	'partner-pro': {
		path: '/account/account-pro',
		verify: async (page) => {
			await expect.poll(() => new URL(page.url()).pathname, { timeout: 30000 }).toBe('/account/account-pro');
			await expect(page.locator('main').getByRole('heading', { name: 'Area Partner Pro', exact: true })).toBeVisible({ timeout: 30000 });
			await expect(page.locator('#auth-modal-email')).toHaveCount(0);
		},
	},
	admin: {
		path: '/account/amministrazione/ordini',
		verify: async (page) => {
			await expect.poll(() => new URL(page.url()).pathname, { timeout: 30000 }).toBe('/account/amministrazione/ordini');
			const main = page.locator('main');
			await expect(main.getByRole('heading', { name: 'Ordini', exact: true })).toBeVisible({ timeout: 30000 });
			await expect(main.getByText('Mostrati', { exact: false })).toBeVisible({ timeout: 30000 });
			await expect(page.locator('#auth-modal-email')).toHaveCount(0);
		},
	},
};

const submitLoginAndWaitForSession = async (page: Page, submitButton: Locator) => {
	const customLoginResponse = page.waitForResponse(
		(response) =>
			response.url().includes('/api/custom-login')
			&& response.request().method() === 'POST'
			&& response.status() === 200,
		{ timeout: 30000 },
	);

	await submitButton.click();
	await customLoginResponse;

	await expect
		.poll(
			async () => {
				const cookies = await page.context().cookies();
				return cookies.some(({ name, value }) => name === 'laravel_session' && Boolean(value));
			},
			{ timeout: 30000 },
		)
		.toBeTruthy();
};

for (const profile of authSetupProfiles) {
	test(`auth setup salva storage state ${profile.name}`, async ({ page }, testInfo) => {
		test.skip(testInfo.project.name !== 'chromium', 'Lo storage state condiviso viene generato una sola volta da chromium.');

		const target = authSetupTargets[profile.name];
		const authEmail = page.locator('#auth-modal-email');
		const authPassword = page.locator('#auth-modal-password');
		const submitButton = authPassword
			.locator('xpath=ancestor::form')
			.getByRole('button', { name: /^Accedi$/ });

		await page.goto('/?auth_modal=login');
		await expect(authEmail).toBeVisible({ timeout: 15000 });

		await authEmail.fill(profile.email);
		await authPassword.fill(profile.password);
		await submitLoginAndWaitForSession(page, submitButton);
		await page.goto(target.path, { waitUntil: 'domcontentloaded' });
		await target.verify(page);

		await page.context().storageState({ path: profile.outputFile });
	});
}
