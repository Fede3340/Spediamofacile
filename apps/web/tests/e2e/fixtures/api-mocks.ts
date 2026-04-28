import type { Page, Route } from '@playwright/test';

/**
 * Helper per installare mock API condivisi (Stripe, wallet, coupon, webhook…)
 * senza dipendere dal backend Laravel. I test Sprint 9.1 sono deterministici:
 * il backend reale sara' coperto da test d'integrazione separati.
 */

export interface StripeSessionState {
	lastPaymentIntent?: Record<string, unknown>;
	succeededCount: number;
	failedCount: number;
	webhookEvents: Array<{ type: string; id: string; data: Record<string, unknown> }>;
}

export const installStripeMocks = async (
	page: Page,
	options: { cardOutcome?: 'succeeded' | 'declined' | 'requires_action' } = {},
) => {
	const outcome = options.cardOutcome ?? 'succeeded';
	const state: StripeSessionState = { succeededCount: 0, failedCount: 0, webhookEvents: [] };

	await page.route('**/api/settings/stripe', async (route: Route) => {
		await route.fulfill({
			status: 200,
			contentType: 'application/json',
			body: JSON.stringify({ publishable_key: 'pk_test_dummy' }),
		});
	});

	await page.route('**/api/stripe/default-payment-method', async (route: Route) => {
		await route.fulfill({
			status: 200,
			contentType: 'application/json',
			body: JSON.stringify({ card: null }),
		});
	});

	await page.route('**/api/stripe/create-payment-intent', async (route: Route) => {
		const body = route.request().postDataJSON() as Record<string, unknown>;
		state.lastPaymentIntent = body;
		await route.fulfill({
			status: 200,
			contentType: 'application/json',
			body: JSON.stringify({
				client_secret: 'pi_test_secret_123',
				payment_intent_id: 'pi_test_123',
			}),
		});
	});

	await page.route('**/api/stripe/confirm-payment**', async (route: Route) => {
		if (outcome === 'succeeded') {
			state.succeededCount += 1;
			await route.fulfill({
				status: 200,
				contentType: 'application/json',
				body: JSON.stringify({ status: 'succeeded', payment_intent_id: 'pi_test_123' }),
			});
		} else if (outcome === 'declined') {
			state.failedCount += 1;
			await route.fulfill({
				status: 402,
				contentType: 'application/json',
				body: JSON.stringify({
					error: 'card_declined',
					message: 'La carta e stata rifiutata dalla banca.',
				}),
			});
		} else {
			await route.fulfill({
				status: 200,
				contentType: 'application/json',
				body: JSON.stringify({ status: 'requires_action', next_action: { type: 'use_stripe_sdk' } }),
			});
		}
	});

	return state;
};

export interface CouponMockState {
	validations: number;
	redemptions: number;
	rejected: Array<{ code: string; reason: string }>;
}

export const installCouponMocks = async (
	page: Page,
	coupons: Array<{ code: string; status: 'valid' | 'expired' | 'max_uses' | 'already_used'; discount?: number }>,
) => {
	const state: CouponMockState = { validations: 0, redemptions: 0, rejected: [] };
	const byCode = new Map(coupons.map((c) => [c.code.toLowerCase(), c]));

	await page.route('**/api/coupons/validate**', async (route: Route) => {
		state.validations += 1;
		const body = route.request().postDataJSON() as { code?: string };
		const code = (body?.code || '').toLowerCase();
		const coupon = byCode.get(code);

		if (!coupon || coupon.status !== 'valid') {
			const reason = coupon?.status || 'not_found';
			state.rejected.push({ code, reason });
			await route.fulfill({
				status: 422,
				contentType: 'application/json',
				body: JSON.stringify({
					error: reason,
					message:
						reason === 'expired'
							? 'Il coupon e scaduto.'
							: reason === 'max_uses'
								? 'Limite utilizzi raggiunto.'
								: reason === 'already_used'
									? 'Hai gia utilizzato questo coupon.'
									: 'Coupon non valido.',
				}),
			});
			return;
		}

		state.redemptions += 1;
		await route.fulfill({
			status: 200,
			contentType: 'application/json',
			body: JSON.stringify({
				valid: true,
				discount: coupon.discount ?? 10,
				currency: 'EUR',
			}),
		});
	});

	return state;
};

export interface WalletMockState {
	balance: number;
	movements: Array<{ id: number; amount: number; type: string; description: string }>;
}

export const installWalletMocks = async (page: Page, initial: { balance: number } = { balance: 0 }) => {
	const state: WalletMockState = {
		balance: initial.balance,
		movements: [],
	};

	await page.route('**/api/wallet/balance', async (route: Route) => {
		await route.fulfill({
			status: 200,
			contentType: 'application/json',
			body: JSON.stringify({ balance: state.balance }),
		});
	});

	await page.route('**/api/wallet/movements**', async (route: Route) => {
		await route.fulfill({
			status: 200,
			contentType: 'application/json',
			body: JSON.stringify({ data: state.movements, meta: { total: state.movements.length } }),
		});
	});

	await page.route('**/api/wallet/topup', async (route: Route) => {
		const body = route.request().postDataJSON() as { amount?: number };
		const amount = Number(body?.amount || 0);
		state.balance += amount;
		state.movements.unshift({
			id: state.movements.length + 1,
			amount,
			type: 'topup',
			description: `Ricarica ${amount.toFixed(2)} EUR`,
		});
		await route.fulfill({
			status: 200,
			contentType: 'application/json',
			body: JSON.stringify({ success: true, new_balance: state.balance }),
		});
	});

	return state;
};
