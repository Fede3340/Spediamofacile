/**
 * adminPricingHelpers — utilities pure per il pannello admin pricing.
 *
 * Sezioni:
 *   1. Normalizzatori payload (string list, tiers, pricing group, europe)
 *   2. Conversioni cents <-> euro (admin-specific)
 *   3. Calcoli prezzo (delegano a priceBandsCalc)
 *   4. Snapshot / clone per dirty-tracking
 *   5. Re-export costanti default + label mapping UI
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
} from '~/utils/priceBandsConstants'
import {
	effectivePriceCents,
	getBandPriceCents,
	normalizeEuropePricing,
	normalizeKeyedPricingGroup,
	normalizeIncrementLadder,
} from '~/utils/priceBandsCalc'
import type { EuropeBand, EuropePricing, EuropeRate, ExtraRules, IncrementLadderRow, PriceBand, PricingRule, PricingRuleGroup } from '~/types/pricing'

type UnknownRecord = Record<string, unknown>;
type AdminTier = { up_to_kg: number | null; price_cents: number };

const isRecord = (value: unknown): value is UnknownRecord => value !== null && typeof value === 'object' && !Array.isArray(value);
const numberOrNull = (value: unknown, fallback: number | null = null): number | null => {
	if (value === null || value === undefined || value === '') return fallback;
	return Number(value || 0);
};
const arrayFrom = (value: unknown): unknown[] => Array.isArray(value) ? value : [];

// ─── 1. Normalizzatori payload ────────────────────────────────────

export const normalizeStringListForAdmin = (values: unknown[] = [], { uppercase = false }: { uppercase?: boolean } = {}): string[] => {
	if (!Array.isArray(values)) return [];
	return [...new Set(values
		.map((value) => String(value || '').trim())
		.filter(Boolean)
		.map((value) => uppercase ? value.toUpperCase() : value.toLowerCase()))];
};

export const normalizeTiersForAdmin = (tiers: unknown[] = []): AdminTier[] => {
	if (!Array.isArray(tiers)) return [];
	return [...tiers]
		.map((tier) => ({
			up_to_kg: isRecord(tier) && (tier.up_to_kg === null || tier.up_to_kg === undefined || tier.up_to_kg === '')
				? null
				: Number(isRecord(tier) ? tier.up_to_kg : 0),
			price_cents: Number(isRecord(tier) ? tier.price_cents || 0 : 0),
		}))
		.sort((a, b) => {
			const left = a.up_to_kg ?? Number.POSITIVE_INFINITY;
			const right = b.up_to_kg ?? Number.POSITIVE_INFINITY;
			return left - right;
		});
};

export const normalizePricingGroupForAdmin = (
	config: Record<string, unknown> = {},
	defaults: PricingRuleGroup = {},
): PricingRuleGroup => Object.fromEntries(
	Object.entries(defaults).map(([key, fallback]) => {
		const fallbackRule = fallback;
		const source = isRecord(config[key]) ? config[key] : {};
		return [key, {
			...fallbackRule,
			...source,
			enabled: source.enabled !== false && fallbackRule.enabled !== false,
			price_cents: numberOrNull(source.price_cents, fallbackRule.price_cents ?? null),
			min_fee_cents: numberOrNull(source.min_fee_cents, fallbackRule.min_fee_cents ?? null),
			percentage_rate: numberOrNull(source.percentage_rate, fallbackRule.percentage_rate ?? null),
			threshold_amount_eur: numberOrNull(source.threshold_amount_eur, fallbackRule.threshold_amount_eur ?? null),
			max_weight_kg: numberOrNull(source.max_weight_kg, fallbackRule.max_weight_kg ?? null),
			threshold_cm: numberOrNull(source.threshold_cm, fallbackRule.threshold_cm ?? null),
			longest_side_threshold_cm: numberOrNull(source.longest_side_threshold_cm, fallbackRule.longest_side_threshold_cm ?? null),
			girth_threshold_cm: numberOrNull(source.girth_threshold_cm, fallbackRule.girth_threshold_cm ?? null),
			min_longest_side_cm: numberOrNull(source.min_longest_side_cm, fallbackRule.min_longest_side_cm ?? null),
			max_secondary_side_cm: numberOrNull(source.max_secondary_side_cm, fallbackRule.max_secondary_side_cm ?? null),
			province_codes: Array.isArray(source?.province_codes)
				? normalizeStringListForAdmin(arrayFrom(source.province_codes), { uppercase: true })
				: [...(fallbackRule.province_codes || [])],
			country_codes: Array.isArray(source?.country_codes)
				? normalizeStringListForAdmin(arrayFrom(source.country_codes), { uppercase: true })
				: [...(fallbackRule.country_codes || [])],
			keyword_list: Array.isArray(source?.keyword_list)
				? normalizeStringListForAdmin(arrayFrom(source.keyword_list))
				: [...(fallbackRule.keyword_list || [])],
			flag_keys: Array.isArray(source?.flag_keys)
				? normalizeStringListForAdmin(arrayFrom(source.flag_keys))
				: [...(fallbackRule.flag_keys || [])],
			delivery_modes: Array.isArray(source?.delivery_modes)
				? normalizeStringListForAdmin(arrayFrom(source.delivery_modes))
				: [...(fallbackRule.delivery_modes || [])],
			tiers: Array.isArray(source?.tiers)
				? normalizeTiersForAdmin(arrayFrom(source.tiers))
				: normalizeTiersForAdmin(fallbackRule.tiers || []),
		} satisfies PricingRule];
	}),
);

export const normalizeEuropePricingForAdmin = (config: Partial<EuropePricing> | UnknownRecord = {}): EuropePricing => {
	const source = isRecord(config) ? config : {};
	const bands: EuropeBand[] = Array.isArray(source.bands)
		? source.bands.map((rawBand, bandIndex) => {
			const band = isRecord(rawBand) ? rawBand : {};
			return {
				id: String(band.id ?? `eu-band-${bandIndex + 1}`),
				label: String(band.label ?? '').trim(),
				max_weight_kg: Number(band.max_weight_kg ?? 0),
				max_volume_m3: Number(band.max_volume_m3 ?? 0),
				volumetric_factor: Number(band.volumetric_factor ?? 250),
				rates: Array.isArray(band.rates)
					? band.rates.map((rawRate): EuropeRate => {
						const rate = isRecord(rawRate) ? rawRate : {};
						return {
							country_code: String(rate.country_code ?? '').trim().toUpperCase(),
							country_name: String(rate.country_name ?? '').trim(),
							price_cents: rate.price_cents === null || rate.price_cents === '' || rate.price_cents === undefined
						? null
						: Number(rate.price_cents),
							quote_required: rate.quote_required === true,
						};
					})
					: [],
			};
		})
		: [];

	return {
		...DEFAULT_EUROPE_PRICING,
		enabled: source.enabled !== false,
		origin_country_code: String(source.origin_country_code ?? 'IT').trim().toUpperCase() || 'IT',
		max_packages: 1,
		max_quantity_per_package: 1,
		bands,
		supported_country_codes: Array.isArray(source.supported_country_codes)
			? source.supported_country_codes.map((code) => String(code).trim().toUpperCase()).filter(Boolean)
			: [...new Set(bands.flatMap((band) => band.rates.map((rate) => rate.country_code)))].sort(),
		version: typeof source.version === 'string' || typeof source.version === 'number' ? source.version : null,
	};
};

export const buildPricingRulesPayload = <T extends Record<string, unknown>>(group: T = {} as T): T =>
	JSON.parse(JSON.stringify(group)) as T;

// ─── 2. Conversioni cents <-> euro (admin) ────────────────────────

/** Converte centesimi (number/string) in euro stringa con 2 decimali. */
export const adminCentsToEuro = (cents: unknown): string => {
	const n = Number(cents)
	if (!Number.isFinite(n)) return '0.00'
	return (n / 100).toFixed(2)
}

/** Converte euro (number/string) in centesimi (integer). */
export const adminEuroToCents = (euros: unknown): number => {
	if (euros === null || euros === undefined || euros === '') return 0
	const cleaned = String(euros).replace(',', '.').replace(/[^\d.-]/g, '')
	const n = Number.parseFloat(cleaned)
	return Number.isFinite(n) ? Math.round(n * 100) : 0
}

/** Variante per increment_cents (sempre >= 0, no decimal). */
export const adminIncrementCentsToEuro = (cents: unknown): string => {
	const n = Math.max(0, toInt(cents, 0))
	return (n / 100).toFixed(2)
}

// ─── 3. Calcoli prezzo (delega a priceBandsCalc) ──────────────────

/** Prezzo effettivo (discount se presente, altrimenti base). */
export const effectivePrice = (band: Partial<PriceBand> | null | undefined) => effectivePriceCents(band)

/** Calcolo prezzo per peso/volume con bands + extra rules. */
export const calculateBandPriceCents = (
	type: 'weight' | 'volume',
	rawValue: number | string,
	priceBandsValue: { weight?: PriceBand[]; volume?: PriceBand[]; extra_rules?: ExtraRules },
) => getBandPriceCents(type, rawValue, {
	weight: priceBandsValue.weight || [],
	volume: priceBandsValue.volume || [],
	extra_rules: priceBandsValue.extra_rules || DEFAULT_EXTRA_RULES,
})

/** Info discount per visualizzazione cella tabella admin. */
export const discountInfo = (band: Partial<PriceBand>): { percentage: number | null; hasDiscount: boolean } => {
	const base = toInt(band.base_price, 0)
	const discount = band.discount_price !== null && band.discount_price !== undefined ? toInt(band.discount_price, 0) : null
	if (discount === null || discount >= base || base <= 0) {
		return { percentage: null, hasDiscount: false }
	}
	return {
		percentage: Math.round((1 - discount / base) * 100),
		hasDiscount: true,
	}
}

// ─── 4. Snapshot / clone per dirty-tracking ───────────────────────

/** Deep clone JSON-safe per snapshot stato originale. */
export const cloneForSnapshot = <T>(value: T): T => {
	if (value === null || value === undefined) return value
	return JSON.parse(JSON.stringify(value))
}

/** Wrapper su normalizeEuropePricing del modulo public. */
export const adminNormalizeEuropePricing = (config: Partial<EuropePricing> = {}) =>
	normalizeEuropePricing(config)

/** Normalizza ladder per payload API admin. */
export const normalizeLadderForPayload = (ladder: unknown, fallbackIncrementCents: unknown): IncrementLadderRow[] =>
	normalizeIncrementLadder(ladder, fallbackIncrementCents)

/** Re-export per il flusso admin servizi. */
export const normalizePricingGroup = normalizeKeyedPricingGroup

/** Sanitizza input array virgola-separato (province, country, keyword, flag). */
export const normalizeArrayFieldInput = (
	input: unknown,
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

// ─── 5. Costanti default + label UI ───────────────────────────────

export const ADMIN_DEFAULT_WEIGHT_BANDS = FALLBACK_WEIGHT_BANDS
export const ADMIN_DEFAULT_VOLUME_BANDS = FALLBACK_VOLUME_BANDS
export const ADMIN_DEFAULT_EXTRA_RULES = DEFAULT_EXTRA_RULES
export const ADMIN_DEFAULT_SUPPLEMENTS = DEFAULT_SUPPLEMENTS
export const ADMIN_DEFAULT_PROMO = DEFAULT_PROMO
export const ADMIN_DEFAULT_EUROPE_PRICING = DEFAULT_EUROPE_PRICING
export const ADMIN_DEFAULT_SERVICE_PRICING = DEFAULT_SERVICE_PRICING
export const ADMIN_DEFAULT_AUTOMATIC_SUPPLEMENTS = DEFAULT_AUTOMATIC_SUPPLEMENTS
export const ADMIN_DEFAULT_OPERATIONAL_FEES = DEFAULT_OPERATIONAL_FEES

const APPLICATION_LABELS: Record<string, string> = {
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
export const formatApplicationLabel = (application: unknown): string => {
	const key = String(application ?? '').trim().toLowerCase()
	return APPLICATION_LABELS[key] || key
}
