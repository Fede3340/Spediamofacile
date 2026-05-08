<script setup>
import TrackingStepper from '~/components/tracking/TrackingStepper.vue';
import TrackingEventsTimeline from '~/components/tracking/TrackingEventsTimeline.vue';
import TrackingActionsBar from '~/components/tracking/TrackingActionsBar.vue';
import { useTrackingDetail } from '~/composables/useTrackingDetail';

definePageMeta({
	// Pagina pubblica: il codice tracking è già di per sé credenziale di accesso minima.
});

const route = useRoute();
const router = useRouter();

const trackingCode = computed(() => String(route.params.tracking || '').trim());
const newSearchInput = ref('');

const {
	data,
	isLoading,
	isRefreshing,
	errorState,
	copyOk,
	currentStepIndex,
	alternateEnd,
	isDelivered,
	statusChipClass,
	etaFormatted,
	STEPS,
	fetchTracking,
	startPolling,
	stopPolling,
	handleVisibilityChange,
	copyCode,
} = useTrackingDetail(trackingCode);

onMounted(async () => {
	await fetchTracking();
	if (data.value && !isDelivered.value) startPolling();
	if (typeof document !== 'undefined') {
		document.addEventListener('visibilitychange', handleVisibilityChange);
	}
});

onBeforeUnmount(() => {
	stopPolling();
	if (typeof document !== 'undefined') {
		document.removeEventListener('visibilitychange', handleVisibilityChange);
	}
});

watch(() => route.params.tracking, async (v, old) => {
	if (v && v !== old) {
		stopPolling();
		data.value = null;
		await fetchTracking();
		if (data.value && !isDelivered.value) startPolling();
	}
});

function submitNewSearch() {
	const v = newSearchInput.value?.trim();
	if (!v) return;
	router.push(`/traccia/${encodeURIComponent(v)}`);
}

useSeoMeta({
	title: () => `Tracking ${trackingCode.value || 'spedizione'}`,
	robots: 'noindex, nofollow',
	description: 'Dettaglio tracking spedizione personale.',
});
</script>

<template>
	<div style="background: var(--gradient-page-surface, #f5f7fa); min-height: 100vh">
		<PublicPageHeader
			:crumbs="[{ label: 'Home', to: '/' }, { label: 'Traccia', to: '/traccia-spedizione' }, { label: trackingCode || 'Dettaglio' }]"
			eyebrow="Dettaglio spedizione"
			:title="trackingCode ? `Tracking ${trackingCode}` : 'Tracking spedizione'"
			description="Aggiornamenti in tempo reale sulla tua spedizione."
		>
			<template #icon>
				<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
					<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
				</svg>
			</template>
		</PublicPageHeader>

		<div class="py-[20px] sm:py-[28px]">
			<div class="my-container" style="max-width: 1080px">

				<!-- Loading skeleton globale -->
				<div v-if="isLoading" class="grid gap-[18px]" aria-busy="true">
					<div class="rounded-[18px] p-[24px] animate-pulse" style="background:#fff; box-shadow: 0 2px 12px rgba(0,0,0,0.04)">
						<div class="h-[20px] w-[40%] rounded bg-[#E6E9EE] mb-[12px]"/>
						<div class="h-[36px] w-[60%] rounded bg-[#E6E9EE] mb-[10px]"/>
						<div class="h-[14px] w-[30%] rounded bg-[#EEF1F5]"/>
					</div>
					<div class="rounded-[18px] p-[24px] animate-pulse" style="background:#fff">
						<div class="h-[60px] rounded bg-[#EEF1F5]"/>
					</div>
				</div>

				<!-- Stato 404 / not found — pattern sf-empty-state condiviso sitewide -->
				<div v-else-if="errorState === 'not_found'" class="sf-empty-state" role="status">
					<div class="sf-empty-state__icon" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<circle cx="11" cy="11" r="8" />
							<line x1="21" y1="21" x2="16.65" y2="16.65" />
						</svg>
					</div>
					<h3 class="sf-empty-state__title">Codice tracking non trovato</h3>
					<p class="sf-empty-state__copy">
						Il codice <span class="font-mono text-[var(--color-brand-primary)]" style="font-weight:700">{{ trackingCode }}</span> non corrisponde a nessuna spedizione. Verifica di averlo digitato correttamente o prova un nuovo codice.
					</p>

					<form class="flex flex-col sm:flex-row gap-[10px] max-w-[460px] w-full mx-auto mt-[6px]" @submit.prevent="submitNewSearch">
						<label for="new-search" class="sr-only">Nuovo codice tracking</label>
						<input
							id="new-search"
							v-model="newSearchInput"
							type="text"
							placeholder="Inserisci un altro codice..."
							class="flex-1 h-[46px] rounded-control px-[14px] text-[14px] text-[var(--color-brand-text)] bg-white ring-[1.5px] ring-[#DFE2E7] focus:ring-[3px] focus:ring-[var(--color-brand-primary)]/60 outline-none transition-all"
							style="font-weight:600"
						>
						<button
							type="submit"
							:disabled="!newSearchInput.trim()"
							class="sf-empty-state__cta"
							aria-label="Cerca nuovo codice"
						>
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
								<circle cx="11" cy="11" r="8" />
								<line x1="21" y1="21" x2="16.65" y2="16.65" />
							</svg>
							<span>Cerca</span>
						</button>
					</form>

					<div class="sf-empty-state__actions">
						<a
							v-if="data?.brt_tracking_url"
							:href="data.brt_tracking_url"
							target="_blank"
							rel="noopener noreferrer"
							class="sf-empty-state__cta sf-empty-state__cta--ghost"
						>
							<span>Cerca su BRT</span>
						</a>
						<NuxtLink to="/contatti" class="sf-empty-state__cta sf-empty-state__cta--ghost">
							<span>Contatta assistenza</span>
						</NuxtLink>
					</div>
				</div>

				<!-- Stato errore di rete -->
				<div v-else-if="errorState === 'network'" class="rounded-[18px] p-[24px] text-center" style="background:#fff">
					<p class="text-[14px] text-[var(--color-brand-accent)] mb-[12px]" style="font-weight:600">
						Impossibile contattare il server. Verifica la connessione.
					</p>
					<button
						type="button"
						class="px-[18px] py-[10px] rounded-control text-[13px] text-white"
						style="background:#095866; font-weight:700"
						@click="fetchTracking()"
					>
						Riprova
					</button>
				</div>

				<!-- Vista principale tracking -->
				<div v-else-if="data" class="grid gap-[18px]">

					<!-- HERO compatto -->
					<section class="rounded-[18px] overflow-hidden" data-shadow="soft" aria-label="Riepilogo spedizione">
						<div class="h-[4px]" data-accent="bar"/>
						<div class="p-[20px] sm:p-[24px]" style="background:#ffffff">
							<div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-[16px]">
								<!-- Codice + copia -->
								<div class="min-w-0">
									<p class="text-[11px] uppercase tracking-[0.4px] text-[#777] mb-[6px] m-0" style="font-weight:700">
										Codice tracking
									</p>
									<div class="flex items-center gap-[10px] flex-wrap">
										<h1 class="font-mono text-[1.5rem] sm:text-[1.75rem] lg:text-[2rem] text-[var(--color-brand-text)] m-0 leading-[1.1] break-all" style="font-weight:800; letter-spacing:-0.01em">
											{{ data.code }}
										</h1>
										<button
											type="button"
											class="inline-flex items-center gap-[6px] px-[10px] py-[6px] rounded-[8px] bg-white text-[var(--color-brand-primary)] ring-[1.5px] ring-[#DFE2E7] cursor-pointer transition-all duration-200 hover:ring-[var(--color-brand-primary)] hover:bg-[#eef7f8] active:scale-[0.97] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-brand-primary)]"
											:aria-label="copyOk ? 'Codice copiato' : 'Copia codice tracking'"
											@click="copyCode"
										>
											<svg v-if="!copyOk" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
												<rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
												<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
											</svg>
											<svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
												<polyline points="20 6 9 17 4 12" />
											</svg>
											<span class="text-[12px]" style="font-weight:600">{{ copyOk ? 'Copiato' : 'Copia' }}</span>
										</button>
									</div>
									<p v-if="data.order_id" class="text-[12px] text-[#7a8493] mt-[6px] m-0">
										Ordine SF #{{ data.order_id }}
									</p>
								</div>

								<!-- Stato + ETA -->
								<div class="flex flex-col items-start lg:items-end gap-[8px]">
									<span class="inline-flex items-center gap-[8px] px-[14px] py-[6px] rounded-full text-[13px] tracking-[0.01em]" :class="statusChipClass" style="font-weight:700">
										<span class="w-[8px] h-[8px] rounded-full bg-current shadow-[0_0_0_3px_rgba(255,255,255,0.7)] shrink-0" aria-hidden="true"/>
										{{ data.status_label }}
									</span>
									<div v-if="etaFormatted && !isDelivered" class="text-right">
										<p class="text-[11px] uppercase tracking-[0.4px] text-[#777] m-0" style="font-weight:700">
											Consegna stimata
										</p>
										<p class="text-[14px] text-[var(--color-brand-text)] m-0 capitalize" style="font-weight:700">
											{{ etaFormatted }}
										</p>
									</div>
									<p v-if="isRefreshing" class="text-[11px] text-[#7a8493] m-0 inline-flex items-center gap-[4px]">
										<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="animate-spin" aria-hidden="true">
											<path d="M21 12a9 9 0 1 1-6.219-8.56" />
										</svg>
										Aggiornamento...
									</p>
								</div>
							</div>

							<p v-if="data.status_description" class="text-[13px] text-[var(--color-brand-text-secondary)] mt-[14px] mb-0 leading-[1.55]">
								{{ data.status_description }}
							</p>
						</div>
					</section>

					<!-- STEPPER -->
					<section class="rounded-[18px] overflow-hidden" data-shadow="soft" aria-label="Avanzamento fasi">
						<div class="h-[3px]" data-accent="bar"/>
						<div class="p-[20px] sm:p-[24px]" style="background:#ffffff">
							<TrackingStepper
								:steps="STEPS"
								:current-index="currentStepIndex"
								:alternate-end="alternateEnd"
							/>
						</div>
					</section>

					<!-- DUE COLONNE: timeline (2/3) + sidebar (1/3) -->
					<section class="grid gap-[18px] lg:grid-cols-3">
						<!-- TIMELINE EVENTI -->
						<div class="lg:col-span-2 rounded-[18px] overflow-hidden" data-shadow="soft">
							<div class="h-[3px]" data-accent="bar"/>
							<div class="p-[20px] sm:p-[24px]" style="background:#ffffff">
								<div class="flex items-center justify-between mb-[14px]">
									<h2 class="text-[1rem] text-[var(--color-brand-text)] m-0" style="font-weight:700">
										Cronologia eventi
									</h2>
									<button
										v-if="!isLoading"
										type="button"
										class="text-[12px] text-[var(--color-brand-primary)] inline-flex items-center gap-[4px] hover:opacity-80"
										style="font-weight:600"
										@click="fetchTracking({ silent: true })"
									>
										<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" :class="{ 'animate-spin': isRefreshing }">
											<polyline points="23 4 23 10 17 10" />
											<polyline points="1 20 1 14 7 14" />
											<path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15" />
										</svg>
										Aggiorna
									</button>
								</div>
								<TrackingEventsTimeline :events="data.events" :loading="false" />
							</div>
						</div>

						<!-- SIDEBAR DETTAGLI -->
						<aside class="rounded-[18px] overflow-hidden" data-shadow="soft" aria-label="Dettagli spedizione">
							<div class="h-[3px]" data-accent="bar"/>
							<div class="p-[20px] sm:p-[24px] grid gap-[16px]" style="background:#ffffff">
								<!-- Origine → destinazione -->
								<div>
									<h3 class="text-[11px] uppercase tracking-[0.4px] text-[#777] mb-[10px] m-0" style="font-weight:700">
										Percorso
									</h3>
									<div v-if="data.origin || data.destination" class="grid gap-[10px]">
										<div class="flex items-start gap-[10px]">
											<div class="w-[28px] h-[28px] rounded-[8px] inline-flex items-center justify-center shrink-0 bg-[#eef7f8] text-[var(--color-brand-primary)]">
												<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
													<circle cx="12" cy="12" r="3" />
												</svg>
											</div>
											<div class="min-w-0">
												<p class="text-[10px] uppercase tracking-[0.06em] text-[#7a8493] m-0" style="font-weight:700">
													Origine
												</p>
												<p class="text-[13px] text-[var(--color-brand-text)] m-0 leading-[1.4]" style="font-weight:600">
													{{ data.origin || 'Non disponibile' }}
												</p>
											</div>
										</div>
										<div class="w-[2px] h-[16px] bg-[#DFE2E7] ml-[13px] rounded-[1px]" aria-hidden="true"/>
										<div class="flex items-start gap-[10px]">
											<div class="w-[28px] h-[28px] rounded-[8px] inline-flex items-center justify-center shrink-0 bg-[#fff5ef] text-[var(--color-brand-accent)]">
												<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
													<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
													<circle cx="12" cy="10" r="3" />
												</svg>
											</div>
											<div class="min-w-0">
												<p class="text-[10px] uppercase tracking-[0.06em] text-[#7a8493] m-0" style="font-weight:700">
													Destinazione
												</p>
												<p class="text-[13px] text-[var(--color-brand-text)] m-0 leading-[1.4]" style="font-weight:600">
													{{ data.destination || 'Non disponibile' }}
												</p>
												<p v-if="data.recipient_name" class="text-[12px] text-[var(--color-brand-text-secondary)] m-0 mt-[2px]">
													{{ data.recipient_name }}
												</p>
											</div>
										</div>
									</div>
									<p v-else class="text-[12px] text-[#7a8493] m-0">
										Dettagli percorso non ancora disponibili.
									</p>
								</div>

								<!-- Pacco -->
								<div v-if="data.package" class="rounded-control p-[12px]" data-surface="grey-inset">
									<h3 class="text-[11px] uppercase tracking-[0.4px] text-[#777] mb-[8px] m-0" style="font-weight:700">
										Pacco
									</h3>
									<dl class="grid grid-cols-2 gap-[8px] m-0">
										<template v-if="data.package.weight">
											<dt class="text-[11px] text-[#7a8493]">Peso</dt>
											<dd class="text-[12px] text-[var(--color-brand-text)] m-0" style="font-weight:600">{{ data.package.weight }} kg</dd>
										</template>
										<template v-if="data.package.parcels">
											<dt class="text-[11px] text-[#7a8493]">Colli</dt>
											<dd class="text-[12px] text-[var(--color-brand-text)] m-0" style="font-weight:600">{{ data.package.parcels }}</dd>
										</template>
										<template v-if="data.package.dimensions">
											<dt class="text-[11px] text-[#7a8493]">Dimensioni</dt>
											<dd class="text-[12px] text-[var(--color-brand-text)] m-0" style="font-weight:600">{{ data.package.dimensions }}</dd>
										</template>
									</dl>
								</div>

								<!-- Servizi attivi -->
								<div v-if="data.package?.services?.length">
									<h3 class="text-[11px] uppercase tracking-[0.4px] text-[#777] mb-[8px] m-0" style="font-weight:700">
										Servizi attivi
									</h3>
									<ul class="grid gap-[6px] m-0 p-0 list-none">
										<li v-for="srv in data.package.services" :key="srv" class="text-[12px] text-[var(--color-brand-text)] inline-flex items-center gap-[6px]">
											<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
												<polyline points="20 6 9 17 4 12" />
											</svg>
											{{ srv }}
										</li>
									</ul>
								</div>

								<!-- Date -->
								<div v-if="data.created_at" class="text-[11px] text-[#7a8493]">
									Creata il {{ data.created_at }}
								</div>
							</div>
						</aside>
					</section>

					<!-- AZIONI -->
					<TrackingActionsBar
						:order-id="data.order_id || data.code"
						:can-reschedule="data.can_reschedule"
						:can-change-address="data.can_change_address"
						:invoice-url="data.invoice_url"
						:brt-tracking-url="data.brt_tracking_url"
						@reschedule="() => router.push(`/contatti?subject=Riprogramma+consegna+${encodeURIComponent(data.code)}`)"
						@change-address="() => router.push(`/contatti?subject=Cambio+indirizzo+${encodeURIComponent(data.code)}`)"
					/>

				</div>

			</div>
		</div>
	</div>
</template>

