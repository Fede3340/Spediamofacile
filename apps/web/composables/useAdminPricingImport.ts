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
import type { BandType, EuropePricing, ExtraRules, IncrementLadderRow, PriceBand, PricingRuleGroup, PromoSettings, SupplementRule } from '~/types/pricing';
import { buildPricingRulesPayload, normalizeEuropePricingForAdmin, normalizePricingGroupForAdmin } from '~/utils/adminPricingHelpers';

type AdminApiClient = <T = unknown>(url: string, options?: Record<string, unknown>) => Promise<T>;
type MaybeDataResponse<T> = { data?: T | { data?: T } };
type PriceBandsApiPayload = Partial<{
	weight: Partial<PriceBand>[]; volume: Partial<PriceBand>[];
	extra_rules: Partial<ExtraRules>; supplements: Partial<SupplementRule>[];
	europe: Partial<EuropePricing>; service_pricing: PricingRuleGroup;
	automatic_supplements: PricingRuleGroup; operational_fees: PricingRuleGroup;
	version: string | number | null;
}>;
type PriceBandsPayload = {
	weight: PriceBand[]; volume: PriceBand[]; extra_rules: ExtraRules; supplements: SupplementRule[];
	europe: ReturnType<typeof normalizeEuropePricingForAdmin>;
	service_pricing: PricingRuleGroup; automatic_supplements: PricingRuleGroup; operational_fees: PricingRuleGroup;
};
type PromoApiPayload = Partial<{
	promo_active: boolean | string; promo_label_text: string; promo_label_color: string;
	promo_label_image: string | null; promo_show_badges: boolean | string; promo_description: string; image_url: string;
}>;
type CreateImportSectionDeps = {
	weightBands: Ref<PriceBand[]>; volumeBands: Ref<PriceBand[]>; bandsFromDb: Ref<boolean>;
	extraRules: Ref<ExtraRules>; supplementRules: Ref<SupplementRule[]>; pricingVersion: Ref<string | number | null>;
	europePricing: Ref<EuropePricing>; servicePricing: Ref<PricingRuleGroup>;
	automaticSupplements: Ref<PricingRuleGroup>; operationalFees: Ref<PricingRuleGroup>;
	originalWeightBands: Ref<PriceBand[]>; originalVolumeBands: Ref<PriceBand[]>;
	originalExtraRules: Ref<ExtraRules | null>; originalSupplementRules: Ref<SupplementRule[]>;
	originalEuropePricing: Ref<EuropePricing | null>; originalServicePricing: Ref<PricingRuleGroup>;
	originalAutomaticSupplements: Ref<PricingRuleGroup>; originalOperationalFees: Ref<PricingRuleGroup>;
	normalizeLadderForPayload: (rows: unknown, fallbackIncrement: unknown) => IncrementLadderRow[];
	showSuccess: (m: string) => void; showError: (e: unknown, fallback: string) => void;
};

const clone = <T>(v: T): T => JSON.parse(JSON.stringify(v));

const asPriceBand = (band: Partial<PriceBand>, type: BandType, idx: number): PriceBand => ({
	id: String(band.id || `${type}-${idx + 1}`),
	type,
	min_value: Number(band.min_value || 0),
	max_value: Number(band.max_value || 0),
	base_price: Number(band.base_price || 0),
	discount_price: band.discount_price === null || band.discount_price === undefined ? null : Number(band.discount_price || 0),
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
		if (nested && typeof nested === 'object' && 'data' in nested && nested.data !== undefined) return nested.data as T;
		return nested as T;
	}
	return response as T;
};

export const createImportSection = ({
	weightBands, volumeBands, bandsFromDb, extraRules, supplementRules, pricingVersion,
	europePricing, servicePricing, automaticSupplements, operationalFees,
	originalWeightBands, originalVolumeBands, originalExtraRules, originalSupplementRules,
	originalEuropePricing, originalServicePricing, originalAutomaticSupplements, originalOperationalFees,
	normalizeLadderForPayload, showSuccess, showError,
}: CreateImportSectionDeps) => {
	const sanctum = useSanctumClient() as AdminApiClient;
	const { forceReload: reloadPublicPriceBands } = usePriceBands();

	const isLoading = ref(true);
	const saving = ref(false);
	const seeding = ref(false);
	const promoLoading = ref(false);
	const promoSaving = ref(false);
	const promoImageUploading = ref(false);
	const promo = ref<PromoSettings>({ active: false, label_text: '', label_color: '#E44203', label_image: null, show_badges: true, description: '' });

	// ── Common: applica extra rules + ladder ─────────────
	const applyExtraRules = (source: Partial<ExtraRules>) => {
		extraRules.value = { ...DEFAULT_EXTRA_RULES, ...source } as ExtraRules;
		extraRules.value.increment_mode = 'flat';
		const inc = extraRules.value.increment_cents;
		extraRules.value.weight_increment_ladder = normalizeLadderForPayload(extraRules.value.weight_increment_ladder, inc);
		extraRules.value.volume_increment_ladder = normalizeLadderForPayload(extraRules.value.volume_increment_ladder, inc);
	};

	// ── Common: applica gruppi servizi/europe e snapshot ─
	const applyServiceGroups = (data: PriceBandsApiPayload) => {
		europePricing.value = normalizeEuropePricingForAdmin(data.europe || DEFAULT_EUROPE_PRICING);
		servicePricing.value = normalizePricingGroupForAdmin(data.service_pricing || {}, DEFAULT_SERVICE_PRICING);
		automaticSupplements.value = normalizePricingGroupForAdmin(data.automatic_supplements || {}, DEFAULT_AUTOMATIC_SUPPLEMENTS);
		operationalFees.value = normalizePricingGroupForAdmin(data.operational_fees || {}, DEFAULT_OPERATIONAL_FEES);
		originalEuropePricing.value = clone(europePricing.value);
		originalServicePricing.value = clone(servicePricing.value);
		originalAutomaticSupplements.value = clone(automaticSupplements.value);
		originalOperationalFees.value = clone(operationalFees.value);
	};

	// ── Payload builders ─────────────────────────────────
	const buildEuropePricingPayload = () => {
		const n = normalizeEuropePricingForAdmin(europePricing.value);
		return {
			enabled: n.enabled !== false, scope: n.scope, origin_country_code: 'IT', max_packages: 1, max_quantity_per_package: 1,
			bands: n.bands.map((band) => ({
				id: band.id, label: band.label,
				max_weight_kg: Number(band.max_weight_kg || 0),
				max_volume_m3: Number(band.max_volume_m3 || 0),
				volumetric_factor: Number(band.volumetric_factor || 250),
				rates: band.rates.map((rate) => ({
					country_code: String(rate.country_code || '').trim().toUpperCase(),
					country_name: String(rate.country_name || '').trim(),
					price_cents: rate.quote_required || rate.price_cents === null || rate.price_cents === undefined ? null : Number(rate.price_cents || 0),
					quote_required: rate.quote_required === true,
				})),
			})),
			supported_country_codes: n.supported_country_codes, version: n.version,
		};
	};

	const bandToPayload = (band: PriceBand, idx: number, type: BandType): PriceBand => ({
		id: band.id || `${type[0]}-${idx + 1}`, type,
		min_value: Number(band.min_value), max_value: Number(band.max_value),
		base_price: Number(band.base_price || 0),
		discount_price: band.discount_price === null ? null : Number(band.discount_price),
		show_discount: band.show_discount !== false, sort_order: idx + 1,
	});

	const buildPricingPayload = (): PriceBandsPayload => {
		const er = extraRules.value;
		const inc = Number(er.increment_cents || 0);
		const ladder = normalizeLadderForPayload([{ from_step: 1, to_step: null, increment_cents: inc }], inc);
		const isManual = er.base_price_cents_mode === 'manual';
		return {
			weight: weightBands.value.map((b, i) => bandToPayload(b, i, 'weight')),
			volume: volumeBands.value.map((b, i) => bandToPayload(b, i, 'volume')),
			extra_rules: {
				enabled: er.enabled !== false,
				weight_start: Number(er.weight_start), weight_step: Number(er.weight_step),
				volume_start: Number(er.volume_start), volume_step: Number(er.volume_step),
				increment_cents: inc, increment_mode: 'flat',
				weight_increment_ladder: ladder, volume_increment_ladder: ladder,
				base_price_cents_mode: isManual ? 'manual' : 'last_band_effective',
				base_price_cents_manual: isManual ? Number(er.base_price_cents_manual || 0) : null,
				weight_resolution: Number(er.weight_resolution || 1),
				volume_resolution: Number(er.volume_resolution || 0.001),
			},
			supplements: supplementRules.value.map((rule, idx) => asSupplementRule(rule, idx)).filter((rule) => rule.prefix.length > 0),
			europe: buildEuropePricingPayload(),
			service_pricing: buildPricingRulesPayload(servicePricing.value),
			automatic_supplements: buildPricingRulesPayload(automaticSupplements.value),
			operational_fees: buildPricingRulesPayload(operationalFees.value),
		};
	};

	// ── Defaults ─────────────────────────────────────────
	const applyDefaults = () => {
		weightBands.value = DEFAULT_WEIGHT_BANDS.map((band, idx) => asPriceBand({ ...band, id: `new-w-${idx}` }, 'weight', idx));
		volumeBands.value = DEFAULT_VOLUME_BANDS.map((band, idx) => asPriceBand({ ...band, id: `new-v-${idx}` }, 'volume', idx));
		applyExtraRules({});
		supplementRules.value = DEFAULT_SUPPLEMENTS.map((rule, idx) => asSupplementRule(rule, idx));
		originalExtraRules.value = clone(extraRules.value);
		originalSupplementRules.value = clone(supplementRules.value);
		applyServiceGroups({});
		pricingVersion.value = null;
		bandsFromDb.value = false;
	};

	// ── Fetch ────────────────────────────────────────────
	const apiGet = async <T>(url: string): Promise<T> => {
		const res = await sanctum<MaybeDataResponse<T> | T>(url);
		return (extractResponseData<T>(res) || {}) as T;
	};

	const fetchPriceBands = async () => {
		isLoading.value = true;
		try {
			const data = await apiGet<PriceBandsApiPayload>("/api/admin/price-bands");
			const w = Array.isArray(data.weight) ? data.weight : [];
			const v = Array.isArray(data.volume) ? data.volume : [];
			if (w.length === 0 && v.length === 0) return applyDefaults();
			weightBands.value = w.map((band, idx) => asPriceBand(band, 'weight', idx));
			volumeBands.value = v.map((band, idx) => asPriceBand(band, 'volume', idx));
			originalWeightBands.value = weightBands.value.map((band) => ({ ...band }));
			originalVolumeBands.value = volumeBands.value.map((band) => ({ ...band }));
			applyExtraRules(data.extra_rules || {});
			supplementRules.value = (Array.isArray(data.supplements) ? data.supplements : DEFAULT_SUPPLEMENTS).map((rule, idx) => asSupplementRule(rule, idx));
			originalExtraRules.value = clone(extraRules.value);
			originalSupplementRules.value = clone(supplementRules.value);
			applyServiceGroups(data);
			pricingVersion.value = data.version || null;
			bandsFromDb.value = true;
		} catch {
			applyDefaults();
		} finally {
			isLoading.value = false;
		}
	};

	const fetchPromoSettings = async () => {
		promoLoading.value = true;
		try {
			const d = await apiGet<PromoApiPayload>("/api/admin/promo-settings");
			const truthy = (v: unknown) => v === 'true' || v === true;
			promo.value = {
				active: truthy(d.promo_active), label_text: d.promo_label_text || '',
				label_color: d.promo_label_color || '#E44203', label_image: d.promo_label_image || null,
				show_badges: truthy(d.promo_show_badges), description: d.promo_description || '',
			};
		} catch { /* Default values already set */ } finally {
			promoLoading.value = false;
		}
	};

	// ── Save ─────────────────────────────────────────────
	const runWithFlag = async (flag: Ref<boolean>, errorMsg: string, fn: () => Promise<void>) => {
		flag.value = true;
		try { await fn(); } catch (e) { showError(e, errorMsg); } finally { flag.value = false; }
	};

	const seedBands = () => runWithFlag(seeding, "Errore durante l'inizializzazione delle fasce.", async () => {
		await sanctum("/api/admin/price-bands/seed", { method: "POST" });
		showSuccess("Fasce di prezzo inizializzate nel database.");
		await fetchPriceBands();
		await reloadPublicPriceBands();
	});

	const savePriceBands = () => runWithFlag(saving, "Errore durante il salvataggio della configurazione prezzi.", async () => {
		const payload = buildPricingPayload();
		const response = await sanctum<MaybeDataResponse<PriceBandsApiPayload> | PriceBandsApiPayload>("/api/admin/price-bands", { method: "PUT", body: payload });
		const data = extractResponseData<PriceBandsApiPayload>(response) || {};
		showSuccess("Configurazione prezzi nazionale ed Europa salvata con successo.");
		bandsFromDb.value = true;
		originalWeightBands.value = (data.weight || payload.weight).map((band, idx) => asPriceBand(band, 'weight', idx));
		originalVolumeBands.value = (data.volume || payload.volume).map((band, idx) => asPriceBand(band, 'volume', idx));
		originalExtraRules.value = clone((data.extra_rules || payload.extra_rules) as ExtraRules);
		originalSupplementRules.value = clone((data.supplements || payload.supplements) as SupplementRule[]);
		applyServiceGroups({
			europe: data.europe || payload.europe || DEFAULT_EUROPE_PRICING,
			service_pricing: data.service_pricing || payload.service_pricing,
			automatic_supplements: data.automatic_supplements || payload.automatic_supplements,
			operational_fees: data.operational_fees || payload.operational_fees,
		});
		pricingVersion.value = data.version || pricingVersion.value;
		await reloadPublicPriceBands();
	});

	const savePromo = () => runWithFlag(promoSaving, "Errore durante il salvataggio della promozione.", async () => {
		const p = promo.value;
		await sanctum("/api/admin/promo-settings", { method: "POST", body: {
			promo_active: p.active ? 'true' : 'false', promo_label_text: p.label_text,
			promo_label_color: p.label_color, promo_show_badges: p.show_badges ? 'true' : 'false',
			promo_description: p.description,
		} });
		showSuccess("Impostazioni promozione salvate con successo.");
		await reloadPublicPriceBands();
	});

	const uploadPromoImage = async (event: Event) => {
		const input = event.target instanceof HTMLInputElement ? event.target : null;
		const file = input?.files?.[0];
		if (!input || !file) return;
		const reject = (msg: string) => { showError(null, msg); input.value = ''; };
		if (!['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'].includes(file.type)) return reject("Formato file non valido. Usa JPG, PNG, GIF o WebP.");
		if (file.size > 2 * 1024 * 1024) return reject("File troppo grande. Dimensione massima: 2MB.");
		await runWithFlag(promoImageUploading, "Errore durante l'upload dell'immagine.", async () => {
			const formData = new FormData();
			formData.append('image', file);
			const res = await sanctum<MaybeDataResponse<PromoApiPayload> | PromoApiPayload>("/api/admin/promo-settings/upload-image", { method: "POST", body: formData });
			promo.value.label_image = (extractResponseData<PromoApiPayload>(res) || {}).image_url || null;
			showSuccess("Immagine promo caricata.");
		});
		input.value = '';
	};

	return {
		isLoading, saving, seeding, promoLoading, promoSaving, promoImageUploading, promo,
		buildPricingPayload, fetchPriceBands, fetchPromoSettings, seedBands, savePriceBands, savePromo, uploadPromoImage,
	};
};
