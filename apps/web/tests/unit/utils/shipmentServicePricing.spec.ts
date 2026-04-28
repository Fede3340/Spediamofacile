import { describe, it, expect } from 'vitest'
import {
  parseCurrencyAmount,
  normalizeServiceKey,
  normalizeSelectedServices,
} from '~/utils/shipmentServicePricing'

describe('parseCurrencyAmount', () => {
  it('parse numero diretto', () => {
    expect(parseCurrencyAmount(12.5)).toBe(12.5)
    expect(parseCurrencyAmount(0)).toBe(0)
  })

  it('parse stringa con virgola italiana', () => {
    expect(parseCurrencyAmount('12,50')).toBe(12.5)
  })

  it('parse stringa con simbolo euro', () => {
    expect(parseCurrencyAmount('12,50 €')).toBe(12.5)
    expect(parseCurrencyAmount('€12,50')).toBe(12.5)
  })

  it('parse stringa con migliaia italiane (punto)', () => {
    expect(parseCurrencyAmount('1.250,00')).toBe(1250)
  })

  it('gestisce null/undefined', () => {
    expect(parseCurrencyAmount(null)).toBe(0)
    expect(parseCurrencyAmount(undefined)).toBe(0)
  })

  it('gestisce NaN/Infinity', () => {
    expect(parseCurrencyAmount(NaN)).toBe(0)
    expect(parseCurrencyAmount(Infinity)).toBe(0)
  })

  it('gestisce stringa non numerica', () => {
    expect(parseCurrencyAmount('abc')).toBe(0)
  })
})

describe('normalizeServiceKey', () => {
  it('normalizza "Contrassegno" -> "contrassegno"', () => {
    expect(normalizeServiceKey('Contrassegno')).toBe('contrassegno')
  })

  it('normalizza "Assicurazione" -> "assicurazione"', () => {
    expect(normalizeServiceKey('Assicurazione')).toBe('assicurazione')
  })

  it('normalizza "Senza Etichetta" -> "senza_etichetta"', () => {
    expect(normalizeServiceKey('Senza Etichetta')).toBe('senza_etichetta')
  })

  it('normalizza "Sponda Idraulica" -> "sponda_idraulica"', () => {
    expect(normalizeServiceKey('Sponda Idraulica')).toBe('sponda_idraulica')
  })

  it('normalizza "SMS/Email Notification" -> "sms_email_notification"', () => {
    expect(normalizeServiceKey('SMS Notification')).toBe('sms_email_notification')
  })

  it('"Nessuno" diventa stringa vuota', () => {
    expect(normalizeServiceKey('Nessuno')).toBe('')
    expect(normalizeServiceKey('nessuno')).toBe('')
  })

  it('gestisce null/undefined/vuoto', () => {
    expect(normalizeServiceKey(null)).toBe('')
    expect(normalizeServiceKey(undefined)).toBe('')
    expect(normalizeServiceKey('')).toBe('')
  })
})

describe('normalizeSelectedServices', () => {
  it('parse stringa comma-separated', () => {
    expect(normalizeSelectedServices('Contrassegno, Assicurazione')).toEqual([
      'contrassegno',
      'assicurazione',
    ])
  })

  it('parse array', () => {
    expect(normalizeSelectedServices(['Contrassegno', 'Assicurazione'])).toEqual([
      'contrassegno',
      'assicurazione',
    ])
  })

  it('rimuove duplicati', () => {
    expect(normalizeSelectedServices('Contrassegno, Contrassegno')).toEqual([
      'contrassegno',
    ])
  })

  it('"Nessuno" diventa array vuoto', () => {
    expect(normalizeSelectedServices('Nessuno')).toEqual([])
    expect(normalizeSelectedServices('nessuno')).toEqual([])
  })

  it('gestisce null/undefined/vuoto', () => {
    expect(normalizeSelectedServices(null)).toEqual([])
    expect(normalizeSelectedServices(undefined)).toEqual([])
    expect(normalizeSelectedServices('')).toEqual([])
  })
})
