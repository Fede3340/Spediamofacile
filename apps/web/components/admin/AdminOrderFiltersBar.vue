<!-- COMPONENTE: AdminOrderFiltersBar.vue -->
<script setup>
import '~/assets/css/admin.css';
import { ref, computed, watch } from 'vue';

const props = defineProps({
	/* Stato filtri controllato dal parent (v-model:filters) */
	filters: {
		type: Object,
		required: true,
	},
	/* Lista chip servizi disponibili (label) */
	services: {
		type: Array,
		default: () => [],
	},
	/* Lista opzioni stato (value/label) */
	statusOptions: {
		type: Array,
		default: () => [],
	},
	/* Conteggio risultati visibili (chip badge) */
	visibleCount: { type: Number, default: 0 },
	totalCount: { type: Number, default: 0 },
	/* Loading export CSV */
	exportLoading: { type: Boolean, default: false },
});

const emit = defineEmits([
	'update:filters',
	'apply',
	'reset',
	'export-csv',
]);

/* --- Sezione "filtri avanzati" collassabile per non sovraccaricare ---- */
const advancedOpen = ref(false);

/* Helper per aggiornare un singolo campo del v-model */
const setField = (key, value) => {
	emit('update:filters', { ...props.filters, [key]: value });
};

/* Toggle multi-select stato (array) */
const toggleStatus = (value) => {
	const current = Array.isArray(props.filters.status) ? [...props.filters.status] : [];
	const idx = current.indexOf(value);
	if (idx >= 0) current.splice(idx, 1);
	else current.push(value);
	setField('status', current);
};

const isStatusActive = (value) => Array.isArray(props.filters.status) && props.filters.status.includes(value);

/* Toggle chip servizio */
const toggleService = (label) => {
	const current = Array.isArray(props.filters.services) ? [...props.filters.services] : [];
	const idx = current.indexOf(label);
	if (idx >= 0) current.splice(idx, 1);
	else current.push(label);
	setField('services', current);
};

const isServiceActive = (label) => Array.isArray(props.filters.services) && props.filters.services.includes(label);

/* Conteggio filtri attivi (per badge sul pulsante "Avanzati") */
const activeAdvancedCount = computed(() => {
	let n = 0;
	if (props.filters.date_from) n++;
	if (props.filters.date_to) n++;
	if (props.filters.amount_min) n++;
	if (props.filters.amount_max) n++;
	if (Array.isArray(props.filters.services) && props.filters.services.length) n++;
	return n;
});

const hasAnyFilter = computed(() => {
	return Boolean(
		props.filters.search
		|| (Array.isArray(props.filters.status) && props.filters.status.length)
		|| activeAdvancedCount.value
	);
});

/* Debounce ricerca testuale */
let searchDebounce;
const onSearchInput = (e) => {
	const value = e.target.value;
	setField('search', value);
	clearTimeout(searchDebounce);
	searchDebounce = setTimeout(() => emit('apply'), 350);
};

/* Apply manuale per gli altri filtri */
const onApply = () => emit('apply');
const onReset = () => emit('reset');
</script>

<template>
	<section class="m6-filters-bar" aria-label="Filtri ordini">
		<!-- Riga 1: ricerca + status multi + azioni primarie -->
		<div class="m6-filters-bar__row m6-filters-bar__row--primary">
			<!-- Ricerca: codice ordine, email, nome cliente -->
			<label class="m6-filters-bar__search">
				<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="m6-filters-bar__search-icon" fill="currentColor">
					<path d="M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3M9.5,5C7,5 5,7 5,9.5C5,12 7,14 9.5,14C12,14 14,12 14,9.5C14,7 12,5 9.5,5Z" />
				</svg>
				<input
					type="search"
					:value="filters.search"
					placeholder="Cerca codice ordine, email o nome cliente"
					class="m6-filters-bar__search-input"
					autocomplete="off"
					@input="onSearchInput" />
				<button
					v-if="filters.search"
					type="button"
					class="m6-filters-bar__search-clear"
					aria-label="Pulisci ricerca"
					@click="setField('search', ''); $emit('apply');">
					&times;
				</button>
			</label>

			<!-- Conteggio risultati -->
			<div class="m6-filters-bar__counter" aria-live="polite">
				<span class="m6-filters-bar__counter-num">{{ visibleCount }}</span>
				<span class="m6-filters-bar__counter-label">su {{ totalCount }} ordini</span>
			</div>

			<!-- Azioni: avanzati + reset + export -->
			<div class="m6-filters-bar__actions">
				<button
					type="button"
					class="m6-filters-bar__btn m6-filters-bar__btn--ghost"
					:aria-expanded="advancedOpen"
					@click="advancedOpen = !advancedOpen">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M6,13H18V11H6M3,6V8H21V6M10,18H14V16H10V18Z" /></svg>
					Filtri avanzati
					<span v-if="activeAdvancedCount" class="m6-filters-bar__badge">{{ activeAdvancedCount }}</span>
				</button>

				<button
					v-if="hasAnyFilter"
					type="button"
					class="m6-filters-bar__btn m6-filters-bar__btn--reset"
					@click="onReset">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z" /></svg>
					Reset
				</button>

				<button
					type="button"
					class="m6-filters-bar__btn m6-filters-bar__btn--primary"
					:disabled="exportLoading"
					@click="$emit('export-csv')">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20M12,19L8,15H10.5V12H13.5V15H16L12,19Z" /></svg>
					{{ exportLoading ? 'Export...' : 'Esporta CSV' }}
				</button>
			</div>
		</div>

		<!-- Riga 2: chip status multi-select -->
		<div v-if="statusOptions.length" class="m6-filters-bar__chips" role="group" aria-label="Filtra per stato">
			<button
				v-for="opt in statusOptions"
				:key="opt.value"
				type="button"
				:class="['m6-filters-bar__chip', isStatusActive(opt.value) ? 'm6-filters-bar__chip--active' : '']"
				:aria-pressed="isStatusActive(opt.value)"
				@click="toggleStatus(opt.value); onApply();">
				{{ opt.label }}
			</button>
		</div>

		<!-- Riga 3 (collassabile): filtri avanzati -->
		<Transition name="m6-fade">
			<div v-if="advancedOpen" class="m6-filters-bar__advanced">
				<div class="m6-filters-bar__advanced-grid">
					<!-- Date range -->
					<div class="m6-field">
						<label class="m6-field__label" for="m6-date-from">Da data</label>
						<input
							id="m6-date-from"
							type="date"
							class="m6-field__input"
							:value="filters.date_from"
							@change="setField('date_from', $event.target.value); onApply();" />
					</div>

					<div class="m6-field">
						<label class="m6-field__label" for="m6-date-to">A data</label>
						<input
							id="m6-date-to"
							type="date"
							class="m6-field__input"
							:value="filters.date_to"
							@change="setField('date_to', $event.target.value); onApply();" />
					</div>

					<!-- Amount min/max (in euro, lato BE convertire) -->
					<div class="m6-field">
						<label class="m6-field__label" for="m6-amount-min">Importo minimo (EUR)</label>
						<input
							id="m6-amount-min"
							type="number"
							min="0"
							step="0.01"
							inputmode="decimal"
							class="m6-field__input"
							placeholder="0,00"
							:value="filters.amount_min"
							@change="setField('amount_min', $event.target.value); onApply();" />
					</div>

					<div class="m6-field">
						<label class="m6-field__label" for="m6-amount-max">Importo massimo (EUR)</label>
						<input
							id="m6-amount-max"
							type="number"
							min="0"
							step="0.01"
							inputmode="decimal"
							class="m6-field__input"
							placeholder="999,00"
							:value="filters.amount_max"
							@change="setField('amount_max', $event.target.value); onApply();" />
					</div>
				</div>

				<!-- Chip servizi -->
				<div v-if="services.length" class="m6-filters-bar__service-chips">
					<span class="m6-filters-bar__service-label">Servizi:</span>
					<button
						v-for="srv in services"
						:key="srv"
						type="button"
						:class="['m6-filters-bar__chip', 'm6-filters-bar__chip--service', isServiceActive(srv) ? 'm6-filters-bar__chip--active' : '']"
						:aria-pressed="isServiceActive(srv)"
						@click="toggleService(srv); onApply();">
						{{ srv }}
					</button>
				</div>
			</div>
		</Transition>
	</section>
</template>
