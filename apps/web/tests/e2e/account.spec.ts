import { test, expect } from '@playwright/test';
import { resolveE2EStorageState } from './utils/authState';

const accountStorageState = resolveE2EStorageState('customer');
const hasAuthStorage = Boolean(accountStorageState);

const expectGuestAuthOverlay = async (page) => {
	await expect(page.getByRole('tab', { name: /accedi/i })).toBeVisible({ timeout: 30000 });
	await expect(page.getByRole('tab', { name: /registrati/i })).toBeVisible({ timeout: 30000 });
};

const openProtectedAccountRoute = async (page, path) => {
	await page.goto(path, { waitUntil: 'domcontentloaded' });
};

const expectAuthenticatedAccountPage = async (page, headingName) => {
	await expect(page.locator('#auth-modal-email')).toHaveCount(0);
	await expect(page.locator('main').getByRole('heading', headingName)).toBeVisible({ timeout: 30000 });
};

const protectedAccountRoutes = [
	['T6.1', '/account', 'dashboard richiede autenticazione'],
	['T6.2', '/account/profilo', 'profilo richiede autenticazione'],
	['T6.3', '/account/indirizzi', 'indirizzi richiede autenticazione'],
	['T6.4', '/account/carte', 'carte richiede autenticazione'],
	['T6.5', '/account/portafoglio', 'portafoglio richiede autenticazione'],
	['T6.6', '/account/spedizioni', 'spedizioni richiede autenticazione'],
	['T6.9', '/account/assistenza', 'assistenza richiede autenticazione'],
	['T6.11', '/account/account-pro', 'area partner pro richiede autenticazione'],
];

test.describe('Account - Protezione Route', () => {
	for (const [code, path, label] of protectedAccountRoutes) {
		test(`${code} - ${label}`, async ({ page }) => {
			await openProtectedAccountRoute(page, path);
			await expectGuestAuthOverlay(page);
		});
	}
});

test.describe('Account - Pagine (richiede auth)', () => {
	if (accountStorageState) {
		test.use({ storageState: accountStorageState });
	}

	test.skip(!hasAuthStorage, 'Richiede utente autenticato - attivare con auth setup');

	test('T6.1.1 - dashboard mostra spedizioni, metriche e storico', async ({ page }) => {
		await page.goto('/account');
		const main = page.locator('main');
		await expectAuthenticatedAccountPage(page, { name: 'Il tuo account', exact: true });
		await expect(main.getByRole('heading', { name: /spedizioni attive/i })).toBeVisible();
		await expect(main.getByText(/^spedite$/i).first()).toBeVisible();
		await expect(main.getByRole('heading', { name: /ultime spedizioni/i })).toBeVisible();
	});

	test('T6.1.2 - dashboard link funzionanti', async ({ page }) => {
		await page.goto('/account');
		await expectAuthenticatedAccountPage(page, { name: 'Il tuo account', exact: true });
		await page.locator('main').getByRole('link', { name: /tutte le spedizioni/i }).first().click();
		await expect(page).toHaveURL(/account\/spedizioni/);
		await expectAuthenticatedAccountPage(page, { name: 'Le tue spedizioni', exact: true });
	});

	test('T6.2.1 - profilo mostra dati utente', async ({ page }) => {
		await page.goto('/account/profilo');
		await expectAuthenticatedAccountPage(page, { name: /il mio profilo/i });
		await expect(page.getByText(/^nome \*$/i)).toBeVisible();
		await expect(page.getByText(/^email \*$/i)).toBeVisible();
		await expect(page.getByText(/^telefono$/i)).toBeVisible();
		await expect(page.getByRole('button', { name: /salva modifiche/i })).toBeVisible();
	});

	test('T6.2.2 - controlli edit mode visibili e stabili', async ({ page }) => {
		await page.goto('/account/profilo');
		await expectAuthenticatedAccountPage(page, { name: /il mio profilo/i });
		await expect(page.getByRole('button', { name: /salva modifiche/i })).toBeVisible();
		await page.getByRole('button', { name: /annulla/i }).click();
		await expect(page.locator('main').getByRole('heading', { name: /il mio profilo/i })).toBeVisible();
		await expect(page).toHaveURL(/\/account\/profilo/);
		await expect(page.getByRole('button', { name: /salva modifiche/i })).toBeVisible();
	});

	test('T6.3.1 - lista indirizzi visibile', async ({ page }) => {
		await page.goto('/account/indirizzi');
		await expect(page.locator('#auth-modal-email')).toHaveCount(0);
		const main = page.locator('main');
		await expect(main.getByRole('heading', { name: /i tuoi indirizzi/i })).toBeVisible();
		await expect(main.getByPlaceholder(/cerca per etichetta, nome, citt/i)).toBeVisible();
		await expect(main.getByRole('button', { name: /nuovo indirizzo/i })).toBeVisible();
		await expect(main.getByText(/la tua rubrica/i).first()).toBeVisible();
	});

	test('T6.4.1 - lista carte visibile', async ({ page }) => {
		await page.goto('/account/carte');
		await expect(page.locator('#auth-modal-email')).toHaveCount(0);
		const main = page.locator('main');
		await expect(main.getByRole('heading', { name: /carte e pagamenti/i })).toBeVisible();
		await expect(main.getByRole('heading', { name: /carte salvate e wallet nello stesso punto/i })).toBeVisible();
		await expect(main.getByRole('link', { name: /apri portafoglio/i })).toBeVisible();
		await expect(main.getByText(/nessuna carta salvata|pagamenti con carta non ancora attivi|predefinita/i).first()).toBeVisible();
	});

	test('T6.5.1 - saldo portafoglio visibile', async ({ page }) => {
		await page.goto('/account/portafoglio');
		await expect(page.locator('#auth-modal-email')).toHaveCount(0);
		await expect(page.getByText(/saldo|balance|€/i).first()).toBeVisible();
	});

	test('T6.6.1 - lista spedizioni visibile', async ({ page }) => {
		await page.goto('/account/spedizioni');
		await expectAuthenticatedAccountPage(page, { name: 'Le tue spedizioni', exact: true });
		await expect(page.getByPlaceholder('Cerca riferimento, tracking, mittente o destinatario...')).toBeVisible();
	});

	test('T6.9.1 - assistenza mostra azioni rapide e ticket', async ({ page }) => {
		await page.goto('/account/assistenza');
		await expect(page.locator('#auth-modal-email')).toHaveCount(0);
		const main = page.locator('main');
		await expect(main.getByRole('heading', { name: 'Assistenza', exact: true })).toBeVisible();
		await expect(main.getByRole('heading', { name: /da dove vuoi partire/i })).toBeVisible();
		await expect(main.getByRole('heading', { name: /apri un ticket/i })).toBeVisible();
		await expect(main.getByText(/segui una spedizione/i)).toBeVisible();
		await expect(main.getByRole('button', { name: /invia richiesta/i })).toBeVisible();
	});

	test('T6.12.1 - area partner pro mostra richiesta o dashboard coerente', async ({ page }) => {
		await page.goto('/account/account-pro');
		await expectAuthenticatedAccountPage(page, { name: 'Area Partner Pro', exact: true });
		const main = page.locator('main');
		const content = (await main.textContent()) || '';
		expect(content).toMatch(/Richiedi accesso|Richiesta registrata|Condividi il link/);
		expect(content).toMatch(/Come funziona|Link invito|Storico commissioni/);
	});
});
