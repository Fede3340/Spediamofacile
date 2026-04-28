import { expect, test } from '@playwright/test'

const QUICK_QUOTE_HOME = '/'

test.describe('Preventivo - Quick Quote Canonico', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(QUICK_QUOTE_HOME)
    await expect(page.locator('h2', { hasText: 'Preventivo Rapido' })).toBeVisible()
  })

  test('T2.1.1 - tipo pacco default selezionato', async ({ page }) => {
    const switcher = page.locator('.package-type-switcher__button')
    await expect(switcher.filter({ hasText: 'Pacco' })).toHaveClass(/package-type-switcher__button--active/)
    await expect(switcher.filter({ hasText: 'Pallet' })).toBeVisible()
    await expect(switcher.filter({ hasText: 'Valigia' })).toBeVisible()
  })

  test('T2.1.2 - switch tipo pacco a Pallet', async ({ page }) => {
    const palletBtn = page.locator('.package-type-switcher__button', { hasText: 'Pallet' }).first()
    await palletBtn.click()
    await expect(palletBtn).toHaveClass(/package-type-switcher__button--active/)
  })

  test('T2.1.3 - switch tipo pacco a Valigia', async ({ page }) => {
    const valigiaBtn = page.locator('.package-type-switcher__button', { hasText: 'Valigia' }).first()
    await valigiaBtn.click()
    await expect(valigiaBtn).toHaveClass(/package-type-switcher__button--active/)
    await expect(page.locator('#weight_0')).toBeVisible()
  })

  test('T2.1.4 - autocomplete citta accetta query utente', async ({ page }) => {
    const originCityInput = page.locator('#origin_city')
    await originCityInput.fill('Milano')
    await page.waitForTimeout(1000)
    await expect(originCityInput).toHaveValue('Milano')
  })

  test('T2.1.5 - route /preventivo resta compatibile e punta al funnel canonico', async ({ page }) => {
    await page.goto('/preventivo')
    await expect(page).toHaveURL(/\/la-tua-spedizione\/2(?:\?step=colli)?/)
    await expect(page.locator('h1', { hasText: 'Preventivo' })).toBeVisible()
  })

  test('T2.1.9 - peso valido accettato', async ({ page }) => {
    const weightInput = page.locator('#weight_0')
    await weightInput.fill('5.5')
    await weightInput.blur()
    await page.waitForTimeout(300)

    const weightError = page.getByText(/peso è obbligatorio|peso valido/i)
    await expect(weightError).not.toBeVisible()
  })

  test('T2.1.10 - dimensioni valide accettate', async ({ page }) => {
    await page.locator('#first_size_0').fill('30')
    await page.locator('#second_size_0').fill('20')
    await page.locator('#third_size_0').fill('15')
    await page.locator('#third_size_0').blur()
    await page.waitForTimeout(300)

    await expect(page.locator('#first_size_0')).toHaveValue('30')
    await expect(page.locator('#second_size_0')).toHaveValue('20')
    await expect(page.locator('#third_size_0')).toHaveValue('15')
  })

  test('T2.1.14 - aggiungi collo funziona', async ({ page }) => {
    await page.locator('.add-package-btn').click()
    await expect(page.locator('#weight_1')).toBeVisible()
  })

  test('T2.1.15 - elimina collo funziona', async ({ page }) => {
    await page.locator('.add-package-btn').click()
    await expect(page.locator('#weight_1')).toBeVisible()

    await page.getByRole('button', { name: 'Elimina pacco 2' }).click()
    await expect(page.locator('#weight_1')).not.toBeVisible()
    await expect(page.locator('#weight_0')).toBeVisible()
  })

  test('T2.1.16 - quantita collo numerica e senza limite UI a 10', async ({ page }) => {
    const quantityInput = page.locator('#quantity_0')
    await quantityInput.fill('15')
    await quantityInput.blur()
    await expect(quantityInput).toHaveValue('15')
  })

  test('T2.1.17 - continua senza dati non avanza al funnel', async ({ page }) => {
    await page.locator('.continue-cta-button').click()
    await page.waitForTimeout(500)
    await expect(page).toHaveURL(QUICK_QUOTE_HOME)
  })

  test('T2.1.18 - form indirizzo ha i campi canonici', async ({ page }) => {
    await expect(page.locator('#origin_city')).toBeVisible()
    await expect(page.locator('#destination_city')).toBeVisible()
    await expect(page.locator('#origin_postal_code')).toHaveAttribute('type', 'hidden')
    await expect(page.locator('#destination_postal_code')).toHaveAttribute('type', 'hidden')
  })

  test('T2.1.19 - form presente nella homepage', async ({ page }) => {
    await expect(page.locator('h2', { hasText: 'Preventivo Rapido' })).toBeVisible()
    await expect(page.locator('.package-type-switcher__button', { hasText: 'Pacco' }).first()).toBeVisible()
  })

  test('T2.1.20 - struttura della sezione tratta e misure è coerente', async ({ page }) => {
    await expect(page.getByText('Inserisci la tratta')).toBeVisible()
    await expect(page.getByText('Inserisci misure e peso')).toBeVisible()
  })

  test('T2.1.21 - la prima riga pacco è già disponibile senza passaggi extra', async ({ page }) => {
    await expect(page.locator('#weight_0')).toBeVisible()
    await expect(page.locator('#first_size_0')).toBeVisible()
    await expect(page.locator('#second_size_0')).toBeVisible()
    await expect(page.locator('#third_size_0')).toBeVisible()
  })

  test('T2.1.22 - reset form funziona', async ({ page }) => {
    await page.locator('#origin_city').fill('Roma 00119')
    await page.locator('#weight_0').fill('5')
    await page.locator('#first_size_0').fill('30')
    await page.locator('#second_size_0').fill('20')
    await page.locator('#third_size_0').fill('15')
    await page.locator('.add-package-btn').click()
    await expect(page.locator('#weight_1')).toBeVisible()

    await page.getByRole('button', { name: 'Azzera il modulo' }).click()
    await page.waitForTimeout(2500)

    await expect(page.locator('#weight_0')).toHaveValue('')
    await expect(page.locator('#origin_postal_code')).toHaveValue('')
    await expect(page.locator('#weight_1')).not.toBeVisible()
    await expect(page.locator('.package-type-switcher__button', { hasText: 'Pacco' }).first()).toHaveClass(/package-type-switcher__button--active/)
  })
})
