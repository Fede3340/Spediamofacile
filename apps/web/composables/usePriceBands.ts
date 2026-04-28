/**
 * usePriceBands.js
 *
 * Thin orchestrator that composes sub-composables and exposes the exact
 * same public API as before.  All constants/normalisation live in
 * usePriceBandsDefaults.js and all pure calculation logic lives in
 * usePriceBandsCalc.js.
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
	normalizeBandArray,
	normalizeExtraRules,
	normalizeSupplements,
	normalizeEuropePricing,
	normalizeKeyedPricingGroup,
} from "./usePriceBandsDefaults";

import {
	findBand,
	getBandPriceCents,
	getBandInfo,
	getExtraBandInfo,
	calcCapSupplementCents,
	calcEuropeQuote,
} from "./usePriceBandsCalc";

// ---- Shared singleton state (module-level, same lifetime as before) ----

const priceBands = ref({
	weight: FALLBACK_WEIGHT_BANDS,
	volume: FALLBACK_VOLUME_BANDS,
	extra_rules: DEFAULT_EXTRA_RULES,
	supplements: DEFAULT_SUPPLEMENTS,
	europe: DEFAULT_EUROPE_PRICING,
	service_pricing: DEFAULT_SERVICE_PRICING,
	automatic_supplements: DEFAULT_AUTOMATIC_SUPPLEMENTS,
	operational_fees: DEFAULT_OPERATIONAL_FEES,
	version: null,
});

const promoSettings = ref(DEFAULT_PROMO);
const loading = ref(false);
const loaded = ref(false);
let lastFetchTime = 0;
const TTL_MS = 5 * 60 * 1000;
let pendingFetchPromise = null;

// ---- Composable ----

export const usePriceBands = () => {
	const runtimeConfig = useRuntimeConfig();
	const apiBase = String(runtimeConfig.public?.apiBase || "http://127.0.0.1:8787").replace(/\/$/, "");

	const publicApiFetch = async (path, options = {}) => {
		const url = path.startsWith("http") ? path : `${apiBase}${path}`;
		return await $fetch(url, {
			credentials: "include",
			...options,
		});
	};

	// In SSR il modulo resta vivo tra richieste in dev.
	// Ripartiamo sempre dal fallback per evitare che il server riusi
	// listini/promozioni caricati in una richiesta precedente e sporchi l'hydration.
	if (import.meta.server) {
		priceBands.value = {
			weight: [...FALLBACK_WEIGHT_BANDS],
			volume: [...FALLBACK_VOLUME_BANDS],
			extra_rules: { ...DEFAULT_EXTRA_RULES },
			supplements: [...DEFAULT_SUPPLEMENTS],
			europe: { ...DEFAULT_EUROPE_PRICING },
			service_pricing: normalizeKeyedPricingGroup({}, DEFAULT_SERVICE_PRICING),
			automatic_supplements: normalizeKeyedPricingGroup({}, DEFAULT_AUTOMATIC_SUPPLEMENTS),
			operational_fees: normalizeKeyedPricingGroup({}, DEFAULT_OPERATIONAL_FEES),
			version: null,
		};
		promoSettings.value = { ...DEFAULT_PROMO };
		loading.value = false;
		loaded.value = false;
		lastFetchTime = 0;
		pendingFetchPromise = null;
	}

	// ---- API fetch ----

	const fetchFromApi = async () => {
		loading.value = true;
		try {
			const res = await publicApiFetch("/api/public/price-bands");
			const payload = res?.data || res || {};
			const data = payload?.data || payload || {};

			const weight = normalizeBandArray(data?.weight, "weight");
			const volume = normalizeBandArray(data?.volume, "volume");
			const extraRules = normalizeExtraRules(data?.extra_rules || DEFAULT_EXTRA_RULES);
			const supplements = normalizeSupplements(data?.supplements || DEFAULT_SUPPLEMENTS);
			const europe = normalizeEuropePricing(data?.europe || DEFAULT_EUROPE_PRICING);
			const version = data?.version || payload?.version || String(Date.now());

			priceBands.value = {
				weight,
				volume,
				extra_rules: extraRules,
				supplements: supplements,
				europe,
				service_pricing: normalizeKeyedPricingGroup(data?.service_pricing || {}, DEFAULT_SERVICE_PRICING),
				automatic_supplements: normalizeKeyedPricingGroup(data?.automatic_supplements || {}, DEFAULT_AUTOMATIC_SUPPLEMENTS),
				operational_fees: normalizeKeyedPricingGroup(data?.operational_fees || {}, DEFAULT_OPERATIONAL_FEES),
				version,
			};

			if (payload?.promo) {
				promoSettings.value = { ...DEFAULT_PROMO, ...payload.promo };
			}

			loaded.value = true;
			lastFetchTime = Date.now();
		} catch (e) {
			priceBands.value = {
				weight: [...FALLBACK_WEIGHT_BANDS],
				volume: [...FALLBACK_VOLUME_BANDS],
				extra_rules: { ...DEFAULT_EXTRA_RULES },
				supplements: [...DEFAULT_SUPPLEMENTS],
				europe: { ...DEFAULT_EUROPE_PRICING },
				service_pricing: normalizeKeyedPricingGroup({}, DEFAULT_SERVICE_PRICING),
				automatic_supplements: normalizeKeyedPricingGroup({}, DEFAULT_AUTOMATIC_SUPPLEMENTS),
				operational_fees: normalizeKeyedPricingGroup({}, DEFAULT_OPERATIONAL_FEES),
				version: String(Date.now()),
			};
			promoSettings.value = { ...DEFAULT_PROMO };
			loaded.value = true;
			lastFetchTime = Date.now();
		} finally {
			loading.value = false;
		}
	};

	const loadPriceBands = async () => {
		const expired = Date.now() - lastFetchTime > TTL_MS;
		if (loaded.value && !expired) return;
		if (pendingFetchPromise) return pendingFetchPromise;
		pendingFetchPromise = fetchFromApi().finally(() => {
			pendingFetchPromise = null;
		});
		return pendingFetchPromise;
	};

	const forceReload = async () => {
		lastFetchTime = 0;
		loaded.value = false;
		await fetchFromApi();
	};

	// ---- Public getters (delegate to usePriceBandsCalc) ----

	const getWeightPrice = (weightKg) => {
		const cents = getBandPriceCents("weight", weightKg, priceBands.value);
		return cents == null ? null : cents / 100;
	};

	const getVolumePrice = (volumeM3) => {
		const cents = getBandPriceCents("volume", volumeM3, priceBands.value);
		return cents == null ? null : cents / 100;
	};

	const getWeightBandInfo = (weightKg) => {
		const value = Number(weightKg);
		if (!Number.isFinite(value) || value <= 0) return null;
		const band = findBand(priceBands.value.weight, value);
		if (band) return getBandInfo(band);
		const cents = getBandPriceCents("weight", value, priceBands.value);
		return cents == null ? null : getExtraBandInfo(cents);
	};

	const getVolumeBandInfo = (volumeM3) => {
		const value = Number(volumeM3);
		if (!Number.isFinite(value) || value <= 0) return null;
		const band = findBand(priceBands.value.volume, value);
		if (band) return getBandInfo(band);
		const cents = getBandPriceCents("volume", value, priceBands.value);
		return cents == null ? null : getExtraBandInfo(cents);
	};

	const getCapSupplementCents = (originCap, destinationCap) => {
		return calcCapSupplementCents(originCap, destinationCap, priceBands.value.supplements || []);
	};

	const getCapSupplement = (originCap, destinationCap) => getCapSupplementCents(originCap, destinationCap) / 100;

	const getEuropeQuote = (destinationCountryCode, weightKg, volumeM3) => {
		return calcEuropeQuote(destinationCountryCode, weightKg, volumeM3, priceBands.value.europe || DEFAULT_EUROPE_PRICING);
	};

	const getMinPrice = () => {
		const firstBand = priceBands.value.weight?.[0];
		if (!firstBand) {
			return { effectivePrice: 8.9, basePrice: 8.9, discountPercent: null, showDiscount: false, hasDiscount: false, isExtra: false };
		}
		return getBandInfo(firstBand);
	};

	// ---- Return exactly the same public API ----

	return {
		priceBands,
		promoSettings,
		loading,
		loaded,
		loadPriceBands,
		forceReload,
		getWeightPrice,
		getVolumePrice,
		getWeightBandInfo,
		getVolumeBandInfo,
		getCapSupplement,
		getCapSupplementCents,
		getEuropeQuote,
		getMinPrice,
	};
};
