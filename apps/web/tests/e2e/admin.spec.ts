import { test, expect } from '@playwright/test';
import { resolveE2EStorageState } from './utils/authState';

const adminStorageState = resolveE2EStorageState('admin');
const hasAuthStorage = Boolean(adminStorageState);
const appBaseURL = (process.env.PLAYWRIGHT_BASE_URL || process.env.TEST_BASE_URL || 'http://127.0.0.1:8787').replace(/\/+$/, '');

const injectStaleAdminCookies = async (page) => {
	await page.context().clearCookies();
	await page.context().addCookies([
		{
			name: 'sf_auth_ui',
			value: encodeURIComponent(JSON.stringify({
				authenticated: true,
				name: 'Admin',
				surname: 'SpediamoFacile',
				email: 'admin@spediamofacile.it',
				createdAt: '2026-03-24T08:00:00.000000Z',
				userType: 'Privato',
				role: 'Admin',
			})),
			url: appBaseURL,
			sameSite: 'Lax',
		},
		{
			name: 'laravel_session',
			value: 'stale-playwright-session',
			url: appBaseURL,
			httpOnly: true,
			sameSite: 'Lax',
		},
	]);
};

const waitForCustomLoginSuccess = (page) =>
	page.waitForResponse(
		(response) =>
			response.url().includes('/api/custom-login')
			&& response.request().method() === 'POST'
			&& response.status() === 200,
		{ timeout: 30000 },
	);

const ensureOverlayLoginIfNeeded = async (page) => {
	const authEmail = page.locator('#auth-modal-email');
	const overlayVisible = await authEmail
		.waitFor({ state: 'visible', timeout: 5000 })
		.then(() => true)
		.catch(() => false);

	if (!overlayVisible) {
		return;
	}

	await authEmail.fill('admin@spediamofacile.it');
	await page.locator('#auth-modal-password').fill('Admin2026!');

	const submitButton = page
		.locator('#auth-modal-password')
		.locator('xpath=ancestor::form')
		.getByRole('button', { name: /^Accedi$/ });

	const customLoginResponse = waitForCustomLoginSuccess(page);
	await submitButton.click();
	await customLoginResponse;
	await expect(authEmail).toHaveCount(0, { timeout: 30000 });
};

const expectProtectedAdminRouteToOpenOverlay = async (page) => {
	await expect(page.locator('#auth-modal-email')).toBeVisible({ timeout: 30000 });
};

const openProtectedAdminRoute = async (page, path) => {
	await page.goto(path, { waitUntil: 'domcontentloaded' });
};

const expectAuthenticatedAdminPage = async (page, headingName) => {
	await expect(page.locator('#auth-modal-email')).toHaveCount(0);
	await expect(page.locator('main').getByRole('heading', headingName)).toBeVisible({ timeout: 30000 });
};

const protectedAdminRoutes = [
	['T7.0.1', '/account/amministrazione', 'admin dashboard richiede autenticazione'],
	['T7.0.2', '/account/amministrazione/ordini', 'admin ordini richiede autenticazione'],
	['T7.0.3', '/account/amministrazione/utenti', 'admin utenti richiede autenticazione'],
	['T7.0.4', '/account/amministrazione/prezzi', 'admin prezzi richiede autenticazione'],
	['T7.0.8', '/account/amministrazione/impostazioni', 'admin impostazioni richiede autenticazione'],
	['T7.0.9', '/account/amministrazione/spedizioni', 'admin spedizioni richiede autenticazione'],
	['T7.0.14', '/account/amministrazione/servizi', 'admin servizi richiede autenticazione'],
	['T7.0.15', '/account/amministrazione/bonifici', 'admin bonifici richiede autenticazione'],
];

test.describe('Admin - Protezione Route', () => {
	for (const [code, path, label] of protectedAdminRoutes) {
		test(`${code} - ${label}`, async ({ page }) => {
			await openProtectedAdminRoute(page, path);
			await expectProtectedAdminRouteToOpenOverlay(page);
		});
	}

	test('T7.0.15 - sessione admin stantia apre subito overlay senza render console', async ({ page }) => {
		await injectStaleAdminCookies(page);
		await page.goto('/account/amministrazione', { waitUntil: 'domcontentloaded' });
		await expect(page.locator('#auth-modal-email')).toBeVisible({ timeout: 30000 });
		await expect(page.locator('main').getByRole('heading', { name: 'Console amministrazione', exact: true })).toHaveCount(0);
	});
});

test.describe('Admin - Pagine (richiede admin auth)', () => {
	test.describe.configure({ mode: 'serial', timeout: 60000 });

	if (adminStorageState) {
		test.use({ storageState: adminStorageState });
	}

	test.skip(!hasAuthStorage, 'Richiede utente Admin autenticato - attivare con auth setup');

	test('T7.1.1 - dashboard admin mostra statistiche', async ({ page }) => {
		await page.goto('/account/amministrazione');
		await ensureOverlayLoginIfNeeded(page);
		const main = page.locator('main');
		await expectAuthenticatedAdminPage(page, { name: 'Console amministrazione', exact: true });
		await expect(main.getByRole('heading', { name: 'Ordini, ricavi e attivita recente', exact: true })).toBeVisible();
		await expect(main.getByText('Ultimi aggiornamenti', { exact: true })).toBeVisible();
		await expect(main.getByText('Apri subito le aree che usi davvero', { exact: true })).toHaveCount(0);
	});

	test('T7.1.2 - admin puo aprire profilo senza loop auth', async ({ page }) => {
		await page.goto('/account/amministrazione/ordini');
		await ensureOverlayLoginIfNeeded(page);
		await expectAuthenticatedAdminPage(page, { name: 'Ordini', exact: true });

		await page.goto('/account/profilo');
		await ensureOverlayLoginIfNeeded(page);
		await expect(page).not.toHaveURL(/auth_modal=login/);
		await expectAuthenticatedAdminPage(page, { name: /il mio profilo/i });
	});

	test('T7.1.3 - /account admin reindirizza alla console canonica e mantiene sidebar operativa', async ({ page }) => {
		await page.setViewportSize({ width: 1440, height: 1100 });
		await page.goto('/account');
		await ensureOverlayLoginIfNeeded(page);
		await expect(page.locator('#auth-modal-email')).toHaveCount(0);
		await expect(page).toHaveURL(/\/account\/amministrazione(?:\?.*)?$/);

		const sidebar = page.locator('.account-route-shell__sidebar');
		await expect(sidebar).toBeVisible();
		await expect(sidebar.getByText('Operativo')).toBeVisible();
		await expect(sidebar.getByRole('link', { name: 'Ordini' })).toBeVisible();
		await expect(sidebar.getByRole('link', { name: 'Spedizioni' })).toBeVisible();
		await expect(sidebar.getByRole('link', { name: 'Bonifici' })).toBeVisible();
		await expect(sidebar.getByText('Clienti')).toBeVisible();
		await expect(sidebar.getByRole('link', { name: 'Utenti', exact: true })).toBeVisible();
		await expect(sidebar.getByRole('link', { name: 'Prezzi', exact: true })).toBeVisible();
		await expect(sidebar.getByRole('link', { name: 'Servizi', exact: true })).toBeVisible();
		await expect(sidebar.getByRole('link', { name: 'Impostazioni', exact: true })).toBeVisible();
	});

	test('T7.2.1 - lista ordini admin paginata', async ({ page }) => {
		await page.goto('/account/amministrazione/ordini');
		await ensureOverlayLoginIfNeeded(page);
		const main = page.locator('main');
		await expectAuthenticatedAdminPage(page, { name: 'Ordini', exact: true });
		await expect(main.getByPlaceholder('Cerca codice ordine, email o nome cliente')).toBeVisible();
		await expect(main.getByRole('button', { name: 'Esporta CSV' })).toBeVisible();
		await expect(main.getByLabel('Indicatori chiave ordini')).toBeVisible();
		await expect(main.getByText('BRT', { exact: true })).toHaveCount(0);
	});

	test('T7.2.2 - lista spedizioni admin resta focalizzata su BRT', async ({ page }) => {
		await page.goto('/account/amministrazione/spedizioni');
		await ensureOverlayLoginIfNeeded(page);
		const main = page.locator('main');
		await expectAuthenticatedAdminPage(page, { name: 'Spedizioni', exact: true });
		await expect(main.getByRole('heading', { name: 'Coda spedizioni BRT' })).toBeVisible();
		await expect(main.getByRole('heading', { name: 'Lista spedizioni BRT' })).toBeVisible();
		await expect(main.getByPlaceholder('Cerca per utente, Parcel ID, tratta...')).toBeVisible();
		await expect(main.getByText('Corrieri', { exact: true })).toHaveCount(0);
	});

	test('T7.3.1 - lista utenti admin', async ({ page }) => {
		await page.goto('/account/amministrazione/utenti');
		await ensureOverlayLoginIfNeeded(page);
		const main = page.locator('main');
		await expectAuthenticatedAdminPage(page, { name: 'Utenti', exact: true });
		await expect(main.getByPlaceholder('Cerca per nome o email...')).toBeVisible();
		await expect(main.getByText('Totale utenti', { exact: true })).toBeVisible();
		await expect(main.getByRole('tab', { name: /richieste pro/i })).toBeVisible();
		await expect(main.getByRole('button', { name: 'Esporta CSV' })).toBeVisible();
		await expect(main.getByText('Corrieri', { exact: true })).toHaveCount(0);
	});

	test('T7.4.1 - fasce prezzo visibili', async ({ page }) => {
		await page.goto('/account/amministrazione/prezzi');
		await ensureOverlayLoginIfNeeded(page);
		const main = page.locator('main');
		await expectAuthenticatedAdminPage(page, { name: 'Prezzi', exact: true });
		await expect(page.getByRole('button', { name: 'Nazionale' })).toBeVisible();
		await expect(main.getByText('Fasce nazionali', { exact: true })).toBeVisible();
		await expect(main.getByText('Tariffe Europa', { exact: true })).toBeVisible();
		await expect(main.getByRole('heading', { name: 'Fasce peso' })).toBeVisible();
		await expect(main.getByRole('heading', { name: 'Fasce volume' })).toBeVisible();
		await expect(main.getByText('Come funziona il calcolatore', { exact: true })).toHaveCount(0);
		await expect(main.getByText('Invio conferma, Esc annulla', { exact: true })).toHaveCount(0);
	});

	test('T7.6.3 - lista servizi visibile', async ({ page }) => {
		await page.goto('/account/amministrazione/servizi');
		await ensureOverlayLoginIfNeeded(page);
		const main = page.locator('main');
		await expectAuthenticatedAdminPage(page, { name: 'Servizi', exact: true });
		await expect(main.getByRole('heading', { name: 'Catalogo servizi' })).toBeVisible();
		await expect(main.getByRole('link', { name: 'Nuovo servizio', exact: true })).toBeVisible();
	});

	test('T7.6.4 - lista bonifici visibile', async ({ page }) => {
		await page.goto('/account/amministrazione/bonifici');
		await ensureOverlayLoginIfNeeded(page);
		const main = page.locator('main');
		await expectAuthenticatedAdminPage(page, { name: 'Bonifici in attesa', exact: true });
		await expect(main.getByRole('heading', { name: 'In attesa di ricezione' })).toBeVisible();
		await expect(main.getByRole('button', { name: 'Aggiorna', exact: true })).toBeVisible();
	});

	test('T7.7.1 - impostazioni admin visibili', async ({ page }) => {
		await page.goto('/account/amministrazione/impostazioni');
		await ensureOverlayLoginIfNeeded(page);
		const main = page.locator('main');
		await expectAuthenticatedAdminPage(page, { name: 'Impostazioni', exact: true });
		await expect(main.getByRole('heading', { name: 'Configurazione Stripe' })).toBeVisible();
		await expect(main.getByRole('heading', { name: 'Configurazione BRT' })).toBeVisible();
		await expect(main.getByRole('heading', { name: 'Impostazioni generali' })).toBeVisible();
		await expect(main.getByRole('button', { name: 'Salva impostazioni', exact: true })).toBeVisible();
	});

});
