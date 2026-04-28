/**
 * adminPrezziHelpers — helpers + costanti default per pagina admin Prezzi.
 *
 * Riusa le constants/calc base da priceBandsConstants/priceBandsCalc, con
 * versioni "admin" specifiche (centesimi-euro conversion, snapshot clone, ecc.).
 */

import {
	FALLBACK_WEIGHT_BANDS,
	FALLBACK_VOLUME_BANDS,
	DEFAULT_EXTRA_RULES,
	DEFAULT_SUPPLEMENTS,
	DEFAULT_PROMO,
	DEFAULT_EUROPE_PRICING,
	DEFAULT_SERVICE_PRICING,
	DEFAULT_AUTOMATIC_SUPPLEMENTS,
	DEFAULT_OPERATIONAL_FEES,
	toInt,
	toNumber,
	normalizeDecimal,
} from '~/utils/priceBandsConstants'

import {
	effectivePriceCents,
	getBandPriceCents,
	normalizeBandArray,
	normalizeEuropePricing,
	normalizeKeyedPricingGroup,
	normalizeIncrementLadder,
} from '~/utils/priceBandsCalc'

// TYPES (interfacce TypeScript per pagina admin Prezzi)

// COSTANTI DEFAULT (alias per pagina admin con prefix ADMIN_)

export const ADMIN_DEFAULT_WEIGHT_BANDS = FALLBACK_WEIGHT_BANDS 
export const ADMIN_DEFAULT_VOLUME_BANDS = FALLBACK_VOLUME_BANDS 
export const ADMIN_DEFAULT_EXTRA_RULES = DEFAULT_EXTRA_RULES 
export const ADMIN_DEFAULT_SUPPLEMENTS = DEFAULT_SUPPLEMENTS 
export const ADMIN_DEFAULT_PROMO = DEFAULT_PROMO 
export const ADMIN_DEFAULT_EUROPE_PRICING = DEFAULT_EUROPE_PRICING 
export const ADMIN_DEFAULT_SERVICE_PRICING = DEFAULT_SERVICE_PRICING
export const ADMIN_DEFAULT_AUTOMATIC_SUPPLEMENTS = DEFAULT_AUTOMATIC_SUPPLEMENTS
export const ADMIN_DEFAULT_OPERATIONAL_FEES = DEFAULT_OPERATIONAL_FEES

// CONVERSIONE CENTS / EURO (admin-specifici)

/** Converte centesimi (number/string) in euro stringa con 2 decimali. */
export const adminCentsToEuro = (cents) => {
	const n = Number(cents)
	if (!Number.isFinite(n)) return '0.00'
	return (n / 100).toFixed(2)
}

/** Converte euro (number/string) in centesimi (integer). */
export const adminEuroToCents = (euros) => {
	if (euros === null || euros === undefined || euros === '') return 0
	const cleaned = String(euros).replace(',', '.').replace(/[^\d.-]/g, '')
	const n = Number.parseFloat(cleaned)
	return Number.isFinite(n) ? Math.round(n * 100) : 0
}

/** Variante per increment_cents (sempre >= 0, no decimal). */
export const adminIncrementCentsToEuro = (cents) => {
	const n = Math.max(0, toInt(cents, 0))
	return (n / 100).toFixed(2)
}

// CALCOLI PREZZO (delega a priceBandsCalc)

/** Prezzo effettivo (discount se presente, altrimenti base). */
export const effectivePrice = (band) => effectivePriceCents(band)

/** Calcolo prezzo per peso/volume con bands + extra rules. */
export const calculateBandPriceCents = (
	type: 'weight' | 'volume',
	rawValue,
	priceBandsValue: { weight?: PriceBand[]; volume?: PriceBand[]; extra_rules?: ExtraRules },
) => getBandPriceCents(type, rawValue, priceBandsValue as never)

/**
 * Info discount per visualizzazione cella tabella admin.
 */
export const discountInfo = (band): { percentage: number | null; hasDiscount: boolean } => {
	const base = toInt(band.base_price, 0)
	const discount = band.discount_price !== null && band.discount_price !== undefined ? toInt(band.discount_price, 0) : null
	if (discount === null || discount >= base || base <= 0) {
		return { percentage: null, hasDiscount: false }
	}
	return {
		percentage: Math.round((1 - discount / base) * 100),
		hasDiscount,
	}
}

// SNAPSHOT / CLONE (per dirty-tracking admin)

/** Deep clone JSON-safe per snapshot stato originale. */
export const cloneForSnapshot = (value) => {
	if (value === null || value === undefined) return value
	return JSON.parse(JSON.stringify(value))
}

// NORMALIZZAZIONE PAYLOAD (admin-specific wrappers)

/** Wrapper su normalizeEuropePricing del modulo public. */
export const adminNormalizeEuropePricing = (config = {}) =>
	normalizeEuropePricing(config) 

/** Normalizza ladder per payload API admin. */
export const normalizeLadderForPayload = (ladder, fallbackIncrementCents) =>
	normalizeIncrementLadder(ladder, fallbackIncrementCents) 

/** Re-export per il flusso admin servizi. */
export const normalizePricingGroup = normalizeKeyedPricingGroup

/**
 * Costruisce payload API per regole pricing.
 * Mantiene struttura backend, copia shallow di ogni rule.
 */
export const buildPricingRulesPayload = (group: Record<string, unknown>): Record<string, unknown> => {
	const result: Record<string, unknown> = {}
	for (const [key, rule] of Object.entries(group || {})) {
		if (!rule || typeof rule !== 'object') continue
		result[key] = { ...(rule as Record<string, unknown>) }
	}
	return result
}

/**
 * Sanitizza input array virgola-separato (province, country, keyword, flag).
 */
export const normalizeArrayFieldInput = (
	input,
	{ uppercase = false }: { uppercase?: boolean } = {},
) => {
	if (Array.isArray(input)) {
		return input.map((s) => String(s ?? '').trim()).filter(Boolean).map((s) => uppercase ? s.toUpperCase() : s.toLowerCase())
	}
	const str = String(input ?? '')
	return str
		.split(/[,\n]/)
		.map((s) => s.trim())
		.filter(Boolean)
		.map((s) => uppercase ? s.toUpperCase() : s.toLowerCase())
}

// LABEL MAPPING (per visualizzazione UI admin)

const APPLICATION_LABELS = {
	per_spedizione: 'Per spedizione',
	per_collo: 'Per collo',
	automatic_destination: 'Automatico destinazione',
	automatic_destination_per_package: 'Automatico destinazione (per collo)',
	automatic_per_package: 'Automatico per collo',
	automatic_package_shape: 'Automatico forma collo',
	manual_quote_only: 'Solo preventivo manuale',
	manual_admin: 'Solo admin (manuale)',
}

/** Traduce application key tecnica in label UI italiana. */
export const formatApplicationLabel = (application) => {
	const key = String(application ?? '').trim().toLowerCase()
	return APPLICATION_LABELS[key] || key
}

// Helpers re-export per backward compat caller
export { toInt, toNumber, normalizeDecimal, normalizeBandArray }
