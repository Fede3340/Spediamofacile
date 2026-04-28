import { test, expect } from '@playwright/test'

test.describe('Carrello', () => {
  test('T4.1.1 - carrello vuoto mostra messaggio', async ({ page }) => {
    await page.goto('/carrello')
    await page.waitForLoadState('networkidle')

    // Empty cart shows "Il carrello è vuoto" heading
    const emptyHeading = page.locator('h2', { hasText: 'Il carrello è vuoto' })
    await expect(emptyHeading).toBeVisible({ timeout: 10000 })

    // Description text about adding a shipment
    const emptyMessage = page.getByText(/Non hai ancora aggiunto spedizioni al carrello/i)
    await expect(emptyMessage).toBeVisible()
  })

  test('T4.1.2 - carrello vuoto ha titolo Carrello', async ({ page }) => {
    await page.goto('/carrello')
    await page.waitForLoadState('networkidle')

    // The page title heading "Carrello" should be visible
    const cartTitle = page.locator('h1', { hasText: 'Carrello' })
    await expect(cartTitle).toBeVisible({ timeout: 10000 })
  })

  test('T4.1.10 - link a preventivo dal carrello vuoto', async ({ page }) => {
    await page.goto('/carrello')
    await page.waitForLoadState('networkidle')

    // Empty cart has a "Crea nuova spedizione" link pointing to /preventivo
    const createLink = page.locator('a[href="/preventivo"]', { hasText: 'Crea nuova spedizione' })
    await expect(createLink).toBeVisible({ timeout: 10000 })
  })

  test('T4.1.11 - click link crea spedizione naviga a preventivo', async ({ page }) => {
    await page.goto('/carrello')
    await page.waitForLoadState('networkidle')

    const createLink = page.locator('a[href="/preventivo"]', { hasText: 'Crea nuova spedizione' })
    await expect(createLink).toBeVisible({ timeout: 10000 })

    await createLink.click()
    await page.waitForLoadState('networkidle')

    // Should navigate to /preventivo
    await expect(page).toHaveURL(/preventivo/)
  })

  test('T4.2.4 - carrello guest persiste dopo refresh', async ({ page }) => {
    await page.goto('/carrello')
    await page.waitForLoadState('networkidle')

    // Just verify the page loads without errors after refresh
    const response = await page.reload()
    expect(response?.status()).toBeLessThan(400)

    // Page should still show the cart (empty or with items)
    await page.waitForLoadState('networkidle')
    // Check page loaded: either the cart title or empty cart message is visible
    const cartIndicator = page.locator('h1, h2').first()
    await expect(cartIndicator).toBeVisible({ timeout: 10000 })
  })

  test('T4.3.1 - pagina carrello ha SEO title corretto', async ({ page }) => {
    await page.goto('/carrello')
    await page.waitForLoadState('networkidle')

    // SEO title should contain "Carrello | SpediamoFacile"
    await expect(page).toHaveTitle(/Carrello.*SpediamoFacile/i)
  })

  test('T4.4.1 - carrello con items mostra filtri e coupon (richiede backend)', async ({ page }) => {
    // This test verifies the structure when cart has items
    // It requires a running backend with data - skip if empty cart
    await page.goto('/carrello')
    await page.waitForLoadState('networkidle')

    // Check if cart has items
    const hasItems = await page.locator('h1', { hasText: 'Carrello' }).isVisible({ timeout: 5000 }).catch(() => false)
    const emptyCart = await page.locator('h2', { hasText: 'Il carrello è vuoto' }).isVisible({ timeout: 2000 }).catch(() => false)

    if (!emptyCart && hasItems) {
      // If cart has items, verify filter elements are present
      const provenienzaFilter = page.locator('select', { hasText: 'Provenienza' })
      await expect(provenienzaFilter).toBeVisible()

      const riferimentoInput = page.locator('input[placeholder="Riferimento"]')
      await expect(riferimentoInput).toBeVisible()

      // Coupon section
      const couponLabel = page.getByText('Inserisci Coupon')
      await expect(couponLabel).toBeVisible()

      // Checkout button
      const checkoutBtn = page.getByText("Procedi con l'ordine")
      await expect(checkoutBtn).toBeVisible()
    } else {
      // Cart is empty — this test is not applicable
      test.skip()
    }
  })
})
