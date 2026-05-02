<script setup>
defineProps({
	servicePricingEntries: { type: Array, required: true },
	automaticSupplementEntries: { type: Array, required: true },
	operationalFeeEntries: { type: Array, required: true },
	filteredServiceEntries: { type: Array, required: true },
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

const inputClass = 'mt-1 w-full h-10 px-3 rounded-control border border-brand-border bg-brand-card text-sm text-brand-text focus:outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20';
const labelClass = 'text-xs text-brand-text-secondary';
</script>

<template>
	<div class="space-y-4">
		<div class="grid grid-cols-1 desktop:grid-cols-3 gap-4">
			<SfStatCard label="Servizi utente" :value="servicePricingEntries.length" tone="primary" icon="mdi:cog-outline" trend-label="Prezzi visibili nel flusso utente." />
			<SfStatCard label="Supplementi automatici" :value="automaticSupplementEntries.length" tone="warning" icon="mdi:flash" trend-label="Regole automatiche destinazione/forma." />
			<SfStatCard label="Fee operative" :value="operationalFeeEntries.length" tone="primary" icon="mdi:cash-multiple" trend-label="Costi gestionali tipo giacenza." />
		</div>

		<div class="space-y-4">
			<div
				v-for="entry in filteredServiceEntries"
				:key="`${entry.section}-${entry.key}`"
				class="rounded-card border border-brand-border bg-brand-card p-4 tablet:p-5 desktop:p-6 shadow-sf-sm overflow-hidden">
				<div class="flex flex-col gap-4 desktop:flex-row desktop:items-start desktop:justify-between">
					<div class="space-y-2 max-w-[760px]">
						<div class="flex flex-wrap items-center gap-2">
							<span class="inline-flex items-center px-2.5 py-1 rounded-pill bg-brand-soft-bg text-brand-primary text-xs font-semibold border border-brand-soft-border">
								{{ entry.section === 'service_pricing' ? 'Servizio utente' : (entry.section === 'automatic_supplements' ? 'Supplemento automatico' : 'Fee operativa') }}
							</span>
							<span class="inline-flex items-center px-2.5 py-1 rounded-pill bg-brand-bg-alt text-brand-text-secondary text-xs font-medium border border-brand-border">
								{{ formatApplicationLabel(entry.rule.application) }}
							</span>
						</div>
						<div>
							<h3 class="text-base font-bold text-brand-text">{{ entry.rule.label }}</h3>
							<p class="text-sm text-brand-text-secondary">{{ entry.rule.description }}</p>
						</div>
					</div>

					<button
						type="button"
						role="switch"
						:aria-checked="entry.rule.enabled ? 'true' : 'false'"
						:aria-label="`Attiva regola ${entry.rule.name || 'servizio'}`"
						:class="entry.rule.enabled ? 'bg-brand-primary' : 'bg-brand-border'"
						class="relative inline-flex h-8 w-14 items-center rounded-full transition-colors cursor-pointer shrink-0"
						@click="entry.rule.enabled = !entry.rule.enabled">
						<span
							:class="entry.rule.enabled ? 'translate-x-[28px]' : 'translate-x-[2px]'"
							class="inline-block h-[26px] w-[26px] transform rounded-full bg-white transition-transform shadow-sm" />
					</button>
				</div>

				<div class="mt-4 grid grid-cols-1 desktop:grid-cols-2 gap-4">
					<label v-if="entry.rule.pricing_type === 'fixed' || entry.rule.price_cents != null" :class="labelClass">
						Prezzo / fee (€)
						<input :value="keyedRuleAmountToEuro(entry.rule)" type="text" :class="inputClass" @input="updateKeyedRuleAmountFromEuro(entry.rule, $event.target.value)">
					</label>
					<label v-if="entry.rule.pricing_type === 'threshold_percentage'" :class="labelClass">
						Soglia (€)
						<input v-model.number="entry.rule.threshold_amount_eur" type="number" min="0" step="0.01" :class="inputClass">
					</label>
					<label v-if="entry.rule.pricing_type === 'threshold_percentage'" :class="labelClass">
						Minimo fisso (€)
						<input :value="keyedRuleMinFeeToEuro(entry.rule)" type="text" :class="inputClass" @input="updateKeyedRuleMinFeeFromEuro(entry.rule, $event.target.value)">
					</label>
					<label v-if="entry.rule.pricing_type === 'threshold_percentage'" :class="labelClass">
						Percentuale (%)
						<input v-model.number="entry.rule.percentage_rate" type="number" min="0" step="0.01" :class="inputClass">
					</label>
					<label v-if="entry.rule.max_weight_kg != null" :class="labelClass">
						Peso massimo (kg)
						<input v-model.number="entry.rule.max_weight_kg" type="number" min="0" step="0.01" :class="inputClass">
					</label>
					<label v-if="entry.rule.threshold_cm != null" :class="labelClass">
						Soglia lato (cm)
						<input v-model.number="entry.rule.threshold_cm" type="number" min="0" step="1" :class="inputClass">
					</label>
					<label v-if="entry.rule.longest_side_threshold_cm != null" :class="labelClass">
						Lato lungo oltre (cm)
						<input v-model.number="entry.rule.longest_side_threshold_cm" type="number" min="0" step="1" :class="inputClass">
					</label>
					<label v-if="entry.rule.girth_threshold_cm != null" :class="labelClass">
						Soglia perimetro secondario (cm)
						<input v-model.number="entry.rule.girth_threshold_cm" type="number" min="0" step="1" :class="inputClass">
					</label>
					<label v-if="entry.rule.min_longest_side_cm != null" :class="labelClass">
						Lunghezza minima (cm)
						<input v-model.number="entry.rule.min_longest_side_cm" type="number" min="0" step="1" :class="inputClass">
					</label>
					<label v-if="entry.rule.max_secondary_side_cm != null" :class="labelClass">
						Lato secondario max (cm)
						<input v-model.number="entry.rule.max_secondary_side_cm" type="number" min="0" step="1" :class="inputClass">
					</label>
				</div>

				<div v-if="entry.rule.tiers?.length" class="mt-4 rounded-card border border-brand-border bg-brand-bg-alt p-3.5">
					<div class="flex items-center justify-between gap-2.5 mb-2.5">
						<h4 class="text-sm font-semibold text-brand-text">Scaglioni peso</h4>
						<SfButton size="sm" @click="addTierRow(entry.rule)">Aggiungi soglia</SfButton>
					</div>
					<div class="space-y-2">
						<div v-for="(tier, tierIndex) in entry.rule.tiers" :key="`${entry.key}-tier-${tierIndex}`" class="grid grid-cols-1 tablet:grid-cols-[1fr_1fr_auto] gap-2 items-end">
							<label :class="labelClass">
								Fino a kg
								<input v-model.number="tier.up_to_kg" type="number" min="0" step="0.01" placeholder="senza limite" :class="inputClass">
							</label>
							<label :class="labelClass">
								Prezzo (€)
								<input :value="(Number(tier.price_cents || 0) / 100).toFixed(2).replace('.', ',')" type="text" :class="inputClass" @input="tier.price_cents = euroToCents($event.target.value) || 0">
							</label>
							<button type="button" class="h-10 px-3 rounded-card border border-red-200 text-red-600 text-xs font-medium hover:bg-red-50 cursor-pointer" @click="removeTierRow(entry.rule, tierIndex)">Rimuovi</button>
						</div>
					</div>
				</div>

				<div v-if="entry.section === 'automatic_supplements'" class="mt-4 grid grid-cols-1 desktop:grid-cols-2 gap-4">
					<label v-if="entry.rule.province_codes?.length || entry.key === 'calabria_sardegna_sicilia' || entry.key === 'brt_point_csi'" :class="labelClass">
						Province
						<input :value="(entry.rule.province_codes || []).join(', ')" type="text" :class="inputClass" @input="updateArrayField(entry.rule, 'province_codes', $event.target.value, { uppercase: true })">
					</label>
					<label v-if="entry.rule.country_codes?.length" :class="labelClass">
						Paesi
						<input :value="(entry.rule.country_codes || []).join(', ')" type="text" :class="inputClass" @input="updateArrayField(entry.rule, 'country_codes', $event.target.value, { uppercase: true })">
					</label>
					<label v-if="entry.rule.keyword_list?.length" :class="[labelClass, 'desktop:col-span-2']">
						Keyword localita
						<input :value="(entry.rule.keyword_list || []).join(', ')" type="text" :class="inputClass" @input="updateArrayField(entry.rule, 'keyword_list', $event.target.value)">
					</label>
					<label v-if="entry.rule.flag_keys?.length" :class="labelClass">
						Flag chiave
						<input :value="(entry.rule.flag_keys || []).join(', ')" type="text" :class="inputClass" @input="updateArrayField(entry.rule, 'flag_keys', $event.target.value)">
					</label>
					<label v-if="entry.rule.delivery_modes?.length" :class="labelClass">
						Delivery mode
						<input :value="(entry.rule.delivery_modes || []).join(', ')" type="text" :class="inputClass" @input="updateArrayField(entry.rule, 'delivery_modes', $event.target.value)">
					</label>
				</div>

				<label class="block mt-4 text-xs text-brand-text-secondary">
					Nota operativa
					<textarea v-model="entry.rule.note" rows="2" class="mt-1 w-full px-3 py-2.5 rounded-control border border-brand-border bg-brand-card text-sm text-brand-text resize-y focus:outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20" />
				</label>
			</div>
		</div>
	</div>
</template>
