import { test, expect, type Route } from '@playwright/test';
import { resolveE2EStorageState } from './utils/authState';
import { installWalletMocks, installStripeMocks } from './fixtures/api-mocks';
import { stripeCards } from './fixtures/stripe-cards';
import { pageFetch, initStubOrigin } from './fixtures/page-request';

const accountStorageState = resolveE2EStorageState('account');

test.describe('Sprint 9.1 - Wallet top-up Stripe @critical @payment', () => {
	if (accountStorageState) {
		test.use({ storageState: accountStorageState });
	}

	test('utente ricarica 50€ con Stripe: balance incrementato e WalletMovement creato', async ({ page }) => {
		await initStubOrigin(page);
		const walletState = await installWalletMocks(page, { balance: 10 });
		const stripeState = await installStripeMocks(page, { cardOutcome: 'succeeded' });

		let transactionLinked = false;
		await page.route('**/api/wallet/topup', async (route: Route) => {
			const body = route.request().postDataJSON() as { amount?: number; payment_intent_id?: string };
			const amount = Number(body?.amount || 0);
			transactionLinked = Boolean(body?.payment_intent_id);
			walletState.balance += amount;
			walletState.movements.unshift({
				id: walletState.movements.length + 1,
				amount,
				type: 'topup',
				description: `Ricarica ${amount.toFixed(2)} EUR - ${body?.payment_intent_id || ''}`,
			});
			await route.fulfill({
				status: 200,
				contentType: 'application/json',
				body: JSON.stringify({
					success: true,
					new_balance: walletState.balance,
					movement_id: walletState.movements[0].id,
					transaction_id: body?.payment_intent_id,
				}),
			});
		});

		const preBalance = await pageFetch(page, '/api/wallet/balance');
		expect((preBalance.body as { balance: number }).balance).toBe(10);

		expect(stripeCards.valid.number).toBe('4242 4242 4242 4242');
		const stripeResp = await pageFetch(page, '/api/stripe/confirm-payment', {
			method: 'POST',
			body: {
				amount: 5000,
				payment_method: { card: { number: stripeCards.valid.number.replace(/\s/g, '') } },
			},
		});
		expect(stripeResp.status).toBe(200);
		expect((stripeResp.body as { status: string }).status).toBe('succeeded');

		const topupResp = await pageFetch(page, '/api/wallet/topup', {
			method: 'POST',
			body: { amount: 50, payment_intent_id: 'pi_test_123' },
		});
		expect(topupResp.status).toBe(200);
		const topupBody = topupResp.body as { success: boolean; new_balance: number; transaction_id: string };
		expect(topupBody.success).toBe(true);
		expect(topupBody.new_balance).toBe(60);
		expect(topupBody.transaction_id).toBe('pi_test_123');

		expect(walletState.balance).toBe(60);
		expect(walletState.movements).toHaveLength(1);
		expect(walletState.movements[0].type).toBe('topup');
		expect(walletState.movements[0].amount).toBe(50);
		expect(transactionLinked).toBe(true);
		expect(stripeState.succeededCount).toBe(1);

		const movementsResp = await pageFetch(page, '/api/wallet/movements');
		const movementsBody = movementsResp.body as { data: unknown[] };
		expect(movementsBody.data).toHaveLength(1);
	});
});
