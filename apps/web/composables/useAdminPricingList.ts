/**
 * @file useAdminPricingList — sezione list/preview del pannello admin pricing.
 *
 * Estratto da useAdminPricing.js. View state, filtri Europa/servizi, computed entries.
 */

// ────────────────────────────────────────────────────────────
// 5. List section
// (Merged from useAdminPricingList.js — 2026-04-20)
// Stato di listing, filtri, ordinamento e computed entries.
// ────────────────────────────────────────────────────────────

export const createListSection = ({
	servicePricing,
	automaticSupplements,
	operationalFees,
	europePricing,
	weightBands,
	volumeBands,
	extraRules,
	bandsFromDb,
	originalWeightBands,
	originalVolumeBands,
	originalExtraRules,
	originalSupplementRules,
	supplementRules,
	originalEuropePricing,
	originalServicePricing,
	originalAutomaticSupplements,
	originalOperationalFees,
	buildPricingPayload,
	centsToEuro,
	calculateBandPriceCentsLocal,
}) => {
	// ── View & filter state ─────────────────────────────
	const adminView = ref('nazionale');
	const compactEuropeView = ref(false);
	const europeSearch = ref('');
	const europeStatusFilter = ref('all');
	const europeBandFilter = ref('all');
	const europeSort = ref('country_asc');
	const serviceSearch = ref('');
	const serviceFilter = ref('all');

	// ── Computed ─────────────────────────────────────────
	const toComparable = (obj) => JSON.stringify(obj);

	const hasChanges = computed(() => {
		const current = toComparable(buildPricingPayload());
		const original = toComparable({
			weight: originalWeightBands.value,
			volume: originalVolumeBands.value,
			extra_rules: originalExtraRules.value || extraRules.value,
			supplements: originalSupplementRules.value || supplementRules.value,
			europe: originalEuropePricing.value || europePricing.value,
			service_pricing: originalServicePricing.value || servicePricing.value,
			automatic_supplements: originalAutomaticSupplements.value || automaticSupplements.value,
			operational_fees: originalOperationalFees.value || operationalFees.value,
		});
		return !bandsFromDb.value || current !== original;
	});

	const servicePricingEntries = computed(() =>
		Object.entries(servicePricing.value || {}).map(([key, rule]) => ({ key, rule, section: 'service_pricing' })),
	);

	const automaticSupplementEntries = computed(() =>
		Object.entries(automaticSupplements.value || {}).map(([key, rule]) => ({ key, rule, section: 'automatic_supplements' })),
	);

	const operationalFeeEntries = computed(() =>
		Object.entries(operationalFees.value || {}).map(([key, rule]) => ({ key, rule, section: 'operational_fees' })),
	);

	const filteredServiceEntries = computed(() => {
		const search = serviceSearch.value.trim().toLowerCase();
		const activeFilter = serviceFilter.value;
		return [
			...(activeFilter === 'all' || activeFilter === 'service_pricing' ? servicePricingEntries.value : []),
			...(activeFilter === 'all' || activeFilter === 'automatic_supplements' ? automaticSupplementEntries.value : []),
			...(activeFilter === 'all' || activeFilter === 'operational_fees' ? operationalFeeEntries.value : []),
		].filter(({ rule }) => {
			if (!search) return true;
			return `${rule.label} ${rule.description} ${rule.note || ''}`.toLowerCase().includes(search);
		});
	});

	const europeBandFilters = computed(() => [
		{ value: 'all', label: 'Tutte le fasce' },
		...(europePricing.value?.bands || []).map((band) => ({ value: band.id, label: band.label })),
	]);

	const filteredEuropeBands = computed(() => {
		const search = europeSearch.value.trim().toLowerCase();
		const status = europeStatusFilter.value;
		const sortMode = europeSort.value;
		const selectedBand = europeBandFilter.value;

		const sortRates = (rates) => [...rates].sort((left, right) => {
			if (sortMode === 'price_asc') {
				return (left.price_cents ?? Number.POSITIVE_INFINITY) - (right.price_cents ?? Number.POSITIVE_INFINITY);
			}
			if (sortMode === 'price_desc') {
				return (right.price_cents ?? -1) - (left.price_cents ?? -1);
			}
			if (sortMode === 'status') {
				return Number(left.quote_required) - Number(right.quote_required);
			}
			return String(left.country_name || left.country_code).localeCompare(String(right.country_name || right.country_code), 'it');
		});

		return (europePricing.value?.bands || [])
			.filter((band) => selectedBand === 'all' || band.id === selectedBand)
			.map((band) => {
				const rates = sortRates((band.rates || []).filter((rate) => {
					const matchesSearch = !search || `${rate.country_name} ${rate.country_code}`.toLowerCase().includes(search);
					const matchesStatus = status === 'all'
						|| (status === 'quote_required' && rate.quote_required)
						|| (status === 'active' && !rate.quote_required);
					return matchesSearch && matchesStatus;
				}));
				return {
					...band,
					rates,
					activeCount: rates.filter((rate) => !rate.quote_required).length,
					quoteCount: rates.filter((rate) => rate.quote_required).length,
				};
			})
			.filter((band) => band.rates.length > 0);
	});

	const extraRuleExamples = computed(() => {
		const firstWeightFrom = Number(extraRules.value.weight_start || 101);
		const firstWeightTo = Number((firstWeightFrom + Number(extraRules.value.weight_step || 50) - Number(extraRules.value.weight_resolution || 1)).toFixed(4));
		const secondWeightFrom = Number((firstWeightFrom + Number(extraRules.value.weight_step || 50)).toFixed(4));
		const secondWeightTo = Number((secondWeightFrom + Number(extraRules.value.weight_step || 50) - Number(extraRules.value.weight_resolution || 1)).toFixed(4));
		const firstVolumeFrom = Number(extraRules.value.volume_start || 0.401);
		const firstVolumeTo = Number((firstVolumeFrom + Number(extraRules.value.volume_step || 0.2) - Number(extraRules.value.volume_resolution || 0.001)).toFixed(4));
		const secondVolumeFrom = Number((firstVolumeFrom + Number(extraRules.value.volume_step || 0.2)).toFixed(4));
		const secondVolumeTo = Number((secondVolumeFrom + Number(extraRules.value.volume_step || 0.2) - Number(extraRules.value.volume_resolution || 0.001)).toFixed(4));
		return { firstWeightFrom, firstWeightTo, secondWeightFrom, secondWeightTo, firstVolumeFrom, firstVolumeTo, secondVolumeFrom, secondVolumeTo };
	});

	const pricingPreviewCases = computed(() => {
		const weightStart = Number(extraRules.value.weight_start || 101);
		const weightStep = Number(extraRules.value.weight_step || 50);
		const weightResolution = Number(extraRules.value.weight_resolution || 1);
		const volumeStart = Number(extraRules.value.volume_start || 0.401);
		const volumeStep = Number(extraRules.value.volume_step || 0.2);
		const volumeResolution = Number(extraRules.value.volume_resolution || 0.001);

		const standardWeight = Number((weightStart - weightResolution).toFixed(4));
		const standardVolume = Number((volumeStart - volumeResolution).toFixed(4));
		const firstExtraWeightMax = Number((weightStart + weightStep - weightResolution).toFixed(4));
		const firstExtraVolumeMax = Number((volumeStart + volumeStep - volumeResolution).toFixed(4));
		const secondExtraWeightStart = Number((weightStart + weightStep).toFixed(4));
		const secondExtraVolumeStart = Number((volumeStart + volumeStep).toFixed(4));

		const rows = [
			{ id: 'standard', label: 'Ultima fascia standard', weight: standardWeight, volume: standardVolume },
			{ id: 'extra1w', label: 'Primo extra (inizio)', weight: weightStart, volume: volumeStart },
			{ id: 'extra1max', label: 'Primo extra (limite)', weight: firstExtraWeightMax, volume: firstExtraVolumeMax },
			{ id: 'extra2', label: 'Secondo extra', weight: secondExtraWeightStart, volume: secondExtraVolumeStart },
		];

		return rows.map((row) => {
			const weightPriceCents = calculateBandPriceCentsLocal('weight', row.weight);
			const volumePriceCents = calculateBandPriceCentsLocal('volume', row.volume);
			const totalCents = Math.max(weightPriceCents, volumePriceCents);
			return { ...row, weightPriceLabel: centsToEuro(weightPriceCents), volumePriceLabel: centsToEuro(volumePriceCents), totalLabel: centsToEuro(totalCents) };
		});
	});

	return {
		adminView,
		compactEuropeView,
		europeSearch,
		europeStatusFilter,
		europeBandFilter,
		europeSort,
		serviceSearch,
		serviceFilter,
		hasChanges,
		servicePricingEntries,
		automaticSupplementEntries,
		operationalFeeEntries,
		filteredServiceEntries,
		europeBandFilters,
		filteredEuropeBands,
		extraRuleExamples,
		pricingPreviewCases,
	};
};
