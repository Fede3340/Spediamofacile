import { test, expect } from '@playwright/test'

test.describe('Navigazione', () => {
  test.describe('Header & Navbar', () => {
    test('T8.1.1 - logo link porta a homepage', async ({ page }) => {
      await page.goto('/contatti')
      await page.locator('a[href="/"]').first().click()
      await expect(page).toHaveURL('/')
    })

    test('T8.1.2 - link Servizi funziona', async ({ page }) => {
      await page.goto('/')
      await page.getByRole('link', { name: /servizi/i }).first().click()
      await expect(page).toHaveURL(/servizi/)
    })

    test('T8.1.3 - link Preventivo Rapido funziona', async ({ page }) => {
      await page.goto('/')
      await page.getByRole('link', { name: /preventivo/i }).first().click()
      await expect(page).toHaveURL(/preventivo/)
    })

    test('T8.1.5 - link Contatti funziona', async ({ page }) => {
      await page.goto('/')
      await page.getByRole('link', { name: /contatti/i }).first().click()
      await expect(page).toHaveURL(/contatti/)
    })

    test('T8.1.6 - icona carrello naviga al carrello', async ({ page }) => {
      await page.goto('/')
      await page.locator('a[href="/carrello"]').first().click()
      await expect(page).toHaveURL(/carrello/)
    })

    test('T8.1.8 - bottone login visibile da guest', async ({ page }) => {
      await page.goto('/')
      // The login button is now a <button> that opens the auth modal (not a link to /autenticazione)
      // On desktop it has class navbar-login-btn, on mobile it's in the hamburger menu
      const loginButtons = page.locator('button.navbar-login-btn')
      await expect(loginButtons.first()).toBeAttached({ timeout: 10000 })
      // At least one of the login buttons should be visible depending on viewport
      const count = await loginButtons.count()
      let anyVisible = false
      for (let i = 0; i < count; i++) {
        if (await loginButtons.nth(i).isVisible()) {
          anyVisible = true
          break
        }
      }
      expect(anyVisible).toBe(true)
    })
  })

  test.describe('Footer', () => {
    test('T8.2.4 - anno copyright corretto', async ({ page }) => {
      await page.goto('/')
      const footer = page.locator('footer')
      await expect(footer).toContainText(new Date().getFullYear().toString())
    })
  })
})

test.describe('Pagine Statiche', () => {
  const staticPages = [
    { path: '/chi-siamo', name: 'Chi siamo', testId: 'T8.4.1' },
    { path: '/faq', name: 'FAQ', testId: 'T8.4.2' },
    { path: '/contatti', name: 'Contatti', testId: 'T8.4.3' },
    { path: '/privacy-policy', name: 'Privacy Policy', testId: 'T8.4.4' },
    { path: '/cookie-policy', name: 'Cookie Policy', testId: 'T8.4.5' },
    { path: '/termini-e-condizioni', name: 'Termini e Condizioni', testId: 'T8.4.6' },
    { path: '/servizi', name: 'Servizi', testId: 'T8.4.8' },
    { path: '/guide', name: 'Guide', testId: 'T8.4.12' },
  ]

  for (const { path, name, testId } of staticPages) {
    test(`${testId} - ${name} carica senza errori`, async ({ page }) => {
      const response = await page.goto(path)
      expect(response?.status()).toBeLessThan(400)
      // Page should have content (not empty)
      const body = await page.locator('body').textContent()
      expect(body?.length).toBeGreaterThan(100)
    })
  }

  test('T8.4.3 - form contatti presente', async ({ page }) => {
    await page.goto('/contatti')
    await expect(page.locator('form')).toBeVisible({ timeout: 10000 })
    await expect(page.locator('#contact-email')).toBeVisible()
    await expect(page.locator('#contact-message')).toBeVisible()
  })
})

test.describe('Traccia Spedizione', () => {
  test('T8.5.1 - pagina tracking accessibile', async ({ page }) => {
    await page.goto('/traccia-spedizione')
    await expect(page.locator('input').first()).toBeVisible()
  })
})
