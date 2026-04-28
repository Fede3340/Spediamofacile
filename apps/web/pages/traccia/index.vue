<script setup>
useSeoMeta({
	title: 'Traccia Spedizione',
	ogTitle: 'Traccia Spedizione',
	description: 'Traccia la tua spedizione in tempo reale con SpediamoFacile.',
	ogDescription: 'Traccia la tua spedizione in tempo reale con SpediamoFacile.',
});

// Breadcrumb: Home › Traccia
useBreadcrumbSchema([
	{ name: 'Home', url: '/' },
	{ name: 'Traccia spedizione' },
]);

const config = useRuntimeConfig();
const route = useRoute();
const trackingCode = ref(route.query.code || '');

const trackingResult = ref(null);
const trackingError = ref(null);
const isLoading = ref(false);

const trackingTips = [
	'Usa il codice ordine SF o il Parcel ID BRT piu recente.',
	'Se la spedizione e appena stata creata, attendi qualche minuto prima di riprovare.',
	'Per anomalie o giacenze, spesso il dettaglio finale e disponibile anche sul portale BRT.',
];

const statusTimeline = [
	{ key: 'processing', label: 'Pagamento ricevuto', icon: 'card' },
	{ key: 'label_generated', label: 'Etichetta generata', icon: 'label' },
	{ key: 'in_transit', label: 'In transito', icon: 'truck' },
	{ key: 'out_for_delivery', label: 'In consegna', icon: 'delivery' },
	{ key: 'delivered', label: 'Consegnato', icon: 'flag' },
];

const alternateEndStates = ['returned', 'refused', 'cancelled'];

const isAlternateEndState = computed(() => {
	if (!trackingResult.value) return false;
	return alternateEndStates.includes(trackingResult.value.raw_status);
});

const alternateEndLabel = computed(() => {
	if (!trackingResult.value) return '';
	const map = { returned: 'Reso al mittente', refused: 'Rifiutato dal destinatario', cancelled: 'Annullato' };
	return map[trackingResult.value.raw_status] || '';
});

const currentStepIndex = computed(() => {
	if (!trackingResult.value) return -1;
	if (isAlternateEndState.value) {
		// For alternate end states, show progress up to the last known step before the end
		const raw = trackingResult.value.raw_status;
		if (raw === 'returned' || raw === 'refused') return statusTimeline.findIndex(s => s.key === 'in_transit');
		return 0; // cancelled = only first step
	}
	return statusTimeline.findIndex(s => s.key === trackingResult.value.raw_status);
});

const statusColorClass = computed(() => {
	if (!trackingResult.value) return '';
	const map = {
		pending: 'bg-yellow-50 text-yellow-700 border border-yellow-200',
		processing: 'bg-[#eef7f8] text-[#095866] border border-[#bdd5da]',
		label_generated: 'bg-[#eef7f8] text-[#095866] border border-[#bdd5da]',
		completed: 'bg-[#f0fdf4] text-[#0a8a7a] border border-[#d1fae5]',
		paid: 'bg-[#f0fdf4] text-[#0a8a7a] border border-[#d1fae5]',
		in_transit: 'bg-[#eef7f8] text-[#095866] border border-[#bdd5da]',
		out_for_delivery: 'bg-[#dff0f3] text-[#074a56] border border-[#b0d8df]',
		delivered: 'bg-[#f0fdf4] text-[#0a8a7a] border border-[#d1fae5]',
		in_giacenza: 'bg-orange-50 text-orange-700 border border-orange-200',
		payment_failed: 'bg-red-50 text-red-700 border border-red-200',
		cancelled: 'bg-gray-50 text-[var(--color-brand-text-secondary)] border border-gray-200',
		refunded: 'bg-orange-50 text-orange-700 border border-orange-200',
		returned: 'bg-[#eef7f8] text-[#095866] border border-[#bdd5da]',
		refused: 'bg-red-50 text-red-700 border border-red-200',
	};
	return map[trackingResult.value.raw_status] || 'bg-gray-50 text-[var(--color-brand-text-secondary)] border border-gray-200';
});

const trackShipment = async () => {
	if (!trackingCode.value.trim()) return;
	trackingError.value = null;
	trackingResult.value = null;
	isLoading.value = true;
	try {
		const apiBase = config.public?.apiBase || config.public?.sanctum?.baseUrl || '';
		const response = await $fetch(`${apiBase}/api/tracking/search`, {
			params: { code: trackingCode.value.trim() },
			credentials: 'include',
		});
		if (response.found) {
			trackingResult.value = response;
		} else {
			trackingResult.value = {
				found: false,
				brt_tracking_url: response.brt_tracking_url,
				message: response.message,
			};
		}
	} catch {
		trackingError.value = 'Errore durante la ricerca. Riprova tra qualche istante.';
	} finally {
		isLoading.value = false;
	}
};

onMounted(() => {
	if (trackingCode.value) trackShipment();
});
</script>

<template>
	<div style="background: var(--gradient-page-surface); min-height: 100vh">
		<PublicPageHeader
			:crumbs="[{ label: 'Home', to: '/' }, { label: 'Traccia' }]"
			eyebrow="Tracking spedizioni"
			title="Traccia spedizione"
			description="Inserisci il codice e controlla lo stato della spedizione in una vista piu chiara, compatta e leggibile.">
			<template #icon>
				<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
					<path d="M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3M9.5,5C7,5 5,7 5,9.5C5,12 7,14 9.5,14C12,14 14,12 14,9.5C14,7 12,5 9.5,5Z" />
				</svg>
			</template>
		</PublicPageHeader>

		<div class="py-[20px] sm:py-[24px]">
			<div class="my-container" style="max-width: 680px">

			<!-- Card di ricerca con accent bar -->
			<div class="rounded-[16px] overflow-hidden mb-[18px]"
				data-shadow="soft">
				<!-- Accent bar teal -->
				<div class="h-[4px]" data-accent="bar" />
				<div class="p-[18px] sm:p-[22px]" style="background: var(--gradient-page-surface)">
					<label class="text-[#777] text-[11px] uppercase tracking-[0.4px] mb-[8px] block" style="font-weight:700">
						Codice tracking
					</label>
					<!-- Grey block per l'input -->
					<div class="rounded-[16px] p-[12px]"
						data-surface="grey-inset">
						<div class="flex gap-[10px]">
							<div class="relative flex-1">
								<svg class="absolute left-[14px] top-1/2 -translate-y-1/2 text-[#C0C5CC] pointer-events-none"
									xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
									<path d="M21,16.5C21,16.88 20.79,17.21 20.47,17.38L12.57,21.82C12.41,21.94 12.21,22 12,22C11.79,22 11.59,21.94 11.43,21.82L3.53,17.38C3.21,17.21 3,16.88 3,16.5V7.5C3,7.12 3.21,6.79 3.53,6.62L11.43,2.18C11.59,2.06 11.79,2 12,2C12.21,2 12.41,2.06 12.57,2.18L20.47,6.62C20.79,6.79 21,7.12 21,7.5V16.5M12,4.15L6.04,7.5L12,10.85L17.96,7.5L12,4.15M5,15.91L11,19.29V12.58L5,9.21V15.91M19,15.91V9.21L13,12.58V19.29L19,15.91Z" />
								</svg>
								<input
									v-model="trackingCode"
									type="text"
									placeholder="es. BRT-2026032801234, SF-000042..."
									class="w-full h-[48px] sm:h-[50px] rounded-[12px] pl-[42px] pr-[14px] text-[15px] text-[#1d2738] bg-white ring-[1.5px] ring-[#DFE2E7] focus:ring-[3px] focus:ring-[#095866]/60 placeholder:text-[var(--color-brand-text-muted)] outline-none transition-all duration-200"
									style="font-weight:600"
									@keyup.enter="trackShipment"
								/>
							</div>
							<SfButton
								size="lg"
								class="shrink-0"
								:loading="isLoading"
								:disabled="!trackingCode.trim()"
								@click="trackShipment">
								<svg v-if="!isLoading" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="white">
									<path d="M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3M9.5,5C7,5 5,7 5,9.5C5,12 7,14 9.5,14C12,14 14,12 14,9.5C14,7 12,5 9.5,5Z" />
								</svg>
								{{ isLoading ? 'Ricerca...' : 'Cerca' }}
							</SfButton>
						</div>
					</div>
					<p v-if="trackingError" class="text-[#E44203] text-[0.8125rem] mt-[10px]">{{ trackingError }}</p>
				</div>
			</div>

			<!-- Risultato: spedizione trovata -->
			<div v-if="trackingResult && trackingResult.found"
				class="rounded-[16px] overflow-hidden"
				data-shadow="soft">
				<!-- Accent bar -->
				<div class="h-[4px]" data-accent="bar" />
				<div class="p-[20px] sm:p-[24px]" style="background: var(--gradient-page-surface)">
					<!-- Header risultato -->
					<div class="flex items-center justify-between mb-[18px] pb-[14px] border-b border-[#DFE2E7] flex-wrap gap-[10px]">
						<div>
							<span class="text-[var(--color-brand-text-muted)] text-[11px] uppercase tracking-[0.4px] block" style="font-weight:600">Spedizione trovata</span>
							<span class="text-[#1d2738] text-[16px]" style="font-weight:700">#{{ trackingResult.order_id }}</span>
						</div>
						<span :class="statusColorClass" class="px-[12px] py-[4px] rounded-full text-[0.8125rem]" style="font-weight:700">
							{{ trackingResult.status }}
						</span>
					</div>

					<!-- Info ordine — grey block -->
					<div class="rounded-[16px] p-[14px] sm:p-[16px] mb-[16px]"
						data-surface="grey-inset">
						<div class="grid grid-cols-1 tablet:grid-cols-2 gap-[10px]">
							<div>
								<p class="text-[11px] uppercase tracking-[0.06em] text-[#777] mb-[2px]" style="font-weight:700">Numero Ordine</p>
								<p class="text-[0.9375rem] text-[#1d2738]" style="font-weight:600">#{{ trackingResult.order_id }}</p>
							</div>
							<div>
								<p class="text-[11px] uppercase tracking-[0.06em] text-[#777] mb-[2px]" style="font-weight:700">Data Ordine</p>
								<p class="text-[0.9375rem] text-[#1d2738]" style="font-weight:600">{{ trackingResult.created_at || '—' }}</p>
							</div>
							<div v-if="trackingResult.brt_parcel_id">
								<p class="text-[11px] uppercase tracking-[0.06em] text-[#777] mb-[2px]" style="font-weight:700">Codice BRT</p>
								<p class="text-[0.9375rem] text-[#1d2738] font-mono" style="font-weight:600">{{ trackingResult.brt_parcel_id }}</p>
							</div>
							<div>
								<p class="text-[11px] uppercase tracking-[0.06em] text-[#777] mb-[2px]" style="font-weight:700">Stato</p>
								<p class="text-[0.9375rem] text-[#1d2738]" style="font-weight:600">{{ trackingResult.status_description }}</p>
							</div>
						</div>
					</div>

					<!-- Timeline in grey block -->
					<div v-if="currentStepIndex >= 0" class="mb-[16px]">
						<h3 class="font-montserrat text-[0.8125rem] text-[#1d2738] uppercase tracking-[0.06em] mb-[14px]" style="font-weight:800">
							Avanzamento spedizione
						</h3>
						<div class="rounded-[16px] p-[16px] sm:p-[20px]"
							data-surface="grey-inset">
							<div class="flex flex-col gap-0">
								<div
									v-for="(step, idx) in statusTimeline"
									:key="step.key"
									class="flex gap-[14px]"
								>
									<div class="flex flex-col items-center">
										<div
											class="w-[32px] h-[32px] rounded-full flex items-center justify-center shrink-0"
											:class="idx <= currentStepIndex ? 'bg-[#095866]' : 'bg-white ring-[1.5px] ring-[#DFE2E7]'"
										>
											<svg v-if="idx <= currentStepIndex" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
												<polyline points="20 6 9 17 4 12" />
											</svg>
											<svg v-else xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
												<circle cx="12" cy="12" r="4" />
											</svg>
										</div>
										<div v-if="idx < statusTimeline.length - 1"
											class="w-[2px] flex-1 min-h-[28px] rounded-[1px]"
											:class="idx < currentStepIndex ? 'bg-[#095866]/30' : 'bg-[#D5D9E0]'"
										/>
									</div>
									<div class="pb-[16px]" :class="{ 'pb-0': idx === statusTimeline.length - 1 && !isAlternateEndState }">
										<span
											class="text-[14px] block"
											:class="idx <= currentStepIndex ? 'text-[#1d2738]' : 'text-[var(--color-brand-text-muted)]'"
											style="font-weight:600">
											{{ step.label }}
										</span>
										<span v-if="idx === currentStepIndex && !isAlternateEndState" class="text-[#095866] text-[12px]" style="font-weight:600">
											Stato attuale
										</span>
										<span v-else-if="idx < currentStepIndex" class="text-[var(--color-brand-text-muted)] text-[12px]">
											Completato
										</span>
									</div>
								</div>

								<!-- Alternate end state (returned / refused / cancelled) -->
								<div v-if="isAlternateEndState" class="flex gap-[14px]">
									<div class="flex flex-col items-center">
										<div class="w-[2px] min-h-[12px] rounded-[1px] bg-[#D5D9E0]" />
										<div
											class="w-[32px] h-[32px] rounded-full flex items-center justify-center shrink-0"
											:class="trackingResult.raw_status === 'refused' ? 'bg-red-600' : 'bg-[#E44203]'"
										>
											<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
												<line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />
											</svg>
										</div>
									</div>
									<div class="pt-[12px]">
										<span
											class="text-[14px] block"
											:class="trackingResult.raw_status === 'refused' ? 'text-red-700' : 'text-[#E44203]'"
											style="font-weight:700">
											{{ alternateEndLabel }}
										</span>
										<span
											class="text-[12px]"
											:class="trackingResult.raw_status === 'refused' ? 'text-red-600' : 'text-[#E44203]'"
											style="font-weight:600">
											Stato finale
										</span>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Link BRT -->
					<div v-if="trackingResult.brt_tracking_url" class="border-t border-[#DFE2E7] pt-[16px]">
						<SfButton :href="trackingResult.brt_tracking_url" variant="secondary">
							<template #leading>
								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
							</template>
							Dettaglio su BRT
						</SfButton>
						<p class="text-[0.8125rem] text-[#777] mt-[8px]">Aggiornamenti in tempo reale sul sito ufficiale BRT.</p>
					</div>
				</div>
			</div>

			<!-- Risultato: non trovato — helpful, with suggestions -->
			<div v-else-if="trackingResult && !trackingResult.found"
				class="rounded-[16px] overflow-hidden text-center"
				data-shadow="soft">
				<div class="h-[4px]" data-accent="bar" />
				<div class="p-[22px] sm:p-[28px]" style="background: var(--gradient-page-surface)">
					<div class="w-[60px] h-[60px] rounded-full flex items-center justify-center mx-auto mb-[14px]"
						style="background: rgba(9, 88, 102, 0.08)">
						<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="#095866" style="opacity:0.7">
							<path d="M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3M9.5,5C7,5 5,7 5,9.5C5,12 7,14 9.5,14C12,14 14,12 14,9.5C14,7 12,5 9.5,5Z" />
						</svg>
					</div>
					<p class="text-[1rem] text-[#1d2738] mb-[6px]" style="font-weight:700">Spedizione non trovata</p>
					<p class="text-[0.875rem] text-[#777] mb-[16px] max-w-[44ch] mx-auto">
						Il codice inserito non corrisponde a nessuna spedizione nel nostro archivio.
					</p>

					<!-- Suggerimenti utili -->
					<div class="rounded-[12px] p-[14px] text-left mb-[16px] max-w-[400px] mx-auto"
						data-surface="grey-inset">
						<p class="text-[12px] text-[#1d2738] mb-[8px]" style="font-weight:700">Suggerimenti:</p>
						<ul class="text-[12px] text-[#5A6474] leading-[1.8] list-none m-0 p-0">
							<li class="flex items-start gap-[6px]">
								<span class="text-[#095866] mt-[2px] shrink-0" style="font-weight:800">-</span>
								Verifica di aver inserito il codice correttamente
							</li>
							<li class="flex items-start gap-[6px]">
								<span class="text-[#095866] mt-[2px] shrink-0" style="font-weight:800">-</span>
								Il tracking potrebbe non essere ancora attivo (attendi 1-2 ore)
							</li>
							<li class="flex items-start gap-[6px]">
								<span class="text-[#095866] mt-[2px] shrink-0" style="font-weight:800">-</span>
								Se hai un codice BRT, prova direttamente sul sito del corriere
							</li>
						</ul>
					</div>

					<div class="flex flex-wrap justify-center gap-[10px]">
						<SfButton v-if="trackingResult.brt_tracking_url" :href="trackingResult.brt_tracking_url" variant="secondary">
							<template #leading>
								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
							</template>
							Cerca su BRT
						</SfButton>
						<SfButton to="/contatti" variant="secondary">
							<template #leading>
								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
									<path d="M20,2H4A2,2 0 0,0 2,4V22L6,18H20A2,2 0 0,0 22,16V4A2,2 0 0,0 20,2M20,16H6L4,18V4H20V16Z" />
								</svg>
							</template>
							Contatta l'assistenza
						</SfButton>
					</div>
				</div>
			</div>

			<!-- Idle hint (prima della ricerca) — helpful, not empty -->
			<div v-else-if="!trackingResult && !trackingError && !isLoading">
				<div class="rounded-[16px] overflow-hidden mb-[18px]"
					data-shadow="soft">
					<div class="h-[3px]" data-accent="bar" />
					<div class="p-[18px] sm:p-[22px]" style="background: var(--gradient-page-surface)">
						<div class="grid gap-[14px]">
							<div class="grid gap-[6px]">
								<h3 class="text-[#1d2738] text-[15px]" style="font-weight:700; font-family: var(--font-montserrat, Montserrat, sans-serif)">
									Prima della ricerca
								</h3>
								<p class="text-[#5A6474] text-[13px] leading-[1.6]">
									Questa pagina serve solo a trovare in fretta lo stato corretto: niente box dimostrativi inutili, solo i controlli minimi davvero utili.
								</p>
							</div>
							<div class="rounded-[12px] p-[14px] sm:p-[16px]"
								data-surface="grey-inset">
								<ul class="grid gap-[10px] m-0 p-0 list-none">
									<li
										v-for="tip in trackingTips"
										:key="tip"
										class="flex items-start gap-[8px] text-[13px] leading-[1.55] text-[#5A6474]">
										<span class="mt-[6px] h-[6px] w-[6px] rounded-full bg-[#095866] shrink-0" aria-hidden="true"></span>
										<span>{{ tip }}</span>
									</li>
								</ul>
							</div>
						</div>
						<p class="text-center mt-[14px]">
							<NuxtLink to="/guide" class="text-[#095866] text-[13px] hover:opacity-80 inline-flex items-center gap-[4px]" style="font-weight:600">
								Leggi la guida dettagliata
								<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
							</NuxtLink>
						</p>
					</div>
				</div>
				<div
					class="rounded-[16px] overflow-hidden"
					data-shadow="medium">
					<div class="p-[16px] sm:p-[18px]" style="background: rgba(255,255,255,0.72)">
						<div class="flex flex-col gap-[12px] sm:flex-row sm:items-center sm:justify-between">
							<div class="grid gap-[4px]">
								<p class="text-[#1d2738] text-[14px]" style="font-weight:700">
									Hai bisogno di un altro percorso?
								</p>
								<p class="text-[#5A6474] text-[13px] leading-[1.55]">
									Se il codice non è ancora pronto, puoi passare dalla tua area spedizioni o aprire un contatto rapido.
								</p>
							</div>
							<div class="flex flex-col gap-[8px] sm:flex-row sm:items-center sm:justify-end">
								<SfButton to="/account/spedizioni" variant="secondary">Le tue spedizioni</SfButton>
								<SfButton to="/contatti" variant="secondary">Contatta assistenza</SfButton>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Link area personale -->
			<p class="text-[#777] text-[0.8125rem] text-center mt-[24px]">
				Puoi anche tracciare le tue spedizioni dall'area
				<NuxtLink to="/account/spedizioni" class="text-[#095866] font-semibold hover:opacity-80">Le tue spedizioni</NuxtLink>.
			</p>

			</div>
		</div>

	</div>
</template>
