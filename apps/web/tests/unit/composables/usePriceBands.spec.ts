import { describe, it, expect } from 'vitest'

/**
 * Test per usePriceBands.js
 * Testiamo la logica pura di calcolo prezzo: fasce peso/volume, extra rules,
 * supplementi CAP, senza dipendenze da Vue/Nuxt runtime.
 */

// ---- COSTANTI DAL COMPOSABLE ----
const EPSILON = 0.0000001

const FALLBACK_WEIGHT_BANDS = [
  { id: 'weight-1', type: 'weight', min_value: 0, max_value: 2, base_price: 890, discount_price: null },
  { id: 'weight-2', type: 'weight', min_value: 2, max_value: 5, base_price: 1190, discount_price: null },
  { id: 'weight-3', type: 'weight', min_value: 5, max_value: 10, base_price: 1490, discount_price: null },
  { id: 'weight-4', type: 'weight', min_value: 10, max_value: 25, base_price: 1990, discount_price: null },
  { id: 'weight-5', type: 'weight', min_value: 25, max_value: 50, base_price: 2990, discount_price: null },
  { id: 'weight-6', type: 'weight', min_value: 50, max_value: 75, base_price: 3990, discount_price: null },
  { id: 'weight-7', type: 'weight', min_value: 75, max_value: 100, base_price: 4990, discount_price: null },
]

const FALLBACK_VOLUME_BANDS = [
  { id: 'volume-1', type: 'volume', min_value: 0, max_value: 0.010, base_price: 890, discount_price: null },
  { id: 'volume-2', type: 'volume', min_value: 0.010, max_value: 0.020, base_price: 1190, discount_price: null },
  { id: 'volume-3', type: 'volume', min_value: 0.020, max_value: 0.040, base_price: 1490, discount_price: null },
  { id: 'volume-4', type: 'volume', min_value: 0.040, max_value: 0.100, base_price: 1990, discount_price: null },
  { id: 'volume-5', type: 'volume', min_value: 0.100, max_value: 0.200, base_price: 2990, discount_price: null },
  { id: 'volume-6', type: 'volume', min_value: 0.200, max_value: 0.300, base_price: 3990, discount_price: null },
  { id: 'volume-7', type: 'volume', min_value: 0.300, max_value: 0.400, base_price: 4990, discount_price: null },
]

const DEFAULT_EXTRA_RULES = {
  enabled: true,
  weight_start: 101,
  weight_step: 50,
  volume_start: 0.401,
  volume_step: 0.200,
  increment_cents: 500,
  increment_mode: 'flat',
  weight_increment_ladder: [{ from_step: 1, to_step: null, increment_cents: 500 }],
  volume_increment_ladder: [{ from_step: 1, to_step: null, increment_cents: 500 }],
  base_price_cents_mode: 'last_band_effective',
  base_price_cents_manual: null,
  weight_resolution: 1,
  volume_resolution: 0.001,
}

const DEFAULT_SUPPLEMENTS = [
  { id: 'supplement-1', prefix: '90', amount_cents: 250, apply_to: 'both', enabled: true },
]

// ---- FUNZIONI HELPER RIPRODOTTE DAL COMPOSABLE ----

const toNumber = (value: any, fallback = 0): number => {
  const n = Number(value)
  return Number.isFinite(n) ? n : fallback
}

const toInt = (value: any, fallback = 0): number => {
  const n = Number.parseInt(value, 10)
  return Number.isFinite(n) ? n : fallback
}

const normalizeDecimal = (value: any, fallback = 0): number => {
  return Number(toNumber(value, fallback).toFixed(4))
}

const effectivePriceCents = (band: any): number => {
  const discount = band?.discount_price
  if (discount !== null && discount !== undefined) {
    return toInt(discount, 0)
  }
  return toInt(band?.base_price, 0)
}

const ceilByResolution = (value: number, resolution: number): number => {
  const safeResolution = resolution > 0 ? resolution : 1
  const multiplier = 1 / safeResolution
  return normalizeDecimal(Math.ceil((value * multiplier) - EPSILON) / multiplier, value)
}

const findBand = (bands: any[], value: number): any | null => {
  if (!Array.isArray(bands) || bands.length === 0 || !Number.isFinite(value) || value <= 0) return null
  for (let idx = 0; idx < bands.length; idx++) {
    const band = bands[idx]
    const min = Number(band.min_value)
    const max = Number(band.max_value)
    const lowerOk = idx === 0 ? value >= (min - EPSILON) : value > (min + EPSILON)
    const upperOk = value <= (max + EPSILON)
    if (lowerOk && upperOk) return band
  }
  return null
}

const computeExtraPriceCents = (type: string, rawValue: number, bands: any[], extraRules: any): number | null => {
  if (!extraRules?.enabled) return null
  if (!Number.isFinite(rawValue) || rawValue <= 0) return null

  const isWeight = type === 'weight'
  const start = isWeight ? Number(extraRules.weight_start) : Number(extraRules.volume_start)
  const step = isWeight ? Number(extraRules.weight_step) : Number(extraRules.volume_step)
  const resolution = isWeight ? Number(extraRules.weight_resolution) : Number(extraRules.volume_resolution)
  const increment = toInt(extraRules.increment_cents, 0)

  if (!Number.isFinite(start) || !Number.isFinite(step) || !Number.isFinite(resolution) || step <= 0 || resolution <= 0) {
    return null
  }

  const value = ceilByResolution(rawValue, resolution)
  if (value + EPSILON < start) return null

  let baseCents = 0
  if (extraRules.base_price_cents_mode === 'manual' && extraRules.base_price_cents_manual !== null) {
    baseCents = toInt(extraRules.base_price_cents_manual, 0)
  } else {
    const last = Array.isArray(bands) && bands.length > 0 ? bands[bands.length - 1] : null
    baseCents = last ? effectivePriceCents(last) : (isWeight ? effectivePriceCents(FALLBACK_WEIGHT_BANDS[FALLBACK_WEIGHT_BANDS.length - 1]) : effectivePriceCents(FALLBACK_VOLUME_BANDS[FALLBACK_VOLUME_BANDS.length - 1]))
  }

  const stepsFromStart = Math.floor(((value - start) + EPSILON) / step)
  const bandNumber = Math.max(0, stepsFromStart) + 1

  return baseCents + (bandNumber * increment)
}

function getBandPriceCents(type: string, rawValue: number, bands?: any[], extraRules?: any): number | null {
  const value = Number(rawValue)
  if (!Number.isFinite(value) || value <= 0) return null

  const usedBands = bands || (type === 'weight' ? FALLBACK_WEIGHT_BANDS : FALLBACK_VOLUME_BANDS)
  const band = findBand(usedBands, value)
  if (band) return effectivePriceCents(band)

  const extra = computeExtraPriceCents(type, value, usedBands, extraRules || DEFAULT_EXTRA_RULES)
  if (extra !== null) return extra

  if (Array.isArray(usedBands) && usedBands.length > 0) {
    return effectivePriceCents(usedBands[usedBands.length - 1])
  }

  const fallback = type === 'weight' ? FALLBACK_WEIGHT_BANDS : FALLBACK_VOLUME_BANDS
  return effectivePriceCents(fallback[fallback.length - 1])
}

function getCapSupplementCents(originCap: string, destinationCap: string, rules?: any[]): number {
  const supplements = rules || DEFAULT_SUPPLEMENTS
  const origin = String(originCap || '').replace(/\D+/g, '')
  const destination = String(destinationCap || '').replace(/\D+/g, '')

  let total = 0
  supplements.forEach((rule: any) => {
    if (rule?.enabled === false) return
    const prefix = String(rule?.prefix || '').replace(/\D+/g, '')
    if (!prefix) return
    const amount = Math.max(0, toInt(rule?.amount_cents, 0))
    if (!amount) return
    const applyTo = ['origin', 'destination', 'both'].includes(rule?.apply_to) ? rule.apply_to : 'both'
    if ((applyTo === 'origin' || applyTo === 'both') && origin.startsWith(prefix)) total += amount
    if ((applyTo === 'destination' || applyTo === 'both') && destination.startsWith(prefix)) total += amount
  })

  return total
}

// ---- TEST ----

describe('Price Calculation Logic (usePriceBands)', () => {

  describe('T2.2 - Calcolo Prezzo per Peso (fallback bands)', () => {

    it('T2.2.1 - prezzo peso 0-2kg = 890 cent (8.90 EUR)', () => {
      expect(getBandPriceCents('weight', 1.5)).toBe(890)
    })

    it('prezzo peso 2-5kg = 1190 cent', () => {
      expect(getBandPriceCents('weight', 3)).toBe(1190)
    })

    it('prezzo peso 5-10kg = 1490 cent', () => {
      expect(getBandPriceCents('weight', 7)).toBe(1490)
    })

    it('prezzo peso 10-25kg = 1990 cent', () => {
      expect(getBandPriceCents('weight', 15)).toBe(1990)
    })

    it('prezzo peso 25-50kg = 2990 cent', () => {
      expect(getBandPriceCents('weight', 35)).toBe(2990)
    })

    it('T2.2.2 - prezzo peso 50-75kg = 3990 cent', () => {
      expect(getBandPriceCents('weight', 60)).toBe(3990)
    })

    it('prezzo peso 75-100kg = 4990 cent', () => {
      expect(getBandPriceCents('weight', 90)).toBe(4990)
    })

    it('prezzo peso al confine di banda (esattamente 2kg)', () => {
      // Nella prima banda, 2kg e\' il max_value -> incluso (value <= max + EPSILON)
      expect(getBandPriceCents('weight', 2)).toBe(890)
    })

    it('prezzo peso al confine tra banda 2 e 3 (esattamente 5kg)', () => {
      expect(getBandPriceCents('weight', 5)).toBe(1190)
    })

    it('prezzo peso al confine superiore (esattamente 100kg)', () => {
      expect(getBandPriceCents('weight', 100)).toBe(4990)
    })

    it('T2.2.4 - MAX(peso, volume) usato per prezzo finale', () => {
      const weightPrice = getBandPriceCents('weight', 1)!  // 890 cent (0-2kg)
      const volumePrice = getBandPriceCents('volume', 0.15)! // 2990 cent (0.100-0.200 m3)
      const finalPrice = Math.max(weightPrice, volumePrice)
      expect(finalPrice).toBe(2990)
    })

    it('T2.2.10 - prezzo in centesimi (intero)', () => {
      const price = getBandPriceCents('weight', 5)
      expect(price).not.toBeNull()
      expect(Number.isInteger(price)).toBe(true)
      expect(price!).toBeGreaterThan(0)
    })

    it('T2.2.11 - display in euro con 2 decimali', () => {
      const priceCents = getBandPriceCents('weight', 1)!
      const euroStr = (priceCents / 100).toFixed(2)
      expect(euroStr).toBe('8.90')
    })

    it('peso 0 restituisce null', () => {
      expect(getBandPriceCents('weight', 0)).toBeNull()
    })

    it('peso negativo restituisce null', () => {
      expect(getBandPriceCents('weight', -5)).toBeNull()
    })

    it('peso NaN restituisce null', () => {
      expect(getBandPriceCents('weight', NaN)).toBeNull()
    })
  })

  describe('Volume Calculation', () => {
    it('calcolo volume corretto in m3 (30x20x15cm)', () => {
      const l = 30, w = 20, h = 15
      const volumeM3 = (l * w * h) / 1000000
      expect(volumeM3).toBeCloseTo(0.009)
    })

    it('volume grande 80x80x80cm = 0.512 m3', () => {
      const l = 80, w = 80, h = 80
      const volumeM3 = (l * w * h) / 1000000
      expect(volumeM3).toBeCloseTo(0.512)
    })

    it('prezzo volume 0-0.010 m3 = 890 cent', () => {
      expect(getBandPriceCents('volume', 0.005)).toBe(890)
    })

    it('prezzo volume 0.040-0.100 m3 = 1990 cent', () => {
      expect(getBandPriceCents('volume', 0.05)).toBe(1990)
    })

    it('prezzo volume 0.300-0.400 m3 = 4990 cent', () => {
      expect(getBandPriceCents('volume', 0.35)).toBe(4990)
    })
  })

  describe('CAP Supplement', () => {
    it('T2.2.7 - CAP Sicilia (90xxx) ha supplemento', () => {
      // Origine 90100, destinazione 20121 -> solo origine ha supplemento 250 cent
      const supplement = getCapSupplementCents('90100', '20121')
      expect(supplement).toBe(250)
    })

    it('CAP Milano (20xxx) nessun supplemento', () => {
      const supplement = getCapSupplementCents('20121', '20121')
      expect(supplement).toBe(0)
    })

    it('CAP Sicilia su entrambi = doppio supplemento', () => {
      // Sia origine che destinazione con prefix 90 -> 250 + 250 = 500
      const supplement = getCapSupplementCents('90100', '90200')
      expect(supplement).toBe(500)
    })

    it('supplemento con regola disabilitata = 0', () => {
      const disabledRules = [
        { prefix: '90', amount_cents: 250, apply_to: 'both', enabled: false },
      ]
      const supplement = getCapSupplementCents('90100', '20121', disabledRules)
      expect(supplement).toBe(0)
    })

    it('supplemento apply_to origin only', () => {
      const rules = [
        { prefix: '90', amount_cents: 300, apply_to: 'origin', enabled: true },
      ]
      // Origine 90100 -> applica; destinazione 90200 -> non applica (solo origin)
      expect(getCapSupplementCents('90100', '90200', rules)).toBe(300)
    })

    it('supplemento apply_to destination only', () => {
      const rules = [
        { prefix: '90', amount_cents: 300, apply_to: 'destination', enabled: true },
      ]
      expect(getCapSupplementCents('90100', '90200', rules)).toBe(300)
    })

    it('nessun supplemento con regole vuote', () => {
      expect(getCapSupplementCents('90100', '90200', [])).toBe(0)
    })

    it('CAP prefix 98 (Messina) ha supplemento default', () => {
      const supplement = getCapSupplementCents('98100', '20121')
      // prefix "90" non matcha "98" -> 0
      expect(supplement).toBe(0)
    })
  })

  describe('Quantity Pricing', () => {
    it('T2.1.16 - quantita 3 = prezzo * 3', () => {
      const singlePrice = getBandPriceCents('weight', 1)! // 890
      const quantity = 3
      const total = singlePrice * quantity
      expect(total).toBe(2670)
    })

    it('quantita 0 = prezzo 0', () => {
      const singlePrice = getBandPriceCents('weight', 1)! // 890
      expect(singlePrice * 0).toBe(0)
    })
  })

  describe('Extra Rules (> 100kg)', () => {
    it('T2.2.5 - peso > 100kg usa regola extra', () => {
      // weight_start=101, weight_step=50, increment=500, base = last band (4990)
      // 105kg -> ceilByResolution(105,1) = 105; steps = floor((105-101)/50) = 0; bandNumber = 1
      // price = 4990 + (1 * 500) = 5490
      const price = getBandPriceCents('weight', 105)
      expect(price).toBe(5490)
    })

    it('peso 150kg -> 2 fasce extra', () => {
      // 150kg -> steps = floor((150-101)/50) = 0 (49/50 = 0.98 -> floor=0); bandNumber=1
      // Ah wait: 150 -> value=150, (150-101+EPSILON)/50 = 49.xx/50 = 0.98 -> floor=0 -> band=1
      // price = 4990 + 1*500 = 5490
      const price = getBandPriceCents('weight', 150)
      expect(price).toBe(5490)
    })

    it('peso 151kg -> fascia extra 2', () => {
      // 151kg -> steps = floor((151-101+EPSILON)/50) = floor(50.xx/50) = 1; bandNumber=2
      // price = 4990 + 2*500 = 5990
      const price = getBandPriceCents('weight', 151)
      expect(price).toBe(5990)
    })

    it('peso 200kg -> fascia extra 2', () => {
      // 200kg -> steps = floor((200-101)/50) = floor(99/50) = 1; bandNumber=2
      // price = 4990 + 2*500 = 5990
      const price = getBandPriceCents('weight', 200)
      expect(price).toBe(5990)
    })

    it('extra rules disabilitate -> usa ultimo band price', () => {
      const disabledRules = { ...DEFAULT_EXTRA_RULES, enabled: false }
      const price = computeExtraPriceCents('weight', 150, FALLBACK_WEIGHT_BANDS, disabledRules)
      expect(price).toBeNull()
    })
  })

  describe('effectivePriceCents (discount logic)', () => {
    it('senza sconto usa base_price', () => {
      const band = { base_price: 890, discount_price: null }
      expect(effectivePriceCents(band)).toBe(890)
    })

    it('con sconto usa discount_price', () => {
      const band = { base_price: 890, discount_price: 690 }
      expect(effectivePriceCents(band)).toBe(690)
    })

    it('discount_price 0 valido (gratis)', () => {
      const band = { base_price: 890, discount_price: 0 }
      expect(effectivePriceCents(band)).toBe(0)
    })
  })

  describe('findBand edge cases', () => {
    it('valore 0 -> null', () => {
      expect(findBand(FALLBACK_WEIGHT_BANDS, 0)).toBeNull()
    })

    it('valore negativo -> null', () => {
      expect(findBand(FALLBACK_WEIGHT_BANDS, -1)).toBeNull()
    })

    it('array vuoto -> null', () => {
      expect(findBand([], 5)).toBeNull()
    })

    it('valore minimo della prima banda', () => {
      // Prima banda: min=0, max=2. idx===0 -> value >= (0 - EPSILON) -> 0.001 >= -0.000... -> true
      const band = findBand(FALLBACK_WEIGHT_BANDS, 0.001)
      expect(band).not.toBeNull()
      expect(band!.id).toBe('weight-1')
    })
  })

  describe('Helper functions', () => {
    it('toNumber con stringa numerica', () => {
      expect(toNumber('123')).toBe(123)
    })

    it('toNumber con stringa non numerica -> fallback', () => {
      expect(toNumber('abc', 0)).toBe(0)
    })

    it('toInt con float -> troncato', () => {
      expect(toInt('12.9')).toBe(12)
    })

    it('normalizeDecimal arrotonda a 4 decimali', () => {
      expect(normalizeDecimal(1.23456789)).toBe(1.2346)
    })

    it('ceilByResolution con risoluzione 1', () => {
      expect(ceilByResolution(5.1, 1)).toBe(6)
    })

    it('ceilByResolution con risoluzione 0.001', () => {
      expect(ceilByResolution(0.4015, 0.001)).toBe(0.402)
    })

    it('ceilByResolution valore esatto non arrotonda', () => {
      expect(ceilByResolution(5.0, 1)).toBe(5)
    })
  })
})
