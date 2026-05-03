<!-- AdminOrderFiltersBar.vue — Barra filtri ordini admin -->
<script setup>

const props = defineProps({
	filters: { type: Object, required: true },
	services: { type: Array, default: () => [] },
	statusOptions: { type: Array, default: () => [] },
	visibleCount: { type: Number, default: 0 },
	totalCount: { type: Number, default: 0 },
	exportLoading: { type: Boolean, default: false },
});

const emit = defineEmits(['update:filters', 'apply', 'reset', 'export-csv']);

const advancedOpen = ref(false);

const setField = (key, value) => {
	emit('update:filters', { ...props.filters, [key]: value });
};

const toggleStatus = (value) => {
	const current = Array.isArray(props.filters.status) ? [...props.filters.status] : [];
	const idx = current.indexOf(value);
	if (idx >= 0) current.splice(idx, 1);
	else current.push(value);
	setField('status', current);
};

const isStatusActive = (value) => Array.isArray(props.filters.status) && props.filters.status.includes(value);

const toggleService = (label) => {
	const current = Array.isArray(props.filters.services) ? [...props.filters.services] : [];
	const idx = current.indexOf(label);
	if (idx >= 0) current.splice(idx, 1);
	else current.push(label);
	setField('services', current);
};

const isServiceActive = (label) => Array.isArray(props.filters.services) && props.filters.services.includes(label);

const activeAdvancedCount = computed(() => {
	let n = 0;
	if (props.filters.date_from) n++;
	if (props.filters.date_to) n++;
	if (props.filters.amount_min) n++;
	if (props.filters.amount_max) n++;
	if (Array.isArray(props.filters.services) && props.filters.services.length) n++;
	return n;
});

const hasAnyFilter = computed(() => Boolean(
	props.filters.search
	|| (Array.isArray(props.filters.status) && props.filters.status.length)
	|| activeAdvancedCount.value,
));

let searchDebounce;
const onSearchInput = (e) => {
	const value = e.target.value;
	setField('search', value);
	clearTimeout(searchDebounce);
	searchDebounce = setTimeout(() => emit('apply'), 350);
};

const onApply = () => emit('apply');
const onReset = () => emit('reset');
</script>

<template>
	<section class="bg-brand-card rounded-card border border-brand-border p-4 flex flex-col gap-3" aria-label="Filtri ordini">
		<div class="flex flex-wrap items-center gap-3">
			<label class="relative flex-1 min-w-[240px]">
				<UIcon name="mdi:magnify" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-brand-text-muted pointer-events-none" />
				<input
					type="search"
					:value="filters.search"
					placeholder="Cerca codice ordine, email o nome cliente"
					class="w-full h-10 pl-9 pr-9 rounded-control border border-brand-border bg-brand-bg-alt text-sm text-brand-text focus:outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20 focus:bg-brand-card"
					autocomplete="off"
					@input="onSearchInput">
				<button
					v-if="filters.search"
					type="button"
					class="absolute right-2 top-1/2 -translate-y-1/2 inline-flex items-center justify-center w-6 h-6 rounded-full text-brand-text-muted hover:bg-brand-border hover:text-brand-accent"
					aria-label="Pulisci ricerca"
					@click="setField('search', ''); $emit('apply');">
					<UIcon name="mdi:close" class="w-3.5 h-3.5" />
				</button>
			</label>

			<div class="flex items-baseline gap-1.5" aria-live="polite">
				<span class="text-base font-bold text-brand-primary tabular-nums">{{ visibleCount }}</span>
				<span class="text-xs text-brand-text-secondary">su {{ totalCount }} ordini</span>
			</div>

			<div class="flex items-center gap-1.5">
				<button
					type="button"
					class="relative inline-flex items-center gap-1.5 h-10 px-3 rounded-control border border-brand-border bg-brand-card text-brand-text text-sm font-semibold cursor-pointer transition hover:bg-brand-bg-alt"
					:aria-expanded="advancedOpen"
					@click="advancedOpen = !advancedOpen">
					<UIcon name="mdi:filter-variant" class="w-4 h-4" />
					Filtri avanzati
					<span v-if="activeAdvancedCount" class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-brand-primary text-white text-[0.6875rem] font-bold">{{ activeAdvancedCount }}</span>
				</button>

				<button
					v-if="hasAnyFilter"
					type="button"
					class="inline-flex items-center gap-1.5 h-10 px-3 rounded-control border border-brand-border bg-brand-card text-brand-text-secondary text-sm font-semibold cursor-pointer transition hover:bg-brand-bg-alt hover:text-brand-text"
					@click="onReset">
					<UIcon name="mdi:close" class="w-4 h-4" />
					Reset
				</button>

				<SfButton
					variant="primary"
					size="sm"
					:loading="exportLoading"
					:disabled="exportLoading"
					@click="$emit('export-csv')">
					<template #leading><UIcon name="mdi:download" class="w-4 h-4" /></template>
					{{ exportLoading ? 'Export...' : 'Esporta CSV' }}
				</SfButton>
			</div>
		</div>

		<div v-if="statusOptions.length" class="flex flex-wrap gap-1.5" role="group" aria-label="Filtra per stato">
			<button
				v-for="opt in statusOptions"
				:key="opt.value"
				type="button"
				:class="[
					'inline-flex items-center px-3 py-1.5 rounded-pill border text-xs font-semibold transition',
					isStatusActive(opt.value)
						? 'bg-brand-primary text-white border-brand-primary shadow-sf-sm'
						: 'bg-brand-card text-brand-text-secondary border-brand-border hover:bg-brand-bg-alt hover:text-brand-text',
				]"
				:aria-pressed="isStatusActive(opt.value)"
				@click="toggleStatus(opt.value); onApply();">
				{{ opt.label }}
			</button>
		</div>

		<Transition
			enter-active-class="transition-all duration-200"
			leave-active-class="transition-all duration-200"
			enter-from-class="opacity-0 -translate-y-2"
			leave-to-class="opacity-0 -translate-y-2">
			<div v-if="advancedOpen" class="pt-3 border-t border-brand-border flex flex-col gap-3">
				<div class="grid grid-cols-1 tablet:grid-cols-2 desktop:grid-cols-4 gap-3">
					<div class="flex flex-col gap-1">
						<label class="text-[0.6875rem] font-bold uppercase tracking-wider text-brand-text-muted" for="m6-date-from">Da data</label>
						<input
							id="m6-date-from"
							type="date"
							class="h-9 px-2.5 rounded-control border border-brand-border bg-brand-card text-sm text-brand-text focus:outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20"
							:value="filters.date_from"
							@change="setField('date_from', $event.target.value); onApply();">
					</div>

					<div class="flex flex-col gap-1">
						<label class="text-[0.6875rem] font-bold uppercase tracking-wider text-brand-text-muted" for="m6-date-to">A data</label>
						<input
							id="m6-date-to"
							type="date"
							class="h-9 px-2.5 rounded-control border border-brand-border bg-brand-card text-sm text-brand-text focus:outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20"
							:value="filters.date_to"
							@change="setField('date_to', $event.target.value); onApply();">
					</div>

					<div class="flex flex-col gap-1">
						<label class="text-[0.6875rem] font-bold uppercase tracking-wider text-brand-text-muted" for="m6-amount-min">Importo minimo (EUR)</label>
						<input
							id="m6-amount-min"
							type="number"
							min="0"
							step="0.01"
							inputmode="decimal"
							class="h-9 px-2.5 rounded-control border border-brand-border bg-brand-card text-sm text-brand-text focus:outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20"
							placeholder="0,00"
							:value="filters.amount_min"
							@change="setField('amount_min', $event.target.value); onApply();">
					</div>

					<div class="flex flex-col gap-1">
						<label class="text-[0.6875rem] font-bold uppercase tracking-wider text-brand-text-muted" for="m6-amount-max">Importo massimo (EUR)</label>
						<input
							id="m6-amount-max"
							type="number"
							min="0"
							step="0.01"
							inputmode="decimal"
							class="h-9 px-2.5 rounded-control border border-brand-border bg-brand-card text-sm text-brand-text focus:outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20"
							placeholder="999,00"
							:value="filters.amount_max"
							@change="setField('amount_max', $event.target.value); onApply();">
					</div>
				</div>

				<div v-if="services.length" class="flex flex-wrap items-center gap-1.5">
					<span class="text-[0.6875rem] font-bold uppercase tracking-wider text-brand-text-muted">Servizi:</span>
					<button
						v-for="srv in services"
						:key="srv"
						type="button"
						:class="[
							'inline-flex items-center px-3 py-1 rounded-pill border text-xs font-semibold transition',
							isServiceActive(srv)
								? 'bg-brand-accent text-white border-brand-accent shadow-sf-sm'
								: 'bg-brand-card text-brand-text-secondary border-brand-border hover:bg-brand-bg-alt hover:text-brand-text',
						]"
						:aria-pressed="isServiceActive(srv)"
						@click="toggleService(srv); onApply();">
						{{ srv }}
					</button>
				</div>
			</div>
		</Transition>
	</section>
</template>
