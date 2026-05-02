/**
 * supplementsStore — supplementi CAP + supplementi automatici + fee operative.
 *
 * (split atomico Pinia 2026-04-26). Comprende:
 *   - state delle 3 categorie (cap-based, automatici, operativi) + snapshot originali
 *   - CRUD dei supplementi CAP base
 *   - helper per aggiornare amount/min-fee/array-field/tier-row di regole keyed
 */
import { defineStore } from 'pinia';
import { adminEuroToCents, ADMIN_DEFAULT_AUTOMATIC_SUPPLEMENTS, ADMIN_DEFAULT_OPERATIONAL_FEES, ADMIN_DEFAULT_SUPPLEMENTS, buildPricingRulesPayload, cloneForSnapshot, normalizeArrayFieldInput, normalizePricingGroup, } from '~/utils/adminPricingHelpers';
type SupplementApplyTo = 'origin' | 'destination' | 'both';
type SupplementRule = {
    id: string;
    prefix: string;
    amount_cents: number;
    apply_to: SupplementApplyTo | string;
    enabled: boolean;
};
type KeyedPricingRule = Record<string, unknown> & {
    price_cents?: number;
    min_fee_cents?: number;
    tiers?: Array<{ up_to_kg: number | null; price_cents: number }>;
};
type PricingRulesGroup = Record<string, KeyedPricingRule>;
type SupplementsPayload = {
    supplements?: SupplementRule[];
    automatic_supplements?: PricingRulesGroup;
    operational_fees?: PricingRulesGroup;
};
const toPricingRulesGroup = (value: unknown): PricingRulesGroup => value as PricingRulesGroup;
export const useAdminSupplementsStore = defineStore('admin-supplements', () => {
    // ---------- STATE ----------
    const supplementRules = ref<SupplementRule[]>(ADMIN_DEFAULT_SUPPLEMENTS[0] ? [{ ...ADMIN_DEFAULT_SUPPLEMENTS[0] }] : []);
    const originalSupplementRules = ref<SupplementRule[]>([]);
    const automaticSupplements = ref<PricingRulesGroup>({});
    const originalAutomaticSupplements = ref<PricingRulesGroup>({});
    const operationalFees = ref<PricingRulesGroup>({});
    const originalOperationalFees = ref<PricingRulesGroup>({});
    // ---------- SUPPLEMENTI CAP: CRUD ----------
    const addSupplement = () => {
        supplementRules.value.push({
            id: `supplement-${Date.now()}`,
            prefix: '',
            amount_cents: 0,
            apply_to: 'both',
            enabled: true,
        });
    };
    const removeSupplement = (idx: number) => {
        supplementRules.value.splice(idx, 1);
    };
    const supplementAmountToEuro = (rule: Partial<SupplementRule>) => {
        const cents = Number(rule?.amount_cents || 0);
        return (cents / 100).toFixed(2).replace('.', ',');
    };
    const updateSupplementAmountFromEuro = (rule: SupplementRule, rawValue: unknown) => {
        const cleaned = String(rawValue || '').replace(/[€\s]/g, '').replace(',', '.');
        const value = Number.parseFloat(cleaned);
        if (!Number.isFinite(value) || value < 0) {
            rule.amount_cents = 0;
            return;
        }
        rule.amount_cents = Math.round(value * 100);
    };
    // ---------- KEYED RULES: helpers (servizi/automatici/operative) ----------
    const keyedRuleAmountToEuro = (rule: KeyedPricingRule) => (Number(rule?.price_cents || 0) / 100).toFixed(2).replace('.', ',');
    const updateKeyedRuleAmountFromEuro = (rule: KeyedPricingRule, rawValue: unknown) => {
        const cents = adminEuroToCents(rawValue);
        rule.price_cents = Math.max(0, cents ?? 0);
    };
    const keyedRuleMinFeeToEuro = (rule: KeyedPricingRule) => (Number(rule?.min_fee_cents || 0) / 100).toFixed(2).replace('.', ',');
    const updateKeyedRuleMinFeeFromEuro = (rule: KeyedPricingRule, rawValue: unknown) => {
        const cents = adminEuroToCents(rawValue);
        rule.min_fee_cents = Math.max(0, cents ?? 0);
    };
    const updateArrayField = (rule: KeyedPricingRule, field: string, rawValue: unknown, { uppercase = false }: { uppercase?: boolean } = {}) => {
        rule[field] = normalizeArrayFieldInput(rawValue, { uppercase });
    };
    const addTierRow = (rule: KeyedPricingRule) => {
        const last = Array.isArray(rule.tiers) && rule.tiers.length ? rule.tiers[rule.tiers.length - 1] : null;
        rule.tiers = Array.isArray(rule.tiers) ? rule.tiers : [];
        rule.tiers.push({
            up_to_kg: last?.up_to_kg != null ? Number(last.up_to_kg) + 5 : null,
            price_cents: Number(last?.price_cents || 0),
        });
    };
    const removeTierRow = (rule: KeyedPricingRule, idx: number, onError?: (message: string) => void) => {
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
        automaticSupplements.value = toPricingRulesGroup(normalizePricingGroup({}, ADMIN_DEFAULT_AUTOMATIC_SUPPLEMENTS));
        operationalFees.value = toPricingRulesGroup(normalizePricingGroup({}, ADMIN_DEFAULT_OPERATIONAL_FEES));
        originalAutomaticSupplements.value = cloneForSnapshot(automaticSupplements.value);
        originalOperationalFees.value = cloneForSnapshot(operationalFees.value);
    };
    const hydrateFromApi = (data: SupplementsPayload) => {
        const supplementsFromApi = Array.isArray(data.supplements)
            ? (data.supplements)
            : ADMIN_DEFAULT_SUPPLEMENTS;
        supplementRules.value = supplementsFromApi.map((rule, idx) => ({
            ...rule,
            id: rule.id || `supplement-${idx + 1}`,
        }));
        originalSupplementRules.value = cloneForSnapshot(supplementRules.value);
        automaticSupplements.value = toPricingRulesGroup(normalizePricingGroup((data.automatic_supplements) || {}, ADMIN_DEFAULT_AUTOMATIC_SUPPLEMENTS));
        operationalFees.value = toPricingRulesGroup(normalizePricingGroup((data.operational_fees) || {}, ADMIN_DEFAULT_OPERATIONAL_FEES));
        originalAutomaticSupplements.value = cloneForSnapshot(automaticSupplements.value);
        originalOperationalFees.value = cloneForSnapshot(operationalFees.value);
    };
    const persistApiResponse = (data: SupplementsPayload, fallbackPayload: SupplementsPayload) => {
        originalSupplementRules.value = cloneForSnapshot((data.supplements) || (fallbackPayload.supplements) || supplementRules.value);
        automaticSupplements.value = toPricingRulesGroup(normalizePricingGroup((data.automatic_supplements) || (fallbackPayload.automatic_supplements) || {}, ADMIN_DEFAULT_AUTOMATIC_SUPPLEMENTS));
        operationalFees.value = toPricingRulesGroup(normalizePricingGroup((data.operational_fees) || (fallbackPayload.operational_fees) || {}, ADMIN_DEFAULT_OPERATIONAL_FEES));
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
