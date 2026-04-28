import { test, expect } from '@playwright/test';

test.describe('Autenticazione', () => {
	const waitForAuthOverlay = async (page) => {
		await expect(page.locator('#auth-modal-email, #auth-forgot-email, #auth-reg-name').first()).toBeVisible({
			timeout: 20000,
		});
	};

	test.describe('Login', () => {
		test('T1.1.1 - modal login accessibile', async ({ page }) => {
			await page.goto('/?auth_modal=login');
			await waitForAuthOverlay(page);
			await expect(page.locator('#auth-modal-email')).toBeVisible();
		});

		test('T1.1.1b - route autenticazione reindirizza al solo overlay', async ({ page }) => {
			await page.goto('/autenticazione?tab=login&redirect=/');
			await expect(page).toHaveURL(/\/$/);
			await waitForAuthOverlay(page);
			await expect(page.locator('#auth-modal-email')).toBeVisible();
		});

		test('T1.1.2 - login con credenziali errate mostra errore', async ({ page }) => {
			await page.goto('/?auth_modal=login');
			await waitForAuthOverlay(page);
			await page.locator('#auth-modal-email').fill('nonexistent@test.com');
			await page.locator('#auth-modal-password').fill('wrongpassword');
			await page.locator('#auth-modal-password').locator('xpath=ancestor::form').getByRole('button', { name: /^accedi$/i }).click();
			await expect
				.poll(async () => await page.locator('body').innerText(), { timeout: 20000 })
				.toMatch(/credenziali non sono corrette|email o password|password non valida/i);
		});

		test('T1.1.12 - checkout legacy reindirizza al funnel canonico', async ({ page }) => {
			await page.goto('/checkout');
			await expect(page).toHaveURL(/\/la-tua-spedizione\/2\?step=colli/);
			await expect(page.locator('#auth-modal-email')).toHaveCount(0);
		});
	});

	test.describe('Registrazione', () => {
		test('T1.2.1 - tab registrazione visibile', async ({ page }) => {
			await page.goto('/?auth_modal=login');
			await waitForAuthOverlay(page);
			await page.getByRole('tab', { name: /registrati/i }).click();
			await expect(page.locator('#auth-reg-name')).toBeVisible({ timeout: 15000 });
		});

		test('T1.2.7 - submit vuoto mostra errori', async ({ page }) => {
			await page.goto('/?auth_modal=login');
			await waitForAuthOverlay(page);
			await page.getByRole('tab', { name: /registrati/i }).click();
			await expect(page.locator('#auth-reg-name')).toBeVisible({ timeout: 15000 });
			await page.locator('input[type="checkbox"]').check();
			await page.getByRole('button', { name: /crea account/i }).click();
			await expect(page.getByText(/inserisci nome e cognome/i)).toBeVisible({ timeout: 5000 });
		});
	});

	test.describe('Protezione Route', () => {
		test('T1.4.1 - checkout legacy apre il funnel canonico', async ({ page }) => {
			await page.goto('/checkout');
			await expect(page).toHaveURL(/\/la-tua-spedizione\/2\?step=colli/);
			await expect(page.locator('#auth-modal-email')).toHaveCount(0);
		});

		test('T1.4.2 - account richiede autenticazione', async ({ page }) => {
			await page.goto('/account');
			await expect(page).toHaveURL(/\/$/);
			await waitForAuthOverlay(page);
		});
	});

	test.describe('Password Recovery', () => {
		test('T1.3.1 - recupero password apre overlay e non una pagina dedicata', async ({ page }) => {
			await page.goto('/recupera-password');
			await expect(page).toHaveURL(/\/$/);
			await waitForAuthOverlay(page);
			await page.getByRole('button', { name: /password dimenticata\?/i }).click();
			await expect(page.locator('#auth-forgot-email')).toBeVisible({ timeout: 15000 });
			await expect(page.getByRole('heading', { name: 'Recupera password' }).last()).toBeVisible();
		});
	});
});
