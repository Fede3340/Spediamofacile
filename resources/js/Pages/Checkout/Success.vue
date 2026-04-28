<script setup>
import { Head, Link } from '@inertiajs/vue3';
defineProps({ order: { type: Object, default: () => ({}) } });
</script>
<template>
	<Head title="Pagamento riuscito" />
	<div class="sf-container py-16 max-w-xl text-center">
		<div class="w-20 h-20 mx-auto rounded-full bg-green-500 text-white flex items-center justify-center text-4xl mb-6">✓</div>
		<h1 class="text-3xl font-bold mb-3">Pagamento riuscito!</h1>
		<p class="text-[var(--color-brand-text-secondary)] mb-6">Ordine #{{ order.id }} confermato. Ti abbiamo inviato un'email di conferma.</p>
		<div class="bg-white border border-[var(--color-brand-border)] rounded-2xl p-6 mb-6 text-left">
			<div class="flex justify-between py-2"><span class="text-[var(--color-brand-text-muted)]">Numero ordine</span><span class="font-semibold">#{{ order.id }}</span></div>
			<div class="flex justify-between py-2"><span class="text-[var(--color-brand-text-muted)]">Totale</span><span class="font-semibold">{{ order.total }}</span></div>
			<div v-if="order.tracking" class="flex justify-between py-2"><span class="text-[var(--color-brand-text-muted)]">Tracking BRT</span><span class="font-semibold">{{ order.tracking }}</span></div>
		</div>
		<Link href="/account/spedizioni" class="inline-block bg-[var(--color-brand-orange)] text-white px-6 py-3 rounded-full font-semibold no-underline">Vedi le mie spedizioni</Link>
	</div>
</template>
