import { expect, type Locator, type Page } from '@playwright/test';
import type { StripeTestCard } from '../fixtures/stripe-cards';

/**
 * Page Object per lo step Pagamento del funnel `/la-tua-spedizione/2?step=pagamento`.
 * Gestisce interazione con Stripe Elements (iframe) e fallback mock `useStripeMock`.
 */
export class CheckoutPage {
	constructor(private readonly page: Page) {}

	get orderSummary(): Locator {
		return this.page.getByText(/Riepilogo ordine/i);
	}

	get paymentSection(): Locator {
		return this.page.getByText(/Metodo di pagamento/i);
	}

	get couponInput(): Locator {
		return this.page.getByPlaceholder(/codice promo|coupon/i).first();
	}

	get couponApplyButton(): Locator {
		return this.page.getByRole('button', { name: /applica|usa coupon/i }).first();
	}

	get payButton(): Locator {
		return this.page.getByRole('button', { name: /paga ora|conferma pagamento|paga/i }).first();
	}

	async waitForPaymentStep(): Promise<void> {
		await expect(this.page).toHaveURL(/step=pagamento/, { timeout: 15000 });
		await expect(this.paymentSection).toBeVisible({ timeout: 15000 });
	}

	async fillStripeCard(card: StripeTestCard): Promise<void> {
		// Stripe Elements espone iframe isolati per Number/Expiry/CVC.
		const cardFrame = this.page
			.frameLocator('iframe[name^="__privateStripeFrame"], iframe[title*="card"], iframe[name^="__privateStripeController"]')
			.first();
		await cardFrame.locator('input[name="cardnumber"], [placeholder*="1234"]').fill(card.number.replace(/\s/g, ''));
		await cardFrame.locator('input[name="exp-date"], [placeholder*="MM"]').fill(card.exp.replace('/', ''));
		await cardFrame.locator('input[name="cvc"], [placeholder*="CVC"]').fill(card.cvc);
	}

	async applyCoupon(code: string): Promise<void> {
		await this.couponInput.fill(code);
		await this.couponApplyButton.click();
	}

	async pay(): Promise<void> {
		await this.payButton.click();
	}
}
