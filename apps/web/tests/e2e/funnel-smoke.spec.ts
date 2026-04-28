import { test, expect } from '@playwright/test'

test.describe('Funnel preventivo smoke', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('http://localhost:8787/la-tua-spedizione/colli', {
      timeout: 10000,
      waitUntil: 'domcontentloaded',
    })
  })

  test('primo stage aperto con titolo Colli', async ({ page }) => {
    // Il primo stage dell'accordion deve essere aperto e mostrare il titolo "Colli"
    const stageColli = page.locator('h1, h2, h3', { hasText: /^Colli$/i }).first()
    await expect(stageColli).toBeVisible({ timeout: 10000 })
  })

  test('stage aperto contiene bottone Conferma', async ({ page }) => {
    // Nello stage aperto deve essere presente il bottone "Conferma"
    const confermaBtn = page.locator('button', { hasText: /^Conferma$/i }).first()
    await expect(confermaBtn).toBeVisible({ timeout: 10000 })
  })

  test('tutti i 4 stage accordion sono nel DOM', async ({ page }) => {
    // I 4 stage del funnel devono essere tutti presenti (anche se chiusi)
    const stages = ['Colli', 'Servizi', 'Indirizzi', 'Pagamento']
    for (const stageName of stages) {
      const stage = page.locator('body').getByText(new RegExp(`^${stageName}$`, 'i')).first()
      await expect(stage).toBeAttached({ timeout: 10000 })
    }
  })

  test('palette senza blu/slate Tailwind', async ({ page }) => {
    await page.waitForLoadState('networkidle', { timeout: 10000 })

    // Raccoglie tutti i colori computati degli elementi nella pagina
    const forbiddenColors = await page.evaluate(() => {
      const forbidden = ['rgb(59, 130, 246)', 'rgb(100, 116, 139)']
      const hits: string[] = []
      const all = document.querySelectorAll('*')
      all.forEach((el) => {
        const style = window.getComputedStyle(el as Element)
        if (forbidden.includes(style.color)) {
          hits.push(`${el.tagName}.${(el as Element).className}: ${style.color}`)
        }
      })
      return hits
    })

    expect(forbiddenColors).toEqual([])
  })

  test('footer non contiene testo "Pronto a spedire"', async ({ page }) => {
    const footer = page.locator('footer')
    await expect(footer).not.toContainText('Pronto a spedire')
  })
})
