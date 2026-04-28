import { test, expect, type Page, type Route } from '@playwright/test';

import { authSetupProfiles, resolveE2EStorageState } from './utils/authState';

const accountStorageState = resolveE2EStorageState('account');
const customerAuthProfile = authSetupProfiles[0];

const ensureAuthenticatedShipmentUser = async (page: Page) => {
	await page.goto('/?auth_modal=login&redirect=/preventivo');
	await page.waitForLoadState('networkidle');

	const authEmail = page.locator('#auth-modal-email');
	const overlayVisible = await authEmail
		.isVisible({ timeout: 5000 })
		.catch(() => false);

	if (overlayVisible) {
		await authEmail.fill(customerAuthProfile.email);
		await page.locator('#auth-modal-password').fill(customerAuthProfile.password);
		await page
			.locator('#auth-modal-password')
			.locator('xpath=ancestor::form')
			.getByRole('button', { name: /^Accedi$/ })
			.click();
		await expect(authEmail).toHaveCount(0, { timeout: 30000 });
	}

	await page.goto('/la-tua-spedizione/2?step=colli');
	await page.waitForLoadState('networkidle');
};

const fillLocationField = async (page: Page, selector: string, value: string) => {
	const input = page.locator(selector);
	await expect(input).toBeVisible();
	await input.fill(value);
	await page.waitForTimeout(900);

	const suggestions = page.locator('ul[role="listbox"] li[role="option"]');
	const hasSuggestions = await suggestions
		.first()
		.isVisible({ timeout: 2500 })
		.catch(() => false);

	if (hasSuggestions) {
		await suggestions.first().click();
	} else {
		await input.press('ArrowDown').catch(() => {});
		await input.press('Enter').catch(() => {});
	}

	await input.blur();
	await page.waitForTimeout(250);
};

const installShipmentFlowMocks = async (
	page: Page,
	options: {
		cartPayload?: Record<string, unknown>;
		sessionPayload?: Record<string, any>;
	} = {},
) => {
	let csrfRequestCount = 0;
	let firstStepRequestCount = 0;
	let secondStepRequestCount = 0;
	let createdOrderId = 987;
	let createdOrderPayload: Record<string, any> | null = null;
	const mockedLocations = [
		{
			place_name: 'Roma',
			postal_code: '00118',
			province: 'RM',
			country_code: 'IT',
			country_name: 'Italia',
		},
		{
			place_name: 'Milano',
			postal_code: '20121',
			province: 'MI',
			country_code: 'IT',
			country_name: 'Italia',
		},
	];
	const defaultSessionPayload: Record<string, any> = {
		data: {
			shipment_details: {},
			packages: [],
			services: null,
			total_price: 0,
			step: 1,
		},
	};
	const defaultCartPayload: Record<string, unknown> = {
		data: [
			{
				package_type: 'Pacco',
				quantity: 1,
				weight: 5,
				first_size: 30,
				second_size: 20,
				third_size: 15,
				single_price: 850,
				content_description: 'Documenti e accessori',
			},
		],
		meta: {
			address_groups: [{ count: 1 }],
			total: '8,50€',
		},
	};
	let sessionPayload: Record<string, any> = options.sessionPayload || defaultSessionPayload;
	let cartPayload: Record<string, unknown> = options.cartPayload || defaultCartPayload;

	await page.route('**/sanctum/csrf-cookie', async (route: Route) => {
		csrfRequestCount += 1;
		await route.fulfill({
			status: 200,
			contentType: 'application/json',
			body: JSON.stringify({ ok: true }),
		});
	});

	await page.route('**/api/locations/by-city?**', async (route: Route) => {
		const url = new URL(route.request().url());
		const city = (url.searchParams.get('city') || '').trim().toLowerCase();
		const matches = mockedLocations.filter((location) => location.place_name.toLowerCase().startsWith(city));

		await route.fulfill({
			status: 200,
			contentType: 'application/json',
			body: JSON.stringify(matches),
		});
	});

	await page.route('**/api/locations/search?**', async (route: Route) => {
		const url = new URL(route.request().url());
		const query = (url.searchParams.get('q') || '').trim().toLowerCase();
		const matches = mockedLocations.filter(
			(location) =>
				location.place_name.toLowerCase().includes(query) || location.postal_code.startsWith(query),
		);

		await route.fulfill({
			status: 200,
			contentType: 'application/json',
			body: JSON.stringify(matches),
		});
	});

	await page.route('**/api/locations/by-cap?**', async (route: Route) => {
		const url = new URL(route.request().url());
		const cap = (url.searchParams.get('cap') || '').trim();
		const matches = mockedLocations.filter((location) => location.postal_code.startsWith(cap));

		await route.fulfill({
			status: 200,
			contentType: 'application/json',
			body: JSON.stringify(matches),
		});
	});

	await page.route('**/api/cart', async (route: Route) => {
		await route.fulfill({
			status: 200,
			contentType: 'application/json',
			body: JSON.stringify(cartPayload),
		});
	});

	await page.route('**/api/session/first-step', async (route: Route) => {
		firstStepRequestCount += 1;
		const requestBody = route.request().postDataJSON();
		sessionPayload = {
			data: {
				shipment_details: requestBody.shipment_details,
				packages: requestBody.packages.map((pack: Record<string, unknown>) => ({
					...pack,
					weight_price: 8.5,
					volume_price: 7.25,
					single_price: 8.5,
				})),
				services: null,
				total_price: 8.5,
				step: 2,
			},
		};

		await route.fulfill({
			status: 200,
			contentType: 'application/json',
			body: JSON.stringify(sessionPayload),
		});
	});

	await page.route('**/api/session/second-step', async (route: Route) => {
		secondStepRequestCount += 1;
		const requestBody = route.request().postDataJSON();

		sessionPayload = {
			data: {
				...sessionPayload.data,
				services: requestBody.services,
				content_description: requestBody.content_description,
				pickup_date: requestBody.pickup_date,
				origin_address: requestBody.origin_address ?? sessionPayload.data.origin_address ?? null,
				destination_address: requestBody.destination_address ?? sessionPayload.data.destination_address ?? null,
				delivery_mode: requestBody.delivery_mode ?? 'home',
				selected_pudo: requestBody.selected_pudo ?? null,
				step: requestBody.origin_address && requestBody.destination_address ? 3 : 2,
			},
		};

		await route.fulfill({
			status: 200,
			contentType: 'application/json',
			body: JSON.stringify(sessionPayload),
		});
	});

	await page.route('**/api/session', async (route: Route) => {
		await route.fulfill({
			status: 200,
			contentType: 'application/json',
			body: JSON.stringify(sessionPayload),
		});
	});

	await page.route('**/api/create-direct-order', async (route: Route) => {
		createdOrderPayload = route.request().postDataJSON();
		await route.fulfill({
			status: 200,
			contentType: 'application/json',
			body: JSON.stringify({
				success: true,
				order_id: createdOrderId,
			}),
		});
	});

	await page.route('**/api/orders/*', async (route: Route) => {
		const orderId = route.request().url().split('/').pop() || String(createdOrderId);
		const fallbackPackages = Array.isArray((cartPayload as Record<string, any>)?.data)
			? (cartPayload as Record<string, any>).data
			: [];
		const sourcePackages = Array.isArray(createdOrderPayload?.packages) && createdOrderPayload.packages.length
			? createdOrderPayload.packages
			: fallbackPackages;
		const originAddress =
			createdOrderPayload?.origin_address
			|| sessionPayload?.data?.origin_address
			|| null;
		const destinationAddress =
			createdOrderPayload?.destination_address
			|| sessionPayload?.data?.destination_address
			|| null;
		const packages = Array.isArray(sourcePackages)
			? sourcePackages.map((pack: Record<string, any>, index: number) => ({
				id: index + 1,
				package_type: pack.package_type || 'Pacco',
				quantity: Number(pack.quantity) || 1,
				weight: Number(pack.weight) || 5,
				first_size: Number(pack.first_size) || 30,
				second_size: Number(pack.second_size) || 20,
				third_size: Number(pack.third_size) || 15,
				content_description: createdOrderPayload?.content_description || pack.content_description || 'Documenti e accessori',
				origin_address: originAddress,
				destination_address: destinationAddress,
				services: createdOrderPayload?.services || {},
				delivery_mode: createdOrderPayload?.delivery_mode || 'home',
				selected_pudo: createdOrderPayload?.selected_pudo || null,
			}))
			: [];

		await route.fulfill({
			status: 200,
			contentType: 'application/json',
			body: JSON.stringify({
				data: {
					id: Number(orderId) || createdOrderId,
					subtotal: '11,90€',
					packages,
				},
			}),
		});
	});

	await page.route('**/api/settings/stripe', async (route: Route) => {
		await route.fulfill({
			status: 200,
			contentType: 'application/json',
			body: JSON.stringify({ publishable_key: '' }),
		});
	});

	await page.route('**/api/stripe/default-payment-method', async (route: Route) => {
		await route.fulfill({
			status: 200,
			contentType: 'application/json',
			body: JSON.stringify({ card: null }),
		});
	});

	await page.route('**/api/wallet/balance', async (route: Route) => {
		await route.fulfill({
			status: 200,
			contentType: 'application/json',
			body: JSON.stringify({ balance: 0 }),
		});
	});

	return {
		getCounts: () => ({
			csrfRequestCount,
			firstStepRequestCount,
			secondStepRequestCount,
			createdOrderId,
		}),
		setCartPayload: (payload: Record<string, unknown>) => {
			cartPayload = payload;
		},
		setSessionPayload: (payload: Record<string, any>) => {
			sessionPayload = payload;
		},
	};
};

const advanceQuoteToServicesStep = async (
	page: Page,
	mockState: { getCounts: () => { csrfRequestCount: number; firstStepRequestCount: number } },
) => {
	await page.goto('/');
	await page.waitForLoadState('networkidle');

	await fillLocationField(page, '#origin_city', 'Roma');
	await fillLocationField(page, '#destination_city', 'Milano');

	await page.getByRole('button', { name: /Pacco/i }).first().click();
	await page.waitForTimeout(500);

	await page.locator('#weight_0').fill('5');
	await page.locator('#first_size_0').fill('30');
	await page.locator('#second_size_0').fill('20');
	await page.locator('#third_size_0').fill('15');
	await page.locator('#third_size_0').blur();
	await page.waitForTimeout(300);

	const continuaBtn = page
		.getByRole('button', { name: /Calcola il prezzo|Calcola e scegli servizio|Vai ai servizi|Continua/i })
		.first();
	await expect(continuaBtn).toBeVisible();
	await continuaBtn.click();

	await expect.poll(() => mockState.getCounts().csrfRequestCount).toBe(1);
	await expect.poll(() => mockState.getCounts().firstStepRequestCount).toBe(1);
	await page.waitForURL(/\/la-tua-spedizione\/2/, { timeout: 10000 });
	await expect(page).toHaveURL(/\/la-tua-spedizione\/2/);
};

const advanceColliToServicesStep = async (page: Page) => {
	await page.getByRole('button', { name: /^Conferma colli e prosegui ai servizi$/i }).click();
	await expect(page.locator('[data-accordion-trigger="services"]')).toHaveAttribute('aria-expanded', 'true');
};

const advanceServicesToAddresses = async (
	page: Page,
	mockState: { getCounts: () => { secondStepRequestCount: number } },
) => {
	await page.locator('#content_description').fill('Documenti e accessori');
	await page.locator('[data-pickup-day]').first().click();
	await page.getByRole('button', { name: /^Conferma servizi e prosegui agli indirizzi$/i }).click();

	await expect.poll(() => mockState.getCounts().secondStepRequestCount).toBe(1);
	await expect(page).toHaveURL(/step=indirizzi/, { timeout: 10000 });
};

const fillAddressStep = async (page: Page) => {
	const originCard = page.locator('.address-entry-card').first();
	const destinationCard = page.locator('.address-entry-card').nth(1);

	await originCard.locator('#first_name').fill('Mario');
	await originCard.locator('#last_name').fill('Rossi');
	await originCard.locator('#telephone').fill('3331234567');
	await originCard.locator('#address').fill('Via Appia');
	await originCard.locator('#address_number').fill('12');
	await originCard.locator('#city').fill('Roma');
	await originCard.locator('#province').fill('RM');
	await originCard.locator('#postal_code').fill('00118');
	await originCard.locator('#email').fill('mario.rossi@example.com');

	await destinationCard.locator('#dest_first_name').fill('Giulia');
	await destinationCard.locator('#dest_last_name').fill('Bianchi');
	await destinationCard.locator('#dest_telephone').fill('3339876543');
	await destinationCard.locator('#dest_address').fill('Via Torino');
	await destinationCard.locator('#dest_address_number').fill('7');
	await destinationCard.locator('#dest_city').fill('Milano');
	await destinationCard.locator('#dest_province').fill('MI');
	await destinationCard.locator('#dest_postal_code').fill('20121');
	await destinationCard.locator('#dest_email').fill('giulia.bianchi@example.com');
	await destinationCard.locator('#dest_postal_code').blur();
	await page.waitForTimeout(80);
};

test.describe('Flusso Spedizione', () => {
	test('T3.1 - pagina preventivo carica correttamente', async ({ page }) => {
		const response = await page.goto('/preventivo');
		expect(response?.status()).toBeLessThan(400);

		await page.waitForLoadState('networkidle');

		// /preventivo è compat route: deve puntare al funnel canonico
		await expect(page).toHaveURL(/\/la-tua-spedizione\/2(?:\?step=colli)?/, { timeout: 10000 });
		await expect(page.locator('h1', { hasText: 'Preventivo' })).toBeVisible({ timeout: 10000 });
	});

	test('T3.2 - step 1 ha tutti i campi necessari', async ({ page }) => {
		await page.goto('/');
		await page.waitForLoadState('networkidle');

		// Address fields
		await expect(page.locator('#origin_city')).toBeVisible();
		await expect(page.locator('#destination_city')).toBeVisible();
		await expect(page.locator('#origin_country_code')).toBeVisible();
		await expect(page.locator('#destination_country_code')).toBeVisible();

		// Package type selector
		await expect(page.getByRole('button', { name: /^Pacco$/ })).toBeVisible();
		await expect(page.getByRole('button', { name: /^Pallet$/ })).toBeVisible();
		await expect(page.getByRole('button', { name: /^Valigia$/ })).toBeVisible();
	});

	test('T3.2.11 - accesso diretto step 3 senza step 2 redirige', async ({ page }) => {
		await page.goto('/la-tua-spedizione/3');
		await page.waitForLoadState('networkidle');
		await expect(page).toHaveURL(/\/la-tua-spedizione\/2\?step=colli/, { timeout: 10000 });
	});

	test('T3.3 - pagina servizi step 2 non accessibile direttamente', async ({ page }) => {
		await page.goto('/la-tua-spedizione/2');
		await page.waitForLoadState('networkidle');
		await expect(page).toHaveURL(/\/la-tua-spedizione\/2\?step=colli/, { timeout: 10000 });
	});

	test('T3.4 - pagina riepilogo carica', async ({ page }) => {
		const response = await page.goto('/riepilogo');
		await page.waitForLoadState('networkidle');

		// Page should load without server errors
		expect(response?.status()).toBeLessThan(500);
		await expect(page).toHaveURL(/\/la-tua-spedizione\/2\?step=colli/, { timeout: 10000 });
	});

	test('T3.5 - flusso completo step 1 compilazione form', async ({ page }) => {
		await page.goto('/');
		await page.waitForLoadState('networkidle');

		await fillLocationField(page, '#origin_city', 'Roma');
		await fillLocationField(page, '#destination_city', 'Milano');

		// Add a package
		await page.getByRole('button', { name: /^Pacco$/ }).click();
		await page.waitForTimeout(500);

		// Fill dimensions
		await page.locator('#weight_0').fill('5');
		await page.locator('#first_size_0').fill('30');
		await page.locator('#second_size_0').fill('20');
		await page.locator('#third_size_0').fill('15');

		// All fields should be filled
		await expect(page.locator('#origin_city')).not.toHaveValue('');
		await expect(page.locator('#destination_city')).not.toHaveValue('');
		await expect(page.locator('#weight_0')).toHaveValue('5');
		await expect(page.locator('#first_size_0')).toHaveValue('30');
		await expect(page.locator('#second_size_0')).toHaveValue('20');
		await expect(page.locator('#third_size_0')).toHaveValue('15');
	});

	test('T3.6 - click Continua con form completo (richiede backend)', async ({ page }) => {
		const mockState = await installShipmentFlowMocks(page);
		await advanceQuoteToServicesStep(page, mockState);
	});

	test('T3.6.1 - step 2 apre davvero il pannello indirizzi a fisarmonica', async ({ page }) => {
		const mockState = await installShipmentFlowMocks(page);
		await advanceQuoteToServicesStep(page, mockState);
		const projectName = test.info().project.name;

		const colliTrigger = page.locator('[data-accordion-trigger="packages"]');
		const servicesTrigger = page.locator('[data-accordion-trigger="services"]');
		const addressesTrigger = page.locator('[data-accordion-trigger="addresses"]');

		await expect(colliTrigger).toHaveAttribute('aria-expanded', 'true');
		await expect(servicesTrigger).toHaveAttribute('aria-expanded', 'false');
		await expect(addressesTrigger).toHaveAttribute('aria-expanded', 'false');

		await advanceColliToServicesStep(page);

		await expect(colliTrigger).toHaveAttribute('aria-expanded', 'false');
		await expect(servicesTrigger).toHaveAttribute('aria-expanded', 'true');
		await expect(addressesTrigger).toHaveAttribute('aria-expanded', 'false');
		await page.screenshot({
			path: 'output/playwright/shipment-step2-accordion-preview-20260404.png',
			fullPage: true,
		});
		await page.screenshot({
			path: `output/codex/wizard-5-3-2026-04-15/wizard-step2-${projectName}-viewport.png`,
		});

		await advanceServicesToAddresses(page, mockState);

		await expect(servicesTrigger).toHaveAttribute('aria-expanded', 'false');
		await expect(addressesTrigger).toHaveAttribute('aria-expanded', 'true');
		await expect(page.locator('#first_name')).toBeVisible();
		await expect(page.locator('#last_name')).toBeVisible();
		await page.screenshot({
			path: 'output/playwright/shipment-step3-accordion-preview-20260404.png',
			fullPage: true,
		});
		await page.screenshot({
			path: `output/codex/wizard-5-3-2026-04-15/wizard-step3-${projectName}-viewport.png`,
		});
	});

	test('T3.6.2 - il funnel apre direttamente Pagamento nello stesso ventaglio senza step Conferma', async ({ page }) => {
		test.setTimeout(45000);
		await ensureAuthenticatedShipmentUser(page);
		const mockState = await installShipmentFlowMocks(page);
		await advanceQuoteToServicesStep(page, mockState);
		const projectName = test.info().project.name;
		const addressesTrigger = page.locator('[data-accordion-trigger="addresses"]');

		await expect(page.locator('div.fixed.bottom-0.left-0.right-0')).toHaveCount(0);
		await expect(page.getByRole('button', { name: /^Conferma colli e prosegui ai servizi$/i })).toBeVisible();
		await expect(page.getByRole('heading', { name: /^Conferma$/i })).toHaveCount(0);
		await expect(page.getByText(/Metodo di pagamento/i)).toHaveCount(0);
		await expect(page.getByText(/Riepilogo ordine/i)).toHaveCount(0);
		await expect(page.getByText(/Totale da pagare/i)).toHaveCount(0);

		await advanceColliToServicesStep(page);

		await expect(page.getByRole('button', { name: /^Conferma servizi e prosegui agli indirizzi$/i })).toBeVisible();
		await expect(page.getByRole('heading', { name: /^Conferma$/i })).toHaveCount(0);
		await expect(page.getByText(/Metodo di pagamento/i)).toHaveCount(0);
		await expect(page.getByText(/Riepilogo ordine/i)).toHaveCount(0);
		await expect(page.getByText(/Totale da pagare/i)).toHaveCount(0);

		await advanceServicesToAddresses(page, mockState);
		await expect(addressesTrigger).toHaveAttribute('aria-expanded', 'true');
		await expect(page.getByRole('button', { name: /^Vai al pagamento$/i })).toBeVisible();

		await fillAddressStep(page);

		await page.getByRole('button', { name: /^Vai al pagamento$/i }).click();
		await expect(page).toHaveURL(/step=pagamento/, { timeout: 10000 });
		await expect(page).not.toHaveURL(/\/riepilogo/, { timeout: 10000 });
		await expect(page.locator('[data-accordion-trigger="payment"]')).toHaveAttribute('aria-expanded', 'true');
		await expect(page.getByText(/Riepilogo ordine/i)).toBeVisible();
		await expect(page.getByText(/Metodo di pagamento/i)).toBeVisible();
		await expect(page.getByText(/Documento fiscale/i)).toBeVisible();
		await expect(page.locator('div.fixed.bottom-0.left-0.right-0')).toHaveCount(0);
		const acceptCookiesButton = page.getByRole('button', { name: /Accetta tutti/i });
		if (await acceptCookiesButton.isVisible().catch(() => false)) {
			await acceptCookiesButton.click();
		}
		await page.waitForTimeout(5200);
		await page.screenshot({
			path: `output/codex/wizard-5-9-2026-04-17/wizard-payment-${projectName}.png`,
			fullPage: true,
		});
	});

	test('T3.6.3 - step indirizzi usa nome completo e card piu ordinate con riepilogo reale', async ({ page }) => {
		const mockState = await installShipmentFlowMocks(page);
		await advanceQuoteToServicesStep(page, mockState);
		await advanceColliToServicesStep(page);

		await advanceServicesToAddresses(page, mockState);

		await expect(page.locator('#first_name')).toBeVisible();
		await expect(page.locator('#last_name')).toBeVisible();
		await expect(page.locator('#telephone')).toBeVisible();
		await expect(page.locator('#dest_first_name')).toBeVisible();
		await expect(page.locator('#dest_last_name')).toBeVisible();
		await expect(page.locator('.address-entry-card').first()).toContainText(/Partenza/i);
		await expect(page.locator('.address-entry-card').first()).toContainText(/Nome/i);
		await expect(page.locator('.address-entry-card').first()).toContainText(/Cognome/i);
		await expect(page.locator('.address-entry-card').nth(1)).toContainText(/Destinazione/i);

		await fillAddressStep(page);
		await expect(page.locator('div.fixed.bottom-0.left-0.right-0')).toHaveCount(0);
		await expect(page.getByRole('button', { name: /^Vai al pagamento$/i })).toBeVisible();
	});

	test('T3.6.4 - step indirizzi spiega il telefono e tiene i dettagli aggiuntivi secondari', async ({ page }) => {
		const mockState = await installShipmentFlowMocks(page);
		await advanceQuoteToServicesStep(page, mockState);
		await advanceColliToServicesStep(page);

		await advanceServicesToAddresses(page, mockState);

		await expect(page.locator('#telephone')).toBeVisible();
		await expect(page.locator('#dest_telephone')).toBeVisible();
		await expect(page.locator('#additional_info')).toBeVisible();
		await expect(page.locator('#intercom')).toBeVisible();
		await expect(page.locator('#email')).toBeVisible();
		await expect(page.locator('#additional_info')).toHaveAttribute('placeholder', /Presso|Ragione sociale|Azienda/i);
		await expect(page.getByPlaceholder('Scala A, int. 4').first()).toBeVisible();
	});

	test.describe('Flusso Spedizione - pagamento nello stesso ventaglio', () => {
		if (accountStorageState) {
			test.use({ storageState: accountStorageState });
		}

		test('T3.6.5 - il pagamento si apre su /la-tua-spedizione/2?step=pagamento senza passare da /checkout', async ({ page }) => {
			test.setTimeout(45000);
			await ensureAuthenticatedShipmentUser(page);
			const orderId = 987;
			const mockState = await installShipmentFlowMocks(page);
			await advanceQuoteToServicesStep(page, mockState);
			await advanceColliToServicesStep(page);
			await advanceServicesToAddresses(page, mockState);
			await fillAddressStep(page);

			await expect(page.getByRole('button', { name: /^Vai al pagamento$/i })).toBeVisible();
			await page.getByRole('button', { name: /^Vai al pagamento$/i }).click();
			await expect(page).toHaveURL(/step=pagamento/, { timeout: 10000 });
			await expect(page).not.toHaveURL(/\/checkout/, { timeout: 10000 });
			await expect(page).toHaveURL(new RegExp(`step=pagamento.*order_id=${orderId}`), { timeout: 10000 });
			await expect(page).not.toHaveURL(/\/checkout/, { timeout: 10000 });
			await expect.poll(() => mockState.getCounts().createdOrderId).toBe(orderId);
			await expect(page.locator('[data-accordion-trigger="payment"]')).toHaveAttribute('aria-expanded', 'true');
			await page.locator('[data-accordion-trigger="payment"]').scrollIntoViewIfNeeded();
			await expect(page.getByText(/Riepilogo ordine/i)).toBeVisible();
			await expect(page.getByText(/Metodo di pagamento/i)).toBeVisible({ timeout: 30000 });
			await expect(page.getByText('Documento fiscale', { exact: true })).toBeVisible({ timeout: 30000 });
			await expect(page.getByText(/Codice promozionale/i)).toBeVisible({ timeout: 30000 });
			await expect(page.locator('.checkout-payment-footer__summary-label')).toContainText(/Totale da pagare/i);
			await expect(page.locator('div.fixed.bottom-0.left-0.right-0')).toHaveCount(0);
			await page.screenshot({
				path: 'output/codex/wizard-5-7-2026-04-17/wizard-payment-desktop.png',
				fullPage: true,
			});
		});
	});

	test('T3.7 - homepage naviga correttamente al preventivo', async ({ page }) => {
		await page.goto('/');
		await page.waitForLoadState('networkidle');

		// Homepage has the Preventivo component embedded
		await expect(page.locator('h2', { hasText: 'Preventivo Rapido' })).toBeVisible({ timeout: 10000 });

		// Fill some data and verify interaction works on homepage too
		await page.locator('#origin_city').fill('Torino');
		await expect(page.locator('#origin_city')).toHaveValue('Torino');
	});
});

