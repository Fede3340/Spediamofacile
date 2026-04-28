<!--
  Componente: ShipmentBrtSection
  Sezione BRT: tracking, download etichetta, rigenerazione etichetta, messaggi errore/successo.
-->
<script setup>
import { computed } from 'vue';
import {
	getBrtTrackingReference,
	getBrtTrackingSearchHref,
	getBrtTrackingUrl,
} from '~/utils/brtTracking';

const props = defineProps({
	orderData: { type: Object, required: true },
	regenerating: { type: Boolean, default: false },
	regenerateError: { type: String, default: null },
	regenerateSuccess: { type: Boolean, default: false },
});

const emit = defineEmits(['downloadLabel', 'regenerateLabel']);
const trackingReference = computed(() => getBrtTrackingReference(props.orderData));
const trackingSearchHref = computed(() => getBrtTrackingSearchHref(props.orderData));
const externalTrackingHref = computed(() => getBrtTrackingUrl(props.orderData));
</script>

<template>
	<div class="bg-white rounded-[16px] p-[24px] border border-[var(--color-brand-border)] mt-[16px]">
		<h3 class="font-montserrat text-[1rem] font-[800] text-[var(--color-brand-text)] mb-[16px]">Spedizione BRT</h3>

		<!-- Tracking number e link prominente -->
		<div v-if="trackingReference" class="bg-[#F0F7F8] border border-[#C5DFE3] rounded-[16px] p-[16px] mb-[16px]">
			<div class="flex items-start gap-[12px]">
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-brand-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 mt-[2px]"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
				<div class="flex-1">
					<p class="text-[0.75rem] text-[var(--color-brand-primary)] uppercase font-medium mb-[4px]">Codice Tracking BRT</p>
					<p class="text-[1.125rem] font-bold text-[var(--color-brand-text)] font-mono tracking-wide mb-[8px]">{{ trackingReference }}</p>
					<div class="flex flex-wrap items-center gap-[10px]">
						<NuxtLink
							v-if="trackingSearchHref"
							:to="trackingSearchHref"
							class="inline-flex items-center gap-[6px] px-[14px] py-[8px] bg-[var(--color-brand-primary)] text-white rounded-[16px] font-semibold text-[0.8125rem] hover:bg-[var(--color-brand-primary-hover)] transition">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
							Traccia spedizione
						</NuxtLink>
						<a v-if="externalTrackingHref" :href="externalTrackingHref" target="_blank" rel="noopener noreferrer"
							class="inline-flex items-center gap-[6px] px-[14px] py-[8px] border border-[var(--color-brand-primary)] text-[var(--color-brand-primary)] rounded-[16px] font-semibold text-[0.8125rem] hover:bg-[var(--color-brand-primary)] hover:text-white transition">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
							Vedi su BRT
						</a>
					</div>
				</div>
			</div>
		</div>

		<!-- Label available -->
		<template v-if="orderData.has_label">
			<div class="flex flex-wrap items-center gap-[10px] mb-[12px]">
				<button @click="emit('downloadLabel')" type="button"
					class="inline-flex items-center gap-[6px] px-[16px] py-[10px] bg-[var(--color-brand-primary)] text-white rounded-[50px] text-[0.875rem] font-semibold hover:bg-[var(--color-brand-primary-hover)] transition cursor-pointer">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
					Scarica Etichetta Spedizione
				</button>
			</div>
		</template>

		<!-- Label NOT available -->
		<template v-else>
			<div v-if="orderData.brt_error" class="bg-red-50 border border-red-200 rounded-[50px] px-[16px] py-[12px] flex items-start gap-[12px] mb-[12px]">
				<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 mt-[1px]"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
				<div class="flex-1">
					<p class="text-[0.875rem] font-semibold text-red-700 mb-[4px]">Generazione etichetta fallita</p>
					<p class="text-[0.8125rem] text-red-600">{{ orderData.brt_error }}</p>
				</div>
			</div>
			<div v-else class="bg-amber-50 border border-amber-200 rounded-[50px] px-[16px] py-[12px] flex items-center gap-[12px] mb-[12px]">
				<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
				<p class="text-[0.8125rem] text-amber-800 flex-1">Etichetta in generazione...</p>
			</div>
			<button @click="emit('regenerateLabel')" :disabled="regenerating" type="button"
				class="inline-flex items-center gap-[6px] px-[16px] py-[10px] bg-[var(--color-brand-accent)] text-white rounded-[50px] text-[0.875rem] font-semibold hover:opacity-90 transition disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer">
				<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
				{{ regenerating ? 'Rigenerazione...' : 'Rigenera Etichetta' }}
			</button>
		</template>

		<!-- Messaggi rigenerazione -->
		<div v-if="regenerateError" class="mt-[10px] bg-red-50 border border-red-200 rounded-[50px] px-[14px] py-[10px] text-red-600 text-[0.8125rem]">{{ regenerateError }}</div>
		<div v-if="regenerateSuccess" class="mt-[10px] bg-[#f0fdf4] border border-[#d1fae5] rounded-[50px] px-[14px] py-[10px] text-[#0a8a7a] text-[0.8125rem]">Etichetta generata con successo!</div>
	</div>
</template>
