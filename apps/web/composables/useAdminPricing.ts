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
 * Le costanti DEFAULT_* sono in `~/utils/adminPricingDefaults.js`.
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
	normalizeStringListForAdmin,
	normalizeTiersForAdmin,
} from '~/utils/adminPricingNormalize';

// Normalizzatori puri estratti in `~/utils/adminPricingNormalize.js`.


// Sezioni Form/Import/List estratte in file dedicati.
import { createFormSection } from '~/composables/useAdminPricingForm';
import { createImportSection } from '~/composables/useAdminPricingImport';
import { createListSection } from '~/composables/useAdminPricingList';

// ────────────────────────────────────────────────────────────
// 6. Facade
// API pubblica preservata: invariata rispetto alla versione pre-consolidamento.
// ────────────────────────────────────────────────────────────

export const useAdminPricing = () => {
	const { actionMessage, showSuccess, showError } = useAdmin();

	// ── Shared reactive state (owned here, passed to sections) ──
	const weightBands = ref([]);
	const volumeBands = ref([]);
	const bandsFromDb = ref(false);
	const originalWeightBands = ref([]);
	const originalVolumeBands = ref([]);
	const extraRules = ref({});
	const supplementRules = ref([
		{ id: 'supplement-1', prefix: '90', amount_cents: 250, apply_to: 'both', enabled: true },
	]);
	const originalExtraRules = ref(null);
	const originalSupplementRules = ref([]);
	const pricingVersion = ref(null);
	const europePricing = ref({});
	const originalEuropePricing = ref(null);
	const servicePricing = ref({});
	const automaticSupplements = ref({});
	const operationalFees = ref({});
	const originalServicePricing = ref({});
	const originalAutomaticSupplements = ref({});
	const originalOperationalFees = ref({});

	// ── Form section (editing, CRUD, utility) ────────
	const form = createFormSection({
		weightBands,
		volumeBands,
		extraRules,
		supplementRules,
		showError,
	});

	// ── Import section (fetch, save, seed, promo, payloads) ──
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

	// ── List section (view state, filters, computed) ──
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

	// ── Return unified interface (backward compatible) ──
	return {
		// State
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
		// Computed
		hasChanges: list.hasChanges,
		servicePricingEntries: list.servicePricingEntries,
		automaticSupplementEntries: list.automaticSupplementEntries,
		operationalFeeEntries: list.operationalFeeEntries,
		filteredServiceEntries: list.filteredServiceEntries,
		europeBandFilters: list.europeBandFilters,
		filteredEuropeBands: list.filteredEuropeBands,
		extraRuleExamples: list.extraRuleExamples,
		pricingPreviewCases: list.pricingPreviewCases,
		// Utility
		centsToEuro: form.centsToEuro,
		euroToCents: form.euroToCents,
		effectivePrice: form.effectivePrice,
		discountInfo: form.discountInfo,
		formatApplicationLabel: form.formatApplicationLabel,
		incrementCentsToEuro: form.incrementCentsToEuro,
		updateLadderIncrementFromEuro: form.updateLadderIncrementFromEuro,
		// Band actions
		startEdit: form.startEdit,
		confirmEdit: form.confirmEdit,
		cancelEdit: form.cancelEdit,
		toggleShowDiscount: form.toggleShowDiscount,
		addBand: form.addBand,
		removeBand: form.removeBand,
		moveBand: form.moveBand,
		// Supplement actions
		addSupplement: form.addSupplement,
		removeSupplement: form.removeSupplement,
		supplementAmountToEuro: form.supplementAmountToEuro,
		updateSupplementAmountFromEuro: form.updateSupplementAmountFromEuro,
		// Ladder actions
		addLadderRow: form.addLadderRow,
		removeLadderRow: form.removeLadderRow,
		ensureLadderContinuity: form.ensureLadderContinuity,
		ladderRowsFor: form.ladderRowsFor,
		// Service/keyed rule helpers
		keyedRuleAmountToEuro: form.keyedRuleAmountToEuro,
		updateKeyedRuleAmountFromEuro: form.updateKeyedRuleAmountFromEuro,
		keyedRuleMinFeeToEuro: form.keyedRuleMinFeeToEuro,
		updateKeyedRuleMinFeeFromEuro: form.updateKeyedRuleMinFeeFromEuro,
		updateArrayField: form.updateArrayField,
		addTierRow: form.addTierRow,
		removeTierRow: form.removeTierRow,
		// Europe helpers
		updateEuropeRateAmountFromEuro: form.updateEuropeRateAmountFromEuro,
		toggleEuropeRateQuote: form.toggleEuropeRateQuote,
		// Fetch/save
		fetchPriceBands: importC.fetchPriceBands,
		fetchPromoSettings: importC.fetchPromoSettings,
		seedBands: importC.seedBands,
		savePriceBands: importC.savePriceBands,
		savePromo: importC.savePromo,
		uploadPromoImage: importC.uploadPromoImage,
	};
};
