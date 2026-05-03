/**
 * useAdminPricing — pannello admin prezzi (consolidato 2026-04-20).
 *
 * Composable unico che raccoglie State + Form + Import + List.
 * Usato da pages/account/amministrazione/prezzi.vue.
 *
 * Sezioni interne:
 *   1. Normalizzatori (list/tiers/group/europe) + buildPricingRulesPayload
 *   2. Form section: editing inline, CRUD fasce/supplementi, conversioni cents<->euro,
 *      ladder helpers, servizi/Europa helpers
 *   3. Import section: fetch/save price-bands + promo-settings, seed, payload builders
 *   4. List section: view state, filtri Europa/servizi, computed entries
 *   5. Facade: compone le sezioni e restituisce API pubblica unificata (backward compatible)
 *
 * Le costanti DEFAULT_* sono in `~/utils/priceBandsConstants`.
 */

import {
	DEFAULT_AUTOMATIC_SUPPLEMENTS,
	DEFAULT_EUROPE_PRICING,
	DEFAULT_EXTRA_RULES,
	DEFAULT_OPERATIONAL_FEES,
	DEFAULT_SERVICE_PRICING,
} from '~/utils/priceBandsConstants';
import {
	normalizeEuropePricingForAdmin,
	normalizePricingGroupForAdmin,
} from '~/utils/adminPricingHelpers';
import type { EuropePricing, ExtraRules, PriceBand, PricingRuleGroup, SupplementRule } from '~/types/pricing';

import { createFormSection } from '~/composables/useAdminPricingForm';
import { createImportSection } from '~/composables/useAdminPricingImport';
import { createListSection } from '~/composables/useAdminPricingList';

export const useAdminPricing = () => {
	const { actionMessage, showSuccess, showError } = useAdmin();

	const weightBands = ref<PriceBand[]>([]);
	const volumeBands = ref<PriceBand[]>([]);
	const bandsFromDb = ref(false);
	const originalWeightBands = ref<PriceBand[]>([]);
	const originalVolumeBands = ref<PriceBand[]>([]);
	const extraRules = ref<ExtraRules>({ ...DEFAULT_EXTRA_RULES } as ExtraRules);
	const supplementRules = ref<SupplementRule[]>([
		{ id: 'supplement-1', prefix: '90', amount_cents: 250, apply_to: 'both', enabled: true },
	]);
	const originalExtraRules = ref<ExtraRules | null>(null);
	const originalSupplementRules = ref<SupplementRule[]>([]);
	const pricingVersion = ref<string | number | null>(null);
	const europePricing = ref<EuropePricing>(normalizeEuropePricingForAdmin(DEFAULT_EUROPE_PRICING));
	const originalEuropePricing = ref<EuropePricing | null>(null);
	const servicePricing = ref<PricingRuleGroup>(normalizePricingGroupForAdmin({}, DEFAULT_SERVICE_PRICING));
	const automaticSupplements = ref<PricingRuleGroup>(normalizePricingGroupForAdmin({}, DEFAULT_AUTOMATIC_SUPPLEMENTS));
	const operationalFees = ref<PricingRuleGroup>(normalizePricingGroupForAdmin({}, DEFAULT_OPERATIONAL_FEES));
	const originalServicePricing = ref<PricingRuleGroup>({});
	const originalAutomaticSupplements = ref<PricingRuleGroup>({});
	const originalOperationalFees = ref<PricingRuleGroup>({});

	const form = createFormSection({
		weightBands,
		volumeBands,
		extraRules,
		supplementRules,
		showError,
	});

	const importC = createImportSection({
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
		normalizeLadderForPayload: form.normalizeLadderForPayload,
		showSuccess,
		showError,
	});

	const list = createListSection({
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
		buildPricingPayload: importC.buildPricingPayload,
		centsToEuro: form.centsToEuro,
		calculateBandPriceCentsLocal: form.calculateBandPriceCentsLocal,
	});

	return {
		isLoading: importC.isLoading,
		saving: importC.saving,
		seeding: importC.seeding,
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
		adminView: list.adminView,
		compactEuropeView: list.compactEuropeView,
		europeSearch: list.europeSearch,
		europeStatusFilter: list.europeStatusFilter,
		europeBandFilter: list.europeBandFilter,
		europeSort: list.europeSort,
		serviceSearch: list.serviceSearch,
		serviceFilter: list.serviceFilter,
		promoLoading: importC.promoLoading,
		promoSaving: importC.promoSaving,
		promoImageUploading: importC.promoImageUploading,
		promo: importC.promo,
		editingCell: form.editingCell,
		editValue: form.editValue,
		actionMessage,
		hasChanges: list.hasChanges,
		servicePricingEntries: list.servicePricingEntries,
		automaticSupplementEntries: list.automaticSupplementEntries,
		operationalFeeEntries: list.operationalFeeEntries,
		filteredServiceEntries: list.filteredServiceEntries,
		europeBandFilters: list.europeBandFilters,
		filteredEuropeBands: list.filteredEuropeBands,
		extraRuleExamples: list.extraRuleExamples,
		pricingPreviewCases: list.pricingPreviewCases,
		centsToEuro: form.centsToEuro,
		euroToCents: form.euroToCents,
		effectivePrice: form.effectivePrice,
		discountInfo: form.discountInfo,
		formatApplicationLabel: form.formatApplicationLabel,
		incrementCentsToEuro: form.incrementCentsToEuro,
		updateLadderIncrementFromEuro: form.updateLadderIncrementFromEuro,
		startEdit: form.startEdit,
		confirmEdit: form.confirmEdit,
		cancelEdit: form.cancelEdit,
		toggleShowDiscount: form.toggleShowDiscount,
		addBand: form.addBand,
		removeBand: form.removeBand,
		moveBand: form.moveBand,
		addSupplement: form.addSupplement,
		removeSupplement: form.removeSupplement,
		supplementAmountToEuro: form.supplementAmountToEuro,
		updateSupplementAmountFromEuro: form.updateSupplementAmountFromEuro,
		addLadderRow: form.addLadderRow,
		removeLadderRow: form.removeLadderRow,
		ensureLadderContinuity: form.ensureLadderContinuity,
		ladderRowsFor: form.ladderRowsFor,
		keyedRuleAmountToEuro: form.keyedRuleAmountToEuro,
		updateKeyedRuleAmountFromEuro: form.updateKeyedRuleAmountFromEuro,
		keyedRuleMinFeeToEuro: form.keyedRuleMinFeeToEuro,
		updateKeyedRuleMinFeeFromEuro: form.updateKeyedRuleMinFeeFromEuro,
		updateArrayField: form.updateArrayField,
		addTierRow: form.addTierRow,
		removeTierRow: form.removeTierRow,
		updateEuropeRateAmountFromEuro: form.updateEuropeRateAmountFromEuro,
		toggleEuropeRateQuote: form.toggleEuropeRateQuote,
		fetchPriceBands: importC.fetchPriceBands,
		fetchPromoSettings: importC.fetchPromoSettings,
		seedBands: importC.seedBands,
		savePriceBands: importC.savePriceBands,
		savePromo: importC.savePromo,
		uploadPromoImage: importC.uploadPromoImage,
	};
};
