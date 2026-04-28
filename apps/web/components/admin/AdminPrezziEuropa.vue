<script setup>
const props = defineProps({
	europePricing: { type: Object, required: true },
	filteredEuropeBands: { type: Array, required: true },
	compactEuropeView: { type: Boolean, required: true },
	// Functions
	centsToEuro: { type: Function, required: true },
	updateEuropeRateAmountFromEuro: { type: Function, required: true },
	toggleEuropeRateQuote: { type: Function, required: true },
});
</script>

<template>
	<div class="rounded-[16px] p-[20px] tablet:p-[24px] desktop:p-[32px] border border-[var(--color-brand-border)]">
		<div class="flex flex-wrap items-start justify-between gap-[16px] mb-[16px]">
			<div class="space-y-[4px]">
				<h2 class="text-[1.125rem] font-bold text-[var(--color-brand-text)]">Europa monocollo</h2>
				<p class="text-[0.75rem] text-[var(--color-brand-text-secondary)]">Listino Italia &rarr; Europa. Un solo collo per spedizione, quantità sempre 1.</p>
			</div>
			<div class="flex flex-wrap gap-[8px] text-[0.75rem]">
				<span class="inline-flex items-center gap-[6px] px-[10px] py-[6px] rounded-full bg-[#F4FAFC] text-[var(--color-brand-primary)] border border-[#D8E9F0]">Origine IT</span>
				<span class="inline-flex items-center gap-[6px] px-[10px] py-[6px] rounded-full bg-[#F4FAFC] text-[var(--color-brand-primary)] border border-[#D8E9F0]">Max colli 1</span>
				<span class="inline-flex items-center gap-[6px] px-[10px] py-[6px] rounded-full bg-[#F4FAFC] text-[var(--color-brand-primary)] border border-[#D8E9F0]">Q.tà per collo 1</span>
			</div>
		</div>

		<div class="space-y-[16px]">
			<div v-if="!filteredEuropeBands.length" class="p-[16px] rounded-[16px] border border-dashed border-[var(--color-brand-border)] text-[var(--color-brand-text-secondary)] text-[0.8125rem]">
				Nessun paese trovato con i filtri attuali.
			</div>
			<div
				v-for="band in filteredEuropeBands"
				:key="band.id"
				class="rounded-[12px] border-[1.5px] border-[#DFE2E7] bg-[#FAFBFC] overflow-hidden">
				<div class="flex flex-wrap items-center justify-between gap-[10px] px-[16px] py-[14px] border-b border-[var(--color-brand-border)] bg-white">
					<div>
						<h3 class="text-[0.9375rem] font-bold text-[var(--color-brand-text)]">{{ band.label }}</h3>
						<p class="text-[0.75rem] text-[var(--color-brand-text-secondary)]">
							Max {{ band.max_weight_kg }} kg &middot; Max {{ Number(band.max_volume_m3).toFixed(3) }} m&sup3; &middot; Fattore volumetrico {{ band.volumetric_factor }}
						</p>
					</div>
					<div class="flex flex-wrap gap-[8px]">
						<span class="inline-flex items-center gap-[6px] px-[10px] py-[5px] rounded-full bg-[#F0F7FA] text-[var(--color-brand-primary)] text-[0.75rem] font-medium">
							{{ band.rates.length }} paesi
						</span>
						<span class="inline-flex items-center gap-[6px] px-[10px] py-[5px] rounded-full bg-[#f0fdf4] text-[#0a8a7a] text-[0.75rem] font-medium border border-[#d1fae5]">
							{{ band.activeCount }} attivi
						</span>
						<span class="inline-flex items-center gap-[6px] px-[10px] py-[5px] rounded-full bg-amber-50 text-amber-700 text-[0.75rem] font-medium border border-amber-200">
							{{ band.quoteCount }} preventivo
						</span>
					</div>
				</div>

				<!-- Vista compatta -->
				<div v-if="compactEuropeView" class="p-[16px] grid grid-cols-1 tablet:grid-cols-2 desktop:grid-cols-3 gap-[10px]">
					<div
						v-for="rate in band.rates"
						:key="`${band.id}-${rate.country_code}-compact`"
						class="rounded-[16px] border border-[#E6EDF1] bg-white px-[14px] py-[12px]">
						<div class="flex items-start justify-between gap-[10px] mb-[8px]">
							<div>
								<p class="text-[0.875rem] font-semibold text-[var(--color-brand-text)]">{{ rate.country_name }}</p>
								<p class="text-[0.75rem] text-[var(--color-brand-text-muted)]">{{ rate.country_code }}</p>
							</div>
							<span :class="rate.quote_required ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-[#EDF6F8] text-[var(--color-brand-primary)] border-[#D8E9F0]'" class="inline-flex items-center px-[8px] py-[4px] rounded-full border text-[0.6875rem] font-semibold">
								{{ rate.quote_required ? 'Manuale' : 'Attivo' }}
							</span>
						</div>
						<input
							:value="rate.price_cents == null ? '' : (Number(rate.price_cents || 0) / 100).toFixed(2).replace('.', ',')"
							@input="updateEuropeRateAmountFromEuro(rate, $event.target.value)"
							:disabled="rate.quote_required"
							type="text"
							placeholder="0,00"
							class="w-full h-[38px] px-[10px] rounded-[12px] border-[1.5px] border-[#DFE2E7] bg-white text-[var(--color-brand-text)] disabled:bg-[var(--color-brand-bg-alt)] disabled:text-[var(--color-brand-text-muted)]">
					</div>
				</div>

				<!-- Vista tabella -->
				<div v-else class="overflow-hidden">
					<table class="w-full min-w-[760px] text-[0.8125rem]">
						<thead>
							<tr class="text-left text-[var(--color-brand-text-secondary)] border-b border-[var(--color-brand-border)] bg-white">
								<th class="px-[16px] py-[10px] font-semibold">Paese</th>
								<th class="px-[16px] py-[10px] font-semibold">Prezzo</th>
								<th class="px-[16px] py-[10px] font-semibold">Stato</th>
							</tr>
						</thead>
						<tbody>
							<tr
								v-for="rate in band.rates"
								:key="`${band.id}-${rate.country_code}`"
								class="border-b border-[#EEF2F4] last:border-0">
								<td class="px-[16px] py-[10px] font-semibold text-[var(--color-brand-text)]">
									{{ rate.country_name }}
									<span class="text-[var(--color-brand-text-muted)] font-medium">({{ rate.country_code }})</span>
								</td>
								<td class="px-[16px] py-[10px]">
									<input
										:value="rate.price_cents == null ? '' : (Number(rate.price_cents || 0) / 100).toFixed(2).replace('.', ',')"
										@input="updateEuropeRateAmountFromEuro(rate, $event.target.value)"
										:disabled="rate.quote_required"
										type="text"
										placeholder="0,00"
										class="w-[120px] h-[38px] px-[10px] rounded-[12px] border-[1.5px] border-[#DFE2E7] bg-white text-[var(--color-brand-text)] disabled:bg-[var(--color-brand-bg-alt)] disabled:text-[var(--color-brand-text-muted)]">
								</td>
								<td class="px-[16px] py-[10px]">
									<button
										type="button"
										@click="toggleEuropeRateQuote(rate)"
										:class="rate.quote_required ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-[#EDF6F8] text-[var(--color-brand-primary)] border-[#D8E9F0]'"
										class="inline-flex items-center gap-[6px] px-[12px] py-[8px] rounded-full border text-[0.75rem] font-medium cursor-pointer">
										{{ rate.quote_required ? 'Preventivo manuale' : 'Prezzo attivo' }}
									</button>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</template>
