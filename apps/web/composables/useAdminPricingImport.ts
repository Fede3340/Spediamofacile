/**
 * @file useAdminPricingImport — sezione import/persistence del pannello admin pricing.
 *
 * payload builders. API:
 *  - GET/PUT /api/admin/price-bands
 *  - POST /api/admin/price-bands/seed
 *  - GET/POST /api/admin/promo-settings
 *  - POST /api/admin/promo-settings/upload-image
 */
import {
	DEFAULT_AUTOMATIC_SUPPLEMENTS,
	DEFAULT_EUROPE_PRICING,
	DEFAULT_EXTRA_RULES,
	DEFAULT_OPERATIONAL_FEES,
	DEFAULT_SERVICE_PRICING,
	DEFAULT_SUPPLEMENTS,
	FALLBACK_VOLUME_BANDS as DEFAULT_VOLUME_BANDS,
	FALLBACK_WEIGHT_BANDS as DEFAULT_WEIGHT_BANDS,
} from '~/utils/priceBandsConstants';
import type { Ref } from 'vue';
import type {
	BandType,
	EuropePricing,
	ExtraRules,
	IncrementLadderRow,
	PriceBand,
	PricingRuleGroup,
	PromoSettings,
	SupplementRule,
} from '~/types/pricing';
import {
	buildPricingRulesPayload,
	normalizeEuropePricingForAdmin,
	normalizePricingGroupForAdmin,
} from '~/utils/adminPricingHelpers';

type AdminApiClient = <T = unknown>(url: string, options?: Record<string, unknown>) => Promise<T>;
type ActionMessage = (message: string) => void;
type ActionError = (error: unknown, fallback: string) => void;
type MaybeDataResponse<T> = { data?: T | { data?: T } };
type PriceBandsApiPayload = Partial<{
	weight: Partial<PriceBand>[];
	volume: Partial<PriceBand>[];
	extra_rules: Partial<ExtraRules>;
	supplements: Partial<SupplementRule>[];
	europe: Partial<EuropePricing>;
	service_pricing: PricingRuleGroup;
	automatic_supplements: PricingRuleGroup;
	operational_fees: PricingRuleGroup;
	version: string | number | null;
}>;
type PriceBandsPayload = {
	weight: PriceBand[];
	volume: PriceBand[];
	extra_rules: ExtraRules;
	supplements: SupplementRule[];
	europe: ReturnType<typeof normalizeEuropePricingForAdmin>;
	service_pricing: PricingRuleGroup;
	automatic_supplements: PricingRuleGroup;
	operational_fees: PricingRuleGroup;
};
type PromoApiPayload = Partial<{
	promo_active: boolean | string;
	promo_label_text: string;
	promo_label_color: string;
	promo_label_image: string | null;
	promo_show_badges: boolean | string;
	promo_description: string;
	image_url: string;
}>;
type CreateImportSectionDeps = {
	weightBands: Ref<PriceBand[]>;
	volumeBands: Ref<PriceBand[]>;
	bandsFromDb: Ref<boolean>;
	extraRules: Ref<ExtraRules>;
	supplementRules: Ref<SupplementRule[]>;
	pricingVersion: Ref<string | number | null>;
	europePricing: Ref<EuropePricing>;
	servicePricing: Ref<PricingRuleGroup>;
	automaticSupplements: Ref<PricingRuleGroup>;
	operationalFees: Ref<PricingRuleGroup>;
	originalWeightBands: Ref<PriceBand[]>;
	originalVolumeBands: Ref<PriceBand[]>;
	originalExtraRules: Ref<ExtraRules | null>;
	originalSupplementRules: Ref<SupplementRule[]>;
	originalEuropePricing: Ref<EuropePricing | null>;
	originalServicePricing: Ref<PricingRuleGroup>;
	originalAutomaticSupplements: Ref<PricingRuleGroup>;
	originalOperationalFees: Ref<PricingRuleGroup>;
	normalizeLadderForPayload: (rows: unknown, fallbackIncrement: unknown) => IncrementLadderRow[];
	showSuccess: ActionMessage;
	showError: ActionError;
};

const asPriceBand = (band: Partial<PriceBand>, type: BandType, idx: number): PriceBand => ({
	id: String(band.id || `${type}-${idx + 1}`),
	type,
	min_value: Number(band.min_value || 0),
	max_value: Number(band.max_value || 0),
	base_price: Number(band.base_price || 0),
	discount_price: band.discount_price === null || band.discount_price === undefined
		? null
		: Number(band.discount_price || 0),
	show_discount: band.show_discount !== false,
	sort_order: Number(band.sort_order || idx + 1),
});

const asSupplementRule = (rule: Partial<SupplementRule>, idx: number): SupplementRule => ({
	id: String(rule.id || `supplement-${idx + 1}`),
	prefix: String(rule.prefix || '').replace(/\D+/g, ''),
	amount_cents: Number(rule.amount_cents || 0),
	apply_to: ['origin', 'destination', 'both'].includes(String(rule.apply_to || '')) ? String(rule.apply_to) : 'both',
	enabled: rule.enabled !== false,
});

const extractResponseData = <T>(response: MaybeDataResponse<T> | T): T => {
	const top = response as MaybeDataResponse<T>;
	if (top && typeof top === 'object' && 'data' in top && top.data !== undefined) {
		const nested = top.data as MaybeDataResponse<T> | T;
		if (nested && typeof nested === 'object' && 'data' in nested && nested.data !== undefined) {
			return nested.data as T;
		}
		return nested as T;
	}
	return response as T;
};

// ────────────────────────────────────────────────────────────
// 4. Import section
// (Merged from useAdminPricingImport.js — 2026-04-20)
// CRUD price-bands + promo-settings, seed, payload/normalizzatori.
// API: /api/admin/price-bands (GET/PUT), /api/admin/price-bands/seed (POST),
//      /api/admin/promo-settings (GET/POST), /api/admin/promo-settings/upload-image (POST).
// ────────────────────────────────────────────────────────────

export const createImportSection = ({
	weightBands,
	volumeBands,
	bandsFromDb,
	extraRules,
	supplementRules,
	pricingVersion,
	europePricing,
	servicePricing,
	automaticSupplements,
	operationalFees,
	originalWeightBands,
	originalVolumeBands,
	originalExtraRules,
	originalSupplementRules,
	originalEuropePricing,
	originalServicePricing,
	originalAutomaticSupplements,
	originalOperationalFees,
	normalizeLadderForPayload,
	showSuccess,
	showError,
}: CreateImportSectionDeps) => {
	const sanctum = useSanctumClient() as AdminApiClient;
	const { forceReload: reloadPublicPriceBands } = usePriceBands();

	// ── Loading state ────────────────────────────────────
	const isLoading = ref(true);
	const saving = ref(false);
	const seeding = ref(false);

	// ── Promo state ──────────────────────────────────────
	const promoLoading = ref(false);
	const promoSaving = ref(false);
	const promoImageUploading = ref(false);
	const promo = ref<PromoSettings>({
		active: false,
		label_text: '',
		label_color: '#E44203',
		label_image: null,
		show_badges: true,
		description: '',
	});

	// ── Payload builders ─────────────────────────────────
	const buildEuropePricingPayload = () => {
		const normalized = normalizeEuropePricingForAdmin(europePricing.value);
		return {
			enabled: normalized.enabled !== false,
			scope: normalized.scope,
			origin_country_code: 'IT',
			max_packages: 1,
			max_quantity_per_package: 1,
			bands: normalized.bands.map((band) => ({
				id: band.id,
				label: band.label,
				max_weight_kg: Number(band.max_weight_kg || 0),
				max_volume_m3: Number(band.max_volume_m3 || 0),
				volumetric_factor: Number(band.volumetric_factor || 250),
				rates: band.rates.map((rate) => ({
					country_code: String(rate.country_code || '').trim().toUpperCase(),
					country_name: String(rate.country_name || '').trim(),
					price_cents: rate.quote_required || rate.price_cents === null || rate.price_cents === undefined
						? null
						: Number(rate.price_cents || 0),
					quote_required: rate.quote_required === true,
				})),
			})),
			supported_country_codes: normalized.supported_country_codes,
			version: normalized.version,
		};
	};

	const buildPricingPayload = (): PriceBandsPayload => ({
		weight: weightBands.value.map((band, idx) => ({
			id: band.id || `w-${idx + 1}`,
			type: 'weight',
			min_value: Number(band.min_value),
			max_value: Number(band.max_value),
			base_price: Number(band.base_price || 0),
			discount_price: band.discount_price === null ? null : Number(band.discount_price),
			show_discount: band.show_discount !== false,
			sort_order: idx + 1,
		} satisfies PriceBand)),
		volume: volumeBands.value.map((band, idx) => ({
			id: band.id || `v-${idx + 1}`,
			type: 'volume',
			min_value: Number(band.min_value),
			max_value: Number(band.max_value),
			base_price: Number(band.base_price || 0),
			discount_price: band.discount_price === null ? null : Number(band.discount_price),
			show_discount: band.show_discount !== false,
			sort_order: idx + 1,
		} satisfies PriceBand)),
		extra_rules: {
			enabled: extraRules.value.enabled !== false,
			weight_start: Number(extraRules.value.weight_start),
			weight_step: Number(extraRules.value.weight_step),
			volume_start: Number(extraRules.value.volume_start),
			volume_step: Number(extraRules.value.volume_step),
			increment_cents: Number(extraRules.value.increment_cents || 0),
			increment_mode: 'flat',
			weight_increment_ladder: normalizeLadderForPayload([{ from_step: 1, to_step: null, increment_cents: Number(extraRules.value.increment_cents || 0) }], Number(extraRules.value.increment_cents || 0)),
			volume_increment_ladder: normalizeLadderForPayload([{ from_step: 1, to_step: null, increment_cents: Number(extraRules.value.increment_cents || 0) }], Number(extraRules.value.increment_cents || 0)),
			base_price_cents_mode: extraRules.value.base_price_cents_mode === 'manual' ? 'manual' : 'last_band_effective',
			base_price_cents_manual: extraRules.value.base_price_cents_mode === 'manual'
				? Number(extraRules.value.base_price_cents_manual || 0)
				: null,
			weight_resolution: Number(extraRules.value.weight_resolution || 1),
			volume_resolution: Number(extraRules.value.volume_resolution || 0.001),
		},
		supplements: supplementRules.value
			.map((rule, idx): SupplementRule => ({
				id: rule.id || `supplement-${idx + 1}`,
				prefix: String(rule.prefix || '').replace(/\D+/g, ''),
				amount_cents: Number(rule.amount_cents || 0),
				apply_to: ['origin', 'destination', 'both'].includes(rule.apply_to) ? rule.apply_to : 'both',
				enabled: rule.enabled !== false,
			}))
			.filter((rule) => rule.prefix.length > 0),
		europe: buildEuropePricingPayload(),
		service_pricing: buildPricingRulesPayload(servicePricing.value),
		automatic_supplements: buildPricingRulesPayload(automaticSupplements.value),
		operational_fees: buildPricingRulesPayload(operationalFees.value),
	});

	// ── Defaults ─────────────────────────────────────────
	const applyDefaults = () => {
		weightBands.value = DEFAULT_WEIGHT_BANDS.map((band, idx) => asPriceBand({ ...band, id: `new-w-${idx}` }, 'weight', idx));
		volumeBands.value = DEFAULT_VOLUME_BANDS.map((band, idx) => asPriceBand({ ...band, id: `new-v-${idx}` }, 'volume', idx));
		extraRules.value = { ...DEFAULT_EXTRA_RULES } as ExtraRules;
		extraRules.value.increment_mode = 'flat';
		extraRules.value.weight_increment_ladder = normalizeLadderForPayload(extraRules.value.weight_increment_ladder, extraRules.value.increment_cents);
		extraRules.value.volume_increment_ladder = normalizeLadderForPayload(extraRules.value.volume_increment_ladder, extraRules.value.increment_cents);
		supplementRules.value = DEFAULT_SUPPLEMENTS.map((rule, idx) => asSupplementRule(rule, idx));
		originalExtraRules.value = JSON.parse(JSON.stringify(extraRules.value));
		originalSupplementRules.value = JSON.parse(JSON.stringify(supplementRules.value));
		europePricing.value = normalizeEuropePricingForAdmin(DEFAULT_EUROPE_PRICING);
		originalEuropePricing.value = JSON.parse(JSON.stringify(europePricing.value));
		servicePricing.value = normalizePricingGroupForAdmin({}, DEFAULT_SERVICE_PRICING);
		automaticSupplements.value = normalizePricingGroupForAdmin({}, DEFAULT_AUTOMATIC_SUPPLEMENTS);
		operationalFees.value = normalizePricingGroupForAdmin({}, DEFAULT_OPERATIONAL_FEES);
		originalServicePricing.value = JSON.parse(JSON.stringify(servicePricing.value));
		originalAutomaticSupplements.value = JSON.parse(JSON.stringify(automaticSupplements.value));
		originalOperationalFees.value = JSON.parse(JSON.stringify(operationalFees.value));
		pricingVersion.value = null;
		bandsFromDb.value = false;
	};

	// ── Fetch ────────────────────────────────────────────
	const fetchPriceBands = async () => {
		isLoading.value = true;
		try {
			const res = await sanctum<MaybeDataResponse<PriceBandsApiPayload> | PriceBandsApiPayload>("/api/admin/price-bands");
			const data = extractResponseData<PriceBandsApiPayload>(res) || {};
			const w = Array.isArray(data.weight) ? data.weight : [];
			const v = Array.isArray(data.volume) ? data.volume : [];
			if (w.length > 0 || v.length > 0) {
				weightBands.value = w.map((band, idx) => asPriceBand(band, 'weight', idx));
				volumeBands.value = v.map((band, idx) => asPriceBand(band, 'volume', idx));
				originalWeightBands.value = weightBands.value.map((band) => ({ ...band }));
				originalVolumeBands.value = volumeBands.value.map((band) => ({ ...band }));
				extraRules.value = { ...DEFAULT_EXTRA_RULES, ...(data.extra_rules || {}) } as ExtraRules;
				extraRules.value.increment_mode = 'flat';
				extraRules.value.weight_increment_ladder = normalizeLadderForPayload(extraRules.value.weight_increment_ladder, extraRules.value.increment_cents);
				extraRules.value.volume_increment_ladder = normalizeLadderForPayload(extraRules.value.volume_increment_ladder, extraRules.value.increment_cents);
				const supplementsFromApi = Array.isArray(data.supplements) ? data.supplements : DEFAULT_SUPPLEMENTS;
				supplementRules.value = supplementsFromApi.map((rule, idx) => asSupplementRule(rule, idx));
				originalExtraRules.value = JSON.parse(JSON.stringify(extraRules.value));
				originalSupplementRules.value = JSON.parse(JSON.stringify(supplementRules.value));
				europePricing.value = normalizeEuropePricingForAdmin(data.europe || DEFAULT_EUROPE_PRICING);
				originalEuropePricing.value = JSON.parse(JSON.stringify(europePricing.value));
				servicePricing.value = normalizePricingGroupForAdmin(data.service_pricing || {}, DEFAULT_SERVICE_PRICING);
				automaticSupplements.value = normalizePricingGroupForAdmin(data.automatic_supplements || {}, DEFAULT_AUTOMATIC_SUPPLEMENTS);
				operationalFees.value = normalizePricingGroupForAdmin(data.operational_fees || {}, DEFAULT_OPERATIONAL_FEES);
				originalServicePricing.value = JSON.parse(JSON.stringify(servicePricing.value));
				originalAutomaticSupplements.value = JSON.parse(JSON.stringify(automaticSupplements.value));
				originalOperationalFees.value = JSON.parse(JSON.stringify(operationalFees.value));
				pricingVersion.value = data.version || null;
				bandsFromDb.value = true;
			} else {
				applyDefaults();
			}
		} catch {
			applyDefaults();
		} finally {
			isLoading.value = false;
		}
	};

	const fetchPromoSettings = async () => {
		promoLoading.value = true;
		try {
			const res = await sanctum<MaybeDataResponse<PromoApiPayload> | PromoApiPayload>("/api/admin/promo-settings");
			const data = extractResponseData<PromoApiPayload>(res) || {};
			promo.value = {
				active: data.promo_active === 'true' || data.promo_active === true,
				label_text: data.promo_label_text || '',
				label_color: data.promo_label_color || '#E44203',
				label_image: data.promo_label_image || null,
				show_badges: data.promo_show_badges === 'true' || data.promo_show_badges === true,
				description: data.promo_description || '',
			};
		} catch {
			// Default values already set
		} finally {
			promoLoading.value = false;
		}
	};

	// ── Save ─────────────────────────────────────────────
	const seedBands = async () => {
		seeding.value = true;
		try {
			await sanctum("/api/admin/price-bands/seed", { method: "POST" });
			showSuccess("Fasce di prezzo inizializzate nel database.");
			await fetchPriceBands();
			await reloadPublicPriceBands();
		} catch (e) {
			showError(e, "Errore durante l'inizializzazione delle fasce.");
		} finally {
			seeding.value = false;
		}
	};

	const savePriceBands = async () => {
		saving.value = true;
		try {
			const payload = buildPricingPayload();
			const response = await sanctum<MaybeDataResponse<PriceBandsApiPayload> | PriceBandsApiPayload>("/api/admin/price-bands", { method: "PUT", body: payload });
			const data = extractResponseData<PriceBandsApiPayload>(response) || {};
			showSuccess("Configurazione prezzi nazionale ed Europa salvata con successo.");
			bandsFromDb.value = true;
			originalWeightBands.value = (data.weight || payload.weight).map((band, idx) => asPriceBand(band, 'weight', idx));
			originalVolumeBands.value = (data.volume || payload.volume).map((band, idx) => asPriceBand(band, 'volume', idx));
			originalExtraRules.value = JSON.parse(JSON.stringify(data.extra_rules || payload.extra_rules));
			originalSupplementRules.value = JSON.parse(JSON.stringify(data.supplements || payload.supplements));
			europePricing.value = normalizeEuropePricingForAdmin(data.europe || payload.europe || DEFAULT_EUROPE_PRICING);
			originalEuropePricing.value = JSON.parse(JSON.stringify(europePricing.value));
			servicePricing.value = normalizePricingGroupForAdmin(data.service_pricing || payload.service_pricing || {}, DEFAULT_SERVICE_PRICING);
			automaticSupplements.value = normalizePricingGroupForAdmin(data.automatic_supplements || payload.automatic_supplements || {}, DEFAULT_AUTOMATIC_SUPPLEMENTS);
			operationalFees.value = normalizePricingGroupForAdmin(data.operational_fees || payload.operational_fees || {}, DEFAULT_OPERATIONAL_FEES);
			originalServicePricing.value = JSON.parse(JSON.stringify(servicePricing.value));
			originalAutomaticSupplements.value = JSON.parse(JSON.stringify(automaticSupplements.value));
			originalOperationalFees.value = JSON.parse(JSON.stringify(operationalFees.value));
			pricingVersion.value = data.version || pricingVersion.value;
			await reloadPublicPriceBands();
		} catch (e) {
			showError(e, "Errore durante il salvataggio della configurazione prezzi.");
		} finally {
			saving.value = false;
		}
	};

	const savePromo = async () => {
		promoSaving.value = true;
		try {
			await sanctum("/api/admin/promo-settings", {
				method: "POST",
				body: {
					promo_active: promo.value.active ? 'true' : 'false',
					promo_label_text: promo.value.label_text,
					promo_label_color: promo.value.label_color,
					promo_show_badges: promo.value.show_badges ? 'true' : 'false',
					promo_description: promo.value.description,
				},
			});
			showSuccess("Impostazioni promozione salvate con successo.");
			await reloadPublicPriceBands();
		} catch (e) {
			showError(e, "Errore durante il salvataggio della promozione.");
		} finally {
			promoSaving.value = false;
		}
	};

	const uploadPromoImage = async (event: Event) => {
		const input = event.target instanceof HTMLInputElement ? event.target : null;
		if (!input) return;
		const file = input.files?.[0];
		if (!file) return;
		const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
		if (!validTypes.includes(file.type)) {
			showError(null, "Formato file non valido. Usa JPG, PNG, GIF o WebP.");
			input.value = '';
			return;
		}
		const maxSize = 2 * 1024 * 1024;
		if (file.size > maxSize) {
			showError(null, "File troppo grande. Dimensione massima: 2MB.");
			input.value = '';
			return;
		}
		promoImageUploading.value = true;
		try {
			const formData = new FormData();
			formData.append('image', file);
			const res = await sanctum<MaybeDataResponse<PromoApiPayload> | PromoApiPayload>("/api/admin/promo-settings/upload-image", { method: "POST", body: formData });
			const data = extractResponseData<PromoApiPayload>(res) || {};
			promo.value.label_image = data.image_url || null;
			showSuccess("Immagine promo caricata.");
		} catch (e) {
			showError(e, "Errore durante l'upload dell'immagine.");
		} finally {
			promoImageUploading.value = false;
			input.value = '';
		}
	};

	return {
		isLoading,
		saving,
		seeding,
		promoLoading,
		promoSaving,
		promoImageUploading,
		promo,
		buildPricingPayload,
		fetchPriceBands,
		fetchPromoSettings,
		seedBands,
		savePriceBands,
		savePromo,
		uploadPromoImage,
	};
};
