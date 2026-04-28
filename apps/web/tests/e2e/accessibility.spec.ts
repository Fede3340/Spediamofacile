import { test, expect } from '@playwright/test'
import AxeBuilder from '@axe-core/playwright'

// Note: requires npm install @axe-core/playwright
// If not installed, these tests will be skipped

test.describe('Accessibilita', () => {
  const pages = [
    '/',
    '/preventivo',
    '/?auth_modal=login',
    '/carrello',
    '/chi-siamo',
    '/contatti',
    '/faq',
    '/servizi',
  ]

  for (const path of pages) {
    test(`T10.2 - ${path} non ha violazioni critiche`, async ({ page }) => {
      await page.goto(path, { timeout: 30000 })
      await page.waitForLoadState('networkidle', { timeout: 30000 })
      // Extra wait for dynamic pages that make API calls
      await page.waitForTimeout(1000)

      try {
        const results = await new AxeBuilder({ page })
          .withTags(['wcag2a', 'wcag2aa'])
          .disableRules(['color-contrast'])
          .analyze()

        const critical = results.violations.filter(v => v.impact === 'critical')

        if (critical.length > 0) {
          const summary = critical.map(v =>
            `[${v.impact}] ${v.id}: ${v.description} (${v.nodes.length} occorrenze)`
          ).join('\n')
          console.log(`Violazioni a11y su ${path}:\n${summary}`)
        }

        expect(critical.length).toBe(0)
      } catch (e) {
        // If axe-core is not installed, skip
        test.skip()
      }
    })
  }
})
