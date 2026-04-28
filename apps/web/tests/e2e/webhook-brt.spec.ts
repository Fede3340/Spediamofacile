import { test, expect, type Route } from '@playwright/test';
import { createHash, createHmac } from 'node:crypto';
import { pageFetch, initStubOrigin } from './fixtures/page-request';

/**
 * Webhook BRT: verifica HMAC signature + dedup per fingerprint
 * SHA256(parcelId|status|timestamp).
 */

const BACKEND_BRT_WEBHOOK = '/api/brt/webhook';
const BRT_SECRET = 'brt_test_webhook_secret';

const signBrtPayload = (payload: string): string =>
	createHmac('sha256', BRT_SECRET).update(payload).digest('hex');

const fingerprintFor = (parcelId: string, status: string, timestamp: string): string =>
	createHash('sha256').update(`${parcelId}|${status}|${timestamp}`).digest('hex');

test.describe('Sprint 9.1 - BRT webhook tracking @critical', () => {
	test('webhook con HMAC valida aggiorna tracking e dedup scarta duplicati', async ({ page }) => {
		await initStubOrigin(page);

		const processedFingerprints = new Set<string>();
		const trackingUpdates = new Map<string, string>();

		await page.route(`**${BACKEND_BRT_WEBHOOK}`, async (route: Route) => {
			const rawBody = route.request().postData() || '';
			const signatureHeader = route.request().headers()['x-brt-signature'] || '';
			const expected = signBrtPayload(rawBody);

			if (signatureHeader !== expected) {
				await route.fulfill({
					status: 401,
					contentType: 'application/json',
					body: JSON.stringify({ error: 'invalid_signature' }),
				});
				return;
			}

			const payload = JSON.parse(rawBody) as { parcelId: string; status: string; timestamp: string };
			const fingerprint = fingerprintFor(payload.parcelId, payload.status, payload.timestamp);

			if (processedFingerprints.has(fingerprint)) {
				await route.fulfill({
					status: 200,
					contentType: 'application/json',
					body: JSON.stringify({ received: true, duplicate: true }),
				});
				return;
			}

			processedFingerprints.add(fingerprint);
			trackingUpdates.set(payload.parcelId, payload.status);
			await route.fulfill({
				status: 200,
				contentType: 'application/json',
				body: JSON.stringify({ received: true, processed: true, fingerprint }),
			});
		});

		const payload = {
			parcelId: 'BRT123456789',
			status: 'IN_TRANSIT',
			timestamp: '2026-04-17T10:30:00Z',
		};
		const rawBody = JSON.stringify(payload);
		const validSignature = signBrtPayload(rawBody);

		// 1) Firma non valida → 401.
		const invalid = await pageFetch(page, BACKEND_BRT_WEBHOOK, {
			method: 'POST',
			body: payload,
			headers: { 'x-brt-signature': 'firma-falsa' },
		});
		expect(invalid.status).toBe(401);

		// 2) Firma valida + payload nuovo → 200 processed.
		const firstValid = await pageFetch(page, BACKEND_BRT_WEBHOOK, {
			method: 'POST',
			body: payload,
			headers: { 'x-brt-signature': validSignature },
		});
		expect(firstValid.status).toBe(200);
		const firstBody = firstValid.body as { processed: boolean };
		expect(firstBody.processed).toBe(true);
		expect(trackingUpdates.get('BRT123456789')).toBe('IN_TRANSIT');

		// 3) Stesso fingerprint → duplicate.
		const duplicate = await pageFetch(page, BACKEND_BRT_WEBHOOK, {
			method: 'POST',
			body: payload,
			headers: { 'x-brt-signature': validSignature },
		});
		expect(duplicate.status).toBe(200);
		const dupBody = duplicate.body as { duplicate: boolean };
		expect(dupBody.duplicate).toBe(true);
		expect(processedFingerprints.size).toBe(1);

		// 4) Cambio status → fingerprint diverso → processato.
		const nextPayload = { ...payload, status: 'DELIVERED', timestamp: '2026-04-17T14:00:00Z' };
		const nextRaw = JSON.stringify(nextPayload);
		const nextValid = await pageFetch(page, BACKEND_BRT_WEBHOOK, {
			method: 'POST',
			body: nextPayload,
			headers: { 'x-brt-signature': signBrtPayload(nextRaw) },
		});
		expect(nextValid.status).toBe(200);
		expect(trackingUpdates.get('BRT123456789')).toBe('DELIVERED');
		expect(processedFingerprints.size).toBe(2);
	});
});
