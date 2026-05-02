<script setup>
defineProps({
	europePricing: { type: Object, required: true },
	filteredEuropeBands: { type: Array, required: true },
	compactEuropeView: { type: Boolean, required: true },
	centsToEuro: { type: Function, required: true },
	updateEuropeRateAmountFromEuro: { type: Function, required: true },
	toggleEuropeRateQuote: { type: Function, required: true },
});
</script>

<template>
	<SfCard padding="md">
		<div class="flex flex-wrap items-start justify-between gap-4 mb-4">
			<div class="space-y-1">
				<h2 class="text-lg font-bold text-brand-text">Europa monocollo</h2>
				<p class="text-xs text-brand-text-secondary">Listino Italia → Europa. Un solo collo per spedizione, quantita sempre 1.</p>
			</div>
			<div class="flex flex-wrap gap-2 text-xs">
				<span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-pill bg-brand-soft-bg text-brand-primary border border-brand-soft-border">Origine IT</span>
				<span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-pill bg-brand-soft-bg text-brand-primary border border-brand-soft-border">Max colli 1</span>
				<span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-pill bg-brand-soft-bg text-brand-primary border border-brand-soft-border">Q.ta per collo 1</span>
			</div>
		</div>

		<div class="space-y-4">
			<div v-if="!filteredEuropeBands.length" class="p-4 rounded-card border border-dashed border-brand-border text-brand-text-secondary text-sm">
				Nessun paese trovato con i filtri attuali.
			</div>
			<div
				v-for="band in filteredEuropeBands"
				:key="band.id"
				class="rounded-card border border-brand-border bg-brand-bg-alt overflow-hidden">
				<div class="flex flex-wrap items-center justify-between gap-2.5 px-4 py-3.5 border-b border-brand-border bg-brand-card">
					<div>
						<h3 class="text-base font-bold text-brand-text">{{ band.label }}</h3>
						<p class="text-xs text-brand-text-secondary">
							Max {{ band.max_weight_kg }} kg · Max {{ Number(band.max_volume_m3).toFixed(3) }} m³ · Fattore volumetrico {{ band.volumetric_factor }}
						</p>
					</div>
					<div class="flex flex-wrap gap-2">
						<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-pill bg-brand-soft-bg text-brand-primary text-xs font-medium">
							{{ band.rates.length }} paesi
						</span>
						<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-pill bg-brand-success-bg text-brand-success-fg text-xs font-medium border border-brand-success/30">
							{{ band.activeCount }} attivi
						</span>
						<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-pill bg-amber-50 text-amber-700 text-xs font-medium border border-amber-200">
							{{ band.quoteCount }} preventivo
						</span>
					</div>
				</div>

				<div v-if="compactEuropeView" class="p-4 grid grid-cols-1 tablet:grid-cols-2 desktop:grid-cols-3 gap-2.5">
					<div
						v-for="rate in band.rates"
						:key="`${band.id}-${rate.country_code}-compact`"
						class="rounded-card border border-brand-border bg-brand-card px-3.5 py-3">
						<div class="flex items-start justify-between gap-2.5 mb-2">
							<div>
								<p class="text-sm font-semibold text-brand-text">{{ rate.country_name }}</p>
								<p class="text-xs text-brand-text-muted">{{ rate.country_code }}</p>
							</div>
							<span :class="rate.quote_required ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-brand-soft-bg text-brand-primary border-brand-soft-border'" class="inline-flex items-center px-2 py-1 rounded-pill border text-[0.6875rem] font-semibold">
								{{ rate.quote_required ? 'Manuale' : 'Attivo' }}
							</span>
						</div>
						<input
							:value="rate.price_cents == null ? '' : (Number(rate.price_cents || 0) / 100).toFixed(2).replace('.', ',')"
							:disabled="rate.quote_required"
							type="text"
							placeholder="0,00"
							class="w-full h-9 px-2.5 rounded-control border border-brand-border bg-brand-card text-brand-text disabled:bg-brand-bg-alt disabled:text-brand-text-muted focus:outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20"
							@input="updateEuropeRateAmountFromEuro(rate, $event.target.value)">
					</div>
				</div>

				<div v-else class="overflow-x-auto">
					<table class="w-full min-w-[760px] text-sm">
						<thead>
							<tr class="text-left text-brand-text-secondary border-b border-brand-border bg-brand-card">
								<th class="px-4 py-2.5 font-semibold">Paese</th>
								<th class="px-4 py-2.5 font-semibold">Prezzo</th>
								<th class="px-4 py-2.5 font-semibold">Stato</th>
							</tr>
						</thead>
						<tbody>
							<tr
								v-for="rate in band.rates"
								:key="`${band.id}-${rate.country_code}`"
								class="border-b border-brand-border last:border-0">
								<td class="px-4 py-2.5 font-semibold text-brand-text">
									{{ rate.country_name }}
									<span class="text-brand-text-muted font-medium">({{ rate.country_code }})</span>
								</td>
								<td class="px-4 py-2.5">
									<input
										:value="rate.price_cents == null ? '' : (Number(rate.price_cents || 0) / 100).toFixed(2).replace('.', ',')"
										:disabled="rate.quote_required"
										type="text"
										placeholder="0,00"
										class="w-[120px] h-9 px-2.5 rounded-control border border-brand-border bg-brand-card text-brand-text disabled:bg-brand-bg-alt disabled:text-brand-text-muted focus:outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20"
										@input="updateEuropeRateAmountFromEuro(rate, $event.target.value)">
								</td>
								<td class="px-4 py-2.5">
									<button
										type="button"
										:class="rate.quote_required ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-brand-soft-bg text-brand-primary border-brand-soft-border'"
										class="inline-flex items-center gap-1.5 px-3 py-2 rounded-pill border text-xs font-medium cursor-pointer"
										@click="toggleEuropeRateQuote(rate)">
										{{ rate.quote_required ? 'Preventivo manuale' : 'Prezzo attivo' }}
									</button>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</SfCard>
</template>
