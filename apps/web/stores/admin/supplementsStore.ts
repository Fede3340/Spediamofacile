/**
 * supplementsStore — supplementi CAP + supplementi automatici + fee operative.
 *
 * Estratto dalla sezione "supplementi" di composables/useAdminPricing.js
 * (split atomico Pinia 2026-04-26). Comprende:
 *   - state delle 3 categorie (cap-based, automatici, operativi) + snapshot originali
 *   - CRUD dei supplementi CAP base
 *   - helper per aggiornare amount/min-fee/array-field/tier-row di regole keyed
 */
import { defineStore } from 'pinia';
import { adminEuroToCents, ADMIN_DEFAULT_AUTOMATIC_SUPPLEMENTS, ADMIN_DEFAULT_OPERATIONAL_FEES, ADMIN_DEFAULT_SUPPLEMENTS, buildPricingRulesPayload, cloneForSnapshot, normalizeArrayFieldInput, normalizePricingGroup, } from '~/utils/adminPrezziHelpers';
export const useAdminSupplementsStore = defineStore('admin-supplements', () => {
    // ---------- STATE ----------
    const supplementRules = ref(ADMIN_DEFAULT_SUPPLEMENTS[0] ? [{ ...ADMIN_DEFAULT_SUPPLEMENTS[0] }] : []);
    const originalSupplementRules = ref([]);
    const automaticSupplements = ref({});
    const originalAutomaticSupplements = ref({});
    const operationalFees = ref({});
    const originalOperationalFees = ref({});
    // ---------- SUPPLEMENTI CAP: CRUD ----------
    const addSupplement = () => {
        supplementRules.value.push({
            id: `supplement-${Date.now()}`,
            prefix: '',
            amount_cents: 0,
            apply_to: 'both',
            enabled,
        });
    };
    const removeSupplement = (idx) => {
        supplementRules.value.splice(idx, 1);
    };
    const supplementAmountToEuro = (rule) => {
        const cents = Number(rule?.amount_cents || 0);
        return (cents / 100).toFixed(2).replace('.', ',');
    };
    const updateSupplementAmountFromEuro = (rule, rawValue) => {
        const cleaned = String(rawValue || '').replace(/[€\s]/g, '').replace(',', '.');
        const value = Number.parseFloat(cleaned);
        if (!Number.isFinite(value) || value < 0) {
            rule.amount_cents = 0;
            return;
        }
        rule.amount_cents = Math.round(value * 100);
    };
    // ---------- KEYED RULES: helpers (servizi/automatici/operative) ----------
    const keyedRuleAmountToEuro = (rule) => (Number(rule?.price_cents || 0) / 100).toFixed(2).replace('.', ',');
    const updateKeyedRuleAmountFromEuro = (rule, rawValue) => {
        const cents = adminEuroToCents(rawValue);
        rule.price_cents = Math.max(0, cents ?? 0);
    };
    const keyedRuleMinFeeToEuro = (rule) => (Number(rule?.min_fee_cents || 0) / 100).toFixed(2).replace('.', ',');
    const updateKeyedRuleMinFeeFromEuro = (rule, rawValue) => {
        const cents = adminEuroToCents(rawValue);
        rule.min_fee_cents = Math.max(0, cents ?? 0);
    };
    const updateArrayField = (rule, field, rawValue, { uppercase = false } = {}) => {
        rule[field] = normalizeArrayFieldInput(rawValue, { uppercase });
    };
    const addTierRow = (rule) => {
        const last = Array.isArray(rule.tiers) && rule.tiers.length ? rule.tiers[rule.tiers.length - 1] : null;
        rule.tiers = Array.isArray(rule.tiers) ? rule.tiers : [];
        rule.tiers.push({
            up_to_kg: last?.up_to_kg != null ? Number(last.up_to_kg) + 5 : null,
            price_cents: Number(last?.price_cents || 0),
        });
    };
    const removeTierRow = (rule, idx, onError) => {
        if (!Array.isArray(rule.tiers) || rule.tiers.length <= 1) {
            onError?.('Serve almeno uno scaglione per la regola selezionata.');
            return;
        }
        rule.tiers.splice(idx, 1);
    };
    // ---------- COMPUTED ENTRIES ----------
    const automaticSupplementEntries = computed(() => Object.entries(automaticSupplements.value || {}).map(([key, rule]) => ({
        key,
        rule,
        section: 'automatic_supplements',
    })));
    const operationalFeeEntries = computed(() => Object.entries(operationalFees.value || {}).map(([key, rule]) => ({
        key,
        rule,
        section: 'operational_fees',
    })));
    // ---------- HYDRATION ----------
    const applyDefaults = () => {
        supplementRules.value = ADMIN_DEFAULT_SUPPLEMENTS.map((rule) => ({ ...rule }));
        originalSupplementRules.value = cloneForSnapshot(supplementRules.value);
        automaticSupplements.value = normalizePricingGroup({}, ADMIN_DEFAULT_AUTOMATIC_SUPPLEMENTS);
        operationalFees.value = normalizePricingGroup({}, ADMIN_DEFAULT_OPERATIONAL_FEES);
        originalAutomaticSupplements.value = cloneForSnapshot(automaticSupplements.value);
        originalOperationalFees.value = cloneForSnapshot(operationalFees.value);
    };
    const hydrateFromApi = (data) => {
        const supplementsFromApi = Array.isArray(data.supplements)
            ? (data.supplements)
            : ADMIN_DEFAULT_SUPPLEMENTS;
        supplementRules.value = supplementsFromApi.map((rule, idx) => ({
            ...rule,
            id: rule.id || `supplement-${idx + 1}`,
        }));
        originalSupplementRules.value = cloneForSnapshot(supplementRules.value);
        automaticSupplements.value = normalizePricingGroup((data.automatic_supplements) || {}, ADMIN_DEFAULT_AUTOMATIC_SUPPLEMENTS);
        operationalFees.value = normalizePricingGroup((data.operational_fees) || {}, ADMIN_DEFAULT_OPERATIONAL_FEES);
        originalAutomaticSupplements.value = cloneForSnapshot(automaticSupplements.value);
        originalOperationalFees.value = cloneForSnapshot(operationalFees.value);
    };
    const persistApiResponse = (data, fallbackPayload) => {
        originalSupplementRules.value = cloneForSnapshot((data.supplements) || (fallbackPayload.supplements));
        automaticSupplements.value = normalizePricingGroup((data.automatic_supplements) || (fallbackPayload.automatic_supplements) || {}, ADMIN_DEFAULT_AUTOMATIC_SUPPLEMENTS);
        operationalFees.value = normalizePricingGroup((data.operational_fees) || (fallbackPayload.operational_fees) || {}, ADMIN_DEFAULT_OPERATIONAL_FEES);
        originalAutomaticSupplements.value = cloneForSnapshot(automaticSupplements.value);
        originalOperationalFees.value = cloneForSnapshot(operationalFees.value);
    };
    // ---------- PAYLOAD ----------
    const buildSupplementsPayload = () => ({
        supplements: supplementRules.value
            .map((rule, idx) => ({
            id: rule.id || `supplement-${idx + 1}`,
            prefix: String(rule.prefix || '').replace(/\D+/g, ''),
            amount_cents: Number(rule.amount_cents || 0),
            apply_to: ['origin', 'destination', 'both'].includes(rule.apply_to) ? rule.apply_to : 'both',
            enabled: rule.enabled !== false,
        }))
            .filter((rule) => rule.prefix.length > 0),
        automatic_supplements: buildPricingRulesPayload(automaticSupplements.value),
        operational_fees: buildPricingRulesPayload(operationalFees.value),
    });
    return {
        // state
        supplementRules,
        originalSupplementRules,
        automaticSupplements,
        originalAutomaticSupplements,
        operationalFees,
        originalOperationalFees,
        // computed
        automaticSupplementEntries,
        operationalFeeEntries,
        // CRUD CAP
        addSupplement,
        removeSupplement,
        supplementAmountToEuro,
        updateSupplementAmountFromEuro,
        // keyed-rule helpers
        keyedRuleAmountToEuro,
        updateKeyedRuleAmountFromEuro,
        keyedRuleMinFeeToEuro,
        updateKeyedRuleMinFeeFromEuro,
        updateArrayField,
        addTierRow,
        removeTierRow,
        // hydration / payload
        applyDefaults,
        hydrateFromApi,
        persistApiResponse,
        buildSupplementsPayload,
    };
});
