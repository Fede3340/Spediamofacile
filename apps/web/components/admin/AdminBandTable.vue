<script setup>
defineProps({
	bands: { type: Array, required: true },
	bandType: { type: String, required: true },
	title: { type: String, required: true },
	subtitle: { type: String, default: '' },
	addLabel: { type: String, required: true },
	minMaxStep: { type: String, default: '1' },
	minWidth: { type: String, default: '700px' },
	editingCell: { type: [String, null], default: null },
	editValue: { type: String, default: '' },
	centsToEuro: { type: Function, required: true },
	effectivePrice: { type: Function, required: true },
	discountInfo: { type: Function, required: true },
	startEdit: { type: Function, required: true },
	confirmEdit: { type: Function, required: true },
	cancelEdit: { type: Function, required: true },
	toggleShowDiscount: { type: Function, required: true },
	addBand: { type: Function, required: true },
	removeBand: { type: Function, required: true },
	moveBand: { type: Function, required: true },
});

const emit = defineEmits(['update:editValue']);

const onEditInput = (event) => {
	emit('update:editValue', event.target.value);
};
</script>

<template>
	<SfCard padding="md">
		<h2 class="text-lg font-bold text-brand-text mb-1.5 flex items-center gap-2">
			<slot name="icon" />
			{{ title }}
		</h2>
		<p class="text-xs text-brand-text-secondary mb-5">{{ subtitle }}</p>

		<div v-if="!bands.length" class="text-center py-8 text-brand-text-secondary">
			<p>Nessuna fascia configurata.</p>
		</div>

		<div v-else class="overflow-x-auto">
			<table class="w-full text-sm" :style="{ minWidth }">
				<thead>
					<tr class="border-b border-brand-border text-left text-brand-text-secondary">
						<th class="pb-3 font-medium">#</th>
						<th class="pb-3 font-medium">Min</th>
						<th class="pb-3 font-medium">Max</th>
						<th class="pb-3 font-medium">Prezzo base</th>
						<th class="pb-3 font-medium">Prezzo scontato</th>
						<th class="pb-3 font-medium">Effettivo</th>
						<th class="pb-3 font-medium">Sconto %</th>
						<th class="pb-3 font-medium text-center">Visibile</th>
						<th class="pb-3 font-medium text-right">Azioni</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="(band, idx) in bands" :key="band.id || idx" :class="['border-b border-brand-border last:border-0', idx % 2 === 1 ? 'bg-brand-bg-alt' : '']">
						<td class="py-3.5 font-bold text-brand-text">{{ idx + 1 }}</td>
						<td class="py-3.5 text-brand-text">
							<input v-model.number="band.min_value" type="number" min="0" :step="minMaxStep" class="w-[86px] h-8 px-2 rounded-control border border-brand-border bg-brand-card text-sm">
						</td>
						<td class="py-3.5 text-brand-text">
							<input v-model.number="band.max_value" type="number" min="0" :step="minMaxStep" class="w-[86px] h-8 px-2 rounded-control border border-brand-border bg-brand-card text-sm">
						</td>
						<td class="py-3.5">
							<div v-if="editingCell === `${bandType}-${idx}-base_price`" class="flex items-center gap-1.5">
								<span class="text-brand-text-secondary">€</span>
								<input
									:id="`edit-${bandType}-${idx}-base_price`"
									:value="editValue"
									type="number"
									min="0"
									step="0.01"
									class="w-[100px] px-2.5 py-2 tablet:py-1.5 bg-brand-card border-2 border-brand-primary rounded-card text-base tablet:text-sm focus:outline-none"
									placeholder="0,00"
									@input="onEditInput"
									@keydown.enter="confirmEdit(bandType, idx, 'base_price')"
									@keydown.esc="cancelEdit()"
									@blur="confirmEdit(bandType, idx, 'base_price')">
							</div>
							<button v-else type="button" class="px-3 py-1.5 rounded-card text-sm font-semibold text-brand-text hover:bg-brand-soft-bg transition cursor-pointer border border-transparent hover:border-brand-soft-border" @click="startEdit(bandType, idx, 'base_price')">
								{{ centsToEuro(band.base_price) }}
							</button>
						</td>
						<td class="py-3.5">
							<div v-if="editingCell === `${bandType}-${idx}-discount_price`" class="flex items-center gap-1.5">
								<span class="text-brand-text-secondary">€</span>
								<input
									:id="`edit-${bandType}-${idx}-discount_price`"
									:value="editValue"
									type="number"
									min="0"
									step="0.01"
									class="w-[100px] px-2.5 py-2 tablet:py-1.5 bg-brand-card border-2 border-brand-primary rounded-card text-base tablet:text-sm focus:outline-none"
									placeholder="vuoto = usa base"
									@input="onEditInput"
									@keydown.enter="confirmEdit(bandType, idx, 'discount_price')"
									@keydown.esc="cancelEdit()"
									@blur="confirmEdit(bandType, idx, 'discount_price')">
							</div>
							<button v-else type="button" class="px-3 py-1.5 rounded-card text-sm text-brand-text-secondary hover:bg-brand-soft-bg transition cursor-pointer border border-transparent hover:border-brand-soft-border" @click="startEdit(bandType, idx, 'discount_price')">
								{{ band.discount_price != null ? centsToEuro(band.discount_price) : '-' }}
							</button>
						</td>
						<td class="py-3.5">
							<span class="font-semibold text-brand-primary text-base">{{ centsToEuro(effectivePrice(band)) }}</span>
						</td>
						<td class="py-3.5">
							<template v-if="discountInfo(band) !== null">
								<span v-if="discountInfo(band) > 0" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-control bg-brand-soft-bg text-brand-primary text-sm font-semibold border border-brand-soft-border">
									-{{ discountInfo(band) }}%
								</span>
								<span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-control bg-amber-50 text-amber-700 text-xs font-medium border border-amber-200">
									+{{ Math.abs(discountInfo(band)) }}% (aumento)
								</span>
							</template>
							<span v-else class="text-brand-text-muted">-</span>
						</td>
						<td class="py-3.5 text-center">
							<button
								type="button" role="switch"
								:aria-checked="band.show_discount ? 'true' : 'false'"
								:aria-label="`Mostra sconto per questa fascia: ${band.show_discount ? 'attivo' : 'disattivato'}`"
								:class="band.show_discount ? 'bg-brand-primary' : 'bg-brand-border'"
								class="relative inline-flex h-8 w-14 tablet:h-6 tablet:w-11 items-center rounded-full transition-colors cursor-pointer"
								@click="toggleShowDiscount(bandType, idx)">
								<span :class="band.show_discount ? 'translate-x-[28px] tablet:translate-x-[22px]' : 'translate-x-[2px]'" class="inline-block h-[26px] w-[26px] tablet:h-5 tablet:w-5 transform rounded-full bg-white transition-transform shadow-sm" />
							</button>
						</td>
						<td class="py-3.5">
							<div class="flex items-center justify-end gap-1.5">
								<button type="button" class="px-2 py-1 rounded-control border border-brand-border text-xs hover:bg-brand-soft-bg cursor-pointer" @click="moveBand(bandType, idx, -1)">↑</button>
								<button type="button" class="px-2 py-1 rounded-control border border-brand-border text-xs hover:bg-brand-soft-bg cursor-pointer" @click="moveBand(bandType, idx, 1)">↓</button>
								<button type="button" class="px-2 py-1 rounded-card border border-red-200 text-red-600 text-xs hover:bg-red-50 cursor-pointer" @click="removeBand(bandType, idx)">Elimina</button>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="mt-3.5 flex justify-end">
			<SfButton size="sm" @click="addBand(bandType)">{{ addLabel }}</SfButton>
		</div>
	</SfCard>
</template>
