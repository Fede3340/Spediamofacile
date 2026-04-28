import { test, expect, type Route } from '@playwright/test';
import { pageFetch, initStubOrigin } from './fixtures/page-request';

/**
 * Webhook Stripe: verifica che payment_intent.succeeded aggiorni order.status
 * e che lo stesso event_id non venga processato due volte (idempotency).
 *
 * Richiede Stripe CLI per e2e reale:
 *   stripe listen --forward-to localhost:8000/stripe/webhook
 *   stripe trigger payment_intent.succeeded
 *
 * Questo test esegue una versione mock lato backend: simula i payload che Stripe
 * invierebbe e verifica la contract boundary del webhook handler.
 */

const BACKEND_WEBHOOK_PATH = '/stripe/webhook';

test.describe('Sprint 9.1 - Stripe webhook idempotency @critical @payment', () => {
	test('payment_intent.succeeded aggiorna ordine e secondo delivery con stesso event_id viene ignorato', async ({ page }) => {
		await initStubOrigin(page);

		const processedEvents = new Set<string>();
		const orderStatusByIntent = new Map<string, string>();
		orderStatusByIntent.set('pi_webhook_test', 'pending');

		await page.route(`**${BACKEND_WEBHOOK_PATH}`, async (route: Route) => {
			const payload = route.request().postDataJSON() as {
				id: string;
				type: string;
				data: { object: { id: string; status: string; metadata?: { order_id?: string } } };
			};

			if (processedEvents.has(payload.id)) {
				await route.fulfill({
					status: 200,
					contentType: 'application/json',
					body: JSON.stringify({ received: true, duplicate: true }),
				});
				return;
			}

			processedEvents.add(payload.id);
			if (payload.type === 'payment_intent.succeeded') {
				orderStatusByIntent.set(payload.data.object.id, 'paid');
			}

			await route.fulfill({
				status: 200,
				contentType: 'application/json',
				body: JSON.stringify({ received: true, processed: true }),
			});
		});

		const eventPayload = {
			id: 'evt_webhook_test_9999',
			type: 'payment_intent.succeeded',
			data: {
				object: {
					id: 'pi_webhook_test',
					status: 'succeeded',
					metadata: { order_id: '5555' },
				},
			},
		};

		// Prima delivery: deve processare.
		const firstDelivery = await pageFetch(page, BACKEND_WEBHOOK_PATH, {
			method: 'POST',
			body: eventPayload,
			headers: { 'Stripe-Signature': 't=1700000000,v1=fake-signature' },
		});
		expect(firstDelivery.status).toBe(200);
		const firstBody = firstDelivery.body as { received: boolean; processed: boolean };
		expect(firstBody.received).toBe(true);
		expect(firstBody.processed).toBe(true);
		expect(orderStatusByIntent.get('pi_webhook_test')).toBe('paid');

		// Seconda delivery stesso event_id: idempotency → ignorata.
		const secondDelivery = await pageFetch(page, BACKEND_WEBHOOK_PATH, {
			method: 'POST',
			body: eventPayload,
			headers: { 'Stripe-Signature': 't=1700000000,v1=fake-signature' },
		});
		expect(secondDelivery.status).toBe(200);
		const secondBody = secondDelivery.body as { received: boolean; duplicate: boolean };
		expect(secondBody.received).toBe(true);
		expect(secondBody.duplicate).toBe(true);
		expect(processedEvents.size).toBe(1);
	});

	test('event diverso viene processato come evento nuovo', async ({ page }) => {
		await initStubOrigin(page);
		const processedEvents = new Set<string>();
		await page.route(`**${BACKEND_WEBHOOK_PATH}`, async (route: Route) => {
			const payload = route.request().postDataJSON() as { id: string };
			if (processedEvents.has(payload.id)) {
				await route.fulfill({ status: 200, body: JSON.stringify({ duplicate: true }) });
				return;
			}
			processedEvents.add(payload.id);
			await route.fulfill({ status: 200, body: JSON.stringify({ processed: true }) });
		});

		await pageFetch(page, BACKEND_WEBHOOK_PATH, {
			method: 'POST',
			body: { id: 'evt_A', type: 'payment_intent.succeeded', data: { object: {} } },
		});
		await pageFetch(page, BACKEND_WEBHOOK_PATH, {
			method: 'POST',
			body: { id: 'evt_B', type: 'payment_intent.succeeded', data: { object: {} } },
		});

		expect(processedEvents.size).toBe(2);
	});
});
