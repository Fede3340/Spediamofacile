<!-- COMPONENTE: TrackingActionsBar (tracking/TrackingActionsBar.vue) -->
<script setup>
defineProps({
	orderId: {
		type: [String, Number],
		default: null,
	},
	canReschedule: {
		type: Boolean,
		default: false,
	},
	canChangeAddress: {
		type: Boolean,
		default: false,
	},
	invoiceUrl: {
		type: String,
		default: null,
	},
	brtTrackingUrl: {
		type: String,
		default: null,
	},
});

const emit = defineEmits(['reschedule', 'change-address']);

// const reclamoLink = computed(() => {
// 	if (!props.orderId) return '/reclami';
// 	return `/reclami?order=${encodeURIComponent(props.orderId)}`;
// });
</script>

<template>
	<div class="tracking-actions-bar rounded-[18px] overflow-hidden" data-shadow="soft">
		<div class="h-[3px]" data-accent="bar"/>
		<div class="p-[16px] sm:p-[18px]" style="background: var(--gradient-page-surface, #ffffff)">
			<p class="text-[11px] uppercase tracking-[0.4px] text-[#777] mb-[12px]" style="font-weight:700">
				Azioni rapide
			</p>

			<div class="flex flex-wrap gap-[10px]">
				<!-- Riprogramma consegna -->
				<SfButton
					v-if="canReschedule"
					variant="primary"
					@click="emit('reschedule')">
					<template #leading>
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
							<rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
							<line x1="16" y1="2" x2="16" y2="6" />
							<line x1="8" y1="2" x2="8" y2="6" />
							<line x1="3" y1="10" x2="21" y2="10" />
						</svg>
					</template>
					Riprogramma consegna
				</SfButton>

				<!-- Cambia indirizzo -->
				<SfButton
					v-if="canChangeAddress"
					variant="primary"
					@click="emit('change-address')">
					<template #leading>
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
							<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
							<path d="M14.5 9.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z" />
						</svg>
					</template>
					Cambia indirizzo
				</SfButton>

				<!-- Reclamo: -- ARCHIVIATO 2026-04-20 (_archive/frontend-simplification-2026-04-20/features/reclami-dedicato) -- -->

				<!-- Fattura -->
				<SfButton
					v-if="invoiceUrl"
					:href="invoiceUrl"
					variant="secondary">
					<template #leading>
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
							<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
							<polyline points="14 2 14 8 20 8" />
							<line x1="9" y1="14" x2="15" y2="14" />
							<line x1="9" y1="18" x2="15" y2="18" />
						</svg>
					</template>
					Scarica fattura
				</SfButton>

				<!-- BRT esterno -->
				<SfButton
					v-if="brtTrackingUrl"
					:href="brtTrackingUrl"
					variant="secondary">
					<template #leading>
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
							<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
							<polyline points="15 3 21 3 21 9" />
							<line x1="10" y1="14" x2="21" y2="3" />
						</svg>
					</template>
					Dettaglio BRT
				</SfButton>
			</div>

			<p
				v-if="!canReschedule && !canChangeAddress && !invoiceUrl && !brtTrackingUrl"
				class="text-[12px] text-[#7a8493] mt-[10px] m-0"
			>
				Nessuna azione aggiuntiva disponibile per questa spedizione.
			</p>
		</div>
	</div>
</template>
