<script setup>
// Lazy: Leaflet (~130 KB gzip) è incluso SOLO in MapPudo.client.vue.
// Caricandolo come defineAsyncComponent il bundle iniziale della pagina
// ritiro/consegna non paga il costo della libreria finché la mappa non viene
// effettivamente renderizzata (Sprint 5.2a).
const MapPudo = defineAsyncComponent(() => import('~/components/pudo/MapPudo.client.vue'));

const props = defineProps({
	initialCity: { type: String, default: '' },
	initialZip: { type: String, default: '' },
});

const emit = defineEmits(['select', 'deselect']);

const {
	searchAddress, searchCity, searchZip,
	loading, geolocating, searched, searchError, searchMeta, referenceUpdateMessage,
	pudoResults, selectedPudoKey, expandedPudoKey, loadingDetailsKey, pudoDetails, detailsErrors, mapClickLoading,
	hasSearchInput, mapPoints, mapReferencePoint, strategyListLabel,
	searchPudo, useCurrentLocation, onMapReferenceClick, selectPudo, toggleDetails,
	distanceLabel, getTodayHoursText, getPudoStatus, formatOpeningHours,
	startNowTimer, stopNowTimer,
} = usePudoSearch(props, emit);

onMounted(() => startNowTimer());
onBeforeUnmount(() => stopNowTimer());
</script>

<template>
	<div class="mt-[16px]">
		<div class="grid grid-cols-1 tablet:grid-cols-2 desktop:grid-cols-[minmax(0,1fr)_190px_120px_auto] gap-[10px] items-end">
			<div class="w-full">
				<label class="block text-[0.75rem] text-[var(--color-brand-text-secondary)] mb-[4px]">Via / Indirizzo (opzionale)</label>
				<input
					id="pudo_search_address"
					v-model="searchAddress"
					type="text"
					placeholder="es. Via Roma 10"
					class="w-full bg-white rounded-[16px] h-[44px] px-[10px] text-[1rem] border border-[var(--color-brand-border)] transition-[border-color,box-shadow] duration-200 focus:border-[var(--color-brand-primary)] focus:shadow-[0_0_0_3px_rgba(9,88,102,0.15)]"
					@keydown.enter.prevent="searchPudo" />
			</div>

			<div class="w-full">
				<label class="block text-[0.75rem] text-[var(--color-brand-text-secondary)] mb-[4px]">Città</label>
				<input
					id="pudo_search_city"
					v-model="searchCity"
					type="text"
					placeholder="es. Iglesias"
					class="w-full bg-white rounded-[16px] h-[44px] px-[10px] text-[1rem] border border-[var(--color-brand-border)] transition-[border-color,box-shadow] duration-200 focus:border-[var(--color-brand-primary)] focus:shadow-[0_0_0_3px_rgba(9,88,102,0.15)]"
					@keydown.enter.prevent="searchPudo" />
			</div>

			<div class="w-full tablet:max-w-[130px]">
				<label class="block text-[0.75rem] text-[var(--color-brand-text-secondary)] mb-[4px]">CAP</label>
				<input
					id="pudo_search_zip"
					v-model="searchZip"
					type="text"
					maxlength="5"
					placeholder="es. 09016"
					class="w-full bg-white rounded-[16px] h-[44px] px-[10px] text-[1rem] border border-[var(--color-brand-border)] transition-[border-color,box-shadow] duration-200 focus:border-[var(--color-brand-primary)] focus:shadow-[0_0_0_3px_rgba(9,88,102,0.15)]"
					@keydown.enter.prevent="searchPudo" />
			</div>

			<div class="col-span-1 tablet:col-span-2 desktop:col-span-1 flex flex-col tablet:flex-row items-stretch tablet:items-end gap-[8px] w-full tablet:w-auto">
				<button
					type="button"
					@click="searchPudo"
					:disabled="loading || !hasSearchInput"
					class="inline-flex items-center justify-center gap-[6px] h-[44px] px-[16px] bg-[var(--color-brand-primary)] text-white rounded-[16px] text-[0.875rem] font-semibold hover:bg-[var(--color-brand-primary-hover)] transition cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed whitespace-nowrap min-w-0 tablet:min-w-[142px]">
					<svg v-if="!loading" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
					<span v-if="loading" class="inline-block w-[16px] h-[16px] border-2 border-white border-t-transparent rounded-full animate-spin"></span>
					{{ loading ? 'Ricerca...' : 'Cerca punti' }}
				</button>
				<button
					type="button"
					@click="useCurrentLocation"
					:disabled="geolocating || loading"
					class="inline-flex items-center justify-center gap-[6px] h-[44px] px-[14px] bg-white text-[var(--color-brand-primary)] border border-[#C6D2D5] rounded-[16px] text-[0.8125rem] font-semibold hover:bg-[#F2F8F9] transition cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed whitespace-nowrap min-w-0 tablet:min-w-[150px]">
					<span v-if="geolocating" class="inline-block w-[14px] h-[14px] border-2 border-[var(--color-brand-primary)] border-t-transparent rounded-full animate-spin"></span>
					<span v-else class="inline-flex items-center gap-[6px]">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
							<circle cx="12" cy="12" r="3"/>
						</svg>
						Usa posizione
					</span>
				</button>
			</div>
		</div>

		<p v-if="searchError" class="text-red-500 text-[0.875rem] mt-[12px]">{{ searchError }}</p>

		<div v-if="searched && !loading" class="mt-[12px] flex flex-wrap items-center gap-[8px] text-[0.8125rem]">
			<span class="inline-flex items-center h-[28px] px-[10px] rounded-full bg-[#ECF6F7] text-[var(--color-brand-primary)] font-semibold">
				{{ pudoResults.length }} risultati trovati
			</span>
			<span class="inline-flex items-center h-[28px] px-[10px] rounded-full bg-[#F4FAFB] text-[var(--color-brand-primary)] border border-[#CBE0E4] font-semibold">
				Provider: BRT
			</span>
			<span v-if="searchMeta?.fallback" class="inline-flex items-center h-[28px] px-[10px] rounded-full bg-amber-50 text-amber-700 border border-amber-200 font-medium">
				Fallback attivo
			</span>
			<span v-if="strategyListLabel" class="text-[var(--color-brand-text-secondary)]">Strategia: {{ strategyListLabel }}</span>
		</div>

		<div v-if="searched" class="mt-[12px] grid grid-cols-1 desktop:grid-cols-2 gap-[14px] items-stretch">
			<div class="order-2 desktop:order-1 h-[360px] tablet:h-[420px] desktop:h-[520px]">
				<div v-if="loading" class="flex items-center justify-center h-full">
					<span class="inline-block w-[28px] h-[28px] border-3 border-[var(--color-brand-border)] border-t-[var(--color-brand-primary)] rounded-full animate-spin"></span>
				</div>

				<div v-else class="h-full flex flex-col">
					<p v-if="pudoResults.length === 0 && !searchError" class="text-[0.875rem] text-[var(--color-brand-text-secondary)] px-[10px] text-center flex-1 flex items-center justify-center">
						Nessun punto di ritiro trovato per questa zona. Prova con un'altra citta o CAP.
					</p>

					<div v-else class="grid grid-cols-1 gap-[10px] content-start flex-1 overflow-y-auto pr-[4px]">
						<div
							v-for="pudo in pudoResults"
							:key="pudo.ui_key"
							class="bg-white rounded-[16px] border-2 p-[14px] transition-[border-color,box-shadow] duration-200 cursor-pointer min-h-[168px]"
							:class="[
								expandedPudoKey === String(pudo.pudo_id || pudo.ui_key) ? 'h-auto' : 'h-[168px]',
								selectedPudoKey === pudo.ui_key ? 'border-[var(--color-brand-primary)] shadow-md' : 'border-[var(--color-brand-border)] hover:border-[var(--color-brand-primary)]/50'
							]"
							@click="selectPudo(pudo)">
							<div class="flex items-start justify-between gap-[10px]">
								<div class="flex-1 min-w-0">
									<div class="flex items-center gap-[6px]">
										<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--color-brand-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
										<span class="text-[0.875rem] font-bold text-[var(--color-brand-text)] truncate">{{ pudo.name }}</span>
									</div>
									<div class="mt-[6px] flex flex-wrap items-center gap-[6px]">
										<span class="inline-flex items-center h-[22px] px-[9px] rounded-full border border-[#CBE0E4] bg-[#F4FAFB] text-[var(--color-brand-primary)] text-[0.6875rem] font-semibold uppercase tracking-[0.2px]">
											Punto BRT
										</span>
									</div>
									<p class="text-[0.8125rem] text-[var(--color-brand-text-secondary)] mt-[3px]">{{ pudo.address }}, {{ pudo.zip_code }} {{ pudo.city }}</p>
								</div>

								<div class="flex flex-col items-end gap-[6px] shrink-0">
									<span class="inline-flex items-center h-[26px] px-[10px] rounded-full bg-[#E7F3F6] border border-[#C0DFE6] text-[var(--color-brand-primary)] text-[0.8125rem] font-black tracking-[0.15px] leading-none">
										Distanza: {{ distanceLabel(pudo) }}
									</span>
									<span class="inline-flex items-center px-[8px] h-[24px] rounded-full border text-[0.6875rem] font-semibold" :class="getPudoStatus(pudo).className">
										{{ getPudoStatus(pudo).label }}
									</span>
									<div
										class="w-[22px] h-[22px] rounded-full border-[2px] flex items-center justify-center"
										:class="selectedPudoKey === pudo.ui_key ? 'border-[var(--color-brand-primary)] bg-[var(--color-brand-primary)]' : 'border-[#95A3B3] bg-transparent'">
										<div v-if="selectedPudoKey === pudo.ui_key" class="w-[10px] h-[10px] rounded-full bg-white"></div>
									</div>
								</div>
							</div>

							<div class="mt-[2px] grid gap-[2px] text-[0.75rem] text-[var(--color-brand-text-secondary)]">
								<p class="inline-flex items-center gap-[4px]">
									<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
									{{ getTodayHoursText(pudo) }}
								</p>
							</div>

							<div v-if="expandedPudoKey === String(pudo.pudo_id || pudo.ui_key)" class="mt-[10px] pt-[10px] border-t border-[#E4E4E4] text-[0.8125rem]" @click.stop>
								<div v-if="loadingDetailsKey === String(pudo.pudo_id || pudo.ui_key)" class="flex items-center gap-[6px] text-[var(--color-brand-text-secondary)]">
									<span class="inline-block w-[14px] h-[14px] border-2 border-[var(--color-brand-primary)] border-t-transparent rounded-full animate-spin"></span>
									Caricamento dettagli...
								</div>
								<template v-else-if="pudoDetails[String(pudo.pudo_id || pudo.ui_key)]">
									<p v-if="pudoDetails[String(pudo.pudo_id || pudo.ui_key)].opening_hours" class="text-[var(--color-brand-text)]">
										<span class="font-semibold">Orari completi:</span>
										{{ formatOpeningHours(pudoDetails[String(pudo.pudo_id || pudo.ui_key)].opening_hours) }}
									</p>
									<p v-if="pudoDetails[String(pudo.pudo_id || pudo.ui_key)].localization_hint" class="text-[var(--color-brand-text-secondary)] mt-[4px]">
										{{ pudoDetails[String(pudo.pudo_id || pudo.ui_key)].localization_hint }}
									</p>
								</template>
								<p v-else-if="detailsErrors[String(pudo.pudo_id || pudo.ui_key)]" class="text-red-600">
									{{ detailsErrors[String(pudo.pudo_id || pudo.ui_key)] }}
								</p>
							</div>

							<button
								type="button"
								@click.stop="toggleDetails(pudo)"
								class="mt-[6px] text-[0.75rem] text-[var(--color-brand-primary)] font-semibold hover:opacity-80 cursor-pointer">
								{{ expandedPudoKey === String(pudo.pudo_id || pudo.ui_key) ? 'Chiudi dettagli' : 'Dettagli e orari' }}
							</button>
						</div>
					</div>
				</div>
			</div>

			<div class="order-1 desktop:order-2 h-[360px] tablet:h-[420px] desktop:h-[520px] desktop:sticky desktop:top-[92px]">
				<div class="h-full bg-white rounded-[16px] border border-[var(--color-brand-border)] p-[8px] flex flex-col">
					<div class="shrink-0 rounded-[16px] border border-[#D8E6EB] bg-[#F8FCFD] px-[10px] py-[8px]">
						<p class="text-[0.75rem] text-[var(--color-brand-text-secondary)]">
							Doppio clic sulla mappa per impostare il punto di riferimento e aggiornare automaticamente via, citta e CAP.
						</p>
						<p v-if="mapClickLoading" class="text-[0.75rem] font-semibold text-[var(--color-brand-primary)] mt-[4px]">Aggiornamento in corso...</p>
						<p v-else-if="referenceUpdateMessage" class="text-[0.75rem] text-[#0a8a7a] mt-[4px]">{{ referenceUpdateMessage }}</p>
					</div>

					<div class="mt-[8px] flex-1 min-h-0">
						<MapPudo
							:points="mapPoints"
							:selected-id="selectedPudoKey"
							:reference-point="mapReferencePoint"
							@select="selectPudo"
							@map-click="onMapReferenceClick" />
					</div>

					<p
						v-if="!loading && mapPoints.length === 0 && !searchError"
						class="mt-[8px] text-[0.8125rem] text-[var(--color-brand-text-secondary)]">
						Nessun punto trovato: la mappa mostra il riferimento inserito oppure la vista Italia.
					</p>
				</div>
			</div>
		</div>
	</div>
</template>
