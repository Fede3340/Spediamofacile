/**
 * usePriceBandsCalc.js
 *
 * Pure price-calculation logic for usePriceBands.
 * All functions receive data as parameters -- no shared state.
 */

import {
	EPSILON,
	toNumber,
	toInt,
	normalizeDecimal,
	FALLBACK_WEIGHT_BANDS,
	FALLBACK_VOLUME_BANDS,
	DEFAULT_EXTRA_RULES,
	DEFAULT_EUROPE_PRICING,
} from "./usePriceBandsDefaults";

// ---- Core helpers ----

export const effectivePriceCents = (band) => {
	const discount = band?.discount_price;
	if (discount !== null && discount !== undefined) {
		return toInt(discount, 0);
	}
	return toInt(band?.base_price, 0);
};

export const ceilByResolution = (value, resolution) => {
	const safeResolution = resolution > 0 ? resolution : 1;
	const multiplier = 1 / safeResolution;
	return normalizeDecimal(Math.ceil((value * multiplier) - EPSILON) / multiplier, value);
};

export const findBand = (bands, value) => {
	if (!Array.isArray(bands) || bands.length === 0 || !Number.isFinite(value) || value <= 0) return null;

	for (let idx = 0; idx < bands.length; idx += 1) {
		const band = bands[idx];
		const min = Number(band.min_value);
		const max = Number(band.max_value);
		const lowerOk = idx === 0 ? value >= (min - EPSILON) : value > (min + EPSILON);
		const upperOk = value <= (max + EPSILON);
		if (lowerOk && upperOk) return band;
	}
	return null;
};

// ---- Extra-band price computation ----

export const computeExtraPriceCents = (type, rawValue, bands, extraRules) => {
	if (!extraRules?.enabled) return null;
	if (!Number.isFinite(rawValue) || rawValue <= 0) return null;

	const isWeight = type === "weight";
	const start = isWeight ? Number(extraRules.weight_start) : Number(extraRules.volume_start);
	const step = isWeight ? Number(extraRules.weight_step) : Number(extraRules.volume_step);
	const resolution = isWeight ? Number(extraRules.weight_resolution) : Number(extraRules.volume_resolution);
	const increment = toInt(extraRules.increment_cents, 0);

	if (!Number.isFinite(start) || !Number.isFinite(step) || !Number.isFinite(resolution) || step <= 0 || resolution <= 0) {
		return null;
	}

	const value = ceilByResolution(rawValue, resolution);
	if (value + EPSILON < start) return null;

	let baseCents = 0;
	if (extraRules.base_price_cents_mode === "manual" && extraRules.base_price_cents_manual !== null) {
		baseCents = toInt(extraRules.base_price_cents_manual, 0);
	} else {
		const last = Array.isArray(bands) && bands.length > 0 ? bands[bands.length - 1] : null;
		baseCents = last ? effectivePriceCents(last) : (isWeight ? effectivePriceCents(FALLBACK_WEIGHT_BANDS[FALLBACK_WEIGHT_BANDS.length - 1]) : effectivePriceCents(FALLBACK_VOLUME_BANDS[FALLBACK_VOLUME_BANDS.length - 1]));
	}

	const stepsFromStart = Math.floor(((value - start) + EPSILON) / step);
	const bandNumber = Math.max(0, stepsFromStart) + 1;

	// Regola business corrente: incremento fisso per ogni fascia extra.
	return baseCents + (bandNumber * increment);
};

// ---- Public band-price getter (operates on priceBands reactive value) ----

export const getBandPriceCents = (type, rawValue, priceBandsValue) => {
	const value = Number(rawValue);
	if (!Number.isFinite(value) || value <= 0) return null;

	const bands = type === "weight" ? priceBandsValue.weight : priceBandsValue.volume;
	const band = findBand(bands, value);
	if (band) return effectivePriceCents(band);

	const extra = computeExtraPriceCents(type, value, bands, priceBandsValue.extra_rules || DEFAULT_EXTRA_RULES);
	if (extra !== null) return extra;

	if (Array.isArray(bands) && bands.length > 0) {
		return effectivePriceCents(bands[bands.length - 1]);
	}

	const fallback = type === "weight" ? FALLBACK_WEIGHT_BANDS : FALLBACK_VOLUME_BANDS;
	return effectivePriceCents(fallback[fallback.length - 1]);
};

// ---- Band info formatters ----

export const getBandInfo = (band) => {
	if (!band) return null;
	const basePriceCents = toInt(band.base_price, 0);
	const discountPriceCents = band.discount_price !== null && band.discount_price !== undefined ? toInt(band.discount_price, 0) : null;
	const effectivePriceCentsValue = discountPriceCents ?? basePriceCents;
	const discountPercent = discountPriceCents !== null && discountPriceCents < basePriceCents && basePriceCents > 0
		? Math.round((1 - discountPriceCents / basePriceCents) * 100)
		: null;

	return {
		effectivePrice: effectivePriceCentsValue / 100,
		basePrice: basePriceCents / 100,
		discountPercent,
		showDiscount: band.show_discount !== false,
		hasDiscount: discountPercent !== null && discountPercent > 0,
		isExtra: false,
	};
};

export const getExtraBandInfo = (cents) => ({
	effectivePrice: cents / 100,
	basePrice: cents / 100,
	discountPercent: null,
	showDiscount: false,
	hasDiscount: false,
	isExtra: true,
});

// ---- CAP supplement calculation ----

export const calcCapSupplementCents = (originCap, destinationCap, supplements) => {
	const rules = supplements || [];
	const origin = String(originCap || "").replace(/\D+/g, "");
	const destination = String(destinationCap || "").replace(/\D+/g, "");

	let total = 0;
	rules.forEach((rule) => {
		if (rule?.enabled === false) return;
		const prefix = String(rule?.prefix || "").replace(/\D+/g, "");
		if (!prefix) return;
		const amount = Math.max(0, toInt(rule?.amount_cents, 0));
		if (!amount) return;
		const applyTo = ["origin", "destination", "both"].includes(rule?.apply_to) ? rule.apply_to : "both";
		if ((applyTo === "origin" || applyTo === "both") && origin.startsWith(prefix)) total += amount;
		if ((applyTo === "destination" || applyTo === "both") && destination.startsWith(prefix)) total += amount;
	});

	return total;
};

// ---- Europe quote lookup ----

export const calcEuropeQuote = (destinationCountryCode, weightKg, volumeM3, europePricing) => {
	const pricing = europePricing || DEFAULT_EUROPE_PRICING;
	const countryCode = String(destinationCountryCode || "").trim().toUpperCase();
	if (!pricing.enabled || !countryCode || countryCode === "IT") {
		return { status: "not_europe", message: "Destinazione non europea o nazionale." };
	}

	if (!pricing.supported_country_codes.includes(countryCode)) {
		return { status: "not_supported", message: "Destinazione europea non configurata nel listino attuale." };
	}

	const weight = Number(weightKg);
	const volume = Number(volumeM3);
	if (!Number.isFinite(weight) || weight <= 0 || !Number.isFinite(volume) || volume <= 0) {
		return { status: "incomplete", message: "Inserisci peso e dimensioni per calcolare il listino Europa." };
	}

	const findEuropeBand = () => {
		const bandByRange = (matcher) => pricing.bands.find((entry) => matcher(entry) && volume <= entry.max_volume_m3);
		return (
			bandByRange((entry) => weight <= 10 && entry.max_weight_kg <= 10 + EPSILON)
			|| bandByRange((entry) => weight > 10 + EPSILON && weight < 25 && entry.max_weight_kg > 10 && entry.max_weight_kg <= 30 + EPSILON)
			|| bandByRange((entry) => weight >= 25 && weight <= 50 + EPSILON && entry.max_weight_kg > 25 && entry.max_weight_kg <= 50 + EPSILON)
			|| bandByRange((entry) => weight > 50 + EPSILON && weight <= 75 + EPSILON && entry.max_weight_kg > 50 && entry.max_weight_kg <= 75 + EPSILON)
			|| bandByRange((entry) => weight > 75 + EPSILON && weight <= 100 + EPSILON && entry.max_weight_kg > 75 && entry.max_weight_kg <= 100 + EPSILON)
			|| pricing.bands.find((entry) => weight <= entry.max_weight_kg && volume <= entry.max_volume_m3)
			|| null
		);
	};

	const band = findEuropeBand();
	if (!band) {
		return { status: "requires_quote", message: "Per questo peso o volume verso l'Europa serve un preventivo manuale." };
	}

	const rate = band.rates.find((entry) => entry.country_code === countryCode);
	if (!rate) {
		return { status: "not_supported", message: "Destinazione europea non configurata nel listino attuale.", band };
	}

	if (rate.quote_required || rate.price_cents === null) {
		return {
			status: "requires_quote",
			message: `Per ${rate.country_name || countryCode} in questa fascia va richiesto un preventivo manuale.`,
			band,
			rate,
		};
	}

	return {
		status: "priced",
		price_cents: rate.price_cents,
		price: Number((rate.price_cents / 100).toFixed(2)),
		band,
		rate,
	};
};
