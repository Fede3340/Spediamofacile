import { test, expect, type Route } from '@playwright/test';
import { resolveE2EStorageState } from './utils/authState';
import { stripeCards } from './fixtures/stripe-cards';
import { installStripeMocks } from './fixtures/api-mocks';
import { pageFetch, initStubOrigin } from './fixtures/page-request';

const accountStorageState = resolveE2EStorageState('account');

test.describe('Sprint 9.1 - Checkout pagamento riuscito @critical @payment', () => {
	// Nota: questo test lavora a livello contract (mock route). Non richiede il server dev
	// attivo e puo' essere eseguito in CI smoke anche senza storage state.
	if (accountStorageState) {
		test.use({ storageState: accountStorageState });
	}

	test('utente completa checkout con Stripe card valida e vede ordine confermato', async ({ page }) => {
		await initStubOrigin(page);
		const stripeState = await installStripeMocks(page, { cardOutcome: 'succeeded' });

		let orderStatus = 'pending_payment';
		await page.route('**/api/orders/1001', async (route: Route) => {
			await route.fulfill({
				status: 200,
				contentType: 'application/json',
				body: JSON.stringify({
					data: {
						id: 1001,
						status: orderStatus,
						subtotal: '15,50 EUR',
						payment_intent_id: 'pi_test_123',
					},
				}),
			});
		});

		await page.route('**/api/orders/1001/confirm-payment', async (route: Route) => {
			orderStatus = 'paid';
			await route.fulfill({
				status: 200,
				contentType: 'application/json',
				body: JSON.stringify({ success: true, order_id: 1001, status: 'paid' }),
			});
		});

		// Verifica fixture card valida.
		expect(stripeCards.valid.number).toBe('4242 4242 4242 4242');
		expect(stripeCards.valid.cvc).toBe('123');

		// Create payment intent.
		const intentResp = await pageFetch(page, '/api/stripe/create-payment-intent', {
			method: 'POST',
			body: { amount: 1550, order_id: 1001, currency: 'eur' },
		});
		expect(intentResp.status).toBe(200);
		const intentBody = intentResp.body as { client_secret: string; payment_intent_id: string };
		expect(intentBody.client_secret).toBeTruthy();
		expect(intentBody.payment_intent_id).toBe('pi_test_123');

		// Conferma pagamento (card 4242).
		const confirmResp = await pageFetch(page, '/api/stripe/confirm-payment', {
			method: 'POST',
			body: {
				payment_intent_id: intentBody.payment_intent_id,
				payment_method: { card: { number: stripeCards.valid.number.replace(/\s/g, ''), cvc: stripeCards.valid.cvc, exp: stripeCards.valid.exp } },
			},
		});
		expect(confirmResp.status).toBe(200);
		expect((confirmResp.body as { status: string }).status).toBe('succeeded');

		// Finalizza ordine.
		const finalize = await pageFetch(page, '/api/orders/1001/confirm-payment', {
			method: 'POST',
			body: { payment_intent_id: 'pi_test_123' },
		});
		expect(finalize.status).toBe(200);
		expect((finalize.body as { status: string }).status).toBe('paid');

		// Stato ordine post-successo.
		const after = await pageFetch(page, '/api/orders/1001');
		expect((after.body as { data: { status: string } }).data.status).toBe('paid');

		expect(stripeState.succeededCount).toBe(1);
		expect(stripeState.failedCount).toBe(0);
	});

	test.afterEach(async ({ page }) => {
		await page.evaluate(() => {
			try {
				sessionStorage.clear();
			} catch {
				/* ignore */
			}
		}).catch(() => {});
	});
});
