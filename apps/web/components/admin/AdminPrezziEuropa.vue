<script setup>
defineProps({
	europePricing: { type: Object, required: true },
	filteredEuropeBands: { type: Array, required: true },
	compactEuropeView: { type: Boolean, required: true },
	centsToEuro: { type: Function, required: true },
	updateEuropeRateAmountFromEuro: { type: Function, required: true },
	toggleEuropeRateQuote: { type: Function, required: true },
});

const rateColumns = [
	{ key: 'country_name', label: 'Paese' },
	{ key: 'price', label: 'Prezzo' },
	{ key: 'status', label: 'Stato' },
];

const formatPrice = (priceCents) =>
	priceCents == null ? '' : (Number(priceCents || 0) / 100).toFixed(2).replace('.', ',');
</script>

<template>
	<SfCard padding="md" title="Europa monocollo" description="Listino Italia → Europa. Un solo collo per spedizione, quantita sempre 1.">
		<template #actions>
			<div class="flex flex-wrap gap-2 text-xs">
				<SfBadge tone="primary">Origine IT</SfBadge>
				<SfBadge tone="primary">Max colli 1</SfBadge>
				<SfBadge tone="primary">Q.ta per collo 1</SfBadge>
			</div>
		</template>

		<div class="space-y-6">
			<SfEmptyState
				v-if="!filteredEuropeBands.length"
				variant="compact"
				title="Nessun paese trovato"
				description="Nessun paese trovato con i filtri attuali." />

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
						<SfBadge tone="primary">{{ band.rates.length }} paesi</SfBadge>
						<SfBadge tone="success">{{ band.activeCount }} attivi</SfBadge>
						<SfBadge tone="warning">{{ band.quoteCount }} preventivo</SfBadge>
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
							<SfBadge :tone="rate.quote_required ? 'warning' : 'primary'">
								{{ rate.quote_required ? 'Manuale' : 'Attivo' }}
							</SfBadge>
						</div>
						<SfInput
							type="text"
							size="sm"
							:model-value="formatPrice(rate.price_cents)"
							:disabled="rate.quote_required"
							placeholder="0,00"
							@update:model-value="(v) => updateEuropeRateAmountFromEuro(rate, v)" />
					</div>
				</div>

				<UTable
					v-else
					:rows="band.rates"
					:columns="rateColumns"
					:ui="{
						wrapper: 'overflow-x-auto',
						thead: 'bg-brand-card',
						th: { base: 'text-left text-brand-text-secondary text-sm font-semibold px-4 py-2.5' },
						td: { base: 'px-4 py-2.5 text-sm border-b border-brand-border' },
					}">
					<template #country_name-data="{ row }">
						<span class="font-semibold text-brand-text">{{ row.country_name }}</span>
						<span class="text-brand-text-muted font-medium"> ({{ row.country_code }})</span>
					</template>
					<template #price-data="{ row }">
						<div class="w-[120px]">
							<SfInput
								type="text"
								size="sm"
								:model-value="formatPrice(row.price_cents)"
								:disabled="row.quote_required"
								placeholder="0,00"
								@update:model-value="(v) => updateEuropeRateAmountFromEuro(row, v)" />
						</div>
					</template>
					<template #status-data="{ row }">
						<button
							type="button"
							:class="row.quote_required ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-brand-soft-bg text-brand-primary border-brand-soft-border'"
							class="inline-flex items-center gap-1.5 px-3 py-2 rounded-pill border text-xs font-medium cursor-pointer"
							@click="toggleEuropeRateQuote(row)">
							{{ row.quote_required ? 'Preventivo manuale' : 'Prezzo attivo' }}
						</button>
					</template>
				</UTable>
			</div>
		</div>
	</SfCard>
</template>
