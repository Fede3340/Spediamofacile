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

const innerInputClass = 'mt-1 w-full h-9 px-2.5 rounded-control border border-brand-border bg-brand-card text-sm focus:outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20';
const innerLabelClass = 'text-xs text-brand-text-secondary';
</script>

<template>
	<div class="space-y-6">
		<SfAlert v-if="!bandsFromDb" tone="warning" title="Fasce di prezzo non ancora nel database">
			<p class="mb-3">Stai vedendo i valori predefiniti del calcolatore. Premi il pulsante per salvarli nel database e poterli modificare.</p>
			<SfButton variant="accent" :loading="seeding" :disabled="seeding" @click="seedBands">
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

		<SfCard padding="md">
			<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
				<div>
					<h2 class="text-lg font-bold text-brand-text mb-1">Regole oltre 7ª fascia</h2>
					<p class="text-xs text-brand-text-muted">Configurazione scaglioni dinamici (es. 101-150, 151-200 e 0,401-0,600, 0,601-0,800).</p>
				</div>
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
			</div>

			<div class="grid grid-cols-1 desktop:grid-cols-2 gap-4">
				<div class="space-y-3 p-3.5 rounded-card border border-brand-border bg-brand-bg-alt">
					<h3 class="text-sm font-semibold text-brand-text">Scaglioni Peso</h3>
					<div class="grid grid-cols-3 gap-2.5">
						<label :class="innerLabelClass">Start
							<input :value="extraRuleValue('weight_start')" type="number" min="0" step="1" :class="innerInputClass" @input="updateExtraRuleNumber('weight_start', $event.target.value)">
						</label>
						<label :class="innerLabelClass">Step
							<input :value="extraRuleValue('weight_step')" type="number" min="0.0001" step="1" :class="innerInputClass" @input="updateExtraRuleNumber('weight_step', $event.target.value)">
						</label>
						<label :class="innerLabelClass">Risoluzione
							<input :value="extraRuleValue('weight_resolution')" type="number" min="0.0001" step="1" :class="innerInputClass" @input="updateExtraRuleNumber('weight_resolution', $event.target.value)">
						</label>
					</div>
					<p class="text-xs text-brand-text-secondary">Preview: {{ extraRuleExamples.firstWeightFrom }}-{{ extraRuleExamples.firstWeightTo }} / {{ extraRuleExamples.secondWeightFrom }}-{{ extraRuleExamples.secondWeightTo }}</p>
				</div>

				<div class="space-y-3 p-3.5 rounded-card border border-brand-border bg-brand-bg-alt">
					<h3 class="text-sm font-semibold text-brand-text">Scaglioni Volume (m³)</h3>
					<div class="grid grid-cols-3 gap-2.5">
						<label :class="innerLabelClass">Start
							<input :value="extraRuleValue('volume_start')" type="number" min="0" step="0.001" :class="innerInputClass" @input="updateExtraRuleNumber('volume_start', $event.target.value)">
						</label>
						<label :class="innerLabelClass">Step
							<input :value="extraRuleValue('volume_step')" type="number" min="0.0001" step="0.001" :class="innerInputClass" @input="updateExtraRuleNumber('volume_step', $event.target.value)">
						</label>
						<label :class="innerLabelClass">Risoluzione
							<input :value="extraRuleValue('volume_resolution')" type="number" min="0.0001" step="0.001" :class="innerInputClass" @input="updateExtraRuleNumber('volume_resolution', $event.target.value)">
						</label>
					</div>
					<p class="text-xs text-brand-text-secondary">Preview: {{ extraRuleExamples.firstVolumeFrom.toFixed(3) }}-{{ extraRuleExamples.firstVolumeTo.toFixed(3) }} / {{ extraRuleExamples.secondVolumeFrom.toFixed(3) }}-{{ extraRuleExamples.secondVolumeTo.toFixed(3) }}</p>
				</div>

				<div class="space-y-3 p-3.5 rounded-card border border-brand-border bg-brand-bg-alt">
					<h3 class="text-sm font-semibold text-brand-text">Incrementi oltre 7ª fascia</h3>
					<div class="grid grid-cols-1 tablet:grid-cols-2 gap-2.5">
						<label :class="innerLabelClass">Base prezzo extra
							<select :value="extraRuleValue('base_price_cents_mode')" :class="innerInputClass" @change="updateExtraRule('base_price_cents_mode', $event.target.value)">
								<option value="last_band_effective">Ultima fascia effettiva</option>
								<option value="manual">Manuale</option>
							</select>
						</label>
						<label :class="innerLabelClass">Incremento fisso per ogni fascia extra (€)
							<input
								:value="(Number(extraRules.increment_cents || 0) / 100).toFixed(2).replace('.', ',')"
								type="text"
								:class="innerInputClass"
								@input="updateExtraRule('increment_cents', Math.max(0, euroToCents($event.target.value) ?? 0))">
						</label>
					</div>
					<label v-if="extraRuleValue('base_price_cents_mode') === 'manual'" :class="innerLabelClass">Prezzo base extra manuale (€)
						<input
							:value="extraRules.base_price_cents_manual == null ? '' : (Number(extraRules.base_price_cents_manual || 0) / 100).toFixed(2).replace('.', ',')"
							type="text"
							:class="innerInputClass"
							@input="updateExtraRuleCents('base_price_cents_manual', $event.target.value)">
					</label>
				</div>

				<div class="p-3.5 rounded-card border border-brand-soft-border bg-brand-soft-bg">
					<h3 class="text-sm font-semibold text-brand-primary mb-2.5">Casi rapidi</h3>
					<div class="overflow-x-auto">
						<table class="w-full min-w-[450px] text-xs">
							<thead>
								<tr class="text-left text-brand-text-secondary border-b border-brand-border">
									<th class="py-1.5">Caso</th>
									<th class="py-1.5">Peso</th>
									<th class="py-1.5">Volume</th>
									<th class="py-1.5">Prezzo peso</th>
									<th class="py-1.5">Prezzo volume</th>
									<th class="py-1.5">Totale (MAX)</th>
								</tr>
							</thead>
							<tbody>
								<tr v-for="row in pricingPreviewCases" :key="row.id" class="border-b border-brand-border last:border-0 text-brand-text">
									<td class="py-2 font-semibold">{{ row.label }}</td>
									<td class="py-2">{{ row.weight }}</td>
									<td class="py-2">{{ row.volume }}</td>
									<td class="py-2">{{ row.weightPriceLabel }}</td>
									<td class="py-2">{{ row.volumePriceLabel }}</td>
									<td class="py-2 font-bold text-brand-primary">{{ row.totalLabel }}</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</SfCard>

		<SfCard padding="md">
			<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
				<div>
					<h2 class="text-lg font-bold text-brand-text mb-1">Supplementi CAP</h2>
					<p class="text-xs text-brand-text-muted">Prefisso CAP + importo + applicazione (origine / destinazione / entrambi).</p>
				</div>
				<SfButton size="sm" @click="addSupplement">Aggiungi supplemento</SfButton>
			</div>

			<div v-if="!supplementRules.length" class="p-3.5 rounded-card border border-dashed border-brand-border text-brand-text-muted text-sm">
				Nessun supplemento attivo. Aggiungi una regola se necessario.
			</div>

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
