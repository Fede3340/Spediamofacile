<script setup>
import { formatDateTimeIt } from '~/utils/date.js';

const props = defineProps({
	movements: { type: Array, default: () => [] },
	isLoadingMovements: { type: Boolean, default: true },
	movementsError: { type: String, default: '' },
});

const emit = defineEmits(['retry-movements']);

const formatDate = (dateStr) => formatDateTimeIt(dateStr);

const hasMovements = computed(() => props.movements?.length > 0);
const hasBlockingError = computed(() => Boolean(props.movementsError) && !hasMovements.value);
const countLabel = computed(() => {
	if (props.isLoadingMovements && !hasMovements.value) return 'Caricamento';
	if (hasBlockingError.value) return 'Da aggiornare';
	if (!hasMovements.value) return 'Ancora nessuno';
	return `${props.movements.length} ${props.movements.length === 1 ? 'movimento' : 'movimenti'}`;
});

const getMovementColor = (mov) => {
	return mov.type === 'credit' ? 'text-brand-success-fg' : 'text-brand-error';
};

const getMovementSign = (mov) => {
	return mov.type === 'credit' ? '+' : '-';
};

const getSourceLabel = (source) => {
	const labels = {
		stripe: 'Carta',
		commission: 'Commissione',
		withdrawal: 'Prelievo',
		wallet: 'Portafoglio',
		refund: 'Rimborso',
	};
	return labels[source] || source || 'Operazione';
};

const getSourceColor = (source) => {
	const colors = {
		stripe: 'bg-brand-primary/10 text-brand-primary',
		commission: 'bg-status-pending-bg text-status-pending-fg',
		withdrawal: 'bg-brand-bg-alt text-brand-text-secondary',
		wallet: 'bg-brand-primary/10 text-brand-primary',
		refund: 'bg-status-failed-bg text-status-failed-fg',
	};
	return colors[source] || 'bg-brand-bg-alt text-brand-text-secondary';
};

const getMovementTitle = (mov) => {
	if (mov.description) return mov.description;
	if (mov.type === 'credit') return 'Entrata sul portafoglio';
	return 'Uscita dal portafoglio';
};

const getMovementSvg = (mov) => {
	if (mov.source === 'commission')
		return 'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zM6 20v-2a4 4 0 0 1 4-4h.5M16 16h2m0 0h2m-2 0v-2m0 2v2';
	if (mov.source === 'withdrawal') return 'M3 6h18M3 12h18M3 18h18M17 6l3 3-3 3';
	if (mov.source === 'wallet') return 'M21 18v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v1';
	if (mov.source === 'refund') return 'M3 10h4l3 8 4-16 3 8h4';
	if (mov.source === 'stripe') {
		return mov.type === 'credit' ? 'M1 4h22v16H1zM1 10h22M12 14h4' : 'M1 4h22v16H1zM1 10h22M10 14h4';
	}
	return 'M7 16V4m0 0L3 8m4-4 4 4M17 8v12m0 0 4-4m-4 4-4-4';
};
</script>

<template>
	<div class="mt-3.5 rounded-card border border-brand-border bg-white p-4 shadow-sf-sm desktop:mt-[18px] desktop:p-5">
		<div class="mb-4 flex flex-col gap-2.5 sm:flex-row sm:items-start sm:justify-between">
			<div class="flex items-start gap-3">
				<div class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-primary/10">
					<svg
						aria-hidden="true"
						width="20"
						height="20"
						viewBox="0 0 24 24"
						fill="none"
						stroke="currentColor"
						stroke-width="2"
						stroke-linecap="round"
						stroke-linejoin="round"
						class="text-brand-primary">
						<path d="M12 8v4l3 3" />
						<circle cx="12" cy="12" r="10" />
					</svg>
				</div>

				<div>
					<h2 class="font-display text-base font-extrabold text-brand-text">Movimenti</h2>
					<p class="mt-1 text-[0.8125rem] leading-snug text-brand-text-secondary">
						Ricariche, pagamenti, rimborsi e commissioni in ordine cronologico.
					</p>
				</div>
			</div>

			<span class="inline-flex w-fit items-center rounded-full bg-brand-bg-alt px-2.5 py-1.5 text-xs font-semibold text-brand-text-secondary">
				{{ countLabel }}
			</span>
		</div>

		<div
			v-if="movementsError && hasMovements"
			class="mb-4 flex flex-col gap-2.5 rounded-card border border-status-pending-fg/30 bg-status-pending-bg px-3 py-2.5 text-[0.8125rem] text-status-pending-fg tablet:flex-row tablet:items-center tablet:justify-between">
			<p class="leading-snug">Non sono riuscito ad aggiornare tutto lo storico in tempo reale. Ti mostro l'ultimo elenco disponibile.</p>
			<SfButton variant="secondary" size="sm" @click="emit('retry-movements')">Riprova storico</SfButton>
		</div>

		<div v-if="isLoadingMovements && !hasMovements" class="space-y-2.5 py-1">
			<div v-for="index in 4" :key="index" class="animate-pulse rounded-card border border-brand-border p-3">
				<div class="flex items-start gap-3">
					<div class="h-[38px] w-[38px] rounded-full bg-brand-bg-alt" />
					<div class="min-w-0 flex-1 space-y-2">
						<div class="h-3.5 w-[220px] max-w-full rounded-full bg-brand-border" />
						<div class="h-3 w-[160px] rounded-full bg-brand-border/60" />
					</div>
					<div class="h-3.5 w-[74px] rounded-full bg-brand-border" />
				</div>
			</div>
		</div>

		<div v-else-if="hasBlockingError" class="py-8 text-center">
			<div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-status-failed-bg">
				<svg
					aria-hidden="true"
					width="24"
					height="24"
					viewBox="0 0 24 24"
					fill="none"
					stroke="currentColor"
					stroke-width="2"
					stroke-linecap="round"
					stroke-linejoin="round"
					class="text-status-failed-fg">
					<circle cx="12" cy="12" r="10" />
					<line x1="12" y1="8" x2="12" y2="12" />
					<line x1="12" y1="16" x2="12.01" y2="16" />
				</svg>
			</div>
			<p class="text-[0.9375rem] font-medium text-brand-text">Storico non disponibile</p>
			<p class="mx-auto mt-1.5 max-w-[420px] text-[0.8125rem] leading-snug text-brand-text-secondary">
				{{ movementsError }}
			</p>
			<div class="mt-4 inline-flex">
				<SfButton variant="secondary" size="sm" @click="emit('retry-movements')">
					<template #leading>
						<svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M21 2v6h-6" />
							<path d="M3 12a9 9 0 0 1 15-6.7L21 8" />
							<path d="M3 22v-6h6" />
							<path d="M21 12a9 9 0 0 1-15 6.7L3 16" />
						</svg>
					</template>
					Riprova storico
				</SfButton>
			</div>
		</div>

		<div v-else-if="!hasMovements" class="py-8 text-center">
			<div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-brand-bg-alt">
				<svg
					aria-hidden="true"
					width="24"
					height="24"
					viewBox="0 0 24 24"
					fill="none"
					stroke="currentColor"
					stroke-width="2"
					stroke-linecap="round"
					stroke-linejoin="round"
					class="text-brand-text-muted">
					<path d="M4 4h16v16H4z" />
					<path d="M4 10h16" />
					<path d="M10 4v16" />
				</svg>
			</div>
			<p class="text-[0.9375rem] font-medium text-brand-text">Nessun movimento</p>
			<p class="mx-auto mt-1.5 max-w-[360px] text-[0.8125rem] leading-snug text-brand-text-secondary">
				I movimenti appariranno qui dopo la prima ricarica o il primo pagamento con il portafoglio.
			</p>
			<NuxtLink to="/preventivo" class="btn-primary btn-compact mt-4 inline-flex items-center gap-1.5">
				<svg
					aria-hidden="true"
					width="17"
					height="17"
					viewBox="0 0 24 24"
					fill="none"
					stroke="currentColor"
					stroke-width="2"
					stroke-linecap="round"
					stroke-linejoin="round">
					<line x1="12" y1="5" x2="12" y2="19" />
					<line x1="5" y1="12" x2="19" y2="12" />
				</svg>
				Crea la tua prima spedizione
			</NuxtLink>
		</div>

		<ul v-else class="space-y-2">
			<li
				v-for="(mov, index) in movements"
				:key="mov.id || `${mov.created_at || 'mov'}-${index}`"
				class="flex flex-col gap-2.5 rounded-card border border-brand-border p-3 sm:flex-row sm:items-center sm:gap-3">
				<div
					:class="[
						'flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-full ring-2 ring-offset-1',
						mov.type === 'credit'
							? 'bg-brand-success-bg ring-brand-success/20'
							: 'bg-status-failed-bg ring-brand-error/20',
					]">
					<svg
						aria-hidden="true"
						width="18"
						height="18"
						viewBox="0 0 24 24"
						fill="none"
						stroke="currentColor"
						stroke-width="2"
						stroke-linecap="round"
						stroke-linejoin="round"
						:class="mov.type === 'credit' ? 'text-brand-success-fg' : 'text-brand-error'">
						<path :d="getMovementSvg(mov)" />
					</svg>
				</div>

				<div class="min-w-0 flex-1">
					<p class="truncate text-sm font-medium text-brand-text">{{ getMovementTitle(mov) }}</p>
					<div class="mt-1 flex flex-wrap items-center gap-2">
						<span class="text-xs text-brand-text-secondary">{{ formatDate(mov.created_at) }}</span>
						<span :class="['rounded-full px-2 py-0.5 text-[0.6875rem] font-medium', getSourceColor(mov.source)]">
							{{ getSourceLabel(mov.source) }}
						</span>
					</div>
				</div>

				<span :class="['self-start whitespace-nowrap text-[0.9375rem] font-bold tabular-nums sm:self-auto', getMovementColor(mov)]">
					{{ getMovementSign(mov) }}&euro;{{ formatEuro(mov.amount) }}
				</span>
			</li>
		</ul>
	</div>
</template>
