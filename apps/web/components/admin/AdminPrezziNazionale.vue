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
	// Functions
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

const emit = defineEmits(['update:editValue']);

/** Shared band-table props (everything except bands/bandType/title/subtitle/addLabel) */
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
</script>

<template>
	<!-- Banner: fasce non salvate nel DB -->
	<div v-if="!bandsFromDb" class="bg-amber-50 rounded-[12px] p-[20px] border border-amber-200 mb-[24px]">
		<div class="flex items-start gap-[12px]">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[24px] h-[24px] text-amber-600 shrink-0 mt-[2px]" fill="currentColor"><path d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z"/></svg>
			<div>
				<h3 class="text-[0.9375rem] font-bold text-amber-800 mb-[4px]">Fasce di prezzo non ancora nel database</h3>
				<p class="text-[0.8125rem] text-amber-700 mb-[12px]">Stai vedendo i valori predefiniti del calcolatore. Premi il pulsante per salvarli nel database e poterli modificare.</p>
				<button type="button"
					@click="seedBands"
					:disabled="seeding"
					class="inline-flex items-center gap-[8px] px-[20px] py-[10px] bg-amber-600 hover:bg-amber-700 text-white rounded-[50px] text-[0.875rem] font-medium transition-colors cursor-pointer disabled:opacity-50">
					<svg v-if="seeding" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[18px] h-[18px] animate-spin" fill="currentColor"><path d="M12,4V2A10,10 0 0,0 2,12H4A8,8 0 0,1 12,4Z"/></svg>
					<svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[18px] h-[18px]" fill="currentColor"><path d="M17,13H13V17H11V13H7V11H11V7H13V11H17M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z"/></svg>
					{{ seeding ? "Inizializzazione..." : "Inizializza fasce nel database" }}
				</button>
			</div>
		</div>
	</div>

	<!-- Fasce peso -->
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
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[20px] h-[20px] text-[#095866]" fill="currentColor"><path d="M12,3A4,4 0 0,1 16,7C16,7.73 15.81,8.41 15.46,9H18C18.95,9 19.75,9.67 19.95,10.56C21.96,18.57 22,18.78 22,19A2,2 0 0,1 20,21H4A2,2 0 0,1 2,19C2,18.78 2.04,18.57 4.05,10.56C4.25,9.67 5.05,9 6,9H8.54C8.19,8.41 8,7.73 8,7A4,4 0 0,1 12,3M12,5A2,2 0 0,0 10,7A2,2 0 0,0 12,9A2,2 0 0,0 14,7A2,2 0 0,0 12,5Z"/></svg>
		</template>
	</AdminBandTable>

	<!-- Fasce volume -->
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
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[20px] h-[20px] text-[#095866]" fill="currentColor"><path d="M21,16.5C21,16.88 20.79,17.21 20.47,17.38L12.57,21.82C12.41,21.94 12.21,22 12,22C11.79,22 11.59,21.94 11.43,21.82L3.53,17.38C3.21,17.21 3,16.88 3,16.5V7.5C3,7.12 3.21,6.79 3.53,6.62L11.43,2.18C11.59,2.06 11.79,2 12,2C12.21,2 12.41,2.06 12.57,2.18L20.47,6.62C20.79,6.79 21,7.12 21,7.5V16.5M12,4.15L6.04,7.5L12,10.85L17.96,7.5L12,4.15M5,15.91L11,19.29V12.58L5,9.21V15.91M19,15.91V9.21L13,12.58V19.29L19,15.91Z"/></svg>
		</template>
	</AdminBandTable>

	<!-- Regole oltre 7a fascia -->
	<div class="rounded-[12px] p-[16px] tablet:p-[20px] desktop:p-[28px] border border-[#E9EBEC] overflow-hidden">
		<div class="flex flex-wrap items-center justify-between gap-[12px] mb-[16px]">
			<div>
				<h2 class="text-[1.125rem] font-bold text-[#252B42] mb-[4px]">Regole oltre 7ª fascia</h2>
				<p class="text-[0.75rem] text-[#737373]">Configurazione scaglioni dinamici (es. 101-150, 151-200 e 0,401-0,600, 0,601-0,800).</p>
			</div>
			<button type="button" @click="extraRules.enabled = !extraRules.enabled"
				role="switch"
				:aria-checked="extraRules.enabled ? 'true' : 'false'"
				aria-label="Attiva regole oltre 7ª fascia"
				:class="extraRules.enabled ? 'bg-[#095866]' : 'bg-[#C8CCD0]'"
				class="relative inline-flex h-[32px] w-[56px] items-center rounded-full transition-colors cursor-pointer">
				<span :class="extraRules.enabled ? 'translate-x-[28px]' : 'translate-x-[2px]'" class="inline-block h-[26px] w-[26px] transform rounded-full bg-white transition-transform shadow-sm" />
			</button>
		</div>

		<div class="grid grid-cols-1 desktop:grid-cols-2 gap-[16px]">
			<div class="space-y-[12px] p-[14px] rounded-[12px] border border-[#E9EBEC] bg-[#FAFBFC]">
				<h3 class="text-[0.875rem] font-semibold text-[#252B42]">Scaglioni Peso</h3>
				<div class="grid grid-cols-3 gap-[10px]">
					<label class="text-[0.75rem] text-[#737373]">Start
						<input v-model.number="extraRules.weight_start" type="number" min="0" step="1" class="mt-[4px] w-full h-[38px] px-[10px] rounded-[12px] border border-[#E9EBEC] bg-white text-[0.8125rem]">
					</label>
					<label class="text-[0.75rem] text-[#737373]">Step
						<input v-model.number="extraRules.weight_step" type="number" min="0.0001" step="1" class="mt-[4px] w-full h-[38px] px-[10px] rounded-[12px] border border-[#E9EBEC] bg-white text-[0.8125rem]">
					</label>
					<label class="text-[0.75rem] text-[#737373]">Risoluzione
						<input v-model.number="extraRules.weight_resolution" type="number" min="0.0001" step="1" class="mt-[4px] w-full h-[38px] px-[10px] rounded-[12px] border border-[#E9EBEC] bg-white text-[0.8125rem]">
					</label>
				</div>
				<p class="text-[0.75rem] text-[#4F5D75]">Preview: {{ extraRuleExamples.firstWeightFrom }}-{{ extraRuleExamples.firstWeightTo }} / {{ extraRuleExamples.secondWeightFrom }}-{{ extraRuleExamples.secondWeightTo }}</p>
			</div>

			<div class="space-y-[12px] p-[14px] rounded-[12px] border border-[#E9EBEC] bg-[#FAFBFC]">
				<h3 class="text-[0.875rem] font-semibold text-[#252B42]">Scaglioni Volume (m&sup3;)</h3>
				<div class="grid grid-cols-3 gap-[10px]">
					<label class="text-[0.75rem] text-[#737373]">Start
						<input v-model.number="extraRules.volume_start" type="number" min="0" step="0.001" class="mt-[4px] w-full h-[38px] px-[10px] rounded-[12px] border border-[#E9EBEC] bg-white text-[0.8125rem]">
					</label>
					<label class="text-[0.75rem] text-[#737373]">Step
						<input v-model.number="extraRules.volume_step" type="number" min="0.0001" step="0.001" class="mt-[4px] w-full h-[38px] px-[10px] rounded-[12px] border border-[#E9EBEC] bg-white text-[0.8125rem]">
					</label>
					<label class="text-[0.75rem] text-[#737373]">Risoluzione
						<input v-model.number="extraRules.volume_resolution" type="number" min="0.0001" step="0.001" class="mt-[4px] w-full h-[38px] px-[10px] rounded-[12px] border border-[#E9EBEC] bg-white text-[0.8125rem]">
					</label>
				</div>
				<p class="text-[0.75rem] text-[#4F5D75]">Preview: {{ extraRuleExamples.firstVolumeFrom.toFixed(3) }}-{{ extraRuleExamples.firstVolumeTo.toFixed(3) }} / {{ extraRuleExamples.secondVolumeFrom.toFixed(3) }}-{{ extraRuleExamples.secondVolumeTo.toFixed(3) }}</p>
			</div>

			<div class="space-y-[12px] p-[14px] rounded-[12px] border border-[#E9EBEC] bg-[#FAFBFC]">
				<h3 class="text-[0.875rem] font-semibold text-[#252B42]">Incrementi oltre 7ª fascia</h3>
				<div class="grid grid-cols-1 tablet:grid-cols-2 gap-[10px]">
					<label class="text-[0.75rem] text-[#737373]">Base prezzo extra
						<select v-model="extraRules.base_price_cents_mode" class="mt-[4px] w-full h-[38px] px-[10px] rounded-[12px] border border-[#E9EBEC] bg-white text-[0.8125rem]">
							<option value="last_band_effective">Ultima fascia effettiva</option>
							<option value="manual">Manuale</option>
						</select>
					</label>
					<label class="text-[0.75rem] text-[#737373]">Incremento fisso per ogni fascia extra (&euro;)
						<input
							:value="(Number(extraRules.increment_cents || 0) / 100).toFixed(2).replace('.', ',')"
							@input="extraRules.increment_cents = Math.max(0, euroToCents($event.target.value) ?? 0)"
							type="text"
							class="mt-[4px] w-full h-[38px] px-[10px] rounded-[12px] border border-[#E9EBEC] bg-white text-[0.8125rem]">
					</label>
				</div>
				<label v-if="extraRules.base_price_cents_mode === 'manual'" class="text-[0.75rem] text-[#737373]">Prezzo base extra manuale (&euro;)
					<input
						:value="extraRules.base_price_cents_manual == null ? '' : (Number(extraRules.base_price_cents_manual || 0) / 100).toFixed(2).replace('.', ',')"
						@input="extraRules.base_price_cents_manual = euroToCents($event.target.value)"
						type="text"
						class="mt-[4px] w-full h-[38px] px-[10px] rounded-[12px] border border-[#E9EBEC] bg-white text-[0.8125rem]">
				</label>
			</div>

			<div class="p-[14px] rounded-[12px] border border-[#D8E9F0] bg-[#F4FAFC]">
				<h3 class="text-[0.875rem] font-semibold text-[#095866] mb-[10px]">Casi rapidi</h3>
				<div class="overflow-hidden">
					<table class="w-full min-w-[450px] text-[0.75rem]">
						<thead>
							<tr class="text-left text-[#67778E] border-b border-[#D8E9F0]">
								<th class="py-[6px]">Caso</th>
								<th class="py-[6px]">Peso</th>
								<th class="py-[6px]">Volume</th>
								<th class="py-[6px]">Prezzo peso</th>
								<th class="py-[6px]">Prezzo volume</th>
								<th class="py-[6px]">Totale (MAX)</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="row in pricingPreviewCases" :key="row.id" class="border-b border-[#EAF2F5] last:border-0 text-[#24344D]">
								<td class="py-[7px] font-semibold">{{ row.label }}</td>
								<td class="py-[7px]">{{ row.weight }}</td>
								<td class="py-[7px]">{{ row.volume }}</td>
								<td class="py-[7px]">{{ row.weightPriceLabel }}</td>
								<td class="py-[7px]">{{ row.volumePriceLabel }}</td>
								<td class="py-[7px] font-bold text-[#095866]">{{ row.totalLabel }}</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

	<!-- Supplementi CAP -->
	<div class="rounded-[12px] p-[20px] tablet:p-[24px] desktop:p-[32px] border border-[#E9EBEC]">
		<div class="flex flex-wrap items-center justify-between gap-[12px] mb-[16px]">
			<div>
				<h2 class="text-[1.125rem] font-bold text-[#252B42] mb-[4px]">Supplementi CAP</h2>
				<p class="text-[0.75rem] text-[#737373]">Prefisso CAP + importo + applicazione (origine / destinazione / entrambi).</p>
			</div>
			<button type="button" class="px-[14px] py-[8px] rounded-[999px] bg-[#095866] text-white text-[0.8125rem] font-medium hover:bg-[#074a56] cursor-pointer" @click="addSupplement">
				Aggiungi supplemento
			</button>
		</div>

		<div v-if="!supplementRules.length" class="p-[14px] rounded-[12px] border border-dashed border-[#E9EBEC] text-[#6A7486] text-[0.8125rem]">
			Nessun supplemento attivo. Aggiungi una regola se necessario.
		</div>

		<div v-else class="space-y-[10px]">
			<AdminSupplementRow
				v-for="(rule, idx) in supplementRules"
				:key="rule.id || idx"
				:rule="rule"
				:supplement-amount-to-euro="supplementAmountToEuro"
				:update-supplement-amount-from-euro="updateSupplementAmountFromEuro"
				@remove="removeSupplement(idx)" />
		</div>
	</div>
</template>
