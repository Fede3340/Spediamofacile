import { describe, it, expect } from 'vitest'
import {
  normalizeLocationText,
  getProvinceLabel,
  locationKey,
  dedupeLocations,
} from '~/utils/location'

describe('normalizeLocationText', () => {
  it('converte in minuscolo', () => {
    expect(normalizeLocationText('MILANO')).toBe('milano')
  })

  it('rimuove accenti', () => {
    expect(normalizeLocationText('Forlì')).toBe('forli')
    expect(normalizeLocationText('Còsenza')).toBe('cosenza')
  })

  it('collassa spazi multipli', () => {
    expect(normalizeLocationText('San   Benedetto')).toBe('san benedetto')
  })

  it('gestisce stringa vuota/null', () => {
    expect(normalizeLocationText('')).toBe('')
    expect(normalizeLocationText(null)).toBe('null')
    expect(normalizeLocationText(undefined)).toBe('')
  })
})

describe('getProvinceLabel', () => {
  it('legge campo province', () => {
    expect(getProvinceLabel({ province: 'mi' })).toBe('MI')
  })

  it('fallback a province_name', () => {
    expect(getProvinceLabel({ province_name: 'rm' })).toBe('RM')
  })

  it('gestisce oggetto vuoto', () => {
    expect(getProvinceLabel({})).toBe('')
  })
})

describe('locationKey', () => {
  it('genera chiave univoca', () => {
    const loc = { postal_code: '20100', place_name: 'Milano', province: 'MI' }
    expect(locationKey(loc)).toBe('20100|milano|MI')
  })

  it('normalizza case nel nome', () => {
    const loc = { postal_code: '00100', place_name: 'ROMA', province: 'RM' }
    expect(locationKey(loc)).toBe('00100|roma|RM')
  })
})

describe('dedupeLocations', () => {
  it('rimuove duplicati per chiave', () => {
    const locations = [
      { postal_code: '20100', place_name: 'Milano', province: 'MI' },
      { postal_code: '20100', place_name: 'Milano', province: 'MI' },
      { postal_code: '00100', place_name: 'Roma', province: 'RM' },
    ]
    const result = dedupeLocations(locations)
    expect(result).toHaveLength(2)
  })

  it('ignora entry senza place_name', () => {
    const locations = [
      { postal_code: '20100', place_name: '', province: 'MI' },
      { postal_code: '20100', place_name: 'Milano', province: 'MI' },
    ]
    const result = dedupeLocations(locations)
    expect(result).toHaveLength(1)
    expect(result[0].place_name).toBe('Milano')
  })

  it('gestisce array vuoto', () => {
    expect(dedupeLocations([])).toHaveLength(0)
    expect(dedupeLocations()).toHaveLength(0)
  })
})
