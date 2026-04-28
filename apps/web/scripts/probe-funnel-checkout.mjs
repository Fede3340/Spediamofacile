import { chromium } from 'playwright';
import { existsSync, mkdirSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const baseUrl = process.env.PROBE_BASE_URL || 'http://127.0.0.1:8787';
const paymentMethod = (process.env.PROBE_PAYMENT_METHOD || 'bonifico').toLowerCase();
const entryMode = (process.env.PROBE_ENTRY_MODE || 'home').toLowerCase();
const scriptDir = dirname(fileURLToPath(import.meta.url));
const nuxtRoot = resolve(scriptDir, '..');
const workspaceRoot = resolve(nuxtRoot, '..');
const screenshotPath = resolve(workspaceRoot, '_LOG', `probe-funnel-${paymentMethod}.png`);
const adminScreenshotPath = resolve(workspaceRoot, '_LOG', `probe-funnel-${paymentMethod}-admin.png`);
const adminStorageStatePath = resolve(nuxtRoot, 'output', 'playwright', 'auth', 'admin.json');

const customer = {
	email: process.env.PROBE_EMAIL || 'cliente@spediamofacile.it',
	password: process.env.PROBE_PASSWORD || 'Cliente2026!',
};

const admin = {
	email: process.env.PROBE_ADMIN_EMAIL || 'admin@spediamofacile.it',
	password: process.env.PROBE_ADMIN_PASSWORD || 'Admin2026!',
};

const shipment = {
	weight: '2',
	length: '30',
	width: '20',
	height: '10',
	content: 'Abbigliamento',
	origin: {
		firstName: 'Mario',
		lastName: 'Rossi',
		address: 'Via Roma',
		addressNumber: '10',
		postalCode: '00119',
		city: 'Roma',
		province: 'RM',
		telephone: '+393331234567',
		email: 'mario@example.com',
	},
	destination: {
		firstName: 'Luigi',
		lastName: 'Bianchi',
		address: 'Via Garibaldi',
		addressNumber: '20',
		postalCode: '09016',
		city: 'Iglesias',
		province: 'SU',
		telephone: '+393339876543',
		email: 'luigi@example.com',
	},
};

const selectors = {
	auth: {
		email: '#auth-modal-email',
		password: '#auth-modal-password',
	},
	home: {
		root: '#preventivo',
		originCountry: '#origin_country_code',
		originQuery: '#origin_city',
		destinationCountry: '#destination_country_code',
		destinationQuery: '#destination_city',
		weight: '#weight_0',
		length: '#first_size_0',
		width: '#second_size_0',
		height: '#third_size_0',
		continue: '#preventivo .continue-cta-button',
		locationSuggestion: '.location-suggestion',
	},
	step1: {
		weight: '#package-weight-0',
		length: '#package-first_size-0',
		width: '#package-second_size-0',
		height: '#package-third_size-0',
		continue: 'button[aria-label="Conferma colli e prosegui ai servizi"]',
	},
	step2: {
		content: '#content_description',
		continue: 'button[aria-label="Conferma servizi e prosegui agli indirizzi"]',
	},
	step3: {
		continue: 'button[aria-label="Vai al pagamento"]',
		origin: {
			firstName: '#first_name',
			lastName: '#last_name',
			address: '#address',
			addressNumber: '#address_number',
			postalCode: '#postal_code',
			city: '#city',
			province: '#province',
			telephone: '#telephone',
			email: '#email',
		},
		destination: {
			firstName: '#dest_first_name',
			lastName: '#dest_last_name',
			address: '#dest_address',
			addressNumber: '#dest_address_number',
			postalCode: '#dest_postal_code',
			city: '#dest_city',
			province: '#dest_province',
			telephone: '#dest_telephone',
			email: '#dest_email',
		},
	},
	step4: {
		accordion: 'button[data-accordion-trigger="payment"]',
		summaryToggle: '.payment-summary-card__toggle',
		methodGrid: '.checkout-payment-options-grid--final',
		cardOption: '.checkout-payment-options-grid--final .checkout-payment-option:has-text("Carta")',
		bonificoOption: '.checkout-payment-options-grid--final .checkout-payment-option:has-text("Bonifico")',
		walletOption: '.checkout-payment-options-grid--final .checkout-payment-option:has-text("Wallet")',
		cardFrame: 'iframe[title="Casella di inserimento sicuro pagamento con carta"]',
		terms: '.checkout-payment-footer__checkbox',
		submit: '.checkout-payment-submit',
		successTitle: '.checkout-success__title',
		accountListLink: '/account/spedizioni',
	},
	account: {
		search: 'input[placeholder="Cerca riferimento, tracking, mittente o destinatario..."]',
	},
	admin: {
		ordersSearch: 'input[placeholder="Cerca codice ordine, email o nome cliente"]',
		shipmentsSearch: 'input[placeholder="Cerca per utente, Parcel ID, tratta..."]',
	},
};

const relevantResponses = [];

const rememberResponse = (label, status, url) => {
	relevantResponses.push({
		label,
		status,
		url,
		at: new Date().toISOString(),
	});

	if (relevantResponses.length > 40) {
		relevantResponses.splice(0, relevantResponses.length - 40);
	}
};

const summarizeBody = (text) => (
	String(text || '')
		.replace(/\s+/g, ' ')
		.slice(0, 1200)
);

const extractOrderIdFromUrl = (currentUrl) => {
	try {
		const parsed = new URL(currentUrl);
		const value = parsed.searchParams.get('order_id');
		return value ? String(value).trim() : '';
	} catch {
		return '';
	}
};

const fillLocationField = async (page, selector, value, suggestionSelector) => {
	await page.locator(selector).fill(value);
	await page.waitForTimeout(900);
	const suggestion = page.locator(suggestionSelector).first();
	if (await suggestion.count()) {
		await suggestion.click({ timeout: 1500 }).catch(() => {});
	}
	await page.locator(selector).blur().catch(() => {});
	await page.waitForTimeout(250);
};

const ensureAuthSession = async (page, { email, password, redirect = '/' }) => {
	await page.goto(`${baseUrl}/?auth_modal=login&redirect=${encodeURIComponent(redirect)}`, {
		waitUntil: 'domcontentloaded',
		timeout: 120000,
	});
	await page.waitForLoadState('networkidle');

	const acceptCookies = page.getByRole('button', { name: /Accetta tutti/i });
	if (await acceptCookies.count()) {
		await acceptCookies.first().click({ timeout: 1000 }).catch(() => {});
	}

	await page.locator(selectors.auth.email).fill(email);
	await page.locator(selectors.auth.password).fill(password);
	await page
		.locator(selectors.auth.password)
		.locator('xpath=ancestor::form')
		.getByRole('button', { name: /^Accedi$/ })
		.click();
	await page.waitForTimeout(2500);
};

const fillAddress = async (page, fieldMap, values) => {
	for (const [key, selector] of Object.entries(fieldMap)) {
		await page.locator(selector).fill(values[key]);
		if (key === 'city') {
			await page.waitForTimeout(700);
			const suggestion = page.locator('.address-field-menu__item').first();
			if (await suggestion.count()) {
				await suggestion.click({ timeout: 1000 }).catch(() => {});
			}
		}
	}
};

const logStep = (label, value) => {
	console.log(`[probe] ${label}:`, value);
};

mkdirSync(dirname(screenshotPath), { recursive: true });

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage({ viewport: { width: 1440, height: 1400 } });

try {
	page.on('console', (msg) => console.log(`[browser:${msg.type()}]`, msg.text()));
	page.on('pageerror', (error) => console.log('[pageerror]', error.message));
	page.on('response', (response) => {
		const url = response.url();
		if (
			url.includes('/api/session/first-step')
			|| url.includes('/api/create-direct-order')
			|| url.includes('/api/cart/')
			|| url.includes('/api/stripe/existing-order-payment-intent')
			|| url.includes('/api/stripe/create-payment-intent')
			|| url.includes('/api/stripe/mark-order-completed')
			|| url.includes('/api/stripe/order-paid')
			|| url.includes('/api/stripe/existing-order-paid')
			|| response.status() >= 400
		) {
			rememberResponse('response', response.status(), url);
			console.log(`[response:${response.status()}] ${url}`);
		}
	});

	const loginRedirect = entryMode === 'home' ? '/' : '/preventivo';
	await ensureAuthSession(page, { ...customer, redirect: loginRedirect });
	logStep('after-login-url', page.url());

	if (entryMode === 'home') {
		await page.goto(`${baseUrl}/`, { waitUntil: 'domcontentloaded', timeout: 120000 });
		await page.waitForLoadState('networkidle');
		await page.locator(selectors.home.root).waitFor({ state: 'visible', timeout: 30000 });

		await fillLocationField(page, selectors.home.originQuery, 'Roma', selectors.home.locationSuggestion);
		await fillLocationField(page, selectors.home.destinationQuery, 'Iglesias', selectors.home.locationSuggestion);
		await page.fill(selectors.home.weight, shipment.weight);
		await page.fill(selectors.home.length, shipment.length);
		await page.fill(selectors.home.width, shipment.width);
		await page.fill(selectors.home.height, shipment.height);
		await page.locator(selectors.home.continue).click();
		await page.waitForURL(/\/la-tua-spedizione\/2/, { timeout: 20000 });
		logStep('after-home-quick-quote-url', page.url());

		if (!/step=servizi/i.test(page.url())) {
			const servicesAccordion = page.locator('button[data-accordion-trigger="services"]').first();
			if (await servicesAccordion.count()) {
				await servicesAccordion.click().catch(() => {});
				await page.waitForTimeout(400);
			}
		}
	} else {
		await page.fill(selectors.step1.weight, shipment.weight);
		await page.fill(selectors.step1.length, shipment.length);
		await page.fill(selectors.step1.width, shipment.width);
		await page.fill(selectors.step1.height, shipment.height);
		await page.locator(selectors.step1.continue).click();
		await page.waitForURL(/step=servizi/, { timeout: 10000 });
		logStep('step2-url', page.url());
	}

	await page.fill(selectors.step2.content, shipment.content);
	await page.locator(selectors.step2.continue).click();
	await page.waitForURL(/step=indirizzi/, { timeout: 15000 });
	logStep('step3-url', page.url());

	await fillAddress(page, selectors.step3.origin, shipment.origin);
	await fillAddress(page, selectors.step3.destination, shipment.destination);
	logStep('step3-before-confirm-url', page.url());
	await page.locator(selectors.step3.continue).click();
	logStep('step3-confirm-button-text', await page.locator(selectors.step3.continue).textContent().catch(() => 'n/a'));
	try {
		await page.waitForURL(/step=pagamento/, { timeout: 20000 });
	} catch (error) {
		const bodyText = await page.locator('body').innerText().catch(() => '');
		logStep('step3-post-click-body', summarizeBody(bodyText));
		logStep('relevant-responses', JSON.stringify(relevantResponses.slice(-12), null, 2));
		if (/troppi tentativi|numero massimo di tentativi|riprova tra|too many requests/i.test(bodyText)) {
			throw new Error('Indirizzi bloccati da rate limit/autocomplete. Attendi il cooldown e rilancia il probe.');
		}
		throw error;
	}
	await page.waitForTimeout(1500);
	logStep('step4-url', page.url());
	logStep('step4-body', summarizeBody(await page.locator('body').innerText().catch(() => '')));
	logStep('relevant-responses', JSON.stringify(relevantResponses.slice(-12), null, 2));
	const orderId = extractOrderIdFromUrl(page.url());
	logStep('order-id-from-url', orderId || 'n/a');

	const summaryToggle = page.locator(selectors.step4.summaryToggle).first();
	if (await summaryToggle.count()) {
		const toggleText = await summaryToggle.textContent().catch(() => '');
		if (/vedi dettagli ordine/i.test(String(toggleText || ''))) {
			await summaryToggle.click();
			await page.waitForTimeout(500);
		}
	}

	const paymentAccordion = page.locator(selectors.step4.accordion);
	if ((await paymentAccordion.getAttribute('aria-expanded')) !== 'true') {
		await paymentAccordion.click();
		await page.waitForTimeout(500);
	}

	await page.locator(selectors.step4.methodGrid).first().waitFor({ state: 'visible', timeout: 10000 });

	const bonifico = page.locator(selectors.step4.bonificoOption).first();
	const wallet = page.locator(selectors.step4.walletOption).first();
	const carta = page.locator(selectors.step4.cardOption).first();

	if (paymentMethod === 'wallet' && await wallet.count()) {
		await wallet.click();
	} else if (paymentMethod === 'carta' && await carta.count()) {
		await carta.click();
	} else if (await bonifico.count()) {
		await bonifico.click();
	}

	await page.waitForTimeout(700);

	if (paymentMethod === 'carta') {
		const frame = page.frameLocator(selectors.step4.cardFrame);
		await frame.getByPlaceholder('Numero carta').fill('4242424242424242');
		await frame.getByPlaceholder('MM / AA').fill('12 / 34');
		await frame.getByPlaceholder('CVC').fill('123');
	}

	const terms = page.locator(selectors.step4.terms).first();
	if (await terms.count()) {
		await terms.click();
		await page.waitForTimeout(300);
	}

	const submit = page.locator(selectors.step4.submit).first();
	logStep('submit-text', await submit.textContent());
	logStep('submit-disabled', await submit.isDisabled());
	if (await submit.isDisabled()) {
		throw new Error('Il bottone finale di pagamento risulta disabilitato: il funnel non è pronto al submit reale.');
	}

	const paymentResponsePromise = page.waitForResponse((response) => {
		const url = response.url();
		if (paymentMethod === 'bonifico' || paymentMethod === 'wallet') {
			return url.includes('/api/stripe/mark-order-completed');
		}
		return (
			url.includes('/api/stripe/existing-order-payment-intent')
			|| url.includes('/api/stripe/create-payment-intent')
			|| url.includes('/api/stripe/existing-order-paid')
			|| url.includes('/api/stripe/order-paid')
		);
	}, { timeout: 45000 }).catch(() => null);

	await submit.click();
	await page.waitForTimeout(300);

	const confirmDialog = page.getByRole('dialog').filter({ hasText: /conferma|pagamento|ordine/i }).first();
	if (await confirmDialog.count()) {
		const confirmButton = confirmDialog.getByRole('button', { name: /^Conferma$/i }).first();
		if (await confirmButton.count()) {
			await confirmButton.click();
		}
	}

	const paymentResponse = await paymentResponsePromise;
	if (paymentResponse) {
		logStep('payment-submit-response', `${paymentResponse.status()} ${paymentResponse.url()}`);
	}

	if (paymentMethod === 'bonifico' || paymentMethod === 'wallet') {
		await Promise.race([
			page.waitForSelector(selectors.step4.successTitle, { timeout: 45000 }),
			page.waitForURL(/checkout_success=1/, { timeout: 45000 }),
		]);
	} else {
		await Promise.race([
			page.waitForSelector(selectors.step4.successTitle, { timeout: 60000 }),
			page.waitForURL(/checkout_success=1/, { timeout: 60000 }),
		]);
	}

	logStep('post-submit-url', page.url());
	logStep('post-submit-body', summarizeBody(await page.locator('body').innerText().catch(() => '')));

	await page.goto(`${baseUrl}${selectors.step4.accountListLink}`, {
		waitUntil: 'domcontentloaded',
		timeout: 120000,
	});
	await page.waitForLoadState('networkidle');
	logStep('account-spedizioni-url', page.url());
	if (orderId) {
		const accountSearch = page.locator(selectors.account.search).first();
		if (await accountSearch.count()) {
			await accountSearch.fill(`#${orderId}`);
			await page.waitForTimeout(800);
		}
	}
	const accountBody = (await page.locator('body').innerText()).slice(0, 3000).replace(/\n+/g, ' | ');
	logStep('account-spedizioni-body', accountBody);
	if (orderId && !accountBody.includes(`#${orderId}`) && !accountBody.includes(`Account #${orderId}`)) {
		throw new Error(`L'ordine #${orderId} non compare in /account/spedizioni dopo il submit.`);
	}

	if (orderId) {
		await page.goto(`${baseUrl}/account/spedizioni/${orderId}`, {
			waitUntil: 'domcontentloaded',
			timeout: 120000,
		});
		await page.waitForLoadState('networkidle');
		logStep('account-spedizione-detail-url', page.url());
		logStep('account-spedizione-detail-body', summarizeBody(await page.locator('body').innerText().catch(() => '')));
	}

	await page.screenshot({ path: screenshotPath, fullPage: true });
	logStep('screenshot', screenshotPath);

	if (orderId && existsSync(adminStorageStatePath)) {
		const createAdminContext = async (useStoredState = true) => browser.newContext({
			viewport: { width: 1440, height: 1400 },
			...(useStoredState ? { storageState: adminStorageStatePath } : {}),
		});

		let adminContext = await createAdminContext(true);
		let adminPage = await adminContext.newPage();
		adminPage.on('console', (msg) => console.log(`[admin-browser:${msg.type()}]`, msg.text()));

		await adminPage.goto(`${baseUrl}/account/amministrazione/ordini`, {
			waitUntil: 'domcontentloaded',
			timeout: 120000,
		});
		await adminPage.waitForLoadState('networkidle');
		const adminOrdersInitialBody = summarizeBody(await adminPage.locator('body').innerText().catch(() => ''));
		if (
			adminPage.url() === `${baseUrl}/`
			|| /accedi|calcola preventivo|preventivo rapido/i.test(adminOrdersInitialBody)
		) {
			await adminContext.close();
			adminContext = await createAdminContext(false);
			adminPage = await adminContext.newPage();
			adminPage.on('console', (msg) => console.log(`[admin-browser:${msg.type()}]`, msg.text()));

			await ensureAuthSession(adminPage, {
				...admin,
				redirect: '/account/amministrazione/ordini',
			});
			await adminPage.goto(`${baseUrl}/account/amministrazione/ordini`, {
				waitUntil: 'domcontentloaded',
				timeout: 120000,
			});
			await adminPage.waitForLoadState('networkidle');
		}
		const adminOrdersResolvedBody = summarizeBody(await adminPage.locator('body').innerText().catch(() => ''));
		if (
			adminPage.url().includes('auth_modal=login')
			|| adminPage.url() === `${baseUrl}/`
			|| /accedi|calcola preventivo|preventivo rapido/i.test(adminOrdersResolvedBody)
		) {
			throw new Error('La verifica admin non è autenticata: la route ordini ricade ancora sulla home o sull’overlay login.');
		}
		const adminOrdersSearch = adminPage.locator(selectors.admin.ordersSearch).first();
		if (await adminOrdersSearch.count()) {
			await adminOrdersSearch.fill(String(orderId));
			await adminPage.waitForTimeout(1200);
		}
		const adminOrdersBody = (await adminPage.locator('body').innerText()).slice(0, 3000).replace(/\n+/g, ' | ');
		logStep('admin-ordini-body', adminOrdersBody);
		if (!adminOrdersBody.includes(`#${orderId}`) && !adminOrdersBody.includes(String(orderId))) {
			throw new Error(`L'ordine #${orderId} non compare in /account/amministrazione/ordini.`);
		}

		await adminPage.goto(`${baseUrl}/account/amministrazione/spedizioni`, {
			waitUntil: 'domcontentloaded',
			timeout: 120000,
		});
		await adminPage.waitForLoadState('networkidle');
		const adminShipmentsSearch = adminPage.locator(selectors.admin.shipmentsSearch).first();
		if (await adminShipmentsSearch.count()) {
			await adminShipmentsSearch.fill(String(orderId));
			await adminPage.waitForTimeout(1200);
		}
		const adminShipmentsBody = summarizeBody(await adminPage.locator('body').innerText().catch(() => ''));
		logStep('admin-spedizioni-body', adminShipmentsBody);
		if (!adminShipmentsBody.includes(`#${orderId}`) && !adminShipmentsBody.includes(String(orderId))) {
			throw new Error(`L'ordine #${orderId} non compare in /account/amministrazione/spedizioni.`);
		}
		await adminPage.screenshot({ path: adminScreenshotPath, fullPage: true });
		logStep('admin-screenshot', adminScreenshotPath);
		await adminContext.close();
	}
} finally {
	await browser.close();
}
