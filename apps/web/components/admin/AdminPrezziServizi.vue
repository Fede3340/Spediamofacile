<script setup>
const props = defineProps({
	servicePricingEntries: { type: Array, required: true },
	automaticSupplementEntries: { type: Array, required: true },
	operationalFeeEntries: { type: Array, required: true },
	filteredServiceEntries: { type: Array, required: true },
	// Functions
	euroToCents: { type: Function, required: true },
	formatApplicationLabel: { type: Function, required: true },
	keyedRuleAmountToEuro: { type: Function, required: true },
	updateKeyedRuleAmountFromEuro: { type: Function, required: true },
	keyedRuleMinFeeToEuro: { type: Function, required: true },
	updateKeyedRuleMinFeeFromEuro: { type: Function, required: true },
	updateArrayField: { type: Function, required: true },
	addTierRow: { type: Function, required: true },
	removeTierRow: { type: Function, required: true },
});
</script>

<template>
	<div class="space-y-[18px]">
		<!-- Stats cards -->
		<div class="grid grid-cols-1 desktop:grid-cols-3 gap-[16px]">
			<div class="rounded-[16px] border border-[#DFE2E7] bg-white p-[18px] shadow-sm">
				<p class="text-[0.75rem] font-semibold uppercase tracking-[0.08em] text-[var(--color-brand-text-secondary)] mb-[8px]">Servizi utente</p>
				<p class="text-[1.5rem] font-bold text-[var(--color-brand-primary)]">{{ servicePricingEntries.length }}</p>
				<p class="text-[0.8125rem] text-[var(--color-brand-text-secondary)] mt-[6px]">Prezzi visibili nel flusso utente e nel riepilogo.</p>
			</div>
			<div class="rounded-[16px] border border-[#DFE2E7] bg-white p-[18px] shadow-sm">
				<p class="text-[0.75rem] font-semibold uppercase tracking-[0.08em] text-[#A05D28] mb-[8px]">Supplementi automatici</p>
				<p class="text-[1.5rem] font-bold text-[var(--color-brand-primary)]">{{ automaticSupplementEntries.length }}</p>
				<p class="text-[0.8125rem] text-[#7C5A46] mt-[6px]">Regole che scattano da destinazione, forma collo o punto BRT.</p>
			</div>
			<div class="rounded-[16px] border border-[#DFE2E7] bg-white p-[18px] shadow-sm">
				<p class="text-[0.75rem] font-semibold uppercase tracking-[0.08em] text-[var(--color-brand-text-secondary)] mb-[8px]">Fee operative</p>
				<p class="text-[1.5rem] font-bold text-[var(--color-brand-text)]">{{ operationalFeeEntries.length }}</p>
				<p class="text-[0.8125rem] text-[var(--color-brand-text-secondary)] mt-[6px]">Costi gestionali come giacenza, separati dalle scelte utente.</p>
			</div>
		</div>

		<!-- Rules list -->
		<div class="space-y-[16px]">
			<div
				v-for="entry in filteredServiceEntries"
				:key="`${entry.section}-${entry.key}`"
				class="rounded-[12px] border-[1.5px] border-[#DFE2E7] bg-white p-[16px] tablet:p-[18px] desktop:p-[20px] shadow-sm overflow-hidden">
				<div class="flex flex-col gap-[16px] desktop:flex-row desktop:items-start desktop:justify-between">
					<div class="space-y-[8px] max-w-[760px]">
						<div class="flex flex-wrap items-center gap-[8px]">
							<span class="inline-flex items-center px-[10px] py-[5px] rounded-full bg-[#F4FAFC] text-[var(--color-brand-primary)] text-[0.75rem] font-semibold border border-[#D8E9F0]">
								{{ entry.section === 'service_pricing' ? 'Servizio utente' : (entry.section === 'automatic_supplements' ? 'Supplemento automatico' : 'Fee operativa') }}
							</span>
							<span class="inline-flex items-center px-[10px] py-[5px] rounded-full bg-[#F8FAFC] text-[var(--color-brand-text-secondary)] text-[0.75rem] font-medium border border-[#E5EAF0]">
								{{ formatApplicationLabel(entry.rule.application) }}
							</span>
						</div>
						<div>
							<h3 class="text-[1rem] font-bold text-[var(--color-brand-text)]">{{ entry.rule.label }}</h3>
							<p class="text-[0.875rem] text-[var(--color-brand-text-secondary)]">{{ entry.rule.description }}</p>
						</div>
					</div>

					<button
						type="button"
						role="switch"
						:aria-checked="entry.rule.enabled ? 'true' : 'false'"
						:aria-label="`Attiva regola ${entry.rule.name || 'servizio'}`"
						@click="entry.rule.enabled = !entry.rule.enabled"
						:class="entry.rule.enabled ? 'bg-[var(--color-brand-primary)]' : 'bg-[#C8CCD0]'"
						class="relative inline-flex h-[32px] w-[56px] items-center rounded-full transition-colors cursor-pointer shrink-0">
						<span
							:class="entry.rule.enabled ? 'translate-x-[28px]' : 'translate-x-[2px]'"
							class="inline-block h-[26px] w-[26px] transform rounded-full bg-white transition-transform shadow-sm" />
					</button>
				</div>

				<!-- Price fields -->
				<div class="mt-[16px] grid grid-cols-1 desktop:grid-cols-2 gap-[16px]">
					<label v-if="entry.rule.pricing_type === 'fixed' || entry.rule.price_cents != null" class="text-[0.75rem] text-[var(--color-brand-text-secondary)]">
						Prezzo / fee (&euro;)
						<input :value="keyedRuleAmountToEuro(entry.rule)" @input="updateKeyedRuleAmountFromEuro(entry.rule, $event.target.value)" type="text" class="mt-[4px] w-full h-[42px] px-[12px] rounded-[12px] border border-[#DFE2E7] bg-white text-[0.875rem] text-[var(--color-brand-text)]">
					</label>
					<label v-if="entry.rule.pricing_type === 'threshold_percentage'" class="text-[0.75rem] text-[var(--color-brand-text-secondary)]">
						Soglia (&euro;)
						<input v-model.number="entry.rule.threshold_amount_eur" type="number" min="0" step="0.01" class="mt-[4px] w-full h-[42px] px-[12px] rounded-[12px] border border-[#DFE2E7] bg-white text-[0.875rem] text-[var(--color-brand-text)]">
					</label>
					<label v-if="entry.rule.pricing_type === 'threshold_percentage'" class="text-[0.75rem] text-[var(--color-brand-text-secondary)]">
						Minimo fisso (&euro;)
						<input :value="keyedRuleMinFeeToEuro(entry.rule)" @input="updateKeyedRuleMinFeeFromEuro(entry.rule, $event.target.value)" type="text" class="mt-[4px] w-full h-[42px] px-[12px] rounded-[12px] border border-[#DFE2E7] bg-white text-[0.875rem] text-[var(--color-brand-text)]">
					</label>
					<label v-if="entry.rule.pricing_type === 'threshold_percentage'" class="text-[0.75rem] text-[var(--color-brand-text-secondary)]">
						Percentuale (%)
						<input v-model.number="entry.rule.percentage_rate" type="number" min="0" step="0.01" class="mt-[4px] w-full h-[42px] px-[12px] rounded-[12px] border border-[#DFE2E7] bg-white text-[0.875rem] text-[var(--color-brand-text)]">
					</label>
					<label v-if="entry.rule.max_weight_kg != null" class="text-[0.75rem] text-[var(--color-brand-text-secondary)]">
						Peso massimo (kg)
						<input v-model.number="entry.rule.max_weight_kg" type="number" min="0" step="0.01" class="mt-[4px] w-full h-[42px] px-[12px] rounded-[12px] border border-[#DFE2E7] bg-white text-[0.875rem] text-[var(--color-brand-text)]">
					</label>
					<label v-if="entry.rule.threshold_cm != null" class="text-[0.75rem] text-[var(--color-brand-text-secondary)]">
						Soglia lato (cm)
						<input v-model.number="entry.rule.threshold_cm" type="number" min="0" step="1" class="mt-[4px] w-full h-[42px] px-[12px] rounded-[12px] border border-[#DFE2E7] bg-white text-[0.875rem] text-[var(--color-brand-text)]">
					</label>
					<label v-if="entry.rule.longest_side_threshold_cm != null" class="text-[0.75rem] text-[var(--color-brand-text-secondary)]">
						Lato lungo oltre (cm)
						<input v-model.number="entry.rule.longest_side_threshold_cm" type="number" min="0" step="1" class="mt-[4px] w-full h-[42px] px-[12px] rounded-[12px] border border-[#DFE2E7] bg-white text-[0.875rem] text-[var(--color-brand-text)]">
					</label>
					<label v-if="entry.rule.girth_threshold_cm != null" class="text-[0.75rem] text-[var(--color-brand-text-secondary)]">
						Soglia perimetro secondario (cm)
						<input v-model.number="entry.rule.girth_threshold_cm" type="number" min="0" step="1" class="mt-[4px] w-full h-[42px] px-[12px] rounded-[12px] border border-[#DFE2E7] bg-white text-[0.875rem] text-[var(--color-brand-text)]">
					</label>
					<label v-if="entry.rule.min_longest_side_cm != null" class="text-[0.75rem] text-[var(--color-brand-text-secondary)]">
						Lunghezza minima (cm)
						<input v-model.number="entry.rule.min_longest_side_cm" type="number" min="0" step="1" class="mt-[4px] w-full h-[42px] px-[12px] rounded-[12px] border border-[#DFE2E7] bg-white text-[0.875rem] text-[var(--color-brand-text)]">
					</label>
					<label v-if="entry.rule.max_secondary_side_cm != null" class="text-[0.75rem] text-[var(--color-brand-text-secondary)]">
						Lato secondario max (cm)
						<input v-model.number="entry.rule.max_secondary_side_cm" type="number" min="0" step="1" class="mt-[4px] w-full h-[42px] px-[12px] rounded-[12px] border border-[#DFE2E7] bg-white text-[0.875rem] text-[var(--color-brand-text)]">
					</label>
				</div>

				<!-- Tiers -->
				<div v-if="entry.rule.tiers?.length" class="mt-[16px] rounded-[16px] border border-[#E9EEF2] bg-[#FBFCFD] p-[14px]">
					<div class="flex items-center justify-between gap-[10px] mb-[10px]">
						<h4 class="text-[0.8125rem] font-semibold text-[var(--color-brand-text)]">Scaglioni peso</h4>
						<button type="button" class="px-[12px] py-[7px] rounded-full bg-[var(--color-brand-primary)] text-white text-[0.75rem] font-medium cursor-pointer" @click="addTierRow(entry.rule)">Aggiungi soglia</button>
					</div>
					<div class="space-y-[8px]">
						<div v-for="(tier, tierIndex) in entry.rule.tiers" :key="`${entry.key}-tier-${tierIndex}`" class="grid grid-cols-1 tablet:grid-cols-[1fr_1fr_auto] gap-[8px] items-end">
							<label class="text-[0.75rem] text-[var(--color-brand-text-secondary)]">
								Fino a kg
								<input v-model.number="tier.up_to_kg" type="number" min="0" step="0.01" placeholder="senza limite" class="mt-[4px] w-full h-[40px] px-[12px] rounded-[12px] border border-[#DFE2E7] bg-white text-[0.8125rem] text-[var(--color-brand-text)]">
							</label>
							<label class="text-[0.75rem] text-[var(--color-brand-text-secondary)]">
								Prezzo (&euro;)
								<input :value="formatEuro(toEuros(tier.price_cents || 0))" @input="tier.price_cents = euroToCents($event.target.value) || 0" type="text" class="mt-[4px] w-full h-[40px] px-[12px] rounded-[12px] border border-[#DFE2E7] bg-white text-[0.8125rem] text-[var(--color-brand-text)]">
							</label>
							<button type="button" class="h-[40px] px-[12px] rounded-[16px] border border-red-200 text-red-600 text-[0.75rem] font-medium hover:bg-red-50 cursor-pointer" @click="removeTierRow(entry.rule, tierIndex)">Rimuovi</button>
						</div>
					</div>
				</div>

				<!-- Array fields for automatic supplements -->
				<div class="mt-[16px] grid grid-cols-1 desktop:grid-cols-2 gap-[16px]" v-if="entry.section === 'automatic_supplements'">
					<label v-if="entry.rule.province_codes?.length || entry.key === 'calabria_sardegna_sicilia' || entry.key === 'brt_point_csi'" class="text-[0.75rem] text-[var(--color-brand-text-secondary)]">
						Province
						<input :value="(entry.rule.province_codes || []).join(', ')" @input="updateArrayField(entry.rule, 'province_codes', $event.target.value, { uppercase: true })" type="text" class="mt-[4px] w-full h-[42px] px-[12px] rounded-[12px] border border-[#DFE2E7] bg-white text-[0.8125rem] text-[var(--color-brand-text)]">
					</label>
					<label v-if="entry.rule.country_codes?.length" class="text-[0.75rem] text-[var(--color-brand-text-secondary)]">
						Paesi
						<input :value="(entry.rule.country_codes || []).join(', ')" @input="updateArrayField(entry.rule, 'country_codes', $event.target.value, { uppercase: true })" type="text" class="mt-[4px] w-full h-[42px] px-[12px] rounded-[12px] border border-[#DFE2E7] bg-white text-[0.8125rem] text-[var(--color-brand-text)]">
					</label>
					<label v-if="entry.rule.keyword_list?.length" class="text-[0.75rem] text-[var(--color-brand-text-secondary)] desktop:col-span-2">
						Keyword località
						<input :value="(entry.rule.keyword_list || []).join(', ')" @input="updateArrayField(entry.rule, 'keyword_list', $event.target.value)" type="text" class="mt-[4px] w-full h-[42px] px-[12px] rounded-[12px] border border-[#DFE2E7] bg-white text-[0.8125rem] text-[var(--color-brand-text)]">
					</label>
					<label v-if="entry.rule.flag_keys?.length" class="text-[0.75rem] text-[var(--color-brand-text-secondary)]">
						Flag chiave
						<input :value="(entry.rule.flag_keys || []).join(', ')" @input="updateArrayField(entry.rule, 'flag_keys', $event.target.value)" type="text" class="mt-[4px] w-full h-[42px] px-[12px] rounded-[12px] border border-[#DFE2E7] bg-white text-[0.8125rem] text-[var(--color-brand-text)]">
					</label>
					<label v-if="entry.rule.delivery_modes?.length" class="text-[0.75rem] text-[var(--color-brand-text-secondary)]">
						Delivery mode
						<input :value="(entry.rule.delivery_modes || []).join(', ')" @input="updateArrayField(entry.rule, 'delivery_modes', $event.target.value)" type="text" class="mt-[4px] w-full h-[42px] px-[12px] rounded-[12px] border border-[#DFE2E7] bg-white text-[0.8125rem] text-[var(--color-brand-text)]">
					</label>
				</div>

				<label class="block mt-[16px] text-[0.75rem] text-[var(--color-brand-text-secondary)]">
					Nota operativa
					<textarea v-model="entry.rule.note" rows="2" class="mt-[4px] w-full px-[12px] py-[10px] rounded-[12px] border border-[#DFE2E7] bg-white text-[0.8125rem] text-[var(--color-brand-text)] resize-y"></textarea>
				</label>
			</div>
		</div>
	</div>
</template>
