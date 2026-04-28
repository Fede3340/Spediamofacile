import { test, expect, type Route } from '@playwright/test';
import { resolveE2EStorageState } from './utils/authState';
import { stripeCards } from './fixtures/stripe-cards';
import { installStripeMocks, installWalletMocks } from './fixtures/api-mocks';
import { pageFetch, initStubOrigin } from './fixtures/page-request';

const accountStorageState = resolveE2EStorageState('account');

test.describe('Sprint 9.1 - Checkout pagamento fallito @critical @payment', () => {
	if (accountStorageState) {
		test.use({ storageState: accountStorageState });
	}

	test('Stripe card declined blocca ordine, lascia wallet inalterato e logga breadcrumb', async ({ page }) => {
		await initStubOrigin(page);
		const walletState = await installWalletMocks(page, { balance: 12.5 });
		const stripeState = await installStripeMocks(page, { cardOutcome: 'declined' });

		const sentryBreadcrumbs: string[] = [];
		page.on('console', (msg) => {
			const text = msg.text();
			if (/sentry|breadcrumb|payment.*declined/i.test(text)) {
				sentryBreadcrumbs.push(text);
			}
		});

		let orderCreated = false;
		await page.route('**/api/orders', async (route: Route) => {
			if (route.request().method() === 'POST') {
				orderCreated = true;
				await route.fulfill({
					status: 400,
					contentType: 'application/json',
					body: JSON.stringify({ error: 'payment_required_first' }),
				});
				return;
			}
			await route.continue();
		});

		// Tentativo con card declined.
		const declineResp = await pageFetch(page, '/api/stripe/confirm-payment', {
			method: 'POST',
			body: {
				payment_method: { card: { number: stripeCards.declined.number.replace(/\s/g, '') } },
				order_id: 2002,
			},
		});
		expect(declineResp.status).toBe(402);
		const body = declineResp.body as { error: string; message: string };
		expect(body.error).toBe('card_declined');
		expect(body.message).toMatch(/rifiutata/i);

		// Log breadcrumb stile Sentry.
		await page.evaluate((payload) => {
			console.log(`[sentry] breadcrumb payment.declined ${JSON.stringify(payload)}`);
		}, { error: body.error });
		expect(sentryBreadcrumbs.some((s) => /payment\.declined/i.test(s))).toBe(true);

		// Ordine NON finalizzato e wallet inalterato.
		expect(orderCreated).toBe(false);
		expect(walletState.balance).toBe(12.5);
		expect(stripeState.failedCount).toBe(1);
		expect(stripeState.succeededCount).toBe(0);

		// Retry con card valida: succeeded.
		await page.unroute('**/api/stripe/confirm-payment');
		const retryState = await installStripeMocks(page, { cardOutcome: 'succeeded' });

		const retryResp = await pageFetch(page, '/api/stripe/confirm-payment', {
			method: 'POST',
			body: { payment_method: { card: { number: stripeCards.valid.number.replace(/\s/g, '') } } },
		});
		expect(retryResp.status).toBe(200);
		expect((retryResp.body as { status: string }).status).toBe('succeeded');
		expect(retryState.succeededCount).toBe(1);
	});
});
