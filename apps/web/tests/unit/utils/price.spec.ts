import { describe, it, expect } from 'vitest'
import { formatPrice, formatEuro, euroToCents, toCents, toEuros, parsePrice } from '~/utils/price'

describe('formatPrice (cents → "X,XX €")', () => {
  it('formatta centesimi standard', () => {
    expect(formatPrice(890)).toBe('8,90 €')
    expect(formatPrice(1250)).toBe('12,50 €')
    expect(formatPrice(100)).toBe('1,00 €')
  })

  it('gestisce zero', () => {
    expect(formatPrice(0)).toBe('0,00 €')
  })

  it('gestisce null/undefined', () => {
    expect(formatPrice(null)).toBe('0,00 €')
    expect(formatPrice(undefined)).toBe('0,00 €')
  })

  it('gestisce centesimi grandi', () => {
    expect(formatPrice(999999)).toBe('9999,99 €')
  })

  it('gestisce numeri decimali come input', () => {
    expect(formatPrice(1)).toBe('0,01 €')
    expect(formatPrice(50)).toBe('0,50 €')
  })
})

describe('formatEuro (euros number → "X,XX")', () => {
  it('formatta euro standard', () => {
    expect(formatEuro(8.9)).toBe('8,90')
    expect(formatEuro(12.5)).toBe('12,50')
  })

  it('gestisce zero', () => {
    expect(formatEuro(0)).toBe('0,00')
  })

  it('gestisce null/undefined', () => {
    expect(formatEuro(null)).toBe('0,00')
    expect(formatEuro(undefined)).toBe('0,00')
  })
})

describe('euroToCents (stringa/numero → centesimi)', () => {
  it('converte formato italiano', () => {
    expect(euroToCents('8,90')).toBe(890)
    expect(euroToCents('12,50')).toBe(1250)
  })

  it('converte formato con simbolo', () => {
    expect(euroToCents('8,90 €')).toBe(890)
    expect(euroToCents('€12,50')).toBe(1250)
  })

  it('converte numero diretto', () => {
    expect(euroToCents(8.9)).toBe(890)
  })

  it('gestisce null/undefined/vuoto', () => {
    expect(euroToCents(null)).toBeNull()
    expect(euroToCents(undefined)).toBeNull()
    expect(euroToCents('')).toBeNull()
  })

  it('gestisce input non valido', () => {
    expect(euroToCents('abc')).toBeNull()
  })
})

describe('toCents / toEuros', () => {
  it('toCents converte correttamente', () => {
    expect(toCents(8.9)).toBe(890)
    expect(toCents(0)).toBe(0)
    expect(toCents(100)).toBe(10000)
  })

  it('toEuros converte correttamente', () => {
    expect(toEuros(890)).toBe(8.9)
    expect(toEuros(0)).toBe(0)
    expect(toEuros(10000)).toBe(100)
  })

  it('roundtrip: toCents(toEuros(x)) === x', () => {
    expect(toCents(toEuros(1250))).toBe(1250)
    expect(toCents(toEuros(999))).toBe(999)
  })
})

describe('parsePrice (input utente → centesimi)', () => {
  it('parse formato italiano (virgola decimale)', () => {
    expect(parsePrice('12,50')).toBe(1250)
    expect(parsePrice('8,90')).toBe(890)
  })

  it('parse formato inglese (punto decimale)', () => {
    expect(parsePrice('12.50')).toBe(1250)
  })

  it('parse con migliaia italiane', () => {
    expect(parsePrice('1.234,56')).toBe(123456)
  })

  it('parse con migliaia inglesi', () => {
    expect(parsePrice('1,234.56')).toBe(123456)
  })

  it('parse con simbolo EUR', () => {
    expect(parsePrice('12,50 EUR')).toBe(1250)
    expect(parsePrice('€12,50')).toBe(1250)
  })

  it('parse con non-breaking space', () => {
    expect(parsePrice('12,50\u00A0€')).toBe(1250)
  })

  it('gestisce null/undefined/vuoto', () => {
    expect(parsePrice(null)).toBeNull()
    expect(parsePrice(undefined)).toBeNull()
    expect(parsePrice('')).toBeNull()
  })
})
