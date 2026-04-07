<!--
  FILE: pages/traccia-spedizione.vue
  SCOPO: Tracking spedizione — ricerca per codice BRT/ordine/riferimento, timeline stato.
  Design allineato al Prototipo: icon badge header, accent bar card, grey block per input.

  API: GET /api/tracking/search?code=XXX
  ROUTE: /traccia-spedizione (pubblica).
  DATI IN INGRESSO: ?code=XXX (query param per precompilare e cercare automaticamente).
-->
<script setup>
useSeoMeta({
	title: 'Traccia Spedizione | SpediamoFacile',
	ogTitle: 'Traccia Spedizione | SpediamoFacile',
	description: 'Traccia la tua spedizione in tempo reale con SpediamoFacile.',
	ogDescription: 'Traccia la tua spedizione in tempo reale con SpediamoFacile.',
});

const config = useRuntimeConfig();
const route = useRoute();
const trackingCode = ref(route.query.code || '');

const trackingResult = ref(null);
const trackingError = ref(null);
const isLoading = ref(false);

const statusTimeline = [
	{ key: 'processing', label: 'Pagamento ricevuto', icon: 'card' },
	{ key: 'completed', label: 'Pronto per la spedizione', icon: 'check' },
	{ key: 'in_transit', label: 'In transito', icon: 'truck' },
	{ key: 'delivered', label: 'Consegnato', icon: 'flag' },
];

const currentStepIndex = computed(() => {
	if (!trackingResult.value) return -1;
	return statusTimeline.findIndex(s => s.key === trackingResult.value.raw_status);
});

const statusColorClass = computed(() => {
	if (!trackingResult.value) return '';
	const map = {
		pending: 'bg-yellow-50 text-yellow-700 border border-yellow-200',
		processing: 'bg-[#eef7f8] text-[#095866] border border-[#bdd5da]',
		completed: 'bg-[#f0fdf4] text-[#0a8a7a] border border-[#d1fae5]',
		in_transit: 'bg-[#eef7f8] text-[#095866] border border-[#bdd5da]',
		delivered: 'bg-[#f0fdf4] text-[#0a8a7a] border border-[#d1fae5]',
		in_giacenza: 'bg-orange-50 text-orange-700 border border-orange-200',
		payment_failed: 'bg-red-50 text-red-700 border border-red-200',
		cancelled: 'bg-gray-50 text-[var(--color-brand-text-secondary)] border border-gray-200',
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
	<div class="py-[32px] sm:py-[48px]" style="background: linear-gradient(180deg, #F8F9FB 0%, #EEF0F3 100%); min-height: 100vh">
		<div class="my-container" style="max-width: 680px">

			<!-- Header centrato -->
			<div class="text-center mb-[28px]">
				<div class="w-[48px] h-[48px] rounded-full flex items-center justify-center mx-auto mb-[14px]"
					style="background: linear-gradient(135deg, #095866, #0a7489); box-shadow: 0 4px 14px rgba(9,88,102,0.2)">
					<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="white">
						<path d="M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3M9.5,5C7,5 5,7 5,9.5C5,12 7,14 9.5,14C12,14 14,12 14,9.5C14,7 12,5 9.5,5Z" />
					</svg>
				</div>
				<h1 class="text-[#1d2738] text-[28px] sm:text-[36px] tracking-[-0.8px] font-montserrat" style="font-weight:800">
					Traccia spedizione
				</h1>
				<p class="text-[#777] text-[15px] mt-[6px]">
					Inserisci il codice di tracciamento per seguire il tuo pacco in tempo reale.
				</p>
			</div>

			<!-- Card di ricerca con accent bar -->
			<div class="rounded-[22px] overflow-hidden mb-[20px]"
				style="box-shadow: 0 0 0 1px rgba(9,88,102,0.05), 0 4px 20px rgba(9,88,102,0.06), 0 16px 48px rgba(9,88,102,0.04)">
				<!-- Accent bar teal -->
				<div class="h-[4px]" style="background: linear-gradient(90deg, #095866 0%, #0b9ab3 50%, #095866 100%)" />
				<div class="p-[20px] sm:p-[24px]" style="background: linear-gradient(180deg, #F8F9FB 0%, #EEF0F3 100%)">
					<label class="text-[#777] text-[11px] uppercase tracking-[0.4px] mb-[8px] block" style="font-weight:700">
						Codice tracking
					</label>
					<!-- Grey block per l'input -->
					<div class="rounded-[14px] p-[12px]"
						style="background: #E6E9EE; box-shadow: inset 0 1px 2px rgba(0,0,0,0.04)">
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
									class="w-full h-[48px] sm:h-[50px] rounded-[12px] pl-[42px] pr-[14px] text-[15px] text-[#1d2738] bg-white ring-[1.5px] ring-[#DFE2E7] focus:ring-[3px] focus:ring-[#095866]/60 placeholder:text-[#999] outline-none transition-all duration-200"
									style="font-weight:600"
									@keyup.enter="trackShipment"
								/>
							</div>
							<button
								type="button"
								:disabled="isLoading || !trackingCode.trim()"
								class="h-[48px] sm:h-[50px] px-[22px] rounded-full text-white text-[14px] flex items-center gap-[6px] cursor-pointer shrink-0 transition-all duration-[350ms] hover:shadow-[0_8px_24px_rgba(228,66,3,0.3)] hover:-translate-y-[1px] disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0"
								style="font-weight:700; background: linear-gradient(135deg, #E44203, #c73600); box-shadow: 0 4px 14px rgba(228,66,3,0.22)"
								@click="trackShipment"
							>
								<svg v-if="isLoading" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="animate-spin"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
								<svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="white">
									<path d="M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3M9.5,5C7,5 5,7 5,9.5C5,12 7,14 9.5,14C12,14 14,12 14,9.5C14,7 12,5 9.5,5Z" />
								</svg>
								{{ isLoading ? 'Ricerca...' : 'Cerca' }}
							</button>
						</div>
					</div>
					<p v-if="trackingError" class="text-[#E44203] text-[0.8125rem] mt-[10px]">{{ trackingError }}</p>
				</div>
			</div>

			<!-- Risultato: spedizione trovata -->
			<div v-if="trackingResult && trackingResult.found"
				class="rounded-[22px] overflow-hidden"
				style="box-shadow: 0 0 0 1px rgba(9,88,102,0.05), 0 4px 20px rgba(9,88,102,0.06), 0 16px 48px rgba(9,88,102,0.04)">
				<!-- Accent bar -->
				<div class="h-[4px]" style="background: linear-gradient(90deg, #095866 0%, #0b9ab3 50%, #095866 100%)" />
				<div class="p-[22px] sm:p-[28px]" style="background: linear-gradient(180deg, #F8F9FB 0%, #EEF0F3 100%)">
					<!-- Header risultato -->
					<div class="flex items-center justify-between mb-[20px] pb-[16px] border-b border-[#DFE2E7] flex-wrap gap-[10px]">
						<div>
							<span class="text-[#999] text-[11px] uppercase tracking-[0.4px] block" style="font-weight:600">Spedizione trovata</span>
							<span class="text-[#1d2738] text-[16px]" style="font-weight:700">#{{ trackingResult.order_id }}</span>
						</div>
						<span :class="statusColorClass" class="px-[12px] py-[4px] rounded-full text-[0.8125rem]" style="font-weight:700">
							{{ trackingResult.status }}
						</span>
					</div>

					<!-- Info ordine — grey block -->
					<div class="rounded-[14px] p-[14px] sm:p-[16px] mb-[18px]"
						style="background: #E6E9EE; box-shadow: inset 0 1px 2px rgba(0,0,0,0.04)">
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
					<div v-if="currentStepIndex >= 0" class="mb-[18px]">
						<h3 class="font-montserrat text-[0.8125rem] text-[#1d2738] uppercase tracking-[0.06em] mb-[14px]" style="font-weight:800">
							Avanzamento spedizione
						</h3>
						<div class="rounded-[16px] p-[16px] sm:p-[20px]"
							style="background: #E6E9EE; box-shadow: inset 0 1px 2px rgba(0,0,0,0.04)">
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
									<div class="pb-[16px]" :class="{ 'pb-0': idx === statusTimeline.length - 1 }">
										<span
											class="text-[14px] block"
											:class="idx <= currentStepIndex ? 'text-[#1d2738]' : 'text-[#999]'"
											style="font-weight:600">
											{{ step.label }}
										</span>
										<span v-if="idx === currentStepIndex" class="text-[#095866] text-[12px]" style="font-weight:600">
											Stato attuale
										</span>
										<span v-else-if="idx < currentStepIndex" class="text-[#999] text-[12px]">
											Completato
										</span>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Link BRT -->
					<div v-if="trackingResult.brt_tracking_url" class="border-t border-[#DFE2E7] pt-[18px]">
						<a
							:href="trackingResult.brt_tracking_url"
							target="_blank"
							rel="noopener noreferrer"
							class="btn-secondary inline-flex items-center gap-[8px] px-[20px] py-[11px] text-[0.875rem]">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
							Dettaglio su BRT
						</a>
						<p class="text-[0.8125rem] text-[#777] mt-[8px]">Aggiornamenti in tempo reale sul sito ufficiale BRT.</p>
					</div>
				</div>
			</div>

			<!-- Risultato: non trovato -->
			<div v-else-if="trackingResult && !trackingResult.found"
				class="rounded-[22px] overflow-hidden text-center"
				style="box-shadow: 0 0 0 1px rgba(9,88,102,0.05), 0 4px 20px rgba(9,88,102,0.06), 0 16px 48px rgba(9,88,102,0.04)">
				<div class="h-[4px]" style="background: linear-gradient(90deg, #095866 0%, #0b9ab3 50%, #095866 100%)" />
				<div class="p-[32px] sm:p-[40px]" style="background: linear-gradient(180deg, #F8F9FB 0%, #EEF0F3 100%)">
					<div class="w-[64px] h-[64px] rounded-full flex items-center justify-center mx-auto mb-[16px]"
						style="background: #E6E9EE">
						<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="#C0C5CC">
							<path d="M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3M9.5,5C7,5 5,7 5,9.5C5,12 7,14 9.5,14C12,14 14,12 14,9.5C14,7 12,5 9.5,5Z" />
						</svg>
					</div>
					<p class="text-[1rem] text-[#1d2738] mb-[6px]" style="font-weight:700">Spedizione non trovata</p>
					<p class="text-[0.875rem] text-[#777] mb-[20px] max-w-[44ch] mx-auto">
						Il codice inserito non corrisponde a nessuna spedizione nel nostro archivio. Se hai un codice BRT, prova direttamente sul sito del corriere.
					</p>
					<a
						v-if="trackingResult.brt_tracking_url"
						:href="trackingResult.brt_tracking_url"
						target="_blank"
						rel="noopener noreferrer"
						class="btn-secondary inline-flex items-center gap-[8px] px-[20px] py-[11px] text-[0.875rem]">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
						Cerca su BRT
					</a>
				</div>
			</div>

			<!-- Idle hint (prima della ricerca) -->
			<div v-else-if="!trackingResult && !trackingError && !isLoading"
				class="flex flex-col items-center py-[32px] opacity-60">
				<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#C0C5CC" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
					<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
				</svg>
				<p class="text-[0.875rem] text-[#777] mt-[8px]">Inserisci un codice per avviare la ricerca</p>
			</div>

			<!-- Link area personale -->
			<p class="text-[#777] text-[0.8125rem] text-center mt-[24px]">
				Puoi anche tracciare le tue spedizioni dall'area
				<NuxtLink to="/account/spedizioni" class="text-[#095866] font-semibold hover:underline">Le tue spedizioni</NuxtLink>.
			</p>

		</div>
	</div>
</template>
