import { expect, type Page } from '@playwright/test';

/**
 * Page Object per le route `/account/*` (area utente autenticato).
 */
export class AccountPage {
	constructor(private readonly page: Page) {}

	async gotoDashboard(): Promise<void> {
		await this.page.goto('/account');
		await this.page.waitForLoadState('networkidle');
	}

	async gotoWallet(): Promise<void> {
		await this.page.goto('/account/portafoglio');
		await this.page.waitForLoadState('networkidle');
	}

	async gotoProfile(): Promise<void> {
		await this.page.goto('/account/profilo');
		await this.page.waitForLoadState('networkidle');
	}

	async expectAuthenticated(): Promise<void> {
		await expect(this.page.locator('main')).toBeVisible();
		await expect(this.page.locator('#auth-modal-email')).toHaveCount(0);
	}

	async openAccountMenu(): Promise<void> {
		const trigger = this.page.getByRole('button', { name: /menu account|il mio account/i }).first();
		if (await trigger.isVisible().catch(() => false)) {
			await trigger.click();
		}
	}
}
