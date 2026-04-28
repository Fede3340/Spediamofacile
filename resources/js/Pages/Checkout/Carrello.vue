<script setup>
import { Head, Link } from '@inertiajs/vue3';
defineProps({
	items: { type: Array, default: () => [] },
	total: { type: String, default: '0,00 €' },
});
</script>
<template>
	<Head title="Carrello" />
	<div class="sf-container py-12 max-w-3xl">
		<h1 class="text-3xl font-bold mb-6">Il tuo carrello</h1>
		<div v-if="items.length" class="space-y-3">
			<div v-for="i in items" :key="i.id" class="bg-white border border-[var(--color-brand-border)] rounded-xl p-4 flex items-center justify-between">
				<div>
					<div class="font-semibold">{{ i.label }}</div>
					<div class="text-sm text-[var(--color-brand-text-secondary)]">{{ i.route }}</div>
				</div>
				<div class="font-bold">{{ i.price }}</div>
			</div>
			<div class="bg-white border-2 border-[var(--color-brand-teal)] rounded-xl p-4 flex items-center justify-between">
				<div class="font-bold">Totale</div>
				<div class="text-xl font-bold text-[var(--color-brand-orange)]">{{ total }}</div>
			</div>
			<Link href="/checkout" class="block w-full bg-[var(--color-brand-orange)] text-white text-center py-3 rounded-full font-semibold hover:bg-[var(--color-brand-orange-light)] no-underline">Vai al checkout</Link>
		</div>
		<div v-else class="text-center py-12 text-[var(--color-brand-text-muted)]">Carrello vuoto. <Link href="/preventivo" class="text-[var(--color-brand-orange)] font-semibold">Inizia un preventivo</Link>.</div>
	</div>
</template>
