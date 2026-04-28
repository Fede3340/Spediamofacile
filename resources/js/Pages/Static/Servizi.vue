<script setup>
import { Head, Link } from '@inertiajs/vue3';
defineProps({ services: { type: Array, default: () => [] } });
</script>
<template>
	<Head title="Servizi" />
	<div class="sf-container py-12">
		<p class="text-sm font-semibold text-[var(--color-brand-orange)] uppercase tracking-wide text-center">Servizi di spedizione</p>
		<h1 class="text-4xl font-bold text-center mt-2 mb-3">I nostri servizi</h1>
		<p class="text-center text-[var(--color-brand-text-secondary)] mb-10 max-w-2xl mx-auto">
			Ritiro, tracking, contrassegno e supporto: tutto attivabile direttamente nel preventivo.
		</p>
		<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
			<Link v-for="s in services" :key="s.slug" :href="`/servizi/${s.slug}`" class="block bg-white border border-[var(--color-brand-border)] rounded-2xl overflow-hidden hover:shadow-lg hover:-translate-y-0.5 transition no-underline text-inherit">
				<div class="p-6 bg-gradient-to-br from-[var(--color-brand-teal)]/10 to-transparent">
					<span class="inline-block bg-[var(--color-brand-teal)] text-white text-xs font-bold uppercase px-3 py-1 rounded-full">{{ s.badge || 'Servizio' }}</span>
				</div>
				<div class="p-6">
					<h3 class="font-bold text-lg mb-2">{{ s.title }}</h3>
					<p class="text-sm text-[var(--color-brand-text-secondary)] mb-3 line-clamp-3">{{ s.description }}</p>
					<span class="text-sm font-semibold text-[var(--color-brand-orange)]">Scopri →</span>
				</div>
			</Link>
		</div>
	</div>
</template>
