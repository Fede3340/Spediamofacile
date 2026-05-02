/**
 * priceBandsCalc — normalizzazione e calcolo fasce prezzo (peso/volume).
 * Tutte funzioni pure, riusate sia dal composable usePriceBands sia dalla controparte admin.
 */

import {
	EPSILON, toNumber, toInt, normalizeDecimal,
	FALLBACK_WEIGHT_BANDS, FALLBACK_VOLUME_BANDS,
	DEFAULT_EXTRA_RULES, DEFAULT_SUPPLEMENTS, DEFAULT_EUROPE_PRICING,
} from '~/utils/priceBandsConstants';
import type {
	BandType, EuropeBand, EuropePricing, ExtraRules, IncrementLadderRow,
	PriceBand, PriceBandsState, PricingRule, PricingRuleGroup, SupplementRule,
} from '~/types/pricing'

// ---- Private helpers ----

const isNil = (v: unknown): boolean => v === null || v === undefined;
const nonNegInt = (v: unknown, fallback = 0): number => Math.max(0, toInt(v, fallback));
const nullableInt = (v: unknown): number | null => (isNil(v) ? null : nonNegInt(v));
const nullableNum = (v: unknown, fallback = 0): number | null => (isNil(v) ? null : toNumber(v, fallback));
const upperList = (v: unknown, fallback: string[] = []): string[] =>
	Array.isArray(v) ? v.map((x) => String(x).trim().toUpperCase()).filter(Boolean) : [...fallback];
const lowerList = (v: unknown, fallback: string[] = []): string[] =>
	Array.isArray(v) ? v.map((x) => String(x).trim().toLowerCase()).filter(Boolean) : [...fallback];
const lastOf = <T>(arr: readonly T[] | undefined): T | null =>
	Array.isArray(arr) && arr.length > 0 ? arr[arr.length - 1] as T : null;

// ---- Normalization helpers ----

export function normalizeIncrementLadder(ladder: unknown, fallbackIncrementCents: unknown): IncrementLadderRow[] {
	const fallbackIncrement = nonNegInt(fallbackIncrementCents, DEFAULT_EXTRA_RULES.increment_cents);
	const source = Array.isArray(ladder) ? ladder as Array<Partial<IncrementLadderRow>> : [];
	const rows = source.map((row, idx) => {
		const fromStep = Math.max(1, toInt(row?.from_step, idx + 1));
		return {
			from_step: fromStep,
			to_step: isNil(row?.to_step) ? null : Math.max(fromStep, toInt(row?.to_step, fromStep)),
			increment_cents: nonNegInt(row?.increment_cents, fallbackIncrement),
		};
	}).sort((a, b) => a.from_step - b.from_step);
	if (!rows.length) return [{ from_step: 1, to_step: null, increment_cents: fallbackIncrement }];
	const last = rows[rows.length - 1];
	if (last) last.to_step = null;
	return rows;
}

export const normalizeBandArray = (bands: unknown = [], type: BandType): PriceBand[] => {
	if (!Array.isArray(bands) || bands.length === 0) return [...(type === "weight" ? FALLBACK_WEIGHT_BANDS : FALLBACK_VOLUME_BANDS)];
	return [...(bands as Array<Partial<PriceBand>>)].map((b, idx) => ({
		id: String(b?.id ?? `${type}-${idx + 1}`),
		type,
		min_value: normalizeDecimal(b?.min_value ?? 0),
		max_value: normalizeDecimal(b?.max_value ?? 0),
		base_price: nonNegInt(b?.base_price, 0),
		discount_price: nullableInt(b?.discount_price),
		show_discount: b?.show_discount !== false,
		sort_order: toInt(b?.sort_order, idx + 1),
	})).sort((a, b) => (a.min_value === b.min_value ? a.max_value - b.max_value : a.min_value - b.min_value));
};

export const normalizeExtraRules = (rules: Partial<ExtraRules> = {}): ExtraRules => {
	const D = DEFAULT_EXTRA_RULES;
	const incCents = nonNegInt(rules?.increment_cents ?? D.increment_cents, D.increment_cents);
	const dec = (v: unknown, d: number) => normalizeDecimal(v ?? d);
	return {
		enabled: rules?.enabled !== false,
		weight_start: dec(rules?.weight_start, D.weight_start),
		weight_step: dec(rules?.weight_step, D.weight_step),
		volume_start: dec(rules?.volume_start, D.volume_start),
		volume_step: dec(rules?.volume_step, D.volume_step),
		increment_cents: incCents,
		increment_mode: "flat",
		weight_increment_ladder: normalizeIncrementLadder(rules?.weight_increment_ladder, incCents),
		volume_increment_ladder: normalizeIncrementLadder(rules?.volume_increment_ladder, incCents),
		base_price_cents_mode: rules?.base_price_cents_mode === "manual" ? "manual" : "last_band_effective",
		base_price_cents_manual: nullableInt(rules?.base_price_cents_manual),
		weight_resolution: dec(rules?.weight_resolution, D.weight_resolution),
		volume_resolution: dec(rules?.volume_resolution, D.volume_resolution),
	};
};

const APPLY_TO_VALUES = ["origin", "destination", "both"];

export const normalizeSupplements = (rules: unknown = []): SupplementRule[] => {
	if (!Array.isArray(rules)) return [...DEFAULT_SUPPLEMENTS];
	if (rules.length === 0) return [];
	return (rules as Array<Partial<SupplementRule>>).map((r, idx) => {
		const applyTo = String(r?.apply_to || "both");
		return {
			id: String(r?.id ?? `supplement-${idx + 1}`),
			prefix: String(r?.prefix ?? "").replace(/\D+/g, ""),
			amount_cents: nonNegInt(r?.amount_cents ?? 0, 0),
			apply_to: APPLY_TO_VALUES.includes(applyTo) ? applyTo : "both",
			enabled: r?.enabled !== false,
		};
	}).filter((r) => r.prefix.length > 0);
};

const normalizeEuropeRate = (rate: Partial<EuropeBand['rates'][number]>) => ({
	country_code: String(rate?.country_code ?? "").trim().toUpperCase(),
	country_name: String(rate?.country_name ?? "").trim(),
	price_cents: nullableInt(rate?.price_cents),
	quote_required: rate?.quote_required === true,
});

export const normalizeEuropePricing = (config: Partial<EuropePricing> = {}): EuropePricing => {
	const bands = Array.isArray(config?.bands)
		? [...config.bands as Array<Partial<EuropeBand>>]
			.map((b, idx) => ({
				id: String(b?.id ?? `eu-band-${idx + 1}`),
				label: String(b?.label ?? "").trim(),
				max_weight_kg: normalizeDecimal(b?.max_weight_kg ?? 0),
				max_volume_m3: Number(toNumber(b?.max_volume_m3 ?? 0, 0).toFixed(6)),
				volumetric_factor: Math.max(1, toInt(b?.volumetric_factor ?? 250, 250)),
				rates: Array.isArray(b?.rates) ? (b.rates as Array<Partial<EuropeBand['rates'][number]>>).map(normalizeEuropeRate).filter((r) => r.country_code) : [],
			}))
			.filter((b) => b.max_weight_kg > 0 && b.max_volume_m3 > 0)
			.sort((a, b) => a.max_weight_kg - b.max_weight_kg)
		: [];
	const supportedCountryCodes = Array.isArray(config?.supported_country_codes)
		? [...new Set(config.supported_country_codes.map((c) => String(c || "").trim().toUpperCase()).filter(Boolean))].sort()
		: [...new Set(bands.flatMap((b) => b.rates.map((r) => r.country_code)))].sort();
	return {
		enabled: config?.enabled !== false && bands.length > 0,
		scope: "europe_monocollo",
		origin_country_code: String(config?.origin_country_code ?? "IT").trim().toUpperCase() || "IT",
		max_packages: Math.max(1, toInt(config?.max_packages ?? 1, 1)),
		max_quantity_per_package: Math.max(1, toInt(config?.max_quantity_per_package ?? 1, 1)),
		supported_country_codes: supportedCountryCodes,
		bands,
		version: config?.version || null,
	};
};

const INT_FIELDS = ['price_cents', 'min_fee_cents'] as const;
const NUM_FIELDS = ['percentage_rate', 'threshold_amount_eur', 'max_weight_kg', 'threshold_cm',
	'longest_side_threshold_cm', 'girth_threshold_cm', 'min_longest_side_cm', 'max_secondary_side_cm'] as const;

export const normalizeKeyedPricingGroup = (config: Record<string, Partial<PricingRule>> = {}, defaults: PricingRuleGroup = {}): PricingRuleGroup => {
	const pickNumeric = (src: Record<string, unknown>, fb: Record<string, unknown>, k: string, asInt: boolean): number | null => {
		const fbVal = fb[k] as number | null | undefined;
		const srcVal = src[k];
		if (isNil(srcVal)) return fbVal ?? null;
		return asInt ? nonNegInt(srcVal, fbVal ?? 0) : toNumber(srcVal, fbVal ?? 0);
	};
	return Object.fromEntries(Object.entries(defaults).map(([key, fallback]) => {
		const src = (config?.[key] && typeof config[key] === "object" ? config[key] : {}) as Record<string, unknown>;
		const fb = fallback as unknown as Record<string, unknown>;
		const out: Record<string, unknown> = { ...fb, ...src, enabled: src.enabled !== false && fb.enabled !== false };
		INT_FIELDS.forEach((f) => { out[f] = pickNumeric(src, fb, f, true); });
		NUM_FIELDS.forEach((f) => { out[f] = pickNumeric(src, fb, f, false); });
		(['province_codes', 'country_codes'] as const).forEach((f) => { out[f] = upperList(src[f], fb[f] as string[] | undefined); });
		(['keyword_list', 'flag_keys', 'delivery_modes'] as const).forEach((f) => { out[f] = lowerList(src[f], fb[f] as string[] | undefined); });
		out.tiers = Array.isArray(src.tiers)
			? (src.tiers as Array<{ up_to_kg?: unknown; price_cents?: unknown }>).map((t) => ({ up_to_kg: nullableNum(t?.up_to_kg, 0), price_cents: nonNegInt(t?.price_cents, 0) }))
			: [...(fallback?.tiers || [])];
		return [key, out as unknown as PricingRule];
	}));
};

// ---- Pure pricing helpers ----

export const effectivePriceCents = (band?: Partial<PriceBand> | null): number => {
	const discount = band?.discount_price;
	return !isNil(discount) ? toInt(discount, 0) : toInt(band?.base_price, 0);
};

export const ceilByResolution = (value: number, resolution: number): number => {
	const safeResolution = resolution > 0 ? resolution : 1;
	const multiplier = 1 / safeResolution;
	return normalizeDecimal(Math.ceil((value * multiplier) - EPSILON) / multiplier, value);
};

export const findBand = (bands: PriceBand[] | undefined, value: number): PriceBand | null => {
	if (!Array.isArray(bands) || bands.length === 0 || !Number.isFinite(value) || value <= 0) return null;
	for (let idx = 0; idx < bands.length; idx += 1) {
		const b = bands[idx];
		if (!b) continue;
		const lowerOk = idx === 0 ? value >= (Number(b.min_value) - EPSILON) : value > (Number(b.min_value) + EPSILON);
		if (lowerOk && value <= (Number(b.max_value) + EPSILON)) return b;
	}
	return null;
};

export const computeExtraPriceCents = (type: BandType, rawValue: number, bands: PriceBand[] | undefined, extraRules: ExtraRules): number | null => {
	if (!extraRules?.enabled || !Number.isFinite(rawValue) || rawValue <= 0) return null;
	const isWeight = type === "weight";
	const start = Number(isWeight ? extraRules.weight_start : extraRules.volume_start);
	const step = Number(isWeight ? extraRules.weight_step : extraRules.volume_step);
	const resolution = Number(isWeight ? extraRules.weight_resolution : extraRules.volume_resolution);
	if (!Number.isFinite(start) || !Number.isFinite(step) || !Number.isFinite(resolution) || step <= 0 || resolution <= 0) return null;
	const value = ceilByResolution(rawValue, resolution);
	if (value + EPSILON < start) return null;
	const fallbackBands = isWeight ? FALLBACK_WEIGHT_BANDS : FALLBACK_VOLUME_BANDS;
	const baseCents = extraRules.base_price_cents_mode === "manual" && extraRules.base_price_cents_manual !== null
		? toInt(extraRules.base_price_cents_manual, 0)
		: effectivePriceCents(lastOf(bands) ?? lastOf(fallbackBands));
	const bandNumber = Math.max(0, Math.floor(((value - start) + EPSILON) / step)) + 1;
	return baseCents + (bandNumber * toInt(extraRules.increment_cents, 0));
};

export const getBandPriceCents = (type: BandType, rawValue: number | string, priceBandsValue: Pick<PriceBandsState, 'weight' | 'volume' | 'extra_rules'>): number | null => {
	const value = Number(rawValue);
	if (!Number.isFinite(value) || value <= 0) return null;
	const bands = type === "weight" ? priceBandsValue.weight : priceBandsValue.volume;
	const band = findBand(bands, value);
	if (band) return effectivePriceCents(band);
	const extra = computeExtraPriceCents(type, value, bands, priceBandsValue.extra_rules || DEFAULT_EXTRA_RULES);
	if (extra !== null) return extra;
	const fallback = type === "weight" ? FALLBACK_WEIGHT_BANDS : FALLBACK_VOLUME_BANDS;
	return effectivePriceCents(lastOf(bands) ?? lastOf(fallback));
};

export const getBandInfo = (band?: PriceBand | null) => {
	if (!band) return null;
	const base = toInt(band.base_price, 0);
	const discount = !isNil(band.discount_price) ? toInt(band.discount_price, 0) : null;
	const discountPercent = discount !== null && discount < base && base > 0 ? Math.round((1 - discount / base) * 100) : null;
	return {
		effectivePrice: (discount ?? base) / 100,
		basePrice: base / 100,
		discountPercent,
		showDiscount: band.show_discount !== false,
		hasDiscount: discountPercent !== null && discountPercent > 0,
		isExtra: false,
	};
};

export const getExtraBandInfo = (cents: number) => ({
	effectivePrice: cents / 100, basePrice: cents / 100, discountPercent: null,
	showDiscount: false, hasDiscount: false, isExtra: true,
});

export const calcCapSupplementCents = (originCap: number | string, destinationCap: number | string, supplements?: SupplementRule[]): number => {
	const origin = String(originCap || "").replace(/\D+/g, "");
	const destination = String(destinationCap || "").replace(/\D+/g, "");
	return (supplements || []).reduce((tot, rule) => {
		const prefix = String(rule?.prefix || "").replace(/\D+/g, "");
		const amount = nonNegInt(rule?.amount_cents, 0);
		if (rule?.enabled === false || !prefix || !amount) return tot;
		const applyTo = APPLY_TO_VALUES.includes(rule?.apply_to) ? rule.apply_to : "both";
		const onOrigin = (applyTo === "origin" || applyTo === "both") && origin.startsWith(prefix);
		const onDest = (applyTo === "destination" || applyTo === "both") && destination.startsWith(prefix);
		return tot + (onOrigin ? amount : 0) + (onDest ? amount : 0);
	}, 0);
};

// Range matchers per fascia peso Europa (logica matematica invariata).
const EUROPE_WEIGHT_RANGES: Array<(w: number, e: EuropeBand) => boolean> = [
	(w, e) => w <= 10 && e.max_weight_kg <= 10 + EPSILON,
	(w, e) => w > 10 + EPSILON && w < 25 && e.max_weight_kg > 10 && e.max_weight_kg <= 30 + EPSILON,
	(w, e) => w >= 25 && w <= 50 + EPSILON && e.max_weight_kg > 25 && e.max_weight_kg <= 50 + EPSILON,
	(w, e) => w > 50 + EPSILON && w <= 75 + EPSILON && e.max_weight_kg > 50 && e.max_weight_kg <= 75 + EPSILON,
	(w, e) => w > 75 + EPSILON && w <= 100 + EPSILON && e.max_weight_kg > 75 && e.max_weight_kg <= 100 + EPSILON,
];

export const calcEuropeQuote = (destinationCountryCode: string, weightKg: number | string, volumeM3: number | string, europePricing?: EuropePricing) => {
	const pricing = europePricing || DEFAULT_EUROPE_PRICING;
	const countryCode = String(destinationCountryCode || "").trim().toUpperCase();
	if (!pricing.enabled || !countryCode || countryCode === "IT") return { status: "not_europe", message: "Destinazione non europea o nazionale." };
	if (!pricing.supported_country_codes.includes(countryCode)) return { status: "not_supported", message: "Destinazione europea non configurata nel listino attuale." };
	const weight = Number(weightKg);
	const volume = Number(volumeM3);
	if (!Number.isFinite(weight) || weight <= 0 || !Number.isFinite(volume) || volume <= 0) {
		return { status: "incomplete", message: "Inserisci peso e dimensioni per calcolare il listino Europa." };
	}
	const findInRange = (m: (e: EuropeBand) => boolean) => pricing.bands.find((e) => m(e) && volume <= e.max_volume_m3);
	const band = EUROPE_WEIGHT_RANGES.reduce<EuropeBand | undefined | null>((acc, fn) => acc || findInRange((e) => fn(weight, e)), null)
		|| pricing.bands.find((e) => weight <= e.max_weight_kg && volume <= e.max_volume_m3)
		|| null;
	if (!band) return { status: "requires_quote", message: "Per questo peso o volume verso l'Europa serve un preventivo manuale." };
	const rate = band.rates.find((e) => e.country_code === countryCode);
	if (!rate) return { status: "not_supported", message: "Destinazione europea non configurata nel listino attuale.", band };
	if (rate.quote_required || rate.price_cents === null) {
		return { status: "requires_quote", message: `Per ${rate.country_name || countryCode} in questa fascia va richiesto un preventivo manuale.`, band, rate };
	}
	return { status: "priced", price_cents: rate.price_cents, price: Number((rate.price_cents / 100).toFixed(2)), band, rate };
};
