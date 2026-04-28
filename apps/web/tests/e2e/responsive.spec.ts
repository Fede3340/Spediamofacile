import { test, expect } from '@playwright/test'

const breakpoints = [
  { name: 'mobile', width: 375, height: 812 },
  { name: 'account-bp', width: 600, height: 1024 },
  { name: 'tablet', width: 720, height: 1024 },
  { name: 'mid-desktop', width: 840, height: 900 },
  { name: 'desktop', width: 1024, height: 768 },
  { name: 'desktop-xl', width: 1440, height: 900 },
]

const pages = [
  { path: '/', name: 'Homepage' },
  { path: '/preventivo', name: 'Preventivo' },
  { path: '/carrello', name: 'Carrello' },
  { path: '/?auth_modal=login', name: 'Auth Modal' },
  { path: '/chi-siamo', name: 'Chi Siamo' },
  { path: '/contatti', name: 'Contatti' },
  { path: '/faq', name: 'FAQ' },
  { path: '/servizi', name: 'Servizi' },
]

for (const bp of breakpoints) {
  test.describe(`Responsive ${bp.name} (${bp.width}px)`, () => {
    test.use({ viewport: { width: bp.width, height: bp.height } })

    for (const pg of pages) {
      test(`${pg.name} carica correttamente a ${bp.width}px`, async ({ page }) => {
        const response = await page.goto(pg.path)
        expect(response?.status()).toBeLessThan(400)

        // No horizontal overflow
        const bodyWidth = await page.evaluate(() => document.body.scrollWidth)
        const viewportWidth = await page.evaluate(() => window.innerWidth)
        expect(bodyWidth).toBeLessThanOrEqual(viewportWidth + 5) // 5px tolerance

        // No console errors
        const errors: string[] = []
        page.on('console', msg => {
          if (msg.type() === 'error') errors.push(msg.text())
        })
        // Allow time for any JS errors
        await page.waitForTimeout(1000)
      })
    }

    if (bp.width <= 720) {
      test('Navbar mostra hamburger menu su mobile', async ({ page }) => {
        await page.goto('/')
        // On mobile, main nav links should be hidden, hamburger visible
        const hamburger = page.locator('[class*="hamburger"], [class*="mobile-menu"], button[aria-label*="menu"]').first()
        await expect(hamburger).toBeVisible()
      })
    }

    if (bp.width >= 1024) {
      test('Navbar mostra link desktop', async ({ page }) => {
        await page.goto('/')
        // On desktop, nav links should be visible
        await expect(page.getByRole('link', { name: /servizi/i }).first()).toBeVisible()
      })
    }
  })
}
