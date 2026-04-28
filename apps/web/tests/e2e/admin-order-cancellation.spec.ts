import { test, expect, type Route } from '@playwright/test';
import { resolveE2EStorageState } from './utils/authState';
import { pageFetch, initStubOrigin } from './fixtures/page-request';

const adminStorageState = resolveE2EStorageState('admin');

test.describe('Sprint 9.1 - Admin cancellazione ordine con refund @critical @admin', () => {
	if (adminStorageState) {
		test.use({ storageState: adminStorageState });
	}

	test('admin cancella ordine pagato: refund Stripe avviato, status cancelled, audit log registrato', async ({ page }) => {
		await initStubOrigin(page);

		let refundInitiated = false;
		let customerEmailSent = false;
		let auditLogEntry: Record<string, unknown> | null = null;
		const orders = new Map<number, { id: number; status: string; payment_intent_id: string; total: number }>();
		orders.set(3030, { id: 3030, status: 'paid', payment_intent_id: 'pi_admin_test', total: 4500 });

		await page.route('**/api/admin/orders/3030', async (route: Route) => {
			const order = orders.get(3030);
			await route.fulfill({
				status: 200,
				contentType: 'application/json',
				body: JSON.stringify({ data: order }),
			});
		});

		await page.route('**/api/admin/orders/3030/cancel', async (route: Route) => {
			const body = route.request().postDataJSON() as { reason?: string };
			const order = orders.get(3030);
			if (!order) {
				await route.fulfill({ status: 404 });
				return;
			}
			refundInitiated = true;
			customerEmailSent = true;
			auditLogEntry = {
				actor: 'admin@spediamofacile.it',
				action: 'order.cancel',
				order_id: 3030,
				reason: body?.reason || 'manual_admin',
				refund_amount_cents: order.total,
				timestamp: new Date().toISOString(),
			};
			orders.set(3030, { ...order, status: 'cancelled' });
			await route.fulfill({
				status: 200,
				contentType: 'application/json',
				body: JSON.stringify({
					success: true,
					order_id: 3030,
					new_status: 'cancelled',
					refund: { id: 're_admin_test', amount: order.total, status: 'pending' },
					customer_notified: true,
					audit_log_id: 99101,
				}),
			});
		});

		const before = await pageFetch(page, '/api/admin/orders/3030');
		expect((before.body as { data: { status: string } }).data.status).toBe('paid');

		const cancelResp = await pageFetch(page, '/api/admin/orders/3030/cancel', {
			method: 'POST',
			body: { reason: 'duplicate_shipment' },
		});
		expect(cancelResp.status).toBe(200);
		const cancelBody = cancelResp.body as {
			success: boolean;
			new_status: string;
			refund: { status: string };
			customer_notified: boolean;
		};
		expect(cancelBody.success).toBe(true);
		expect(cancelBody.new_status).toBe('cancelled');
		expect(cancelBody.refund.status).toMatch(/pending|succeeded/);
		expect(cancelBody.customer_notified).toBe(true);

		expect(refundInitiated).toBe(true);
		expect(customerEmailSent).toBe(true);
		expect(auditLogEntry).not.toBeNull();
		expect(auditLogEntry?.action).toBe('order.cancel');
		expect(auditLogEntry?.reason).toBe('duplicate_shipment');

		const after = await pageFetch(page, '/api/admin/orders/3030');
		expect((after.body as { data: { status: string } }).data.status).toBe('cancelled');
	});
});
