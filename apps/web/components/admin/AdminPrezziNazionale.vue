<script setup>
const props = defineProps({
	weightBands: { type: Array, required: true },
	volumeBands: { type: Array, required: true },
	extraRules: { type: Object, required: true },
	supplementRules: { type: Array, required: true },
	bandsFromDb: { type: Boolean, required: true },
	seeding: { type: Boolean, required: true },
	editingCell: { type: [String, null], default: null },
	editValue: { type: String, default: '' },
	extraRuleExamples: { type: Object, required: true },
	pricingPreviewCases: { type: Array, required: true },
	centsToEuro: { type: Function, required: true },
	euroToCents: { type: Function, required: true },
	effectivePrice: { type: Function, required: true },
	discountInfo: { type: Function, required: true },
	startEdit: { type: Function, required: true },
	confirmEdit: { type: Function, required: true },
	cancelEdit: { type: Function, required: true },
	toggleShowDiscount: { type: Function, required: true },
	addBand: { type: Function, required: true },
	removeBand: { type: Function, required: true },
	moveBand: { type: Function, required: true },
	seedBands: { type: Function, required: true },
	addSupplement: { type: Function, required: true },
	removeSupplement: { type: Function, required: true },
	supplementAmountToEuro: { type: Function, required: true },
	updateSupplementAmountFromEuro: { type: Function, required: true },
});

const emit = defineEmits(['update:editValue', 'update:extraRules', 'update:supplementRules']);

const extraRuleValue = (field) => props.extraRules?.[field];
const updateExtraRule = (field, value) => {
	emit('update:extraRules', { ...props.extraRules, [field]: value });
};
const updateExtraRuleNumber = (field, rawValue) => {
	const numeric = Number(rawValue);
	updateExtraRule(field, Number.isFinite(numeric) ? numeric : 0);
};
const updateExtraRuleCents = (field, rawValue, fallback = null) => {
	updateExtraRule(field, props.euroToCents(rawValue) ?? fallback);
};
const updateSupplementRule = (index, nextRule) => {
	emit('update:supplementRules', props.supplementRules.map((rule, ruleIndex) => (ruleIndex === index ? nextRule : rule)));
};

const bandTableShared = computed(() => ({
	editingCell: props.editingCell,
	editValue: props.editValue,
	centsToEuro: props.centsToEuro,
	effectivePrice: props.effectivePrice,
	discountInfo: props.discountInfo,
	startEdit: props.startEdit,
	confirmEdit: props.confirmEdit,
	cancelEdit: props.cancelEdit,
	toggleShowDiscount: props.toggleShowDiscount,
	addBand: props.addBand,
	removeBand: props.removeBand,
	moveBand: props.moveBand,
}));

const baseModeOptions = [
	{ value: 'last_band_effective', label: 'Ultima fascia effettiva' },
	{ value: 'manual', label: 'Manuale' },
];

const incrementEuroValue = computed(() =>
	(Number(props.extraRules?.increment_cents || 0) / 100).toFixed(2).replace('.', ','),
);
const manualBaseEuroValue = computed(() => {
	const raw = props.extraRules?.base_price_cents_manual;
	return raw == null ? '' : (Number(raw || 0) / 100).toFixed(2).replace('.', ',');
});

const previewCaseColumns = [
	{ key: 'label', label: 'Caso' },
	{ key: 'weight', label: 'Peso' },
	{ key: 'volume', label: 'Volume' },
	{ key: 'weightPriceLabel', label: 'Prezzo peso' },
	{ key: 'volumePriceLabel', label: 'Prezzo volume' },
	{ key: 'totalLabel', label: 'Totale (MAX)' },
];
</script>

<template>
	<div class="space-y-6">
		<SfAlert v-if="!bandsFromDb" tone="warning" title="Fasce di prezzo non ancora nel database">
			<p class="mb-3">Stai vedendo i valori predefiniti del calcolatore. Premi il pulsante per salvarli nel database e poterli modificare.</p>
			<SfButton variant="primary" :loading="seeding" :disabled="seeding" @click="seedBands">
				<template #leading><UIcon name="mdi:plus-circle" class="w-[18px] h-[18px]" /></template>
				{{ seeding ? "Inizializzazione..." : "Inizializza fasce nel database" }}
			</SfButton>
		</SfAlert>

		<AdminBandTable
			:bands="weightBands"
			band-type="weight"
			title="Fasce peso"
			subtitle="Clicca sul prezzo per modificarlo. I valori sono in euro."
			add-label="Aggiungi fascia peso"
			min-max-step="1"
			min-width="700px"
			v-bind="bandTableShared"
			@update:edit-value="emit('update:editValue', $event)">
			<template #icon>
				<UIcon name="mdi:weight-kilogram" class="w-5 h-5 text-brand-primary" />
			</template>
		</AdminBandTable>

		<AdminBandTable
			:bands="volumeBands"
			band-type="volume"
			title="Fasce volume"
			subtitle="Fasce basate sul peso volumetrico (L x P x H / 5000). Completamente editabili (min/max/prezzo)."
			add-label="Aggiungi fascia volume"
			min-max-step="0.001"
			min-width="760px"
			v-bind="bandTableShared"
			@update:edit-value="emit('update:editValue', $event)">
			<template #icon>
				<UIcon name="mdi:cube-outline" class="w-5 h-5 text-brand-primary" />
			</template>
		</AdminBandTable>

		<SfCard padding="md" title="Regole oltre 7ª fascia" description="Configurazione scaglioni dinamici (es. 101-150, 151-200 e 0,401-0,600, 0,601-0,800).">
			<template #actions>
				<button
					type="button"
					role="switch"
					:aria-checked="extraRuleValue('enabled') ? 'true' : 'false'"
					aria-label="Attiva regole oltre 7ª fascia"
					:class="extraRuleValue('enabled') ? 'bg-brand-primary' : 'bg-brand-border'"
					class="relative inline-flex h-8 w-14 items-center rounded-full transition-colors cursor-pointer"
					@click="updateExtraRule('enabled', !extraRuleValue('enabled'))">
					<span :class="extraRuleValue('enabled') ? 'translate-x-[28px]' : 'translate-x-[2px]'" class="inline-block h-[26px] w-[26px] transform rounded-full bg-white transition-transform shadow-sm" />
				</button>
			</template>

			<div class="grid grid-cols-1 desktop:grid-cols-2 gap-4">
				<div class="space-y-3 p-3.5 rounded-card border border-brand-border bg-brand-bg-alt">
					<h3 class="text-sm font-semibold text-brand-text">Scaglioni Peso</h3>
					<div class="grid grid-cols-3 gap-2.5">
						<SfFormGroup label="Start">
							<SfInput
								type="number"
								size="sm"
								:model-value="extraRuleValue('weight_start')"
								@update:model-value="(v) => updateExtraRuleNumber('weight_start', v)" />
						</SfFormGroup>
						<SfFormGroup label="Step">
							<SfInput
								type="number"
								size="sm"
								:model-value="extraRuleValue('weight_step')"
								@update:model-value="(v) => updateExtraRuleNumber('weight_step', v)" />
						</SfFormGroup>
						<SfFormGroup label="Risoluzione">
							<SfInput
								type="number"
								size="sm"
								:model-value="extraRuleValue('weight_resolution')"
								@update:model-value="(v) => updateExtraRuleNumber('weight_resolution', v)" />
						</SfFormGroup>
					</div>
					<p class="text-xs text-brand-text-secondary">Preview: {{ extraRuleExamples.firstWeightFrom }}-{{ extraRuleExamples.firstWeightTo }} / {{ extraRuleExamples.secondWeightFrom }}-{{ extraRuleExamples.secondWeightTo }}</p>
				</div>

				<div class="space-y-3 p-3.5 rounded-card border border-brand-border bg-brand-bg-alt">
					<h3 class="text-sm font-semibold text-brand-text">Scaglioni Volume (m³)</h3>
					<div class="grid grid-cols-3 gap-2.5">
						<SfFormGroup label="Start">
							<SfInput
								type="number"
								size="sm"
								:model-value="extraRuleValue('volume_start')"
								@update:model-value="(v) => updateExtraRuleNumber('volume_start', v)" />
						</SfFormGroup>
						<SfFormGroup label="Step">
							<SfInput
								type="number"
								size="sm"
								:model-value="extraRuleValue('volume_step')"
								@update:model-value="(v) => updateExtraRuleNumber('volume_step', v)" />
						</SfFormGroup>
						<SfFormGroup label="Risoluzione">
							<SfInput
								type="number"
								size="sm"
								:model-value="extraRuleValue('volume_resolution')"
								@update:model-value="(v) => updateExtraRuleNumber('volume_resolution', v)" />
						</SfFormGroup>
					</div>
					<p class="text-xs text-brand-text-secondary">Preview: {{ extraRuleExamples.firstVolumeFrom.toFixed(3) }}-{{ extraRuleExamples.firstVolumeTo.toFixed(3) }} / {{ extraRuleExamples.secondVolumeFrom.toFixed(3) }}-{{ extraRuleExamples.secondVolumeTo.toFixed(3) }}</p>
				</div>

				<div class="space-y-3 p-3.5 rounded-card border border-brand-border bg-brand-bg-alt">
					<h3 class="text-sm font-semibold text-brand-text">Incrementi oltre 7ª fascia</h3>
					<div class="grid grid-cols-1 tablet:grid-cols-2 gap-2.5">
						<SfFormGroup label="Base prezzo extra">
							<SfSelect
								size="sm"
								:model-value="extraRuleValue('base_price_cents_mode')"
								:options="baseModeOptions"
								@update:model-value="(v) => updateExtraRule('base_price_cents_mode', v)" />
						</SfFormGroup>
						<SfFormGroup label="Incremento fisso per ogni fascia extra (€)">
							<SfInput
								type="text"
								size="sm"
								:model-value="incrementEuroValue"
								@update:model-value="(v) => updateExtraRule('increment_cents', Math.max(0, euroToCents(v) ?? 0))" />
						</SfFormGroup>
					</div>
					<SfFormGroup v-if="extraRuleValue('base_price_cents_mode') === 'manual'" label="Prezzo base extra manuale (€)">
						<SfInput
							type="text"
							size="sm"
							:model-value="manualBaseEuroValue"
							@update:model-value="(v) => updateExtraRuleCents('base_price_cents_manual', v)" />
					</SfFormGroup>
				</div>

				<div class="p-3.5 rounded-card border border-brand-soft-border bg-brand-soft-bg">
					<h3 class="text-sm font-semibold text-brand-primary mb-2.5">Casi rapidi</h3>
					<UTable
						:rows="pricingPreviewCases"
						:columns="previewCaseColumns"
						:ui="{ wrapper: 'overflow-x-auto', th: { base: 'text-left text-brand-text-secondary text-xs py-1.5' }, td: { base: 'py-2 text-xs text-brand-text' } }">
						<template #label-data="{ row }">
							<span class="font-semibold">{{ row.label }}</span>
						</template>
						<template #totalLabel-data="{ row }">
							<span class="font-bold text-brand-primary">{{ row.totalLabel }}</span>
						</template>
					</UTable>
				</div>
			</div>
		</SfCard>

		<SfCard padding="md" title="Supplementi CAP" description="Prefisso CAP + importo + applicazione (origine / destinazione / entrambi).">
			<template #actions>
				<SfButton size="sm" @click="addSupplement">Aggiungi supplemento</SfButton>
			</template>

			<SfEmptyState
				v-if="!supplementRules.length"
				title="Nessun supplemento attivo"
				description="Aggiungi una regola se necessario." />

			<div v-else class="space-y-2.5">
				<AdminSupplementRow
					v-for="(rule, idx) in supplementRules"
					:key="rule.id || idx"
					:rule="rule"
					:supplement-amount-to-euro="supplementAmountToEuro"
					:update-supplement-amount-from-euro="updateSupplementAmountFromEuro"
					@update:rule="updateSupplementRule(idx, $event)"
					@remove="removeSupplement(idx)" />
			</div>
		</SfCard>
	</div>
</template>
