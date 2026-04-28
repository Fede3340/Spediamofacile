import { expect, type Page } from '@playwright/test'

/**
 * Page Object del quick quote canonico.
 * L'ingresso utente principale è la homepage; /preventivo resta solo compat.
 */
export class PreventivoPage {
  constructor(private readonly page: Page) {}

  async goto(): Promise<void> {
    await this.page.goto('/')
    await expect(this.page.locator('h2', { hasText: 'Preventivo Rapido' })).toBeVisible()
  }

  async gotoLegacyPreventivo(): Promise<void> {
    await this.page.goto('/preventivo')
    await expect(this.page).toHaveURL(/\/la-tua-spedizione\/2(?:\?step=colli)?/)
  }

  async fillCityField(selector: string, value: string): Promise<void> {
    const input = this.page.locator(selector)
    await expect(input).toBeVisible()
    await input.fill(value)
    await this.page.waitForTimeout(900)

    const suggestions = this.page.locator('ul[role="listbox"] li[role="option"]')
    const hasSuggestions = await suggestions.first().isVisible({ timeout: 2500 }).catch(() => false)
    if (hasSuggestions) {
      await suggestions.first().click()
    } else {
      await input.blur()
    }

    await this.page.waitForTimeout(250)
  }

  async fillOrigin(city: string): Promise<void> {
    await this.fillCityField('#origin_city', city)
  }

  async fillDestination(city: string): Promise<void> {
    await this.fillCityField('#destination_city', city)
  }

  async selectPackageType(type: 'Pacco' | 'Pallet' | 'Valigia'): Promise<void> {
    await this.page.locator('.package-type-switcher__button', { hasText: type }).first().click()
    await this.page.waitForTimeout(400)
  }

  async fillPackage(index: number, data: { weight: string; size1: string; size2: string; size3: string; quantity?: string }): Promise<void> {
    await this.page.locator(`#weight_${index}`).fill(data.weight)
    await this.page.locator(`#first_size_${index}`).fill(data.size1)
    await this.page.locator(`#second_size_${index}`).fill(data.size2)
    await this.page.locator(`#third_size_${index}`).fill(data.size3)
    if (data.quantity) {
      await this.page.locator(`#quantity_${index}`).fill(data.quantity)
    }
    await this.page.locator(`#third_size_${index}`).blur()
    await this.page.waitForTimeout(200)
  }

  async addAnotherPackage(): Promise<void> {
    await this.page.locator('.add-package-btn').first().click()
    await this.page.waitForTimeout(300)
  }

  async submitQuote(): Promise<void> {
    const cta = this.page.locator('.continue-cta-button').first()
    await expect(cta).toBeVisible()
    await cta.click()
  }
}
