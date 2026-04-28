<script setup>
import { Head, Link } from '@inertiajs/vue3';
defineProps({
	tracking: { type: Object, default: () => ({}) },
	notFound: { type: Boolean, default: false },
});
</script>
<template>
	<Head title="Stato spedizione" />
	<div class="sf-container py-12 max-w-2xl">
		<Link href="/traccia" class="text-sm text-[var(--color-brand-orange)]">← Nuova ricerca</Link>
		<h1 class="text-3xl font-bold mb-6 mt-2">Stato spedizione</h1>
		<div v-if="notFound" class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg">
			Nessuna spedizione trovata con questo codice.
			<a v-if="tracking.brt_tracking_url" :href="tracking.brt_tracking_url" target="_blank" rel="noopener" class="block mt-2 text-[var(--color-brand-orange)] font-semibold">Apri su BRT.it →</a>
		</div>
		<div v-else class="bg-white border border-[var(--color-brand-border)] rounded-2xl p-6 space-y-3">
			<div class="flex justify-between items-center pb-3 border-b border-[var(--color-brand-border)]">
				<div>
					<div class="text-xs uppercase font-semibold text-[var(--color-brand-text-muted)]">Tracking</div>
					<div class="font-bold">{{ tracking.tracking_number || tracking.parcel_id || '—' }}</div>
				</div>
				<span class="text-xs px-3 py-1 rounded-full bg-[var(--color-brand-teal)] text-white font-bold uppercase">{{ tracking.status_label || tracking.status }}</span>
			</div>
			<p class="text-[var(--color-brand-text-secondary)]">{{ tracking.status_description || '' }}</p>
			<div v-if="tracking.brt_tracking_url" class="pt-3 border-t border-[var(--color-brand-border)]">
				<a :href="tracking.brt_tracking_url" target="_blank" rel="noopener" class="text-sm text-[var(--color-brand-orange)] font-semibold">Vedi su BRT.it →</a>
			</div>
		</div>
	</div>
</template>
