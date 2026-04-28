/**
 * @file useAdminPricingImport — sezione import/persistence del pannello admin pricing.
 *
 * Estratto da useAdminPricing.js. CRUD price-bands + promo-settings, seed iniziale,
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
	DEFAULT_VOLUME_BANDS,
	DEFAULT_WEIGHT_BANDS,
} from '~/utils/adminPricingDefaults';
import {
	buildPricingRulesPayload,
	normalizeEuropePricingForAdmin,
	normalizePricingGroupForAdmin,
} from '~/utils/adminPricingNormalize';

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
}) => {
	const sanctum = useSanctumClient();
	const { forceReload: reloadPublicPriceBands } = usePriceBands();

	// ── Loading state ────────────────────────────────────
	const isLoading = ref(true);
	const saving = ref(false);
	const seeding = ref(false);

	// ── Promo state ──────────────────────────────────────
	const promoLoading = ref(false);
	const promoSaving = ref(false);
	const promoImageUploading = ref(false);
	const promo = ref({
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
					price_cents: rate.quote_required || rate.price_cents === null || rate.price_cents === '' || rate.price_cents === undefined
						? null
						: Number(rate.price_cents || 0),
					quote_required: rate.quote_required === true,
				})),
			})),
		};
	};

	const buildPricingPayload = () => ({
		weight: weightBands.value.map((band, idx) => ({
			id: band.id || `w-${idx + 1}`,
			min_value: Number(band.min_value),
			max_value: Number(band.max_value),
			base_price: Number(band.base_price || 0),
			discount_price: band.discount_price === null || band.discount_price === '' ? null : Number(band.discount_price),
			show_discount: band.show_discount !== false,
			sort_order: idx + 1,
		})),
		volume: volumeBands.value.map((band, idx) => ({
			id: band.id || `v-${idx + 1}`,
			min_value: Number(band.min_value),
			max_value: Number(band.max_value),
			base_price: Number(band.base_price || 0),
			discount_price: band.discount_price === null || band.discount_price === '' ? null : Number(band.discount_price),
			show_discount: band.show_discount !== false,
			sort_order: idx + 1,
		})),
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
			.map((rule, idx) => ({
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
		weightBands.value = DEFAULT_WEIGHT_BANDS.map((b, i) => ({ ...b, id: `new-w-${i}` }));
		volumeBands.value = DEFAULT_VOLUME_BANDS.map((b, i) => ({ ...b, id: `new-v-${i}` }));
		extraRules.value = { ...DEFAULT_EXTRA_RULES };
		extraRules.value.increment_mode = 'flat';
		extraRules.value.weight_increment_ladder = normalizeLadderForPayload(extraRules.value.weight_increment_ladder, extraRules.value.increment_cents);
		extraRules.value.volume_increment_ladder = normalizeLadderForPayload(extraRules.value.volume_increment_ladder, extraRules.value.increment_cents);
		supplementRules.value = DEFAULT_SUPPLEMENTS.map(rule => ({ ...rule }));
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
			const res = await sanctum("/api/admin/price-bands");
			const payload = res?.data || res || {};
			const data = payload?.data || payload || {};
			const w = data.weight || [];
			const v = data.volume || [];
			if (w.length > 0 || v.length > 0) {
				weightBands.value = w.map(b => ({ ...b }));
				volumeBands.value = v.map(b => ({ ...b }));
				originalWeightBands.value = w.map(b => ({ ...b }));
				originalVolumeBands.value = v.map(b => ({ ...b }));
				extraRules.value = { ...DEFAULT_EXTRA_RULES, ...(data.extra_rules || {}) };
				extraRules.value.increment_mode = 'flat';
				extraRules.value.weight_increment_ladder = normalizeLadderForPayload(extraRules.value.weight_increment_ladder, extraRules.value.increment_cents);
				extraRules.value.volume_increment_ladder = normalizeLadderForPayload(extraRules.value.volume_increment_ladder, extraRules.value.increment_cents);
				const supplementsFromApi = Array.isArray(data.supplements) ? data.supplements : DEFAULT_SUPPLEMENTS;
				supplementRules.value = supplementsFromApi.map((rule, idx) => ({ id: rule.id || `supplement-${idx + 1}`, ...rule }));
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
		} catch (e) {
			applyDefaults();
		} finally {
			isLoading.value = false;
		}
	};

	const fetchPromoSettings = async () => {
		promoLoading.value = true;
		try {
			const res = await sanctum("/api/admin/promo-settings");
			const data = res?.data || res || {};
			promo.value = {
				active: data.promo_active === 'true' || data.promo_active === true,
				label_text: data.promo_label_text || '',
				label_color: data.promo_label_color || '#E44203',
				label_image: data.promo_label_image || null,
				show_badges: data.promo_show_badges === 'true' || data.promo_show_badges === true,
				description: data.promo_description || '',
			};
		} catch (e) {
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
			const response = await sanctum("/api/admin/price-bands", { method: "PUT", body: payload });
			const data = response?.data || {};
			showSuccess("Configurazione prezzi nazionale ed Europa salvata con successo.");
			bandsFromDb.value = true;
			originalWeightBands.value = (data.weight || payload.weight).map(b => ({ ...b }));
			originalVolumeBands.value = (data.volume || payload.volume).map(b => ({ ...b }));
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

	const uploadPromoImage = async (event) => {
		const file = event.target.files?.[0];
		if (!file) return;
		const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
		if (!validTypes.includes(file.type)) {
			showError(null, "Formato file non valido. Usa JPG, PNG, GIF o WebP.");
			event.target.value = '';
			return;
		}
		const maxSize = 2 * 1024 * 1024;
		if (file.size > maxSize) {
			showError(null, "File troppo grande. Dimensione massima: 2MB.");
			event.target.value = '';
			return;
		}
		promoImageUploading.value = true;
		try {
			const formData = new FormData();
			formData.append('image', file);
			const res = await sanctum("/api/admin/promo-settings/upload-image", { method: "POST", body: formData });
			promo.value.label_image = res?.image_url || null;
			showSuccess("Immagine promo caricata.");
		} catch (e) {
			showError(e, "Errore durante l'upload dell'immagine.");
		} finally {
			promoImageUploading.value = false;
			event.target.value = '';
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
