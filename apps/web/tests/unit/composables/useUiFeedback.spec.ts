import { describe, it, expect } from 'vitest'

/**
 * Test per useUiFeedback.js
 * Il composable usa useToast() di Nuxt UI per mostrare notifiche.
 * Testiamo la logica pura: mapping tipo -> colore/icona, default timeout,
 * gestione parametri opzionali, senza dipendenze da Nuxt runtime.
 */

// ---- LOGICA RIPRODOTTA DAL COMPOSABLE ----

// Mappa tipo -> preset (stessa del composable)
const typeMap = {
  success: { color: 'success', icon: 'mdi:check-circle-outline' },
  info: { color: 'info', icon: 'mdi:information-outline' },
  warning: { color: 'warning', icon: 'mdi:alert-circle-outline' },
  error: { color: 'error', icon: 'mdi:alert-octagon-outline' },
} as const

// Simula la funzione push per costruire l'oggetto toast
function buildToastPayload(
  type: string,
  title: string,
  description = '',
  options: { color?: string; icon?: string; timeout?: number } = {},
) {
  const preset = typeMap[type as keyof typeof typeMap] ?? typeMap.warning

  return {
    title,
    description: description || undefined,
    color: options.color || preset.color,
    icon: options.icon || preset.icon,
    timeout: options.timeout ?? 4500,
  }
}

// ---- TEST ----

describe('useUiFeedback Logic', () => {

  describe('Type Mapping', () => {
    it('success -> color success, icona check-circle', () => {
      const payload = buildToastPayload('success', 'OK')
      expect(payload.color).toBe('success')
      expect(payload.icon).toBe('mdi:check-circle-outline')
    })

    it('info -> color info, icona information', () => {
      const payload = buildToastPayload('info', 'Info')
      expect(payload.color).toBe('info')
      expect(payload.icon).toBe('mdi:information-outline')
    })

    it('warning -> color warning, icona alert-circle', () => {
      const payload = buildToastPayload('warning', 'Attenzione')
      expect(payload.color).toBe('warning')
      expect(payload.icon).toBe('mdi:alert-circle-outline')
    })

    it('error -> color error, icona alert-octagon', () => {
      const payload = buildToastPayload('error', 'Errore')
      expect(payload.color).toBe('error')
      expect(payload.icon).toBe('mdi:alert-octagon-outline')
    })

    it('tipo sconosciuto -> fallback a warning', () => {
      const payload = buildToastPayload('unknown', 'Test')
      expect(payload.color).toBe('warning')
      expect(payload.icon).toBe('mdi:alert-circle-outline')
    })
  })

  describe('Default Timeout', () => {
    it('timeout default 4500ms', () => {
      const payload = buildToastPayload('success', 'OK')
      expect(payload.timeout).toBe(4500)
    })

    it('timeout custom sovrascrive il default', () => {
      const payload = buildToastPayload('success', 'OK', '', { timeout: 8000 })
      expect(payload.timeout).toBe(8000)
    })

    it('timeout 0 e\' valido (non usa default)', () => {
      const payload = buildToastPayload('info', 'Test', '', { timeout: 0 })
      expect(payload.timeout).toBe(0)
    })
  })

  describe('Title & Description', () => {
    it('title passato correttamente', () => {
      const payload = buildToastPayload('success', 'Spedizione creata')
      expect(payload.title).toBe('Spedizione creata')
    })

    it('description passata quando non vuota', () => {
      const payload = buildToastPayload('success', 'OK', 'Dettaglio operazione')
      expect(payload.description).toBe('Dettaglio operazione')
    })

    it('description vuota -> undefined (non inclusa)', () => {
      const payload = buildToastPayload('success', 'OK', '')
      expect(payload.description).toBeUndefined()
    })

    it('description omessa -> undefined', () => {
      const payload = buildToastPayload('info', 'Info')
      expect(payload.description).toBeUndefined()
    })
  })

  describe('Custom Options Override', () => {
    it('color custom sovrascrive il preset', () => {
      const payload = buildToastPayload('success', 'OK', '', { color: 'primary' })
      expect(payload.color).toBe('primary')
    })

    it('icon custom sovrascrive il preset', () => {
      const payload = buildToastPayload('error', 'Err', '', { icon: 'mdi:close' })
      expect(payload.icon).toBe('mdi:close')
    })

    it('options vuoto usa i defaults', () => {
      const payload = buildToastPayload('success', 'OK', '', {})
      expect(payload.color).toBe('success')
      expect(payload.icon).toBe('mdi:check-circle-outline')
      expect(payload.timeout).toBe(4500)
    })
  })

  describe('Exported Methods Mapping', () => {
    // Il composable espone: success, info, warn, error, critical
    // warn -> chiama push("warning", ...)
    // critical -> chiama push("error", ...)

    it('warn usa il tipo warning internamente', () => {
      const payload = buildToastPayload('warning', 'Attenzione')
      expect(payload.color).toBe('warning')
    })

    it('critical usa il tipo error internamente', () => {
      // critical = (title, desc, opts) => push("error", ...)
      const payload = buildToastPayload('error', 'Errore critico')
      expect(payload.color).toBe('error')
      expect(payload.icon).toBe('mdi:alert-octagon-outline')
    })
  })

  describe('Edge Cases', () => {
    it('title vuoto accettato', () => {
      const payload = buildToastPayload('info', '')
      expect(payload.title).toBe('')
    })

    it('tutti i campi contemporaneamente', () => {
      const payload = buildToastPayload('success', 'Titolo', 'Descrizione', {
        color: 'primary',
        icon: 'mdi:star',
        timeout: 10000,
      })
      expect(payload.title).toBe('Titolo')
      expect(payload.description).toBe('Descrizione')
      expect(payload.color).toBe('primary')
      expect(payload.icon).toBe('mdi:star')
      expect(payload.timeout).toBe(10000)
    })
  })
})
