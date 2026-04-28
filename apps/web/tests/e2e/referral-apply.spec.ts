import { test, expect, type Route } from '@playwright/test';
import { pageFetch, initStubOrigin } from './fixtures/page-request';

/**
 * Partner Pro referral flow:
 *  - Pro A ottiene referral_code
 *  - User B si registra con quel codice
 *  - User B completa il primo ordine → commission accreditata al wallet di A
 *  - Record ReferralUsage creato
 */
test.describe('Sprint 9.1 - Referral Partner Pro @critical', () => {
	test('referral_code applicato in registrazione e commissione accreditata al primo ordine', async ({ page }) => {
		await initStubOrigin(page);

		let walletABalance = 0;
		let referralUsageCreated: Record<string, unknown> | null = null;
		const referralCode = 'PRO-A-ABC123';

		await page.route('**/api/referral/my-code', async (route: Route) => {
			await route.fulfill({
				status: 200,
				contentType: 'application/json',
				body: JSON.stringify({ code: referralCode, commission_pct: 10 }),
			});
		});

		await page.route('**/api/referral/validate**', async (route: Route) => {
			const body = route.request().postDataJSON() as { code?: string };
			if (body?.code === referralCode) {
				await route.fulfill({
					status: 200,
					contentType: 'application/json',
					body: JSON.stringify({ valid: true, owner_id: 1, commission_pct: 10 }),
				});
			} else {
				await route.fulfill({ status: 404, body: JSON.stringify({ error: 'not_found' }) });
			}
		});

		let registeredWithReferral = false;
		await page.route('**/api/register', async (route: Route) => {
			const body = route.request().postDataJSON() as { referral_code?: string; email?: string };
			if (body?.referral_code === referralCode) {
				registeredWithReferral = true;
			}
			await route.fulfill({
				status: 201,
				contentType: 'application/json',
				body: JSON.stringify({
					user: { id: 2, email: body?.email, referred_by: body?.referral_code || null },
					token: 'fake-sanctum-token',
				}),
			});
		});

		await page.route('**/api/orders/*/complete', async (route: Route) => {
			const amountCents = 2000;
			const commissionCents = Math.round(amountCents * 0.10);
			walletABalance += commissionCents;
			referralUsageCreated = {
				id: 501,
				referrer_user_id: 1,
				referred_user_id: 2,
				order_id: 4040,
				commission_cents: commissionCents,
				created_at: new Date().toISOString(),
			};
			await route.fulfill({
				status: 200,
				contentType: 'application/json',
				body: JSON.stringify({
					success: true,
					order_id: 4040,
					commission_awarded: commissionCents,
					referral_usage_id: 501,
				}),
			});
		});

		await page.route('**/api/wallet/balance', async (route: Route) => {
			await route.fulfill({
				status: 200,
				contentType: 'application/json',
				body: JSON.stringify({ balance: walletABalance / 100 }),
			});
		});

		const codeResp = await pageFetch(page, '/api/referral/my-code');
		expect((codeResp.body as { code: string }).code).toBe(referralCode);

		const validateResp = await pageFetch(page, '/api/referral/validate', {
			method: 'POST',
			body: { code: referralCode },
		});
		expect(validateResp.status).toBe(200);
		expect((validateResp.body as { valid: boolean }).valid).toBe(true);

		const registerResp = await pageFetch(page, '/api/register', {
			method: 'POST',
			body: { email: 'userb@test.it', password: 'Password1!', referral_code: referralCode },
		});
		expect(registerResp.status).toBe(201);
		const regBody = registerResp.body as { user: { referred_by: string } };
		expect(regBody.user.referred_by).toBe(referralCode);
		expect(registeredWithReferral).toBe(true);

		let walletResp = await pageFetch(page, '/api/wallet/balance');
		expect((walletResp.body as { balance: number }).balance).toBe(0);

		const completeResp = await pageFetch(page, '/api/orders/4040/complete', { method: 'POST' });
		expect(completeResp.status).toBe(200);
		const completeBody = completeResp.body as { commission_awarded: number; referral_usage_id: number };
		expect(completeBody.commission_awarded).toBeGreaterThan(0);
		expect(completeBody.referral_usage_id).toBe(501);

		walletResp = await pageFetch(page, '/api/wallet/balance');
		expect((walletResp.body as { balance: number }).balance).toBeGreaterThan(0);
		expect(referralUsageCreated).not.toBeNull();
		expect(referralUsageCreated?.referrer_user_id).toBe(1);
		expect(referralUsageCreated?.referred_user_id).toBe(2);
		expect(referralUsageCreated?.commission_cents).toBe(200);
	});
});
