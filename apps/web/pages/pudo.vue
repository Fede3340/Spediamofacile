<!--
	PAGINA: /pudo — Trova un punto BRT
	Layout 2 colonne desktop (lista 380px + mappa fill), stack mobile.
	Header: input ricerca CAP/indirizzo, filtri (orari/servizi), bottone geolocalizzazione.
	Stack: usePudoSearch composable, components/pudo/* per UI, Leaflet client-only mappa.
-->
<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { usePudoSearch } from '~/composables/usePudo'

definePageMeta({
	// Layout di default (Header + Footer del sito)
})

useSeoMeta({
	title: 'Punti BRT — Trova il PUDO più vicino',
	description:
		'Cerca i punti di ritiro e consegna BRT (PUDO) più vicini a te per indirizzo, CAP o città. Mappa interattiva con orari e servizi disponibili.',
	ogTitle: 'Punti BRT — Trova il PUDO più vicino',
	ogDescription:
		'Mappa e lista dei punti BRT di ritiro e consegna pacchi in Italia. Cerca per indirizzo, CAP, città o usa la geolocalizzazione.',
	robots: 'index, follow',
})

useHead({
	link: [{ rel: 'canonical', href: 'https://spediamofacile.it/pudo' }],
	script: [
		// Service schema: locator dei punti BRT come servizio pubblico offerto.
		// Preferito a LocalBusiness singolo perché i PUDO sono punti terzi.
		{
			key: 'pudo-service-schema',
			type: 'application/ld+json',
			innerHTML: JSON.stringify({
				'@context': 'https://schema.org',
				'@type': 'Service',
				name: 'Punti BRT — PUDO locator',
				url: 'https://spediamofacile.it/pudo',
				serviceType: 'Parcel Pickup and Drop-off locator',
				provider: {
					'@type': 'Organization',
					'@id': 'https://spediamofacile.it/#organization',
					name: 'SpediamoFacile',
				},
				areaServed: { '@type': 'Country', name: 'IT' },
				description:
					'Mappa e lista dei punti BRT (PUDO) di ritiro e consegna pacchi in Italia. Cerca per indirizzo, CAP o città.',
			}),
		},
	],
})

// Breadcrumb: Home › Punti BRT
useBreadcrumbSchema([
	{ name: 'Home', url: '/' },
	{ name: 'Punti BRT' },
])

// Mappa caricata SOLO client-side (Leaflet usa window).
const PudoMap = defineAsyncComponent(() => import('~/components/pudo/PudoMap.vue'))

// usePudoSearch mantiene API legacy e API pagina: qui usiamo solo la API pagina pubblica.
const {
	query,
	filters,
	loading,
	geolocating,
	searched,
	searchError,
	results,
	rawResults,
	selectedKey,
	selected,
	mapPoints,
	referencePoint,
	search,
	searchImmediate,
	useCurrentLocation,
	selectPudo,
	clearSelection,
	resetFilters,
	startNowTimer,
	stopNowTimer,
} = usePudoSearch()

const detailOpen = ref(false)

const onPudoFromList = (p) => {
	selectPudo(p)
	detailOpen.value = true
}
const onPudoFromMap = (p) => {
	selectPudo(p)
	detailOpen.value = true
}
const closeDetail = () => {
	detailOpen.value = false
}
const handleChosen = (p) => {
	// Pagina pubblica: confermiamo + chiudiamo. Riusabile nel funnel
	// agganciando v-on:pudo-selected al composable parent.
	closeDetail()
	if (typeof window !== 'undefined') {
		window.scrollTo({ top: 0, behavior: 'smooth' })
	}
	selectPudo(p)
}
const onInput = (e) => {
	const value = e.target?.value ?? ''
	search(value)
}
const onSubmit = (e) => {
	e.preventDefault()
	searchImmediate()
}

const totalCount = computed(() => results.value.length)
const filtersActiveCount = computed(() =>
	(filters.value.openNow ? 1 : 0) +
	(filters.value.ritiro ? 1 : 0) +
	(filters.value.consegna ? 1 : 0) +
	(filters.value.sabato ? 1 : 0)
)

onMounted(() => startNowTimer())
onBeforeUnmount(() => stopNowTimer())
</script>

<template>
	<main class="pudo-page bg-[#F8FAFB] min-h-screen overflow-x-hidden">
		<!-- HERO + RICERCA -->
		<section class="bg-gradient-to-br from-[#F2F8F9] to-white border-b border-[var(--color-brand-border,#E9EBEC)]">
			<div class="max-w-[1280px] mx-auto px-[16px] tablet:px-[24px] py-[28px] tablet:py-[44px]">
				<div class="max-w-[760px]">
					<span class="inline-flex items-center gap-[6px] text-[0.75rem] font-semibold uppercase tracking-wide text-[var(--color-brand-primary,#095866)] bg-[#E6F0F2] rounded-full px-[10px] py-[4px] mb-[12px]">
						<span class="w-[6px] h-[6px] rounded-full bg-[#E44203]"></span>
						Rete BRT
					</span>
					<h1 class="text-[1.75rem] tablet:text-[2.25rem] font-bold text-[var(--color-brand-text,#0f172a)] leading-tight">
						Trova il punto BRT più vicino
					</h1>
					<p class="mt-[10px] text-[0.9375rem] tablet:text-[1rem] text-[var(--color-brand-text-secondary,#4b5563)] max-w-[640px]">
						Inserisci CAP, città o indirizzo per scoprire i punti di ritiro e consegna più comodi.
						Vedi orari, servizi e indicazioni in un colpo d'occhio.
					</p>
				</div>

				<!-- SEARCH BAR -->
				<form
					role="search"
					class="mt-[20px] tablet:mt-[28px] bg-white rounded-[18px] border border-[var(--color-brand-border,#E9EBEC)] shadow-[0_4px_20px_rgba(9,88,102,0.06)] p-[12px] tablet:p-[14px]"
					@submit="onSubmit">
					<div class="flex flex-col tablet:flex-row gap-[10px]">
						<!-- Input -->
						<div class="flex-1 relative">
							<label for="pudo-search" class="sr-only">CAP, città o indirizzo</label>
							<svg class="absolute left-[14px] top-1/2 -translate-y-1/2 text-[var(--color-brand-text-secondary,#4b5563)]"
								width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
								stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
								<circle cx="11" cy="11" r="8"></circle>
								<path d="m21 21-4.35-4.35"></path>
							</svg>
							<input
								id="pudo-search"
								type="text"
								:value="query"
								placeholder="CAP, città o indirizzo (es. 20121, Milano, Via Dante 1)"
								autocomplete="off"
								enterkeyhint="search"
								class="w-full h-[48px] pl-[40px] pr-[14px] rounded-[14px] border border-[var(--color-brand-border,#E9EBEC)] bg-white text-[0.9375rem] text-[var(--color-brand-text,#0f172a)] placeholder:text-[var(--color-brand-text-secondary,#94a3a8)] focus:outline-none focus:border-[var(--color-brand-primary,#095866)] focus:shadow-[0_0_0_3px_rgba(9,88,102,0.15)] transition"
								@input="onInput"
							/>
						</div>
						<!-- Geolocalizzazione -->
						<button
							type="button"
							:disabled="geolocating || loading"
							class="inline-flex items-center justify-center gap-[8px] h-[48px] px-[18px] rounded-[14px] border border-[var(--color-brand-border,#E9EBEC)] bg-white text-[var(--color-brand-primary,#095866)] text-[0.875rem] font-semibold hover:bg-[#F2F8F9] transition cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
							@click="useCurrentLocation">
							<svg v-if="!geolocating" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
								stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
								<circle cx="12" cy="12" r="3"></circle>
								<path d="M12 2v3"></path>
								<path d="M12 19v3"></path>
								<path d="M2 12h3"></path>
								<path d="M19 12h3"></path>
							</svg>
							<span v-else class="inline-block w-[16px] h-[16px] border-2 border-[var(--color-brand-primary,#095866)] border-t-transparent rounded-full animate-spin"></span>
							{{ geolocating ? 'Localizzo...' : 'Vicino a me' }}
						</button>
						<!-- Cerca -->
						<button
							type="submit"
							:disabled="loading"
							class="inline-flex items-center justify-center gap-[8px] h-[48px] px-[22px] rounded-[14px] bg-[var(--color-brand-primary,#095866)] text-white text-[0.9375rem] font-semibold hover:bg-[var(--color-brand-primary-hover,#074a56)] transition cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
							<svg v-if="!loading" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
								stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
								<circle cx="11" cy="11" r="8"></circle>
								<path d="m21 21-4.35-4.35"></path>
							</svg>
							<span v-else class="inline-block w-[16px] h-[16px] border-2 border-white border-t-transparent rounded-full animate-spin"></span>
							{{ loading ? 'Ricerca...' : 'Cerca punti' }}
						</button>
					</div>

					<!-- FILTRI -->
					<div class="flex items-center flex-wrap gap-[8px] mt-[12px] pt-[12px] border-t border-[var(--color-brand-border,#E9EBEC)]">
						<span class="text-[0.75rem] font-semibold uppercase tracking-wide text-[var(--color-brand-text-secondary,#4b5563)] mr-[4px]">
							Filtri
						</span>
						<label class="inline-flex items-center gap-[6px] cursor-pointer">
							<input v-model="filters.openNow" type="checkbox" class="sr-only peer" />
							<span class="inline-flex items-center gap-[5px] text-[0.8125rem] font-medium px-[10px] py-[5px] rounded-full border border-[var(--color-brand-border,#E9EBEC)] bg-white text-[var(--color-brand-text,#0f172a)] peer-checked:bg-[var(--color-brand-primary,#095866)] peer-checked:text-white peer-checked:border-[var(--color-brand-primary,#095866)] peer-focus-visible:ring-2 peer-focus-visible:ring-[#E44203] transition-colors">
								Aperto ora
							</span>
						</label>
						<label class="inline-flex items-center gap-[6px] cursor-pointer">
							<input v-model="filters.ritiro" type="checkbox" class="sr-only peer" />
							<span class="inline-flex items-center gap-[5px] text-[0.8125rem] font-medium px-[10px] py-[5px] rounded-full border border-[var(--color-brand-border,#E9EBEC)] bg-white text-[var(--color-brand-text,#0f172a)] peer-checked:bg-[var(--color-brand-primary,#095866)] peer-checked:text-white peer-checked:border-[var(--color-brand-primary,#095866)] peer-focus-visible:ring-2 peer-focus-visible:ring-[#E44203] transition-colors">
								Ritiro
							</span>
						</label>
						<label class="inline-flex items-center gap-[6px] cursor-pointer">
							<input v-model="filters.consegna" type="checkbox" class="sr-only peer" />
							<span class="inline-flex items-center gap-[5px] text-[0.8125rem] font-medium px-[10px] py-[5px] rounded-full border border-[var(--color-brand-border,#E9EBEC)] bg-white text-[var(--color-brand-text,#0f172a)] peer-checked:bg-[var(--color-brand-primary,#095866)] peer-checked:text-white peer-checked:border-[var(--color-brand-primary,#095866)] peer-focus-visible:ring-2 peer-focus-visible:ring-[#E44203] transition-colors">
								Consegna
							</span>
						</label>
						<label class="inline-flex items-center gap-[6px] cursor-pointer">
							<input v-model="filters.sabato" type="checkbox" class="sr-only peer" />
							<span class="inline-flex items-center gap-[5px] text-[0.8125rem] font-medium px-[10px] py-[5px] rounded-full border border-[var(--color-brand-border,#E9EBEC)] bg-white text-[var(--color-brand-text,#0f172a)] peer-checked:bg-[var(--color-brand-primary,#095866)] peer-checked:text-white peer-checked:border-[var(--color-brand-primary,#095866)] peer-focus-visible:ring-2 peer-focus-visible:ring-[#E44203] transition-colors">
								Aperto sabato
							</span>
						</label>
						<button
							v-if="filtersActiveCount > 0"
							type="button"
							class="ml-auto text-[0.75rem] font-semibold text-[#E44203] hover:underline cursor-pointer"
							@click="resetFilters">
							Azzera filtri ({{ filtersActiveCount }})
						</button>
					</div>
				</form>

				<!-- Stato risultati / errori -->
				<div v-if="searched || searchError" class="mt-[14px] flex items-center gap-[10px] flex-wrap text-[0.8125rem]">
					<span v-if="searchError" class="inline-flex items-center gap-[6px] text-[#a52f02] bg-[#fff1ec] border border-[#fcd9c8] rounded-full px-[10px] py-[4px]">
						<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
							stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
							<circle cx="12" cy="12" r="10"></circle>
							<line x1="12" y1="8" x2="12" y2="12"></line>
							<line x1="12" y1="16" x2="12.01" y2="16"></line>
						</svg>
						{{ searchError }}
					</span>
					<span v-else-if="!loading" class="text-[var(--color-brand-text-secondary,#4b5563)]">
						<strong class="text-[var(--color-brand-text,#0f172a)]">{{ totalCount }}</strong>
						<template v-if="totalCount === 1"> punto trovato</template>
						<template v-else> punti trovati</template>
						<template v-if="totalCount !== rawResults.length">
							(su {{ rawResults.length }} totali)
						</template>
					</span>
				</div>
			</div>
		</section>

		<!-- LAYOUT LISTA + MAPPA -->
		<section class="max-w-[1280px] mx-auto px-[16px] tablet:px-[24px] py-[20px] tablet:py-[28px]">
			<div class="grid grid-cols-1 desktop:grid-cols-[380px_1fr] gap-[20px] desktop:h-[calc(100vh-260px)] desktop:min-h-[560px]">
				<!-- LISTA -->
				<aside class="bg-white rounded-[16px] border border-[var(--color-brand-border,#E9EBEC)] overflow-hidden flex flex-col desktop:max-h-full">
					<header class="px-[16px] py-[12px] border-b border-[var(--color-brand-border,#E9EBEC)] bg-[#FAFCFC]">
						<h2 class="text-[0.875rem] font-bold text-[var(--color-brand-text,#0f172a)]">
							Risultati
						</h2>
						<p class="text-[0.75rem] text-[var(--color-brand-text-secondary,#4b5563)] mt-[2px]">
							<template v-if="loading">Ricerca in corso...</template>
							<template v-else-if="!searched">Cerca per CAP, città o indirizzo</template>
							<template v-else-if="totalCount === 0">Nessun risultato per la tua ricerca</template>
							<template v-else>{{ totalCount }} {{ totalCount === 1 ? 'punto' : 'punti' }} BRT</template>
						</p>
					</header>
					<div class="flex-1 min-h-[320px] desktop:min-h-0 desktop:overflow-hidden">
						<PudoList
							:items="results"
							:loading="loading"
							:selected-key="selectedKey"
							:has-reference="referencePoint != null"
							@select="onPudoFromList"
						/>
					</div>
				</aside>

				<!-- MAPPA -->
				<div class="bg-white rounded-[16px] border border-[var(--color-brand-border,#E9EBEC)] overflow-hidden min-h-[420px] desktop:min-h-0 desktop:h-full">
					<ClientOnly>
						<PudoMap
							:points="mapPoints"
							:selected-key="selectedKey"
							:reference-point="referencePoint"
							@select="onPudoFromMap"
						/>
						<template #fallback>
							<div class="flex items-center justify-center h-full min-h-[420px] text-[0.875rem] text-[var(--color-brand-text-secondary,#4b5563)]">
								Caricamento mappa...
							</div>
						</template>
					</ClientOnly>
				</div>
			</div>
		</section>

		<!-- DETAIL PANEL (drawer) -->
		<PudoDetailPanel
			:pudo="selected"
			:open="detailOpen && selected != null"
			@close="closeDetail"
			@pudo-selected="handleChosen"
		/>
	</main>
</template>

<style scoped>
.pudo-page :deep(.leaflet-bar a) {
	color: var(--color-brand-primary, #095866);
}
.pudo-page :deep(.leaflet-control-attribution) {
	font-size: 10px;
}
</style>
