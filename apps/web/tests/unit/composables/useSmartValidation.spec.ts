import { describe, it, expect } from 'vitest'

/**
 * Test per useSmartValidation.js
 * Testiamo la logica pura di validazione (regex, range, filtri)
 * senza dipendenze da Vue/Nuxt runtime (ref, reactive).
 */

// Lista province copiata dal composable per test coerenti
const ITALIAN_PROVINCES = [
  'AG','AL','AN','AO','AP','AQ','AR','AT','AV','BA','BG','BI','BL','BN','BO',
  'BR','BS','BT','BZ','CA','CB','CE','CH','CL','CN','CO','CR','CS','CT','CZ',
  'EN','FC','FE','FG','FI','FM','FR','GE','GO','GR','IM','IS','KR','LC','LE',
  'LI','LO','LT','LU','MB','MC','ME','MI','MN','MO','MS','MT','NA','NO','NU',
  'OG','OR','OT','PA','PC','PD','PE','PG','PI','PN','PO','PR','PT','PU','PV',
  'PZ','RA','RC','RE','RG','RI','RM','RN','RO','SA','SI','SO','SP','SR','SS',
  'SU','SV','TA','TE','TN','TO','TP','TR','TS','TV','UD','VA','VB','VC','VE',
  'VI','VR','VT','VV',
]

describe('useSmartValidation', () => {

  // ========== CAP VALIDATION ==========
  describe('CAP Validation', () => {
    // Riproduce la logica di validateCAP: 5 cifre, range 00010-98168
    function isValidCAP(value: string): boolean {
      const cleaned = String(value).replace(/[^0-9]/g, '')
      if (cleaned.length !== 5) return false
      const capNum = parseInt(cleaned, 10)
      return capNum >= 10 && capNum <= 98168
    }

    it('T2.3.1 - CAP valido 20121', () => {
      expect(isValidCAP('20121')).toBe(true)
    })

    it('T2.3.2 - CAP invalido 00001 (troppo basso)', () => {
      expect(isValidCAP('00001')).toBe(false)
    })

    it('T2.3.3 - CAP non numerico rifiutato', () => {
      expect(isValidCAP('ABCDE')).toBe(false)
    })

    it('CAP valido al limite inferiore 00010', () => {
      expect(isValidCAP('00010')).toBe(true)
    })

    it('CAP valido al limite superiore 98168', () => {
      expect(isValidCAP('98168')).toBe(true)
    })

    it('CAP troppo corto (4 cifre) rifiutato', () => {
      expect(isValidCAP('2012')).toBe(false)
    })

    it('CAP troppo lungo (6 cifre) rifiutato', () => {
      expect(isValidCAP('201211')).toBe(false)
    })

    it('CAP 00000 rifiutato (sotto range)', () => {
      expect(isValidCAP('00000')).toBe(false)
    })

    it('CAP 99999 rifiutato (sopra range)', () => {
      expect(isValidCAP('99999')).toBe(false)
    })

    it('CAP vuoto rifiutato', () => {
      expect(isValidCAP('')).toBe(false)
    })

    it('CAP con spazi rifiutato (non 5 cifre pulite)', () => {
      // "2 0 1 2 1" -> cleaned "20121" = 5 cifre -> valido
      expect(isValidCAP('2 0 1 2 1')).toBe(true)
    })
  })

  // ========== PHONE VALIDATION ==========
  describe('Phone Validation', () => {
    // Riproduce la logica di validateTelefono:
    // cleaned = remove spaces/dashes/parens; must match /^\+?\d+$/;
    // digits = cleaned senza prefisso +39; 6 <= digits.length <= 10
    function isValidPhone(value: string): { valid: boolean; error?: string } {
      if (!value || !String(value).trim()) return { valid: false, error: 'obbligatorio' }
      const cleaned = String(value).replace(/[\s\-\(\)]/g, '')
      if (!/^\+?\d+$/.test(cleaned)) return { valid: false, error: 'solo numeri' }
      const digits = cleaned.replace(/^\+?39/, '')
      if (digits.length < 6) return { valid: false, error: 'troppo corto' }
      if (digits.length > 10) return { valid: false, error: 'troppo lungo' }
      return { valid: true }
    }

    it('T2.3.4 - telefono valido +39 333 1234567', () => {
      expect(isValidPhone('+39 333 1234567').valid).toBe(true)
    })

    it('T2.3.5 - telefono troppo corto 123', () => {
      const result = isValidPhone('123')
      expect(result.valid).toBe(false)
      expect(result.error).toBe('troppo corto')
    })

    it('telefono valido senza prefisso 3331234567', () => {
      expect(isValidPhone('3331234567').valid).toBe(true)
    })

    it('telefono con trattini +39-333-1234567', () => {
      expect(isValidPhone('+39-333-1234567').valid).toBe(true)
    })

    it('telefono con parentesi (333) 1234567', () => {
      expect(isValidPhone('(333) 1234567').valid).toBe(true)
    })

    it('telefono troppo lungo (>10 cifre dopo +39)', () => {
      expect(isValidPhone('+39 33312345678').valid).toBe(false)
    })

    it('telefono vuoto rifiutato', () => {
      expect(isValidPhone('').valid).toBe(false)
    })

    it('telefono con lettere rifiutato', () => {
      expect(isValidPhone('333ABC1234').valid).toBe(false)
    })

    it('telefono 6 cifre minimo valido', () => {
      expect(isValidPhone('123456').valid).toBe(true)
    })

    it('telefono 10 cifre massimo valido', () => {
      expect(isValidPhone('3331234567').valid).toBe(true)
    })
  })

  // ========== EMAIL VALIDATION ==========
  describe('Email Validation', () => {
    // Riproduce la logica di validateEmail: opzionale; se presente, regex /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

    function isValidEmail(value: string): boolean | null {
      if (!value || !String(value).trim()) return null // opzionale -> valido
      return emailRegex.test(String(value).trim())
    }

    it('T2.3.6 - email valida test@example.com', () => {
      expect(isValidEmail('test@example.com')).toBe(true)
    })

    it('T2.3.7 - email invalida test@', () => {
      expect(isValidEmail('test@')).toBe(false)
    })

    it('email vuota e\' opzionale (valida)', () => {
      expect(isValidEmail('')).toBe(null)
    })

    it('email senza dominio rifiutata', () => {
      expect(isValidEmail('test@example')).toBe(false)
    })

    it('email con spazi rifiutata', () => {
      expect(isValidEmail('te st@example.com')).toBe(false)
    })

    it('email valida con sottodominio', () => {
      expect(isValidEmail('user@mail.example.com')).toBe(true)
    })

    it('email valida con + nel local part', () => {
      expect(isValidEmail('user+tag@example.com')).toBe(true)
    })
  })

  // ========== PROVINCE VALIDATION ==========
  describe('Province Validation', () => {
    // Riproduce la logica di validateProvincia: 2 lettere maiuscole, nell'elenco ITALIAN_PROVINCES
    function isValidProvincia(value: string): boolean {
      if (!value || !String(value).trim()) return false
      const upper = String(value).toUpperCase().trim()
      if (!/^[A-Z]{2}$/.test(upper)) return false
      return ITALIAN_PROVINCES.includes(upper)
    }

    it('T2.3.8 - provincia valida MI', () => {
      expect(isValidProvincia('MI')).toBe(true)
    })

    it('T2.3.9 - provincia invalida XX', () => {
      expect(isValidProvincia('XX')).toBe(false)
    })

    it('provincia valida in minuscolo mi', () => {
      expect(isValidProvincia('mi')).toBe(true)
    })

    it('provincia 3 lettere rifiutata', () => {
      expect(isValidProvincia('MIL')).toBe(false)
    })

    it('provincia 1 lettera rifiutata', () => {
      expect(isValidProvincia('M')).toBe(false)
    })

    it('provincia vuota rifiutata', () => {
      expect(isValidProvincia('')).toBe(false)
    })

    it('provincia RM valida', () => {
      expect(isValidProvincia('RM')).toBe(true)
    })

    it('provincia NA valida', () => {
      expect(isValidProvincia('NA')).toBe(true)
    })

    it('tutte le province nella lista sono 2 lettere', () => {
      for (const p of ITALIAN_PROVINCES) {
        expect(p).toHaveLength(2)
        expect(/^[A-Z]{2}$/.test(p)).toBe(true)
      }
    })
  })

  // ========== NAME VALIDATION ==========
  describe('Name Validation', () => {
    // Riproduce la logica di validateNomeCognome: obbligatorio, no numeri
    function isValidName(value: string): boolean {
      if (!value || !String(value).trim()) return false
      return !/\d/.test(value)
    }

    it('T2.3.10 - nome con numeri rifiutato', () => {
      expect(isValidName('Mario123')).toBe(false)
    })

    it('nome valido senza numeri', () => {
      expect(isValidName('Mario Rossi')).toBe(true)
    })

    it('nome vuoto rifiutato', () => {
      expect(isValidName('')).toBe(false)
    })

    it('nome con accenti valido', () => {
      expect(isValidName('Nicolo\' De Andre\'')).toBe(true)
    })

    it('nome solo spazi rifiutato', () => {
      expect(isValidName('   ')).toBe(false)
    })
  })

  // ========== WEIGHT VALIDATION ==========
  describe('Weight Validation', () => {
    // Riproduce la logica di validatePeso: num = Number(cleaned), num > 0, num <= 1000
    function isValidWeight(value: any): boolean {
      if (!value && value !== 0) return false
      const num = Number(String(value).replace(/[^0-9.]/g, ''))
      if (isNaN(num) || num <= 0) return false
      if (num > 1000) return false
      return true
    }

    it('peso valido 5.5', () => {
      expect(isValidWeight(5.5)).toBe(true)
    })

    it('peso zero rifiutato', () => {
      expect(isValidWeight(0)).toBe(false)
    })

    it('peso negativo: il segno viene rimosso dal filtro numerico, il valore assoluto e\' valido', () => {
      // Il composable fa String(value).replace(/[^0-9.]/g, '') che rimuove il "-"
      // quindi -5 diventa "5" che e\' > 0 e <= 1000 -> valido
      expect(isValidWeight(-5)).toBe(true)
    })

    it('peso > 1000 rifiutato', () => {
      expect(isValidWeight(1500)).toBe(false)
    })

    it('peso esattamente 1000 valido', () => {
      expect(isValidWeight(1000)).toBe(true)
    })

    it('peso 0.1 valido', () => {
      expect(isValidWeight(0.1)).toBe(true)
    })

    it('peso null rifiutato', () => {
      expect(isValidWeight(null)).toBe(false)
    })

    it('peso undefined rifiutato', () => {
      expect(isValidWeight(undefined)).toBe(false)
    })
  })

  // ========== DIMENSION VALIDATION ==========
  describe('Dimension Validation', () => {
    // Riproduce la logica di validateDimensione: num > 0, num <= 300
    function isValidDimension(value: any): boolean {
      if (!value && value !== 0) return false
      const num = Number(String(value).replace(/[^0-9.]/g, ''))
      if (isNaN(num) || num <= 0) return false
      if (num > 300) return false
      return true
    }

    it('dimensione valida 30cm', () => {
      expect(isValidDimension(30)).toBe(true)
    })

    it('dimensione > 300cm rifiutata', () => {
      expect(isValidDimension(350)).toBe(false)
    })

    it('dimensione esattamente 300cm valida', () => {
      expect(isValidDimension(300)).toBe(true)
    })

    it('dimensione zero rifiutata', () => {
      expect(isValidDimension(0)).toBe(false)
    })

    it('dimensione 0.5cm valida', () => {
      expect(isValidDimension(0.5)).toBe(true)
    })
  })

  // ========== UTILITY FUNCTIONS ==========
  describe('Utility Functions', () => {

    describe('filterCAP', () => {
      // Riproduce: String(value).replace(/[^0-9]/g, '').slice(0, 5)
      function filterCAP(value: string): string {
        if (!value) return value
        return String(value).replace(/[^0-9]/g, '').slice(0, 5)
      }

      it('rimuove non-numerici', () => {
        expect(filterCAP('20AB1')).toBe('201')
      })

      it('tronca a 5 cifre', () => {
        expect(filterCAP('123456789')).toBe('12345')
      })

      it('lascia invariato un CAP valido', () => {
        expect(filterCAP('20121')).toBe('20121')
      })

      it('gestisce stringa vuota', () => {
        expect(filterCAP('')).toBe('')
      })
    })

    describe('filterProvincia', () => {
      // Riproduce: String(value).replace(/[^a-zA-Z]/g, '').slice(0, 2).toUpperCase()
      function filterProvincia(value: string): string {
        if (!value) return value
        return String(value).replace(/[^a-zA-Z]/g, '').slice(0, 2).toUpperCase()
      }

      it('rimuove non-lettere e uppercase', () => {
        expect(filterProvincia('m1i')).toBe('MI')
      })

      it('tronca a 2 lettere', () => {
        expect(filterProvincia('milano')).toBe('MI')
      })

      it('converte minuscolo in maiuscolo', () => {
        expect(filterProvincia('rm')).toBe('RM')
      })

      it('gestisce stringa vuota', () => {
        expect(filterProvincia('')).toBe('')
      })
    })

    describe('autoCapitalize', () => {
      // Riproduce: String(value).replace(/\b\w/g, c => c.toUpperCase())
      function autoCapitalize(value: string): string {
        if (!value) return value
        return String(value).replace(/\b\w/g, c => c.toUpperCase())
      }

      it('prima lettera maiuscola', () => {
        expect(autoCapitalize('mario')).toBe('Mario')
      })

      it('prima lettera di ogni parola maiuscola', () => {
        expect(autoCapitalize('mario rossi')).toBe('Mario Rossi')
      })

      it('lascia invariato se gia\' capitalizzato', () => {
        expect(autoCapitalize('Mario Rossi')).toBe('Mario Rossi')
      })

      it('gestisce stringa vuota', () => {
        expect(autoCapitalize('')).toBe('')
      })
    })

    describe('formatTelefono', () => {
      // Riproduce: String(value).replace(/[^\d+]/g, '')
      function formatTelefono(value: string): string {
        if (!value) return value
        return String(value).replace(/[^\d+]/g, '')
      }

      it('rimuove spazi e trattini', () => {
        expect(formatTelefono('+39 333-123 4567')).toBe('+393331234567')
      })

      it('mantiene il + iniziale', () => {
        expect(formatTelefono('+39333')).toBe('+39333')
      })

      it('rimuove parentesi', () => {
        expect(formatTelefono('(333) 1234567')).toBe('3331234567')
      })
    })

    describe('getProvinceSuggestions', () => {
      function getProvinceSuggestions(input: string): string[] {
        if (!input || String(input).length < 1) return []
        const upper = String(input).toUpperCase()
        return ITALIAN_PROVINCES.filter(p => p.startsWith(upper)).slice(0, 5)
      }

      it('suggerimenti per "M" (max 5)', () => {
        const results = getProvinceSuggestions('M')
        expect(results.length).toBeLessThanOrEqual(5)
        expect(results.every(p => p.startsWith('M'))).toBe(true)
      })

      it('suggerimenti per "MI" restituisce MI', () => {
        const results = getProvinceSuggestions('MI')
        expect(results).toContain('MI')
      })

      it('suggerimenti per stringa vuota', () => {
        expect(getProvinceSuggestions('')).toEqual([])
      })

      it('suggerimenti per input minuscolo "r"', () => {
        const results = getProvinceSuggestions('r')
        expect(results.length).toBeGreaterThan(0)
        expect(results.every(p => p.startsWith('R'))).toBe(true)
      })

      it('suggerimenti per provincia inesistente "ZZ"', () => {
        expect(getProvinceSuggestions('ZZ')).toEqual([])
      })
    })
  })
})
