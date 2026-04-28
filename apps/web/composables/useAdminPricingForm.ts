/**
 * @file useAdminPricingForm — sezione form del pannello admin pricing.
 *
 * Estratto da useAdminPricing.js. Gestisce: editing inline, CRUD fasce/supplementi,
 * helpers servizi/Europa/ladder, conversioni cents<->euro.
 *
 * Riceve come deps gli ref reattivi dello store admin. Esporta `createFormSection`
 * usato dall'orchestratore `useAdminPricing`.
 */

// ────────────────────────────────────────────────────────────
// 3. Form section
// (Merged from useAdminPricingForm.js — 2026-04-20)
// Stato form + editing inline + CRUD per fasce/supplementi,
// helpers servizi/Europa/ladder, conversioni cents<->euro.
// ────────────────────────────────────────────────────────────

export const createFormSection = ({
	weightBands,
	volumeBands,
	extraRules,
	supplementRules,
	showError,
}) => {
	// ── Editing state ────────────────────────────────────
	const editingCell = ref(null);
	const editValue = ref('');

	// ── Utility ──────────────────────────────────────────
	const centsToEuro = (cents) => {
		if (cents == null || cents === '') return '-';
		return (Number(cents) / 100).toFixed(2).replace('.', ',') + '\u20AC';
	};

	const euroToCents = (euro) => {
		if (euro == null || euro === '') return null;
		const cleaned = String(euro).replace(/[€\s]/g, '').replace(',', '.');
		const num = parseFloat(cleaned);
		return isNaN(num) ? null : Math.round(num * 100);
	};

	const effectivePrice = (band) => {
		return band.discount_price != null ? band.discount_price : band.base_price;
	};

	const discountInfo = (band) => {
		if (band.discount_price == null || band.base_price <= 0) return null;
		const diff = ((1 - band.discount_price / band.base_price) * 100);
		return Math.round(diff);
	};

	const formatApplicationLabel = (value) => ({
		per_spedizione: 'Per spedizione',
		automatic_destination_per_package: 'Automatico su destinazione / collo',
		automatic_destination: 'Automatico su destinazione',
		automatic_package_shape: 'Automatico per forma collo',
		automatic_per_package: 'Automatico per collo',
		manual_quote_only: 'Solo preventivo manuale',
		manual_admin: 'Fee operativa admin',
	}[value] || value || '\u2014');

	const incrementCentsToEuro = (value) => (Number(value || 0) / 100).toFixed(2).replace('.', ',');

	const updateLadderIncrementFromEuro = (row, rawValue) => {
		const cents = euroToCents(rawValue);
		row.increment_cents = Math.max(0, cents ?? 0);
	};

	// ── Ladder helpers ───────────────────────────────────
	const normalizeLadderForPayload = (rows, fallbackIncrement) => {
		const fallback = Math.max(0, Number(fallbackIncrement || 0));
		const source = Array.isArray(rows) ? rows : [];
		const normalized = source
			.map((row, idx) => {
				const fromStep = Math.max(1, Number.parseInt(row?.from_step ?? (idx + 1), 10) || 1);
				const toRaw = row?.to_step;
				const toStep = toRaw === null || toRaw === '' || toRaw === undefined
					? null
					: Math.max(fromStep, Number.parseInt(toRaw, 10) || fromStep);
				const increment = Math.max(0, Number.parseInt(row?.increment_cents ?? fallback, 10) || 0);
				return { from_step: fromStep, to_step: toStep, increment_cents: increment };
			})
			.sort((a, b) => a.from_step - b.from_step);

		if (!normalized.length) {
			return [{ from_step: 1, to_step: null, increment_cents: fallback }];
		}
		normalized[normalized.length - 1].to_step = null;
		return normalized;
	};

	const ladderRowsFor = (kind) => {
		return kind === 'weight' ? extraRules.value.weight_increment_ladder : extraRules.value.volume_increment_ladder;
	};

	const addLadderRow = (kind) => {
		const rows = ladderRowsFor(kind);
		const payloadRows = normalizeLadderForPayload(rows, extraRules.value.increment_cents);
		const last = payloadRows[payloadRows.length - 1] || { from_step: 1, to_step: null, increment_cents: Number(extraRules.value.increment_cents || 0) };
		const fromStep = last.to_step == null ? (last.from_step + 1) : (last.to_step + 1);
		rows.push({
			from_step: fromStep,
			to_step: null,
			increment_cents: Number(last.increment_cents || extraRules.value.increment_cents || 0),
		});
	};

	const removeLadderRow = (kind, idx) => {
		const rows = ladderRowsFor(kind);
		if (rows.length <= 1) {
			showError(null, 'Deve rimanere almeno uno scaglione incremento.');
			return;
		}
		rows.splice(idx, 1);
	};

	const ensureLadderContinuity = (kind) => {
		const rows = ladderRowsFor(kind);
		const normalized = normalizeLadderForPayload(rows, extraRules.value.increment_cents);
		const rebuilt = normalized.map((row, idx) => ({
			from_step: idx === 0 ? 1 : normalized[idx - 1].to_step + 1,
			to_step: idx === normalized.length - 1 ? null : (row.to_step ?? row.from_step),
			increment_cents: row.increment_cents,
		}));
		if (kind === 'weight') {
			extraRules.value.weight_increment_ladder = rebuilt;
		} else {
			extraRules.value.volume_increment_ladder = rebuilt;
		}
	};

	// ── Preview price calc (internal) ────────────────────
	const PREVIEW_EPSILON = 0.0000001;

	const effectivePriceCentsLocal = (band) => {
		if (!band) return 0;
		if (band.discount_price != null && Number(band.discount_price) >= 0) {
			return Number(band.discount_price);
		}
		return Number(band.base_price || 0);
	};

	const ceilByResolutionLocal = (value, resolution) => {
		const safeResolution = Number(resolution) > 0 ? Number(resolution) : 1;
		const multiplier = 1 / safeResolution;
		return Number((Math.ceil((Number(value) * multiplier) - PREVIEW_EPSILON) / multiplier).toFixed(4));
	};

	const findBandLocal = (bands, rawValue) => {
		const value = Number(rawValue);
		if (!Array.isArray(bands) || !bands.length || !Number.isFinite(value) || value <= 0) return null;
		for (let idx = 0; idx < bands.length; idx += 1) {
			const band = bands[idx];
			const min = Number(band.min_value);
			const max = Number(band.max_value);
			const lowerOk = idx === 0 ? value >= (min - PREVIEW_EPSILON) : value > (min + PREVIEW_EPSILON);
			const upperOk = value <= (max + PREVIEW_EPSILON);
			if (lowerOk && upperOk) return band;
		}
		return null;
	};

	const calculateExtraPriceCentsLocal = (type, rawValue) => {
		if (!extraRules.value?.enabled) return null;
		const isWeight = type === 'weight';
		const start = Number(isWeight ? extraRules.value.weight_start : extraRules.value.volume_start);
		const step = Number(isWeight ? extraRules.value.weight_step : extraRules.value.volume_step);
		const resolution = Number(isWeight ? extraRules.value.weight_resolution : extraRules.value.volume_resolution);
		const increment = Number(extraRules.value.increment_cents || 0);
		if (!Number.isFinite(start) || !Number.isFinite(step) || !Number.isFinite(resolution) || step <= 0 || resolution <= 0 || increment < 0) {
			return null;
		}
		const value = ceilByResolutionLocal(rawValue, resolution);
		if (value + PREVIEW_EPSILON < start) return null;
		let baseCents = 0;
		if (extraRules.value.base_price_cents_mode === 'manual') {
			baseCents = Number(extraRules.value.base_price_cents_manual || 0);
		} else {
			const sourceBands = isWeight ? weightBands.value : volumeBands.value;
			const lastBand = sourceBands[sourceBands.length - 1];
			baseCents = effectivePriceCentsLocal(lastBand);
		}
		const stepsFromStart = Math.floor(((value - start) + PREVIEW_EPSILON) / step);
		const bandNumber = Math.max(0, stepsFromStart) + 1;
		return Math.max(0, Math.round(baseCents + (bandNumber * increment)));
	};

	const calculateBandPriceCentsLocal = (type, rawValue) => {
		const bands = type === 'weight' ? weightBands.value : volumeBands.value;
		const band = findBandLocal(bands, rawValue);
		if (band) return effectivePriceCentsLocal(band);
		const extraPrice = calculateExtraPriceCentsLocal(type, rawValue);
		if (extraPrice !== null) return extraPrice;
		const lastBand = bands[bands.length - 1];
		return effectivePriceCentsLocal(lastBand);
	};

	// ── Band edit actions ────────────────────────────────
	const startEdit = (type, idx, field) => {
		const key = `${type}-${idx}-${field}`;
		editingCell.value = key;
		const bands = type === 'weight' ? weightBands.value : volumeBands.value;
		const cents = bands[idx][field];
		editValue.value = cents != null ? (Number(cents) / 100).toFixed(2).replace('.', ',') : '';
		nextTick(() => {
			const input = document.getElementById(`edit-${key}`);
			if (input) { input.focus(); input.select(); }
		});
	};

	const confirmEdit = (type, idx, field) => {
		const key = `${type}-${idx}-${field}`;
		if (editingCell.value !== key) return;
		const bands = type === 'weight' ? weightBands.value : volumeBands.value;
		const newCents = euroToCents(editValue.value);
		if (newCents !== null && newCents < 0) {
			showError(null, "Il prezzo non può essere negativo.");
			editingCell.value = null;
			editValue.value = '';
			return;
		}
		bands[idx][field] = newCents;
		editingCell.value = null;
		editValue.value = '';
	};

	const cancelEdit = () => {
		editingCell.value = null;
		editValue.value = '';
	};

	const toggleShowDiscount = (type, idx) => {
		const bands = type === 'weight' ? weightBands.value : volumeBands.value;
		bands[idx].show_discount = !bands[idx].show_discount;
	};

	const addBand = (type) => {
		const bands = type === 'weight' ? weightBands.value : volumeBands.value;
		const last = bands[bands.length - 1];
		const min = last ? Number(last.max_value) : 0;
		const max = Number((min + (type === 'weight' ? 50 : 0.2)).toFixed(3));
		bands.push({
			id: `${type}-new-${Date.now()}-${Math.random().toString(36).slice(2, 6)}`,
			type,
			min_value: Number(min.toFixed(3)),
			max_value: max,
			base_price: last ? Number(last.base_price || 0) : 0,
			discount_price: null,
			show_discount: true,
			sort_order: bands.length + 1,
		});
	};

	const removeBand = (type, idx) => {
		const bands = type === 'weight' ? weightBands.value : volumeBands.value;
		if (bands.length <= 1) {
			showError(null, "Deve rimanere almeno una fascia.");
			return;
		}
		bands.splice(idx, 1);
	};

	const moveBand = (type, idx, direction) => {
		const bands = type === 'weight' ? weightBands.value : volumeBands.value;
		const target = idx + direction;
		if (target < 0 || target >= bands.length) return;
		[bands[idx], bands[target]] = [bands[target], bands[idx]];
	};

	// ── Supplement actions ────────────────────────────────
	const addSupplement = () => {
		supplementRules.value.push({
			id: `supplement-${Date.now()}`,
			prefix: '',
			amount_cents: 0,
			apply_to: 'both',
			enabled: true,
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

	// ── Service / keyed rule helpers ─────────────────────
	const keyedRuleAmountToEuro = (rule) => (Number(rule?.price_cents || 0) / 100).toFixed(2).replace('.', ',');

	const updateKeyedRuleAmountFromEuro = (rule, rawValue) => {
		const cents = euroToCents(rawValue);
		rule.price_cents = Math.max(0, cents ?? 0);
	};

	const keyedRuleMinFeeToEuro = (rule) => (Number(rule?.min_fee_cents || 0) / 100).toFixed(2).replace('.', ',');

	const updateKeyedRuleMinFeeFromEuro = (rule, rawValue) => {
		const cents = euroToCents(rawValue);
		rule.min_fee_cents = Math.max(0, cents ?? 0);
	};

	const normalizeArrayFieldInput = (rawValue, { uppercase = false } = {}) =>
		String(rawValue || '')
			.split(',')
			.map((item) => String(item || '').trim())
			.filter(Boolean)
			.map((item) => uppercase ? item.toUpperCase() : item.toLowerCase());

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

	const removeTierRow = (rule, idx) => {
		if (!Array.isArray(rule.tiers) || rule.tiers.length <= 1) {
			showError(null, 'Serve almeno uno scaglione per la regola selezionata.');
			return;
		}
		rule.tiers.splice(idx, 1);
	};

	// ── Europe rate helpers ──────────────────────────────
	const updateEuropeRateAmountFromEuro = (rate, rawValue) => {
		const cents = euroToCents(rawValue);
		rate.price_cents = cents == null ? null : Math.max(0, cents);
	};

	const toggleEuropeRateQuote = (rate) => {
		rate.quote_required = !rate.quote_required;
		if (rate.quote_required) {
			rate.price_cents = null;
		}
	};

	return {
		editingCell,
		editValue,
		centsToEuro,
		euroToCents,
		effectivePrice,
		discountInfo,
		formatApplicationLabel,
		incrementCentsToEuro,
		updateLadderIncrementFromEuro,
		normalizeLadderForPayload,
		addLadderRow,
		removeLadderRow,
		ensureLadderContinuity,
		ladderRowsFor,
		calculateBandPriceCentsLocal,
		startEdit,
		confirmEdit,
		cancelEdit,
		toggleShowDiscount,
		addBand,
		removeBand,
		moveBand,
		addSupplement,
		removeSupplement,
		supplementAmountToEuro,
		updateSupplementAmountFromEuro,
		keyedRuleAmountToEuro,
		updateKeyedRuleAmountFromEuro,
		keyedRuleMinFeeToEuro,
		updateKeyedRuleMinFeeFromEuro,
		updateArrayField,
		addTierRow,
		removeTierRow,
		updateEuropeRateAmountFromEuro,
		toggleEuropeRateQuote,
	};
};
