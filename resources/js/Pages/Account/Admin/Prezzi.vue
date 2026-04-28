<script setup>
import { Head } from '@inertiajs/vue3';
defineProps({
	weightBands: { type: Array, default: () => [] },
	volumeBands: { type: Array, default: () => [] },
});
</script>
<template>
	<Head title="Prezzi admin" />
	<div class="sf-container py-12">
		<h1 class="text-3xl font-bold mb-6">Gestione prezzi</h1>
		<div class="grid md:grid-cols-2 gap-6">
			<div class="bg-white border rounded-2xl p-5">
				<h2 class="font-bold mb-3">Bands peso</h2>
				<table class="w-full text-sm">
					<thead><tr class="text-xs uppercase text-[var(--color-brand-text-muted)]"><th class="text-left p-1">Da</th><th class="text-left p-1">A</th><th class="text-left p-1">Prezzo</th></tr></thead>
					<tbody>
						<tr v-for="b in weightBands" :key="b.id" class="border-t"><td class="p-1">{{ b.min_value }} kg</td><td class="p-1">{{ b.max_value }} kg</td><td class="p-1 font-semibold">{{ (b.base_price/100).toFixed(2) }} €</td></tr>
					</tbody>
				</table>
			</div>
			<div class="bg-white border rounded-2xl p-5">
				<h2 class="font-bold mb-3">Bands volume</h2>
				<table class="w-full text-sm">
					<thead><tr class="text-xs uppercase text-[var(--color-brand-text-muted)]"><th class="text-left p-1">Da</th><th class="text-left p-1">A</th><th class="text-left p-1">Prezzo</th></tr></thead>
					<tbody>
						<tr v-for="b in volumeBands" :key="b.id" class="border-t"><td class="p-1">{{ b.min_value }} m³</td><td class="p-1">{{ b.max_value }} m³</td><td class="p-1 font-semibold">{{ (b.base_price/100).toFixed(2) }} €</td></tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</template>
