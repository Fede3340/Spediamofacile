/**
 * @file shipmentServicePricing — Utility shipmentServicePricing.
 */
// Vincolo cross-stack: le regole di pricing (DEFAULT_* e funzioni di calcolo) devono restare allineate al backend Laravel; prezzi in centesimi int per coerenza con MyMoney.

// ---------------------------------------------------------------------------
// TIPI
// ---------------------------------------------------------------------------

/**
 * @typedef {'per_spedizione'
 *   | 'automatic_destination'
 *   | 'automatic_destination_per_package'
 *   | 'automatic_package_shape'
 *   | 'automatic_per_package'
 *   | 'manual_quote_only'
 *   | string} PricingApplication
 */

/**
 * @typedef {'fixed'
 *   | 'threshold_percentage'
 *   | 'tiered_weight'
 *   | 'fixed_with_threshold'
 *   | string} PricingType
 */

/**
 * @typedef {Object} PricingTier
 * @property {number | null} up_to_kg
 * @property {number} price_cents
 */

/**
 * @typedef {Object} ServicePricingRule
 * @property {string} label
 * @property {string} [description]
 * @property {PricingType} pricing_type
 * @property {boolean} enabled
 * @property {PricingApplication} application
 * @property {string} [note]
 * @property {number | null} [price_cents]
 * @property {number | null} [min_fee_cents]
 * @property {number | null} [percentage_rate]
 * @property {number | null} [threshold_amount_eur]
 * @property {number | null} [max_weight_kg]
 * @property {number | null} [threshold_cm]
 * @property {number | null} [longest_side_threshold_cm]
 * @property {number | null} [girth_threshold_cm]
 * @property {number | null} [min_longest_side_cm]
 * @property {number | null} [max_secondary_side_cm]
 * @property {string[]} [province_codes]
 * @property {string[]} [country_codes]
 * @property {string[]} [keyword_list]
 * @property {string[]} [flag_keys]
 * @property {string[]} [delivery_modes]
 * @property {PricingTier[]} [tiers]
 */

/**
 * @typedef {Object} PricingConfig
 * @property {Record<string, ServicePricingRule>} service_pricing
 * @property {Record<string, ServicePricingRule>} automatic_supplements
 */

/**
 * @typedef {Object} PackageInput
 * @property {string | null} [package_type]
 * @property {number | string | null} [quantity]
 * @property {number | string | null} [weight]
 * @property {number | string | null} [first_size]
 * @property {number | string | null} [second_size]
 * @property {number | string | null} [third_size]
 * @property {number | string | null} [length]
 * @property {number | string | null} [width]
 * @property {number | string | null} [height]
 */

/**
 * @typedef {Object} NormalizedPackage
 * @property {string} package_type
 * @property {number} weight_kg
 * @property {number} quantity
 * @property {number} first_size_cm
 * @property {number} second_size_cm
 * @property {number} third_size_cm
 * @property {number} max_side_cm
 * @property {number} secondary_side_sum_cm
 * @property {PackageInput} raw
 */

/**
 * @typedef {Object} AddressInput
 * @property {string | null} [country]
 * @property {string | null} [country_code]
 * @property {string | null} [province]
 * @property {string | null} [city]
 * @property {string | null} [address]
 * @property {string | null} [additional_information]
 * @property {string | null} [postal_code]
 * @property {string | null} [address_number]
 * @property {string | null} [pudo_id]
 * @property {string | null} [name]
 * @property {number | null} [latitude]
 * @property {number | null} [longitude]
 */

/**
 * @typedef {Object} NormalizedAddress
 * @property {string} country
 * @property {string} province
 * @property {string} city
 * @property {string} address
 * @property {string} additional_information
 */

/**
 * @typedef {Object} SurchargeItem
 * @property {string} key
 * @property {string} label
 * @property {'service' | 'automatic_supplement'} type
 * @property {boolean} automatic
 * @property {string} application
 * @property {number} amount_cents
 * @property {number} amount
 */

/**
 * @typedef {Object} SurchargeResult
 * @property {number} total
 * @property {number} total_cents
 * @property {SurchargeItem[]} items
 */

/**
 * @typedef {Object} CalculateShipmentSurchargeOptions
 * @property {string[] | string} [selectedServices]
 * @property {string} [serviceType]
 * @property {Record<string, unknown>} [serviceData]
 * @property {boolean} [smsEmailNotification]
 * @property {Partial<PricingConfig> | null} [pricingConfig]
 * @property {PackageInput[]} [packages]
 * @property {AddressInput} [originAddress]
 * @property {AddressInput} [destinationAddress]
 * @property {string} [deliveryMode]
 * @property {AddressInput | null} [selectedPudo]
 * @property {boolean} [requiresManualQuote]
 */

// ---------------------------------------------------------------------------
// DEFAULTS
// ---------------------------------------------------------------------------

/** @type {Record<string, ServicePricingRule>} */
const DEFAULT_SERVICE_PRICING = Object.freeze({
	senza_etichetta: {
		label: 'Senza etichetta',
		description: "Il corriere stampa e applica l'etichetta al ritiro.",
		pricing_type: 'fixed',
		price_cents: 99,
		enabled: true,
		application: 'per_spedizione',
		note: '',
	},
	notifications: {
		label: 'Notifiche spedizione',
		description: 'SMS ed email al ritiro, in transito e alla consegna.',
		pricing_type: 'fixed',
		price_cents: 50,
		enabled: true,
		application: 'per_spedizione',
		note: '',
	},
	sponda_idraulica: {
		label: 'Sponda idraulica',
		description: 'Supplemento per mezzo con pedana.',
		pricing_type: 'fixed',
		price_cents: 1500,
		enabled: true,
		application: 'per_spedizione',
		note: '',
	},
	contrassegno: {
		label: 'Contrassegno',
		description: 'Incasso alla consegna comprensivo di bonifico.',
		pricing_type: 'threshold_percentage',
		threshold_amount_eur: 300,
		min_fee_cents: 700,
		percentage_rate: 2,
		enabled: true,
		application: 'per_spedizione',
		note: '',
	},
	assicurazione: {
		label: 'Assicurazione',
		description: 'Protezione economica sulla merce dichiarata.',
		pricing_type: 'threshold_percentage',
		threshold_amount_eur: 300,
		min_fee_cents: 700,
		percentage_rate: 2,
		enabled: true,
		application: 'per_spedizione',
		note: '',
	},
})

/** @type {Record<string, ServicePricingRule>} */
const DEFAULT_AUTOMATIC_SUPPLEMENTS = Object.freeze({
	calabria_sardegna_sicilia: {
		label: 'Calabria / Sardegna / Sicilia',
		description: 'Supplemento automatico destinazione per collo.',
		enabled: true,
		pricing_type: 'tiered_weight',
		application: 'automatic_destination_per_package',
		province_codes: ['AG', 'CL', 'CT', 'EN', 'ME', 'PA', 'RG', 'SR', 'TP', 'CA', 'CI', 'NU', 'OG', 'OR', 'OT', 'SS', 'SU', 'VS', 'CS', 'CZ', 'KR', 'RC', 'VV'],
		tiers: [
			{ up_to_kg: 10, price_cents: 600 },
			{ up_to_kg: 25, price_cents: 700 },
			{ up_to_kg: 50, price_cents: 800 },
			{ up_to_kg: 100, price_cents: 1500 },
			{ up_to_kg: null, price_cents: 2000 },
		],
		note: '',
	},
	brt_point_csi: {
		label: 'BRT Point Calabria / Sardegna / Sicilia',
		description: 'Supplemento ridotto per consegna presso punto BRT fino a 20 kg/collo.',
		enabled: true,
		pricing_type: 'fixed_with_threshold',
		application: 'automatic_destination_per_package',
		province_codes: ['AG', 'CL', 'CT', 'EN', 'ME', 'PA', 'RG', 'SR', 'TP', 'CA', 'CI', 'NU', 'OG', 'OR', 'OT', 'SS', 'SU', 'VS', 'CS', 'CZ', 'KR', 'RC', 'VV'],
		delivery_modes: ['pudo'],
		max_weight_kg: 20,
		price_cents: 200,
		note: '',
	},
	isole_minori_italia: {
		label: 'Isole minori Italia',
		description: 'Supplemento automatico per localita italiane insulari minori.',
		enabled: true,
		pricing_type: 'fixed',
		application: 'automatic_destination',
		country_codes: ['IT'],
		keyword_list: ['la maddalena', 'carloforte', 'calasetta', 'pantelleria', 'lampedusa', 'linosa', 'favignana', 'levanzo', 'marettimo', 'lipari', 'vulcano', 'salina', 'panarea', 'stromboli', 'filicudi', 'alicudi', 'ustica', 'ponza', 'ventotene', 'procida', 'ischia', 'capri', 'elba', 'giglio', 'giannutri', 'tremiti', 'capraia'],
		price_cents: 2000,
		note: '',
	},
	isole_minori_europa: {
		label: 'Isole minori Europa',
		description: 'Supplemento automatico per localita europee insulari minori.',
		enabled: true,
		pricing_type: 'fixed',
		application: 'automatic_destination',
		country_codes: ['ES', 'PT', 'FR', 'GR', 'HR', 'MT', 'CY'],
		keyword_list: ['ibiza', 'formentera', 'menorca', 'minorca', 'mallorca', 'majorca', 'canarie', 'canary', 'tenerife', 'gran canaria', 'fuerteventura', 'lanzarote', 'madeira', 'azores', 'porto santo', 'corsica', 'corfu', 'santorini', 'mykonos', 'rodos', 'rhodes', 'crete'],
		price_cents: 2500,
		note: '',
	},
	fuori_sagoma: {
		label: 'Fuori sagoma',
		description: 'Supplemento automatico per colli fuori sagoma.',
		enabled: true,
		pricing_type: 'tiered_weight',
		application: 'automatic_package_shape',
		flag_keys: ['fuori_sagoma', 'out_of_gauge', 'oversized'],
		longest_side_threshold_cm: 100,
		girth_threshold_cm: 260,
		tiers: [
			{ up_to_kg: 10, price_cents: 300 },
			{ up_to_kg: null, price_cents: 500 },
		],
		note: '',
	},
	lato_superiore_130cm: {
		label: 'Lato superiore a 130 cm',
		description: 'Supplemento automatico per colli con lato massimo oltre 130 cm.',
		enabled: true,
		pricing_type: 'fixed',
		application: 'automatic_per_package',
		threshold_cm: 130,
		price_cents: 500,
		note: '',
	},
	aste_tubi: {
		label: 'Aste / Tubi',
		description: 'Supplemento per colli molto lunghi e stretti.',
		enabled: true,
		pricing_type: 'fixed',
		application: 'automatic_per_package',
		flag_keys: ['aste_tubi', 'tubi', 'tubo', 'rod_tube'],
		min_longest_side_cm: 100,
		max_secondary_side_cm: 20,
		price_cents: 500,
		note: '',
	},
	eu_manual_extra: {
		label: 'Extra Europa su preventivo manuale',
		description: 'Fee extra per pratiche Europa con preventivo manuale.',
		enabled: true,
		pricing_type: 'fixed',
		application: 'manual_quote_only',
		price_cents: 1500,
		note: '',
	},
})

// ---------------------------------------------------------------------------
// HELPERS
// ---------------------------------------------------------------------------

/**
 * @param {number | string | null | undefined} value
 * @returns {number}
 */
const roundCurrency = (value) => Math.round((Number(value) || 0) * 100) / 100

/**
 * Parsing tollerante: accetta "1.234,56", "1234.56", "€ 1,23", numero o null.
 * @param {unknown} value
 * @returns {number}
 */
export const parseCurrencyAmount = (value) => {
	if (value === null || value === undefined) return 0
	if (typeof value === 'number') return Number.isFinite(value) ? value : 0

	const normalized = String(value)
		.trim()
		.replace(/[€\s]/g, '')
		.replace(/\.(?=\d{3}(?:\D|$))/g, '')
		.replace(',', '.')

	const parsed = Number(normalized)
	return Number.isFinite(parsed) ? parsed : 0
}

/**
 * @param {unknown} value
 * @returns {number}
 */
const parseNumericValue = (value) => {
	const parsed = parseCurrencyAmount(value)
	return parsed > 0 ? parsed : 0
}

/**
 * Normalizza la chiave di un servizio umano-readable in chiave canonica (es. "Senza etichetta" -> "senza_etichetta").
 * @param {unknown} value
 * @returns {string}
 */
export const normalizeServiceKey = (value) => {
	const raw = String(value || '')
		.trim()
		.toLowerCase()
		.normalize('NFD')
		.replace(/[\u0300-\u036f]/g, '')

	if (!raw || raw === 'nessuno') return ''
	if (raw.includes('senza') && raw.includes('etichetta')) return 'senza_etichetta'
	if (raw.includes('contrassegno')) return 'contrassegno'
	if (raw.includes('assicurazione')) return 'assicurazione'
	if (raw.includes('sponda')) return 'sponda_idraulica'
	if (raw.includes('sms') || raw.includes('notifiche')) return 'sms_email_notification'
	return raw.replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '')
}

/**
 * Normalizza la selezione servizi (array/stringa csv) in lista di chiavi canoniche dedupplicate.
 * @param {string[] | string | null | undefined} serviceSelection
 * @returns {string[]}
 */
export const normalizeSelectedServices = (serviceSelection) => {
	if (Array.isArray(serviceSelection)) {
		return [...new Set(serviceSelection.map(normalizeServiceKey).filter(Boolean))]
	}

	const raw = String(serviceSelection || '').trim()
	if (!raw || raw.toLowerCase() === 'nessuno') return []
	return [...new Set(raw.split(',').map((entry) => normalizeServiceKey(entry)).filter(Boolean))]
}

/**
 * Primo valore non undefined fra i nomi chiave elencati.
 * @param {Record<string, unknown> | null | undefined} source
 * @param {string[]} [keys]
 * @returns {unknown | undefined}
 */
const getNested = (source, keys = []) => {
	if (!source || typeof source !== 'object') return undefined
	for (const key of keys) {
		if (key in source) return source[key]
	}
	return undefined
}

/**
 * @param {Record<string, unknown>} [serviceData]
 * @returns {number}
 */
const getContrassegnoAmount = (serviceData = {}) => {
	const contrassegno = getNested(serviceData, ['contrassegno', 'Contrassegno'])
	return parseCurrencyAmount(contrassegno?.importo)
}

/**
 * @param {Record<string, unknown>} [serviceData]
 * @returns {number}
 */
const getAssicurazioneAmount = (serviceData = {}) => {
	const assicurazione = getNested(serviceData, ['assicurazione', 'Assicurazione'])
	if (!assicurazione || typeof assicurazione !== 'object') return 0

	return Object.values(assicurazione)
		.map(parseCurrencyAmount)
		.reduce((sum, value) => sum + value, 0)
}

/**
 * Normalizza una lista di stringhe: trim, filtro vuoti, dedup e opzionale uppercase/lowercase.
 * @param {unknown} items
 * @param {{ uppercase?: boolean }} [options]
 * @returns {string[]}
 */
const normalizeList = (items, { uppercase = false } = {}) => {
	if (!Array.isArray(items)) return []
	return [...new Set(items
		.map((item) => String(item || '').trim())
		.filter(Boolean)
		.map((item) => (uppercase ? item.toUpperCase() : item.toLowerCase())),
	)]
}

/**
 * Normalizza i tier di peso: up_to_kg numerico o null, ordinati per peso crescente.
 * @param {PricingTier[]} [tiers]
 * @returns {PricingTier[]}
 */
const normalizeTiers = (tiers = []) => {
	if (!Array.isArray(tiers)) return []
	return [...tiers]
		.map((tier) => ({
			up_to_kg: tier?.up_to_kg === null || tier?.up_to_kg === undefined || tier.up_to_kg === ''
				? null
				: parseNumericValue(tier.up_to_kg),
			price_cents: Math.max(0, Math.round(Number(tier?.price_cents || 0))),
		}))
		.sort((a, b) => {
			const left = a.up_to_kg ?? Number.POSITIVE_INFINITY
			const right = b.up_to_kg ?? Number.POSITIVE_INFINITY
			return left - right
		})
}

/**
 * Normalizza un gruppo chiave->regola applicando fallback e coercizioni numeriche.
 * @param {Record<string, Partial<ServicePricingRule>>} [group]
 * @param {Record<string, ServicePricingRule>} [defaults]
 * @returns {Record<string, ServicePricingRule>}
 */
const normalizeKeyedPricingGroup = (
	group = {},
	defaults = {},
) => Object.fromEntries(
	Object.entries(defaults).map(([key, fallback]) => {
		const source = group?.[key] && typeof group[key] === 'object' ? group[key] : {}
		/**
		 * @param {unknown} srcVal
		 * @param {number | null | undefined} fbVal
		 * @returns {number | null}
		 */
		const numberOrFallback = (srcVal, fbVal) => {
			if (srcVal === null || srcVal === undefined) return fbVal ?? null
			return Number(srcVal) || 0
		}
		/**
		 * @param {unknown} srcVal
		 * @param {number | null | undefined} fbVal
		 * @returns {number | null}
		 */
		const centsOrFallback = (srcVal, fbVal) => {
			if (srcVal === null || srcVal === undefined) return fbVal ?? null
			return Math.max(0, Math.round(Number(srcVal || 0)))
		}
		return [key, {
			...fallback,
			...source,
			enabled: source?.enabled !== false && fallback?.enabled !== false,
			price_cents: centsOrFallback(source?.price_cents, fallback?.price_cents),
			min_fee_cents: centsOrFallback(source?.min_fee_cents, fallback?.min_fee_cents),
			percentage_rate: numberOrFallback(source?.percentage_rate, fallback?.percentage_rate),
			threshold_amount_eur: numberOrFallback(source?.threshold_amount_eur, fallback?.threshold_amount_eur),
			max_weight_kg: numberOrFallback(source?.max_weight_kg, fallback?.max_weight_kg),
			threshold_cm: numberOrFallback(source?.threshold_cm, fallback?.threshold_cm),
			longest_side_threshold_cm: numberOrFallback(source?.longest_side_threshold_cm, fallback?.longest_side_threshold_cm),
			girth_threshold_cm: numberOrFallback(source?.girth_threshold_cm, fallback?.girth_threshold_cm),
			min_longest_side_cm: numberOrFallback(source?.min_longest_side_cm, fallback?.min_longest_side_cm),
			max_secondary_side_cm: numberOrFallback(source?.max_secondary_side_cm, fallback?.max_secondary_side_cm),
			province_codes: Array.isArray(source?.province_codes)
				? normalizeList(source.province_codes, { uppercase: true })
				: [...(fallback?.province_codes || [])],
			country_codes: Array.isArray(source?.country_codes)
				? normalizeList(source.country_codes, { uppercase: true })
				: [...(fallback?.country_codes || [])],
			keyword_list: Array.isArray(source?.keyword_list)
				? normalizeList(source.keyword_list)
				: [...(fallback?.keyword_list || [])],
			flag_keys: Array.isArray(source?.flag_keys)
				? normalizeList(source.flag_keys)
				: [...(fallback?.flag_keys || [])],
			delivery_modes: Array.isArray(source?.delivery_modes)
				? normalizeList(source.delivery_modes)
				: [...(fallback?.delivery_modes || [])],
			tiers: Array.isArray(source?.tiers)
				? normalizeTiers(source.tiers)
				: normalizeTiers(fallback?.tiers || []),
		}]
	}),
)

/**
 * @param {Partial<PricingConfig>} [pricingConfig]
 * @returns {PricingConfig}
 */
const normalizePricingConfig = (pricingConfig = {}) => ({
	service_pricing: normalizeKeyedPricingGroup(pricingConfig?.service_pricing || {}, DEFAULT_SERVICE_PRICING),
	automatic_supplements: normalizeKeyedPricingGroup(pricingConfig?.automatic_supplements || {}, DEFAULT_AUTOMATIC_SUPPLEMENTS),
})

/**
 * @param {PackageInput[]} [packages]
 * @returns {NormalizedPackage[]}
 */
const normalizePackages = (packages = []) => {
	if (!Array.isArray(packages)) return []
	return packages
		.map((pkg) => {
			if (!pkg || typeof pkg !== 'object') return null
			const first = parseNumericValue(pkg.first_size ?? pkg.length)
			const second = parseNumericValue(pkg.second_size ?? pkg.width)
			const third = parseNumericValue(pkg.third_size ?? pkg.height)
			const dimensions = [first, second, third].sort((a, b) => b - a)
			return {
				package_type: String(pkg.package_type || '').trim().toLowerCase(),
				weight_kg: parseNumericValue(pkg.weight),
				quantity: Math.max(1, parseInt(String(pkg.quantity ?? '1'), 10) || 1),
				first_size_cm: first,
				second_size_cm: second,
				third_size_cm: third,
				max_side_cm: dimensions[0] || 0,
				secondary_side_sum_cm: (dimensions[1] || 0) + (dimensions[2] || 0),
				raw: pkg,
			}
		})
		.filter((pkg) => pkg !== null)
}

/**
 * @param {AddressInput} [address]
 * @returns {NormalizedAddress}
 */
const normalizeAddress = (address = {}) => ({
	country: String(address?.country || address?.country_code || 'IT').trim().toUpperCase(),
	province: String(address?.province || '').trim().toUpperCase(),
	city: String(address?.city || '').trim().toLowerCase(),
	address: String(address?.address || '').trim().toLowerCase(),
	additional_information: String(address?.additional_information || '').trim().toLowerCase(),
})

/**
 * @param {number} weightKg
 * @param {PricingTier[]} [tiers]
 * @returns {number}
 */
const findTierPriceCents = (weightKg, tiers = []) => {
	for (const tier of tiers) {
		if (tier?.up_to_kg === null || tier?.up_to_kg === undefined || weightKg <= Number(tier.up_to_kg || 0)) {
			return Math.max(0, Math.round(Number(tier?.price_cents || 0)))
		}
	}
	return 0
}

/**
 * @param {NormalizedAddress} address
 * @param {string[]} [provinceCodes]
 * @returns {boolean}
 */
const matchesProvince = (address, provinceCodes = []) => {
	const province = String(address?.province || '').trim().toUpperCase()
	return province !== '' && normalizeList(provinceCodes, { uppercase: true }).includes(province)
}

/**
 * @param {NormalizedAddress} address
 * @param {ServicePricingRule} rule
 * @returns {boolean}
 */
const matchesMinorIsland = (address, rule) => {
	const countryCodes = normalizeList(rule?.country_codes || [], { uppercase: true })
	if (countryCodes.length && !countryCodes.includes(String(address?.country || '').trim().toUpperCase())) {
		return false
	}

	const haystack = [address?.city, address?.address, address?.additional_information]
		.filter(Boolean)
		.join(' | ')
		.toLowerCase()

	if (!haystack) return false
	return normalizeList(rule?.keyword_list || []).some((keyword) => keyword && haystack.includes(keyword))
}

/**
 * @param {Record<string, unknown>} [pkg]
 * @param {Record<string, unknown>} [serviceData]
 * @param {string[]} [flagKeys]
 * @returns {boolean}
 */
const matchesAnyFlag = (pkg = {}, serviceData = {}, flagKeys = []) => normalizeList(flagKeys).some((flagKey) => {
	if (!flagKey) return false
	return Boolean(pkg?.[flagKey] || serviceData?.[flagKey])
})

/**
 * @param {NormalizedPackage} pkg
 * @param {Record<string, unknown>} serviceData
 * @param {ServicePricingRule} rule
 * @returns {boolean}
 */
const matchesOutOfGauge = (pkg, serviceData, rule) => {
	if (matchesAnyFlag(pkg.raw, serviceData, rule?.flag_keys || [])) return true
	const longestThreshold = Number(rule?.longest_side_threshold_cm || 0)
	const girthThreshold = Number(rule?.girth_threshold_cm || 0)
	return (longestThreshold > 0 && pkg.max_side_cm > longestThreshold)
		|| (girthThreshold > 0 && pkg.secondary_side_sum_cm > girthThreshold)
}

/**
 * @param {NormalizedPackage} pkg
 * @param {Record<string, unknown>} serviceData
 * @param {ServicePricingRule} rule
 * @returns {boolean}
 */
const matchesRodsAndTubes = (pkg, serviceData, rule) => {
	if (matchesAnyFlag(pkg.raw, serviceData, rule?.flag_keys || [])) return true
	const minLongest = Number(rule?.min_longest_side_cm || 0)
	const maxSecondary = Number(rule?.max_secondary_side_cm || 0)
	return pkg.max_side_cm >= minLongest
		&& pkg.secondary_side_sum_cm > 0
		&& pkg.secondary_side_sum_cm <= (maxSecondary * 2)
}

/**
 * @param {string} key
 * @param {ServicePricingRule | undefined} rule
 * @param {number | null | undefined} amountCents
 * @param {boolean} [automatic]
 * @returns {SurchargeItem}
 */
const buildFixedItem = (key, rule, amountCents, automatic = false) => ({
	key,
	label: String(rule?.label || key),
	type: automatic ? 'automatic_supplement' : 'service',
	automatic,
	application: String(rule?.application || ''),
	amount_cents: Math.max(0, Math.round(Number(amountCents || 0))),
	amount: roundCurrency(Math.max(0, Number(amountCents || 0)) / 100),
})

/**
 * Calcola la fee a soglia percentuale (contrassegno/assicurazione).
 * @param {unknown} amount
 * @param {ServicePricingRule} rule
 * @returns {number}
 */
const calculateThresholdFeeCents = (amount, rule) => {
	const normalizedAmount = parseCurrencyAmount(amount)
	if (normalizedAmount <= 0) return 0
	const threshold = Number(rule?.threshold_amount_eur ?? 300)
	const minFee = Math.max(0, Math.round(Number(rule?.min_fee_cents ?? 0)))
	const percentageRate = Number(rule?.percentage_rate ?? 0)
	if (normalizedAmount <= threshold) return minFee
	return Math.round(normalizedAmount * 100 * (percentageRate / 100))
}

/**
 * @typedef {Object} AutomaticSupplementsInput
 * @property {Record<string, ServicePricingRule>} automaticConfig
 * @property {Record<string, unknown>} serviceData
 * @property {NormalizedPackage[]} packages
 * @property {AddressInput} destinationAddress
 * @property {string} deliveryMode
 * @property {boolean} requiresManualQuote
 * @property {AddressInput} [originAddress]
 */

/**
 * Calcola la lista di supplementi automatici attivi per lo shipment corrente.
 * @param {AutomaticSupplementsInput} input
 * @returns {SurchargeItem[]}
 */
const calculateAutomaticSupplementItems = ({
	automaticConfig,
	serviceData,
	packages,
	destinationAddress,
	deliveryMode,
	requiresManualQuote,
}) => {
	/** @type {SurchargeItem[]} */
	const items = []

	const destination = normalizeAddress(destinationAddress)

	if (automaticConfig.calabria_sardegna_sicilia?.enabled && matchesProvince(destination, automaticConfig.calabria_sardegna_sicilia.province_codes)) {
		for (const pkg of packages) {
			const fee = findTierPriceCents(pkg.weight_kg, automaticConfig.calabria_sardegna_sicilia.tiers)
			if (fee > 0) {
				items.push(buildFixedItem('calabria_sardegna_sicilia', automaticConfig.calabria_sardegna_sicilia, fee * pkg.quantity, true))
			}
		}
	}

	if (
		automaticConfig.brt_point_csi?.enabled
		&& deliveryMode === 'pudo'
		&& matchesProvince(destination, automaticConfig.brt_point_csi.province_codes)
	) {
		const maxWeight = Number(automaticConfig.brt_point_csi.max_weight_kg || 0)
		const fee = Math.max(0, Math.round(Number(automaticConfig.brt_point_csi.price_cents || 0)))
		for (const pkg of packages) {
			if (fee > 0 && pkg.weight_kg > 0 && (!maxWeight || pkg.weight_kg <= maxWeight)) {
				items.push(buildFixedItem('brt_point_csi', automaticConfig.brt_point_csi, fee * pkg.quantity, true))
			}
		}
	}

	if (automaticConfig.isole_minori_italia?.enabled && matchesMinorIsland(destination, automaticConfig.isole_minori_italia)) {
		items.push(buildFixedItem('isole_minori_italia', automaticConfig.isole_minori_italia, automaticConfig.isole_minori_italia.price_cents, true))
	}

	if (automaticConfig.isole_minori_europa?.enabled && matchesMinorIsland(destination, automaticConfig.isole_minori_europa)) {
		items.push(buildFixedItem('isole_minori_europa', automaticConfig.isole_minori_europa, automaticConfig.isole_minori_europa.price_cents, true))
	}

	if (automaticConfig.fuori_sagoma?.enabled) {
		for (const pkg of packages) {
			if (!matchesOutOfGauge(pkg, serviceData, automaticConfig.fuori_sagoma)) continue
			const fee = findTierPriceCents(pkg.weight_kg, automaticConfig.fuori_sagoma.tiers)
			if (fee > 0) {
				items.push(buildFixedItem('fuori_sagoma', automaticConfig.fuori_sagoma, fee * pkg.quantity, true))
			}
		}
	}

	if (automaticConfig.lato_superiore_130cm?.enabled) {
		const threshold = Number(automaticConfig.lato_superiore_130cm.threshold_cm || 130)
		const fee = Math.max(0, Math.round(Number(automaticConfig.lato_superiore_130cm.price_cents || 0)))
		for (const pkg of packages) {
			if (fee > 0 && pkg.max_side_cm > threshold) {
				items.push(buildFixedItem('lato_superiore_130cm', automaticConfig.lato_superiore_130cm, fee * pkg.quantity, true))
			}
		}
	}

	if (automaticConfig.aste_tubi?.enabled) {
		const fee = Math.max(0, Math.round(Number(automaticConfig.aste_tubi.price_cents || 0)))
		for (const pkg of packages) {
			if (fee > 0 && matchesRodsAndTubes(pkg, serviceData, automaticConfig.aste_tubi)) {
				items.push(buildFixedItem('aste_tubi', automaticConfig.aste_tubi, fee * pkg.quantity, true))
			}
		}
	}

	if (automaticConfig.eu_manual_extra?.enabled && requiresManualQuote) {
		items.push(buildFixedItem('eu_manual_extra', automaticConfig.eu_manual_extra, automaticConfig.eu_manual_extra.price_cents, true))
	}

	return items.filter((item) => item.amount_cents > 0)
}

// ---------------------------------------------------------------------------
// API PUBBLICA
// ---------------------------------------------------------------------------

/**
 * Calcola il totale dei supplementi (servizi selezionati + supplementi automatici) per una spedizione.
 * @param {CalculateShipmentSurchargeOptions} [options]
 * @returns {SurchargeResult}
 */
export const calculateShipmentServiceSurcharge = ({
	selectedServices = [],
	serviceType = '',
	serviceData = {},
	smsEmailNotification = false,
	pricingConfig = null,
	packages = [],
	originAddress = {},
	destinationAddress = {},
	deliveryMode = '',
	selectedPudo = null,
	requiresManualQuote = false,
} = {}) => {
	const config = normalizePricingConfig(pricingConfig || {})
	const servicePricing = config.service_pricing
	const automaticConfig = config.automatic_supplements
	const normalizedServices = normalizeSelectedServices(
		(Array.isArray(selectedServices) && selectedServices.length) ? selectedServices : serviceType,
	)
	const selected = new Set(normalizedServices)
	const normalizedPackages = normalizePackages(packages)
	const effectiveDeliveryMode = String(
		deliveryMode
		|| serviceData?.delivery_mode
		|| serviceData?.deliveryMode
		|| (selectedPudo ? 'pudo' : 'home'),
	).trim().toLowerCase()
	const effectiveDestination = effectiveDeliveryMode === 'pudo' && selectedPudo
		? selectedPudo
		: destinationAddress
	/** @type {SurchargeItem[]} */
	const items = []

	if (selected.has('senza_etichetta') && servicePricing.senza_etichetta?.enabled) {
		items.push(buildFixedItem('senza_etichetta', servicePricing.senza_etichetta, servicePricing.senza_etichetta.price_cents))
	}

	if (selected.has('sponda_idraulica') && servicePricing.sponda_idraulica?.enabled) {
		items.push(buildFixedItem('sponda_idraulica', servicePricing.sponda_idraulica, servicePricing.sponda_idraulica.price_cents))
	}

	if (selected.has('contrassegno') && servicePricing.contrassegno?.enabled) {
		const amountCents = calculateThresholdFeeCents(getContrassegnoAmount(serviceData), servicePricing.contrassegno)
		if (amountCents > 0) {
			items.push(buildFixedItem('contrassegno', servicePricing.contrassegno, amountCents))
		}
	}

	if (selected.has('assicurazione') && servicePricing.assicurazione?.enabled) {
		const amountCents = calculateThresholdFeeCents(getAssicurazioneAmount(serviceData), servicePricing.assicurazione)
		if (amountCents > 0) {
			items.push(buildFixedItem('assicurazione', servicePricing.assicurazione, amountCents))
		}
	}

	const notificationsEnabled = Boolean(
		smsEmailNotification
		|| getNested(serviceData, ['sms_email_notification', 'smsEmailNotification']),
	)
	if (notificationsEnabled && servicePricing.notifications?.enabled) {
		items.push(buildFixedItem('notifications', servicePricing.notifications, servicePricing.notifications.price_cents))
	}

	items.push(...calculateAutomaticSupplementItems({
		automaticConfig,
		serviceData,
		packages: normalizedPackages,
		destinationAddress: effectiveDestination,
		deliveryMode: effectiveDeliveryMode,
		requiresManualQuote: Boolean(requiresManualQuote),
		originAddress,
	}))

	const total = roundCurrency(items.reduce((sum, item) => sum + item.amount, 0))

	return {
		total,
		total_cents: Math.round(total * 100),
		items,
	}
}
