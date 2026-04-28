import { test, expect } from '@playwright/test'

test.describe('Checkout legacy', () => {
  test('T5.1 - checkout legacy rientra nel funnel canonico sullo step colli', async ({ page }) => {
    const response = await page.goto('/checkout')
    expect(response?.status()).toBeLessThan(500)

    await page.waitForLoadState('networkidle')

    const url = new URL(page.url())
    expect(url.pathname).toBe('/la-tua-spedizione/2')
    expect(url.searchParams.get('step')).toBe('colli')
    expect(url.pathname).not.toBe('/checkout')

    await expect(page.locator('h2', { hasText: 'Colli' })).toBeVisible()
    await expect(page.locator('#auth-modal-email, #auth-forgot-email, #auth-reg-name')).toHaveCount(0)
  })
})
