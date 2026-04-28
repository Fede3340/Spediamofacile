import { test, expect, type Route } from '@playwright/test';
import { installCouponMocks } from './fixtures/api-mocks';
import { pageFetch, initStubOrigin } from './fixtures/page-request';

test.describe('Sprint 9.1 - Coupon abuse prevention @critical', () => {
	test('coupon scaduto viene rifiutato', async ({ page }) => {
		await initStubOrigin(page);
		const state = await installCouponMocks(page, [
			{ code: 'EXPIRED2024', status: 'expired' },
			{ code: 'ACTIVE10', status: 'valid', discount: 10 },
		]);

		const response = await pageFetch(page, '/api/coupons/validate', {
			method: 'POST',
			body: { code: 'EXPIRED2024', cart_total: 50 },
		});
		expect(response.status).toBe(422);
		const body = response.body as { error: string; message: string };
		expect(body.error).toBe('expired');
		expect(body.message).toMatch(/scaduto/i);
		expect(state.rejected).toContainEqual({ code: 'expired2024', reason: 'expired' });
		expect(state.redemptions).toBe(0);
	});

	test('coupon single-use gia utilizzato dallo stesso user viene rifiutato al secondo uso', async ({ page }) => {
		await initStubOrigin(page);
		let callCount = 0;
		await page.route('**/api/coupons/validate**', async (route: Route) => {
			callCount += 1;
			if (callCount === 1) {
				await route.fulfill({
					status: 200,
					contentType: 'application/json',
					body: JSON.stringify({ valid: true, discount: 15, currency: 'EUR' }),
				});
			} else {
				await route.fulfill({
					status: 422,
					contentType: 'application/json',
					body: JSON.stringify({ error: 'already_used', message: 'Hai gia utilizzato questo coupon.' }),
				});
			}
		});

		const first = await pageFetch(page, '/api/coupons/validate', {
			method: 'POST',
			body: { code: 'SINGLEUSE' },
		});
		expect(first.status).toBe(200);
		expect((first.body as { valid: boolean }).valid).toBe(true);

		const second = await pageFetch(page, '/api/coupons/validate', {
			method: 'POST',
			body: { code: 'SINGLEUSE' },
		});
		expect(second.status).toBe(422);
		expect((second.body as { error: string }).error).toBe('already_used');
	});

	test('coupon con max_uses raggiunto viene rifiutato', async ({ page }) => {
		await initStubOrigin(page);
		await installCouponMocks(page, [{ code: 'MAXED', status: 'max_uses' }]);

		const response = await pageFetch(page, '/api/coupons/validate', {
			method: 'POST',
			body: { code: 'MAXED', cart_total: 100 },
		});
		expect(response.status).toBe(422);
		const body = response.body as { error: string; message: string };
		expect(body.error).toBe('max_uses');
		expect(body.message).toMatch(/utilizzi|raggiunto/i);
	});

	test('race condition: due checkout simultanei con coupon single-use — uno vince, uno rifiutato', async ({ page }) => {
		await initStubOrigin(page);
		let redeemed = false;
		await page.route('**/api/coupons/redeem', async (route: Route) => {
			if (redeemed) {
				await route.fulfill({
					status: 409,
					contentType: 'application/json',
					body: JSON.stringify({ error: 'race_conflict', message: 'Coupon appena riscattato da altra sessione.' }),
				});
			} else {
				redeemed = true;
				await new Promise((r) => setTimeout(r, 50));
				await route.fulfill({
					status: 200,
					contentType: 'application/json',
					body: JSON.stringify({ success: true, locked_by: 'session_A' }),
				});
			}
		});

		const [respA, respB] = await Promise.all([
			pageFetch(page, '/api/coupons/redeem', { method: 'POST', body: { code: 'RACE', session: 'A' } }),
			pageFetch(page, '/api/coupons/redeem', { method: 'POST', body: { code: 'RACE', session: 'B' } }),
		]);

		const statuses = [respA.status, respB.status].sort();
		expect(statuses).toEqual([200, 409]);
		const winnerBody = respA.status === 200 ? respA.body : respB.body;
		const loserBody = respA.status === 409 ? respA.body : respB.body;
		expect((winnerBody as { success: boolean }).success).toBe(true);
		expect((loserBody as { error: string }).error).toBe('race_conflict');
	});
});
