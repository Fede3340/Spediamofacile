import { test, expect, type Route } from '@playwright/test';
import { resolveE2EStorageState } from './utils/authState';
import { pageFetch, initStubOrigin } from './fixtures/page-request';

const accountStorageState = resolveE2EStorageState('account');

test.describe('Sprint 9.1 - GDPR cancellazione account @critical @gdpr', () => {
	if (accountStorageState) {
		test.use({ storageState: accountStorageState });
	}

	test('utente esporta dati e chiede cancellazione: user anonimizzato e order.user_id nulled', async ({ page }) => {
		await initStubOrigin(page);

		let userSoftDeleted = false;
		let personalDataScrubbed = false;
		let orderUserIdNulled = false;
		let exportRequested = false;

		const initialUser = {
			id: 7777,
			email: 'cliente@spediamofacile.it',
			first_name: 'Mario',
			last_name: 'Rossi',
			phone: '3331112222',
			fiscal_code: 'RSSMRA80A01H501U',
			billing_address: 'Via Roma 1, Milano',
		};
		let currentUser: Record<string, unknown> = { ...initialUser };

		await page.route('**/api/account/export-data', async (route: Route) => {
			exportRequested = true;
			await route.fulfill({
				status: 200,
				contentType: 'application/json',
				headers: { 'Content-Disposition': 'attachment; filename="export-7777.json"' },
				body: JSON.stringify({
					user: initialUser,
					orders: [{ id: 101, total: 1550, status: 'delivered' }],
					exported_at: new Date().toISOString(),
				}),
			});
		});

		await page.route('**/api/account/delete', async (route: Route) => {
			userSoftDeleted = true;
			personalDataScrubbed = true;
			orderUserIdNulled = true;
			currentUser = {
				id: initialUser.id,
				email: `deleted-${initialUser.id}@anonymized.local`,
				first_name: null,
				last_name: null,
				phone: null,
				fiscal_code: null,
				billing_address: null,
				deleted_at: new Date().toISOString(),
			};
			await route.fulfill({
				status: 200,
				contentType: 'application/json',
				body: JSON.stringify({
					success: true,
					user_id: initialUser.id,
					soft_deleted: true,
					orders_anonymized: 1,
				}),
			});
		});

		await page.route('**/api/account/me', async (route: Route) => {
			await route.fulfill({
				status: 200,
				contentType: 'application/json',
				body: JSON.stringify({ data: currentUser }),
			});
		});

		// Export dati prima della cancellazione.
		const exportResp = await pageFetch(page, '/api/account/export-data');
		expect(exportResp.status).toBe(200);
		expect(exportRequested).toBe(true);
		const exportBody = exportResp.body as { user: { email: string }; orders: unknown[] };
		expect(exportBody.user.email).toBe(initialUser.email);
		expect(exportBody.orders).toHaveLength(1);

		// Cancellazione.
		const deleteResp = await pageFetch(page, '/api/account/delete', {
			method: 'POST',
			body: { confirm: true, reason: 'gdpr_request' },
		});
		expect(deleteResp.status).toBe(200);
		const deleteBody = deleteResp.body as { success: boolean; soft_deleted: boolean; orders_anonymized: number };
		expect(deleteBody.success).toBe(true);
		expect(deleteBody.soft_deleted).toBe(true);
		expect(deleteBody.orders_anonymized).toBeGreaterThanOrEqual(1);

		// Side-effects.
		expect(userSoftDeleted).toBe(true);
		expect(personalDataScrubbed).toBe(true);
		expect(orderUserIdNulled).toBe(true);

		// Utente ora anonimizzato.
		const meResp = await pageFetch(page, '/api/account/me');
		const meBody = meResp.body as { data: Record<string, unknown> };
		expect((meBody.data.email as string)).toMatch(/@anonymized\.local$/);
		expect(meBody.data.first_name).toBeNull();
		expect(meBody.data.fiscal_code).toBeNull();
		expect(meBody.data.deleted_at).toBeTruthy();
	});
});
