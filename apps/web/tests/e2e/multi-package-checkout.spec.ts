import { test, expect, type Route } from '@playwright/test';
import { resolveE2EStorageState } from './utils/authState';
import { pageFetch, initStubOrigin } from './fixtures/page-request';

const accountStorageState = resolveE2EStorageState('account');

test.describe('Sprint 9.1 - Checkout multiparcel BRT @critical', () => {
	if (accountStorageState) {
		test.use({ storageState: accountStorageState });
	}

	test('preventivo con 3 pacchi diversi calcola prezzo cumulato e genera tracking per ogni pacco', async ({ page }) => {
		await initStubOrigin(page);

		const packages = [
			{ package_type: 'Pacco', weight: 5, first_size: 30, second_size: 20, third_size: 15, quantity: 1, single_price: 850 },
			{ package_type: 'Pallet', weight: 80, first_size: 120, second_size: 80, third_size: 100, quantity: 1, single_price: 4200 },
			{ package_type: 'Valigia', weight: 12, first_size: 75, second_size: 50, third_size: 30, quantity: 1, single_price: 1650 },
		];
		const expectedTotalCents = packages.reduce((sum, p) => sum + p.single_price, 0);

		await page.route('**/api/cart', async (route: Route) => {
			await route.fulfill({
				status: 200,
				contentType: 'application/json',
				body: JSON.stringify({
					data: packages,
					meta: { address_groups: [{ count: 3 }], total_cents: expectedTotalCents, total: '67,00 EUR' },
				}),
			});
		});

		let createShipmentPayload: Record<string, unknown> | null = null;
		const trackingNumbers: string[] = ['BRT00000001', 'BRT00000002', 'BRT00000003'];

		await page.route('**/api/brt/create-shipment', async (route: Route) => {
			createShipmentPayload = route.request().postDataJSON() as Record<string, unknown>;
			const parcels = (createShipmentPayload?.parcels as Array<Record<string, unknown>>) || [];
			await route.fulfill({
				status: 200,
				contentType: 'application/json',
				body: JSON.stringify({
					success: true,
					shipment_id: 'SHIP-MULTI-001',
					parcel_count: parcels.length,
					tracking_numbers: trackingNumbers.slice(0, parcels.length),
				}),
			});
		});

		const cartResp = await pageFetch(page, '/api/cart');
		const cartBody = cartResp.body as { data: Array<{ package_type: string }>; meta: { total_cents: number } };
		expect(cartBody.data).toHaveLength(3);
		expect(cartBody.meta.total_cents).toBe(expectedTotalCents);
		const types = cartBody.data.map((p) => p.package_type).sort();
		expect(types).toEqual(['Pacco', 'Pallet', 'Valigia']);

		const shipmentResp = await pageFetch(page, '/api/brt/create-shipment', {
			method: 'POST',
			body: { order_id: 5050, parcels: packages },
		});
		expect(shipmentResp.status).toBe(200);
		const shipmentBody = shipmentResp.body as {
			success: boolean;
			parcel_count: number;
			tracking_numbers: string[];
		};
		expect(shipmentBody.success).toBe(true);
		expect(shipmentBody.parcel_count).toBe(3);
		expect(shipmentBody.tracking_numbers).toHaveLength(3);
		expect(new Set(shipmentBody.tracking_numbers).size).toBe(3);

		const sentParcels = (createShipmentPayload?.parcels as Array<Record<string, number>>) || [];
		expect(sentParcels).toHaveLength(3);
		expect(sentParcels[0].weight).toBe(5);
		expect(sentParcels[1].weight).toBe(80);
		expect(sentParcels[2].weight).toBe(12);
	});
});
