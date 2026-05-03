/**
 * @file useAdminPricingForm — sezione form del pannello admin pricing.
 *
 * Riceve come deps gli ref reattivi dello store admin. Esporta `createFormSection`
 * usato dall'orchestratore `useAdminPricing`.
 */
import type { Ref } from 'vue';
import type { BandType, EuropeRate, ExtraRules, IncrementLadderRow, PriceBand } from '~/types/pricing';

type EditablePriceField = 'base_price' | 'discount_price';
type SupplementRule = { id: string; prefix: string; amount_cents: number; apply_to: string; enabled: boolean };
type KeyedRule = Record<string, unknown> & {
	price_cents?: number;
	min_fee_cents?: number;
	tiers?: Array<{ up_to_kg: number | null; price_cents: number }>;
};
type CreateFormSectionDeps = {
	weightBands: Ref<PriceBand[]>;
	volumeBands: Ref<PriceBand[]>;
	extraRules: Ref<ExtraRules>;
	supplementRules: Ref<SupplementRule[]>;
	showError: (error: unknown, fallback: string) => void;
};

const PREVIEW_EPSILON = 0.0000001;
const APPLICATION_LABELS: Record<string, string> = {
	per_spedizione: 'Per spedizione',
	automatic_destination_per_package: 'Automatico su destinazione / collo',
	automatic_destination: 'Automatico su destinazione',
	automatic_package_shape: 'Automatico per forma collo',
	automatic_per_package: 'Automatico per collo',
	manual_quote_only: 'Solo preventivo manuale',
	manual_admin: 'Fee operativa admin',
};

const euroToCentsImpl = (euro: unknown): number | null => {
	if (euro == null || euro === '') return null;
	const num = Number.parseFloat(String(euro).replace(/[€\s]/g, '').replace(',', '.'));
	return Number.isNaN(num) ? null : Math.round(num * 100);
};

const formatCentsLabel = (cents: unknown): string => (Number(cents || 0) / 100).toFixed(2).replace('.', ',');

const effectivePriceCentsLocal = (band?: Partial<PriceBand> | null): number => {
	if (!band) return 0;
	if (band.discount_price != null && Number(band.discount_price) >= 0) return Number(band.discount_price);
	return Number(band.base_price || 0);
};

export const createFormSection = ({ weightBands, volumeBands, extraRules, supplementRules, showError }: CreateFormSectionDeps) => {
	const editingCell = ref<string | null>(null);
	const editValue = ref('');
	const bandsOf = (type: BandType) => type === 'weight' ? weightBands.value : volumeBands.value;

	const centsToEuro = (cents: unknown): string => {
		if (cents == null || cents === '') return '-';
		return formatCentsLabel(cents) + '€';
	};
	const euroToCents = euroToCentsImpl;
	const effectivePrice = (band: Partial<PriceBand>) => band.discount_price != null ? band.discount_price : band.base_price;
	const discountInfo = (band: Partial<PriceBand>): number | null => {
		const basePrice = Number(band.base_price || 0);
		if (band.discount_price == null || basePrice <= 0) return null;
		return Math.round((1 - band.discount_price / basePrice) * 100);
	};
	const formatApplicationLabel = (value: unknown) => APPLICATION_LABELS[String(value || '')] || String(value || '—');
	const incrementCentsToEuro = formatCentsLabel;
	const setCentsFromEuro = (target: Record<string, unknown>, field: string, rawValue: unknown) => {
		target[field] = Math.max(0, euroToCentsImpl(rawValue) ?? 0);
	};

	const ladderRowsFor = (kind: BandType): IncrementLadderRow[] =>
		kind === 'weight' ? extraRules.value.weight_increment_ladder : extraRules.value.volume_increment_ladder;

	const setLadder = (kind: BandType, rows: IncrementLadderRow[]) => {
		if (kind === 'weight') extraRules.value.weight_increment_ladder = rows;
		else extraRules.value.volume_increment_ladder = rows;
	};

	const updateLadderIncrementFromEuro = (row: IncrementLadderRow, rawValue: unknown) => {
		row.increment_cents = Math.max(0, euroToCentsImpl(rawValue) ?? 0);
	};

	const normalizeLadderForPayload = (rows: unknown, fallbackIncrement: unknown): IncrementLadderRow[] => {
		const fallback = Math.max(0, Number(fallbackIncrement || 0));
		const source = Array.isArray(rows) ? rows : [];
		const normalized = source.map((row, idx) => {
			const fromStep = Math.max(1, Number.parseInt(row?.from_step ?? (idx + 1), 10) || 1);
			const toRaw = row?.to_step;
			const toStep = toRaw === null || toRaw === '' || toRaw === undefined
				? null
				: Math.max(fromStep, Number.parseInt(toRaw, 10) || fromStep);
			return { from_step: fromStep, to_step: toStep, increment_cents: Math.max(0, Number.parseInt(row?.increment_cents ?? fallback, 10) || 0) };
		}).sort((a, b) => a.from_step - b.from_step);
		if (!normalized.length) return [{ from_step: 1, to_step: null, increment_cents: fallback }];
		const last = normalized[normalized.length - 1];
		if (last) last.to_step = null;
		return normalized;
	};

	const addLadderRow = (kind: BandType) => {
		const rows = ladderRowsFor(kind);
		const payloadRows = normalizeLadderForPayload(rows, extraRules.value.increment_cents);
		const last = payloadRows[payloadRows.length - 1] || { from_step: 1, to_step: null, increment_cents: Number(extraRules.value.increment_cents || 0) };
		const fromStep = last.to_step == null ? (last.from_step + 1) : (last.to_step + 1);
		rows.push({ from_step: fromStep, to_step: null, increment_cents: Number(last.increment_cents || extraRules.value.increment_cents || 0) });
	};

	const removeLadderRow = (kind: BandType, idx: number) => {
		const rows = ladderRowsFor(kind);
		if (rows.length <= 1) return showError(null, 'Deve rimanere almeno uno scaglione incremento.');
		rows.splice(idx, 1);
	};

	const ensureLadderContinuity = (kind: BandType) => {
		const normalized = normalizeLadderForPayload(ladderRowsFor(kind), extraRules.value.increment_cents);
		setLadder(kind, normalized.map((row, idx) => ({
			from_step: idx === 0 ? 1 : ((normalized[idx - 1]?.to_step ?? normalized[idx - 1]?.from_step ?? 0) + 1),
			to_step: idx === normalized.length - 1 ? null : (row.to_step ?? row.from_step),
			increment_cents: row.increment_cents,
		})));
	};

	const ceilByResolutionLocal = (value: unknown, resolution: unknown): number => {
		const safeResolution = Number(resolution) > 0 ? Number(resolution) : 1;
		const m = 1 / safeResolution;
		return Number((Math.ceil((Number(value) * m) - PREVIEW_EPSILON) / m).toFixed(4));
	};

	const findBandLocal = (bands: PriceBand[], rawValue: unknown): PriceBand | null => {
		const value = Number(rawValue);
		if (!Array.isArray(bands) || !bands.length || !Number.isFinite(value) || value <= 0) return null;
		for (let idx = 0; idx < bands.length; idx += 1) {
			const band = bands[idx];
			if (!band) continue;
			const lowerOk = idx === 0 ? value >= (Number(band.min_value) - PREVIEW_EPSILON) : value > (Number(band.min_value) + PREVIEW_EPSILON);
			if (lowerOk && value <= (Number(band.max_value) + PREVIEW_EPSILON)) return band;
		}
		return null;
	};

	const calculateExtraPriceCentsLocal = (type: BandType, rawValue: unknown): number | null => {
		const er = extraRules.value;
		if (!er?.enabled) return null;
		const isWeight = type === 'weight';
		const start = Number(isWeight ? er.weight_start : er.volume_start);
		const step = Number(isWeight ? er.weight_step : er.volume_step);
		const resolution = Number(isWeight ? er.weight_resolution : er.volume_resolution);
		const increment = Number(er.increment_cents || 0);
		if (!Number.isFinite(start) || !Number.isFinite(step) || !Number.isFinite(resolution) || step <= 0 || resolution <= 0 || increment < 0) return null;
		const value = ceilByResolutionLocal(rawValue, resolution);
		if (value + PREVIEW_EPSILON < start) return null;
		const baseCents = er.base_price_cents_mode === 'manual'
			? Number(er.base_price_cents_manual || 0)
			: effectivePriceCentsLocal((isWeight ? weightBands.value : volumeBands.value).slice(-1)[0]);
		const bandNumber = Math.max(0, Math.floor(((value - start) + PREVIEW_EPSILON) / step)) + 1;
		return Math.max(0, Math.round(baseCents + (bandNumber * increment)));
	};

	const calculateBandPriceCentsLocal = (type: BandType, rawValue: unknown): number => {
		const bands = bandsOf(type);
		const band = findBandLocal(bands, rawValue);
		if (band) return effectivePriceCentsLocal(band);
		const extraPrice = calculateExtraPriceCentsLocal(type, rawValue);
		return extraPrice !== null ? extraPrice : effectivePriceCentsLocal(bands[bands.length - 1]);
	};

	const startEdit = (type: BandType, idx: number, field: EditablePriceField) => {
		const key = `${type}-${idx}-${field}`;
		editingCell.value = key;
		const cents = bandsOf(type)[idx]?.[field];
		editValue.value = cents != null ? formatCentsLabel(cents) : '';
		nextTick(() => {
			const input = document.getElementById(`edit-${key}`) as HTMLInputElement | null;
			if (input) { input.focus(); input.select(); }
		});
	};

	const confirmEdit = (type: BandType, idx: number, field: EditablePriceField) => {
		const key = `${type}-${idx}-${field}`;
		if (editingCell.value !== key) return;
		const newCents = euroToCentsImpl(editValue.value);
		if (newCents !== null && newCents < 0) {
			showError(null, "Il prezzo non può essere negativo.");
		} else {
			const band = bandsOf(type)[idx];
			if (band) {
				if (field === 'base_price') band.base_price = newCents ?? 0;
				else band.discount_price = newCents;
			}
		}
		editingCell.value = null;
		editValue.value = '';
	};

	const cancelEdit = () => { editingCell.value = null; editValue.value = ''; };

	const toggleShowDiscount = (type: BandType, idx: number) => {
		const band = bandsOf(type)[idx];
		if (band) band.show_discount = !band.show_discount;
	};

	const addBand = (type: BandType) => {
		const bands = bandsOf(type);
		const last = bands[bands.length - 1];
		const min = last ? Number(last.max_value) : 0;
		bands.push({
			id: `${type}-new-${Date.now()}-${Math.random().toString(36).slice(2, 6)}`,
			type,
			min_value: Number(min.toFixed(3)),
			max_value: Number((min + (type === 'weight' ? 50 : 0.2)).toFixed(3)),
			base_price: last ? Number(last.base_price || 0) : 0,
			discount_price: null,
			show_discount: true,
			sort_order: bands.length + 1,
		});
	};

	const removeBand = (type: BandType, idx: number) => {
		const bands = bandsOf(type);
		if (bands.length <= 1) return showError(null, "Deve rimanere almeno una fascia.");
		bands.splice(idx, 1);
	};

	const moveBand = (type: BandType, idx: number, direction: number) => {
		const bands = bandsOf(type);
		const target = idx + direction;
		if (target < 0 || target >= bands.length) return;
		const current = bands[idx];
		const next = bands[target];
		if (!current || !next) return;
		[bands[idx], bands[target]] = [next, current];
	};

	const addSupplement = () => {
		supplementRules.value.push({ id: `supplement-${Date.now()}`, prefix: '', amount_cents: 0, apply_to: 'both', enabled: true });
	};
	const removeSupplement = (idx: number) => { supplementRules.value.splice(idx, 1); };
	const supplementAmountToEuro = (rule: Partial<SupplementRule>): string => formatCentsLabel(rule?.amount_cents);
	const updateSupplementAmountFromEuro = (rule: SupplementRule, rawValue: unknown) => {
		const value = Number.parseFloat(String(rawValue || '').replace(/[€\s]/g, '').replace(',', '.'));
		rule.amount_cents = (!Number.isFinite(value) || value < 0) ? 0 : Math.round(value * 100);
	};

	const keyedRuleAmountToEuro = (rule: KeyedRule): string => formatCentsLabel(rule?.price_cents);
	const updateKeyedRuleAmountFromEuro = (rule: KeyedRule, rawValue: unknown) => setCentsFromEuro(rule, 'price_cents', rawValue);
	const keyedRuleMinFeeToEuro = (rule: KeyedRule): string => formatCentsLabel(rule?.min_fee_cents);
	const updateKeyedRuleMinFeeFromEuro = (rule: KeyedRule, rawValue: unknown) => setCentsFromEuro(rule, 'min_fee_cents', rawValue);

	const updateArrayField = (rule: KeyedRule, field: string, rawValue: unknown, { uppercase = false }: { uppercase?: boolean } = {}) => {
		rule[field] = String(rawValue || '').split(',').map((item) => String(item || '').trim()).filter(Boolean)
			.map((item) => uppercase ? item.toUpperCase() : item.toLowerCase());
	};

	const addTierRow = (rule: KeyedRule) => {
		const last = Array.isArray(rule.tiers) && rule.tiers.length ? rule.tiers[rule.tiers.length - 1] : null;
		rule.tiers = Array.isArray(rule.tiers) ? rule.tiers : [];
		rule.tiers.push({
			up_to_kg: last?.up_to_kg != null ? Number(last.up_to_kg) + 5 : null,
			price_cents: Number(last?.price_cents || 0),
		});
	};

	const removeTierRow = (rule: KeyedRule, idx: number) => {
		if (!Array.isArray(rule.tiers) || rule.tiers.length <= 1) return showError(null, 'Serve almeno uno scaglione per la regola selezionata.');
		rule.tiers.splice(idx, 1);
	};

	const updateEuropeRateAmountFromEuro = (rate: EuropeRate, rawValue: unknown) => {
		const cents = euroToCentsImpl(rawValue);
		rate.price_cents = cents == null ? null : Math.max(0, cents);
	};
	const toggleEuropeRateQuote = (rate: EuropeRate) => {
		rate.quote_required = !rate.quote_required;
		if (rate.quote_required) rate.price_cents = null;
	};

	return {
		editingCell, editValue,
		centsToEuro, euroToCents, effectivePrice, discountInfo, formatApplicationLabel, incrementCentsToEuro,
		updateLadderIncrementFromEuro, normalizeLadderForPayload, addLadderRow, removeLadderRow, ensureLadderContinuity, ladderRowsFor,
		calculateBandPriceCentsLocal,
		startEdit, confirmEdit, cancelEdit, toggleShowDiscount, addBand, removeBand, moveBand,
		addSupplement, removeSupplement, supplementAmountToEuro, updateSupplementAmountFromEuro,
		keyedRuleAmountToEuro, updateKeyedRuleAmountFromEuro, keyedRuleMinFeeToEuro, updateKeyedRuleMinFeeFromEuro,
		updateArrayField, addTierRow, removeTierRow,
		updateEuropeRateAmountFromEuro, toggleEuropeRateQuote,
	};
};
