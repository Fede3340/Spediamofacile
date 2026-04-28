/**
 * usePriceBandsDefaults.js
 *
 * All constants, fallback data, and normalization functions for usePriceBands.
 * Pure functions only -- no Vue reactivity or API calls.
 */

// ---- Numeric helpers ----

export const EPSILON = 0.0000001;

export const toNumber = (value, fallback = 0) => {
	const n = Number(value);
	return Number.isFinite(n) ? n : fallback;
};

export const toInt = (value, fallback = 0) => {
	const n = Number.parseInt(value, 10);
	return Number.isFinite(n) ? n : fallback;
};

export const normalizeDecimal = (value, fallback = 0) => {
	return Number(toNumber(value, fallback).toFixed(4));
};

// ---- Fallback band arrays ----

export const FALLBACK_WEIGHT_BANDS = [
	{ id: "weight-1", type: "weight", min_value: 0, max_value: 2, base_price: 890, discount_price: null, show_discount: true, sort_order: 1 },
	{ id: "weight-2", type: "weight", min_value: 2, max_value: 5, base_price: 1190, discount_price: null, show_discount: true, sort_order: 2 },
	{ id: "weight-3", type: "weight", min_value: 5, max_value: 10, base_price: 1490, discount_price: null, show_discount: true, sort_order: 3 },
	{ id: "weight-4", type: "weight", min_value: 10, max_value: 25, base_price: 1990, discount_price: null, show_discount: true, sort_order: 4 },
	{ id: "weight-5", type: "weight", min_value: 25, max_value: 50, base_price: 2990, discount_price: null, show_discount: true, sort_order: 5 },
	{ id: "weight-6", type: "weight", min_value: 50, max_value: 75, base_price: 3990, discount_price: null, show_discount: true, sort_order: 6 },
	{ id: "weight-7", type: "weight", min_value: 75, max_value: 100, base_price: 4990, discount_price: null, show_discount: true, sort_order: 7 },
];

export const FALLBACK_VOLUME_BANDS = [
	{ id: "volume-1", type: "volume", min_value: 0, max_value: 0.010, base_price: 890, discount_price: null, show_discount: true, sort_order: 1 },
	{ id: "volume-2", type: "volume", min_value: 0.010, max_value: 0.020, base_price: 1190, discount_price: null, show_discount: true, sort_order: 2 },
	{ id: "volume-3", type: "volume", min_value: 0.020, max_value: 0.040, base_price: 1490, discount_price: null, show_discount: true, sort_order: 3 },
	{ id: "volume-4", type: "volume", min_value: 0.040, max_value: 0.100, base_price: 1990, discount_price: null, show_discount: true, sort_order: 4 },
	{ id: "volume-5", type: "volume", min_value: 0.100, max_value: 0.200, base_price: 2990, discount_price: null, show_discount: true, sort_order: 5 },
	{ id: "volume-6", type: "volume", min_value: 0.200, max_value: 0.300, base_price: 3990, discount_price: null, show_discount: true, sort_order: 6 },
	{ id: "volume-7", type: "volume", min_value: 0.300, max_value: 0.400, base_price: 4990, discount_price: null, show_discount: true, sort_order: 7 },
];

// ---- Default config objects ----

export const DEFAULT_EXTRA_RULES = {
	enabled: true,
	weight_start: 101,
	weight_step: 50,
	volume_start: 0.401,
	volume_step: 0.200,
	increment_cents: 500,
	increment_mode: "flat",
	weight_increment_ladder: [{ from_step: 1, to_step: null, increment_cents: 500 }],
	volume_increment_ladder: [{ from_step: 1, to_step: null, increment_cents: 500 }],
	base_price_cents_mode: "last_band_effective",
	base_price_cents_manual: null,
	weight_resolution: 1,
	volume_resolution: 0.001,
};

export const DEFAULT_SUPPLEMENTS = [
	{ id: "supplement-1", prefix: "90", amount_cents: 250, apply_to: "both", enabled: true },
];

export const DEFAULT_PROMO = {
	active: false,
	label_text: "",
	label_color: "#E44203",
	label_image: null,
	show_badges: false,
	description: "",
};

export const DEFAULT_EUROPE_PRICING = {
	enabled: false,
	scope: "europe_monocollo",
	origin_country_code: "IT",
	max_packages: 1,
	max_quantity_per_package: 1,
	supported_country_codes: [],
	bands: [],
	version: null,
};

export const DEFAULT_SERVICE_PRICING = {
	senza_etichetta: {
		label: "Senza etichetta",
		description: "Il corriere stampa e applica l'etichetta al ritiro.",
		pricing_type: "fixed",
		price_cents: 99,
		enabled: true,
		application: "per_spedizione",
		note: "",
	},
	notifications: {
		label: "Notifiche spedizione",
		description: "SMS ed email al ritiro, in transito e alla consegna.",
		pricing_type: "fixed",
		price_cents: 50,
		enabled: true,
		application: "per_spedizione",
		note: "",
	},
	sponda_idraulica: {
		label: "Sponda idraulica",
		description: "Supplemento per mezzo con pedana.",
		pricing_type: "fixed",
		price_cents: 1500,
		enabled: true,
		application: "per_spedizione",
		note: "",
	},
	contrassegno: {
		label: "Contrassegno",
		description: "Incasso alla consegna comprensivo di bonifico.",
		pricing_type: "threshold_percentage",
		threshold_amount_eur: 300,
		min_fee_cents: 700,
		percentage_rate: 2,
		enabled: true,
		application: "per_spedizione",
		note: "",
	},
	assicurazione: {
		label: "Assicurazione",
		description: "Protezione economica sulla merce dichiarata.",
		pricing_type: "threshold_percentage",
		threshold_amount_eur: 300,
		min_fee_cents: 700,
		percentage_rate: 2,
		enabled: true,
		application: "per_spedizione",
		note: "",
	},
};

export const DEFAULT_AUTOMATIC_SUPPLEMENTS = {
	calabria_sardegna_sicilia: {
		label: "Calabria / Sardegna / Sicilia",
		description: "Supplemento automatico destinazione per collo.",
		enabled: true,
		pricing_type: "tiered_weight",
		application: "automatic_destination_per_package",
		province_codes: ["AG","CL","CT","EN","ME","PA","RG","SR","TP","CA","CI","NU","OG","OR","OT","SS","SU","VS","CS","CZ","KR","RC","VV"],
		tiers: [
			{ up_to_kg: 10, price_cents: 600 },
			{ up_to_kg: 25, price_cents: 700 },
			{ up_to_kg: 50, price_cents: 800 },
			{ up_to_kg: 100, price_cents: 1500 },
			{ up_to_kg: null, price_cents: 2000 },
		],
		note: "",
	},
	brt_point_csi: {
		label: "BRT Point Calabria / Sardegna / Sicilia",
		description: "Supplemento ridotto per consegna presso punto BRT fino a 20 kg/collo.",
		enabled: true,
		pricing_type: "fixed_with_threshold",
		application: "automatic_destination_per_package",
		province_codes: ["AG","CL","CT","EN","ME","PA","RG","SR","TP","CA","CI","NU","OG","OR","OT","SS","SU","VS","CS","CZ","KR","RC","VV"],
		delivery_modes: ["pudo"],
		max_weight_kg: 20,
		price_cents: 200,
		note: "",
	},
	isole_minori_italia: {
		label: "Isole minori Italia",
		description: "Supplemento automatico per localita' italiane insulari minori.",
		enabled: true,
		pricing_type: "fixed",
		application: "automatic_destination",
		country_codes: ["IT"],
		keyword_list: ["la maddalena","carloforte","calasetta","pantelleria","lampedusa","linosa","favignana","levanzo","marettimo","lipari","vulcano","salina","panarea","stromboli","filicudi","alicudi","ustica","ponza","ventotene","procida","ischia","capri","elba","giglio","giannutri","tremiti","capraia"],
		price_cents: 2000,
		note: "",
	},
	isole_minori_europa: {
		label: "Isole minori Europa",
		description: "Supplemento automatico per localita' europee insulari minori.",
		enabled: true,
		pricing_type: "fixed",
		application: "automatic_destination",
		country_codes: ["ES","PT","FR","GR","HR","MT","CY"],
		keyword_list: ["ibiza","formentera","menorca","minorca","mallorca","majorca","canarie","canary","tenerife","gran canaria","fuerteventura","lanzarote","madeira","azores","porto santo","corsica","corfu","santorini","mykonos","rodos","rhodes","crete"],
		price_cents: 2500,
		note: "",
	},
	fuori_sagoma: {
		label: "Fuori sagoma",
		description: "Supplemento automatico per colli fuori sagoma.",
		enabled: true,
		pricing_type: "tiered_weight",
		application: "automatic_package_shape",
		flag_keys: ["fuori_sagoma", "out_of_gauge", "oversized"],
		longest_side_threshold_cm: 100,
		girth_threshold_cm: 260,
		tiers: [
			{ up_to_kg: 10, price_cents: 300 },
			{ up_to_kg: null, price_cents: 500 },
		],
		note: "",
	},
	lato_superiore_130cm: {
		label: "Lato superiore a 130 cm",
		description: "Supplemento automatico per colli con lato massimo oltre 130 cm.",
		enabled: true,
		pricing_type: "fixed",
		application: "automatic_per_package",
		threshold_cm: 130,
		price_cents: 500,
		note: "",
	},
	aste_tubi: {
		label: "Aste / Tubi",
		description: "Supplemento per colli molto lunghi e stretti.",
		enabled: true,
		pricing_type: "fixed",
		application: "automatic_per_package",
		flag_keys: ["aste_tubi", "tubi", "tubo", "rod_tube"],
		min_longest_side_cm: 100,
		max_secondary_side_cm: 20,
		price_cents: 500,
		note: "",
	},
	eu_manual_extra: {
		label: "Extra Europa su preventivo manuale",
		description: "Fee extra per pratiche Europa con preventivo manuale.",
		enabled: true,
		pricing_type: "fixed",
		application: "manual_quote_only",
		price_cents: 1500,
		note: "",
	},
};

export const DEFAULT_OPERATIONAL_FEES = {
	giacenza: {
		label: "Giacenza",
		description: "Costo operativo per gestione giacenza.",
		pricing_type: "fixed",
		price_cents: 1000,
		enabled: true,
		application: "manual_admin",
		note: "",
	},
};

// ---- Normalization functions ----

export function normalizeIncrementLadder(ladder, fallbackIncrementCents) {
	const fallbackIncrement = Math.max(0, toInt(fallbackIncrementCents, DEFAULT_EXTRA_RULES.increment_cents));
	const source = Array.isArray(ladder) ? ladder : [];
	const rows = source
		.map((row, idx) => {
			const fromStep = Math.max(1, toInt(row?.from_step, idx + 1));
			const rawTo = row?.to_step;
			const toStep = rawTo === null || rawTo === "" || rawTo === undefined ? null : Math.max(fromStep, toInt(rawTo, fromStep));
			const increment = Math.max(0, toInt(row?.increment_cents, fallbackIncrement));
			return { from_step: fromStep, to_step: toStep, increment_cents: increment };
		})
		.sort((a, b) => a.from_step - b.from_step);

	if (!rows.length) {
		return [{ from_step: 1, to_step: null, increment_cents: fallbackIncrement }];
	}

	rows[rows.length - 1].to_step = null;
	return rows;
}

export const normalizeBandArray = (bands = [], type) => {
	if (!Array.isArray(bands) || bands.length === 0) {
		return type === "weight" ? [...FALLBACK_WEIGHT_BANDS] : [...FALLBACK_VOLUME_BANDS];
	}

	return [...bands]
		.map((band, idx) => ({
			id: String(band?.id ?? `${type}-${idx + 1}`),
			type,
			min_value: normalizeDecimal(band?.min_value ?? 0),
			max_value: normalizeDecimal(band?.max_value ?? 0),
			base_price: Math.max(0, toInt(band?.base_price, 0)),
			discount_price: band?.discount_price === null || band?.discount_price === "" || band?.discount_price === undefined
				? null
				: Math.max(0, toInt(band.discount_price, 0)),
			show_discount: band?.show_discount !== false,
			sort_order: toInt(band?.sort_order, idx + 1),
		}))
		.sort((a, b) => {
			if (a.min_value === b.min_value) return a.max_value - b.max_value;
			return a.min_value - b.min_value;
		});
};

export const normalizeExtraRules = (rules = {}) => ({
	enabled: rules?.enabled !== false,
	weight_start: normalizeDecimal(rules?.weight_start ?? DEFAULT_EXTRA_RULES.weight_start),
	weight_step: normalizeDecimal(rules?.weight_step ?? DEFAULT_EXTRA_RULES.weight_step),
	volume_start: normalizeDecimal(rules?.volume_start ?? DEFAULT_EXTRA_RULES.volume_start),
	volume_step: normalizeDecimal(rules?.volume_step ?? DEFAULT_EXTRA_RULES.volume_step),
	increment_cents: Math.max(0, toInt(rules?.increment_cents ?? DEFAULT_EXTRA_RULES.increment_cents, DEFAULT_EXTRA_RULES.increment_cents)),
	increment_mode: "flat",
	weight_increment_ladder: normalizeIncrementLadder(rules?.weight_increment_ladder, toInt(rules?.increment_cents ?? DEFAULT_EXTRA_RULES.increment_cents, DEFAULT_EXTRA_RULES.increment_cents)),
	volume_increment_ladder: normalizeIncrementLadder(rules?.volume_increment_ladder, toInt(rules?.increment_cents ?? DEFAULT_EXTRA_RULES.increment_cents, DEFAULT_EXTRA_RULES.increment_cents)),
	base_price_cents_mode: rules?.base_price_cents_mode === "manual" ? "manual" : "last_band_effective",
	base_price_cents_manual: rules?.base_price_cents_manual === null || rules?.base_price_cents_manual === "" || rules?.base_price_cents_manual === undefined
		? null
		: Math.max(0, toInt(rules.base_price_cents_manual, 0)),
	weight_resolution: normalizeDecimal(rules?.weight_resolution ?? DEFAULT_EXTRA_RULES.weight_resolution),
	volume_resolution: normalizeDecimal(rules?.volume_resolution ?? DEFAULT_EXTRA_RULES.volume_resolution),
});

export const normalizeSupplements = (rules = []) => {
	if (!Array.isArray(rules)) {
		return [...DEFAULT_SUPPLEMENTS];
	}
	if (rules.length === 0) {
		return [];
	}

	return rules
		.map((rule, idx) => ({
			id: String(rule?.id ?? `supplement-${idx + 1}`),
			prefix: String(rule?.prefix ?? "").replace(/\D+/g, ""),
			amount_cents: Math.max(0, toInt(rule?.amount_cents ?? 0, 0)),
			apply_to: ["origin", "destination", "both"].includes(rule?.apply_to) ? rule.apply_to : "both",
			enabled: rule?.enabled !== false,
		}))
		.filter((rule) => rule.prefix.length > 0);
};

export const normalizeEuropePricing = (config = {}) => {
	const bands = Array.isArray(config?.bands)
		? [...config.bands]
			.map((band, idx) => ({
				id: String(band?.id ?? `eu-band-${idx + 1}`),
				label: String(band?.label ?? "").trim(),
				max_weight_kg: normalizeDecimal(band?.max_weight_kg ?? 0),
				max_volume_m3: Number(toNumber(band?.max_volume_m3 ?? 0, 0).toFixed(6)),
				volumetric_factor: Math.max(1, toInt(band?.volumetric_factor ?? 250, 250)),
				rates: Array.isArray(band?.rates)
					? band.rates
						.map((rate) => ({
							country_code: String(rate?.country_code ?? "").trim().toUpperCase(),
							country_name: String(rate?.country_name ?? "").trim(),
							price_cents: rate?.price_cents === null || rate?.price_cents === undefined || rate?.price_cents === ""
								? null
								: Math.max(0, toInt(rate.price_cents, 0)),
							quote_required: rate?.quote_required === true,
						}))
						.filter((rate) => rate.country_code)
					: [],
			}))
			.filter((band) => band.max_weight_kg > 0 && band.max_volume_m3 > 0)
			.sort((a, b) => a.max_weight_kg - b.max_weight_kg)
		: [];

	const supportedCountryCodes = Array.isArray(config?.supported_country_codes)
		? [...new Set(config.supported_country_codes.map((code) => String(code || "").trim().toUpperCase()).filter(Boolean))].sort()
		: [...new Set(bands.flatMap((band) => band.rates.map((rate) => rate.country_code)))].sort();

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

export const normalizeKeyedPricingGroup = (config = {}, defaults = {}) => {
	return Object.fromEntries(
		Object.entries(defaults).map(([key, fallback]) => {
			const source = config?.[key] && typeof config[key] === "object" ? config[key] : {};
			return [key, {
				...fallback,
				...source,
				enabled: source?.enabled !== false && fallback?.enabled !== false,
				price_cents: source?.price_cents === null || source?.price_cents === undefined
					? fallback?.price_cents ?? null
					: Math.max(0, toInt(source.price_cents, fallback?.price_cents ?? 0)),
				min_fee_cents: source?.min_fee_cents === null || source?.min_fee_cents === undefined
					? fallback?.min_fee_cents ?? null
					: Math.max(0, toInt(source.min_fee_cents, fallback?.min_fee_cents ?? 0)),
				percentage_rate: source?.percentage_rate === null || source?.percentage_rate === undefined
					? fallback?.percentage_rate ?? null
					: toNumber(source.percentage_rate, fallback?.percentage_rate ?? 0),
				threshold_amount_eur: source?.threshold_amount_eur === null || source?.threshold_amount_eur === undefined
					? fallback?.threshold_amount_eur ?? null
					: toNumber(source.threshold_amount_eur, fallback?.threshold_amount_eur ?? 0),
				max_weight_kg: source?.max_weight_kg === null || source?.max_weight_kg === undefined
					? fallback?.max_weight_kg ?? null
					: toNumber(source.max_weight_kg, fallback?.max_weight_kg ?? 0),
				threshold_cm: source?.threshold_cm === null || source?.threshold_cm === undefined
					? fallback?.threshold_cm ?? null
					: toNumber(source.threshold_cm, fallback?.threshold_cm ?? 0),
				longest_side_threshold_cm: source?.longest_side_threshold_cm === null || source?.longest_side_threshold_cm === undefined
					? fallback?.longest_side_threshold_cm ?? null
					: toNumber(source.longest_side_threshold_cm, fallback?.longest_side_threshold_cm ?? 0),
				girth_threshold_cm: source?.girth_threshold_cm === null || source?.girth_threshold_cm === undefined
					? fallback?.girth_threshold_cm ?? null
					: toNumber(source.girth_threshold_cm, fallback?.girth_threshold_cm ?? 0),
				min_longest_side_cm: source?.min_longest_side_cm === null || source?.min_longest_side_cm === undefined
					? fallback?.min_longest_side_cm ?? null
					: toNumber(source.min_longest_side_cm, fallback?.min_longest_side_cm ?? 0),
				max_secondary_side_cm: source?.max_secondary_side_cm === null || source?.max_secondary_side_cm === undefined
					? fallback?.max_secondary_side_cm ?? null
					: toNumber(source.max_secondary_side_cm, fallback?.max_secondary_side_cm ?? 0),
				province_codes: Array.isArray(source?.province_codes) ? source.province_codes.map((item) => String(item).trim().toUpperCase()).filter(Boolean) : [...(fallback?.province_codes || [])],
				country_codes: Array.isArray(source?.country_codes) ? source.country_codes.map((item) => String(item).trim().toUpperCase()).filter(Boolean) : [...(fallback?.country_codes || [])],
				keyword_list: Array.isArray(source?.keyword_list) ? source.keyword_list.map((item) => String(item).trim().toLowerCase()).filter(Boolean) : [...(fallback?.keyword_list || [])],
				flag_keys: Array.isArray(source?.flag_keys) ? source.flag_keys.map((item) => String(item).trim().toLowerCase()).filter(Boolean) : [...(fallback?.flag_keys || [])],
				delivery_modes: Array.isArray(source?.delivery_modes) ? source.delivery_modes.map((item) => String(item).trim().toLowerCase()).filter(Boolean) : [...(fallback?.delivery_modes || [])],
				tiers: Array.isArray(source?.tiers)
					? source.tiers.map((tier) => ({
						up_to_kg: tier?.up_to_kg === null || tier?.up_to_kg === undefined || tier?.up_to_kg === "" ? null : toNumber(tier.up_to_kg, 0),
						price_cents: Math.max(0, toInt(tier?.price_cents, 0)),
					}))
					: [...(fallback?.tiers || [])],
			}];
		}),
	);
};
