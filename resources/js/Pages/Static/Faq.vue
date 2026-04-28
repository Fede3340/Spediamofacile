<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
const items = [
	{ q: 'Quanto costa spedire?', a: 'Da 8,90 € per pacchi fino a 2 kg in Italia.' },
	{ q: 'Quanto tempo impiega la consegna?', a: '24-48 ore in Italia, 3-5 giorni in Europa.' },
	{ q: 'Come funziona il ritiro?', a: 'Scegli data e fascia oraria. Il corriere passa a casa.' },
	{ q: 'Posso spedire senza etichetta?', a: 'Si: il corriere stampa al ritiro. +0,99 EUR.' },
];
const open = ref([]);
const toggle = (i) => open.value.includes(i) ? open.value = open.value.filter(x=>x!==i) : open.value.push(i);
</script>
<template>
	<Head title="FAQ" />
	<div class="sf-container py-12 max-w-3xl">
		<h1 class="text-4xl font-bold text-center mb-8">Domande frequenti</h1>
		<div class="space-y-3">
			<div v-for="(item, i) in items" :key="i" class="bg-white border border-[var(--color-brand-border)] rounded-xl overflow-hidden">
				<button @click="toggle(i)" class="w-full px-5 py-4 text-left font-semibold hover:bg-[var(--color-brand-bg)] flex items-center justify-between">
					<span>{{ item.q }}</span>
					<span class="text-[var(--color-brand-orange)] text-2xl">{{ open.includes(i) ? '−' : '+' }}</span>
				</button>
				<div v-if="open.includes(i)" class="px-5 pb-4 text-[var(--color-brand-text-secondary)]">{{ item.a }}</div>
			</div>
		</div>
	</div>
</template>
