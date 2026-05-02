<!-- AdminSpedizioniFlatView.vue - Vista lista spedizioni BRT admin. -->
<script setup>
import { getBrtTrackingUrl } from '~/utils/brtTracking';

defineProps({
	shipments: { type: Array, default: () => [] },
	formatDate: { type: Function, required: true },
	downloadLabel: { type: Function, required: true },
	getAvailableStatuses: { type: Function, required: true },
});

const emit = defineEmits(['change-status']);

const trattaLabel = (shipment) => {
	const from = shipment?.brt_departure_depot;
	const to = shipment?.brt_arrival_depot;
	if (from && to) return `${from} -> ${to}`;
	if (from) return `${from} -> -`;
	return '-';
};

const EXECUTION_STATUS_META = {
	requested: { tone: 'info', label: 'Ritiro richiesto' },
	completed: { tone: 'success', label: 'Ritiro completato' },
	failed: { tone: 'danger', label: 'Ritiro fallito' },
	manual_required: { tone: 'warning', label: 'Ritiro manuale' },
	pending: { tone: 'warning', label: 'Ritiro in attesa' },
	sent: { tone: 'success', label: 'Documenti inviati' },
	ready: { tone: 'info', label: 'Documenti pronti' },
};

const TONE_CLASS = {
	success: 'bg-brand-success-bg text-brand-success-fg',
	warning: 'bg-amber-50 text-amber-700',
	info: 'bg-brand-soft-bg text-brand-soft-text',
	neutral: 'bg-brand-bg-alt text-brand-text-secondary',
	danger: 'bg-red-50 text-red-700',
};

const buildExecutionBadges = (shipment) => {
	const badges = [];
	if (shipment?.pickup_status && EXECUTION_STATUS_META[shipment.pickup_status]) {
		badges.push(EXECUTION_STATUS_META[shipment.pickup_status]);
	}
	if (shipment?.bordero_status === 'completed') {
		badges.push({ tone: 'success', label: 'Bordero ok' });
	}
	if (shipment?.documents_status && EXECUTION_STATUS_META[shipment.documents_status]) {
		badges.push(EXECUTION_STATUS_META[shipment.documents_status]);
	}
	return badges;
};

const executionErrorLabel = (shipment) => {
	const error = String(shipment?.execution_error || '').trim();
	if (!error) return '';
	if (error.length <= 72) return error;
	return `${error.slice(0, 69)}...`;
};

const trackingHref = (shipment) => getBrtTrackingUrl(shipment);
</script>

<template>
	<AdminTableLayout
		:items="shipments"
		:columns="[
			{ key: 'id', label: 'Spedizione', width: '6%' },
			{ key: 'user', label: 'Utente', width: '18%' },
			{ key: 'tratta', label: 'Tratta', width: '9%' },
			{ key: 'parcel', label: 'Parcel ID BRT', width: '14%' },
			{ key: 'status', label: 'Stato', width: '20%' },
			{ key: 'created_at', label: 'Data', width: '9%' },
			{ key: 'actions', label: 'Azioni', width: '24%' },
		]">
		<template #mobile-card="{ item }">
			<article class="p-4 rounded-card border border-brand-border bg-brand-card transition hover:border-brand-primary/40">
				<div class="flex items-start gap-3 mb-3">
					<div class="flex-1 min-w-0">
						<div class="mb-1.5 flex flex-wrap items-center gap-2">
							<span class="text-sm font-bold text-brand-primary tabular-nums">#{{ item.id }}</span>
							<AdminStatusBadge :status="item.status" type="order" />
						</div>
						<p class="m-0 text-sm font-semibold text-brand-text">{{ item.user?.name }} {{ item.user?.surname }}</p>
						<p class="m-0 text-xs text-brand-text-secondary truncate">{{ item.user?.email }}</p>
					</div>
					<div class="shrink-0 text-right">
						<p class="m-0 text-xs text-brand-text-secondary whitespace-nowrap">{{ formatDate(item.created_at) }}</p>
						<span v-if="item.brt_pudo_id" class="mt-1 inline-flex items-center px-2 py-0.5 rounded-full bg-brand-accent-surface text-brand-accent text-[0.6875rem] font-semibold">PUDO</span>
					</div>
				</div>

				<div class="flex flex-col gap-1.5 py-2 border-t border-brand-border">
					<div class="flex justify-between text-xs">
						<span class="text-brand-text-muted">Tratta</span>
						<span class="text-brand-text font-medium">{{ trattaLabel(item) }}</span>
					</div>
					<div class="flex justify-between text-xs">
						<span class="text-brand-text-muted">Parcel ID</span>
						<span v-if="item.brt_parcel_id" class="font-mono text-brand-text">{{ item.brt_parcel_id }}</span>
						<span v-else class="text-brand-text-muted">-</span>
					</div>
				</div>

				<div v-if="buildExecutionBadges(item).length" class="mt-2.5 flex flex-wrap gap-2">
					<span
						v-for="badge in buildExecutionBadges(item)"
						:key="`${item.id}-${badge.label}`"
						:class="['inline-flex items-center px-2 py-0.5 rounded-full text-[0.6875rem] font-semibold', TONE_CLASS[badge.tone]]">
						{{ badge.label }}
					</span>
				</div>

				<p
					v-if="executionErrorLabel(item)"
					class="mt-2 text-[11px] leading-relaxed text-red-700">
					{{ executionErrorLabel(item) }}
				</p>

				<div class="mt-3 flex flex-wrap gap-1.5">
					<a
						v-if="trackingHref(item)"
						:href="trackingHref(item)"
						target="_blank"
						rel="noopener noreferrer"
						class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-pill border border-brand-border bg-brand-card text-brand-text text-xs font-semibold no-underline transition hover:bg-brand-bg-alt hover:border-brand-primary/40">
						<UIcon name="mdi:truck-fast" class="w-3.5 h-3.5" />
						Tracking
					</a>
					<button
						v-if="item.brt_parcel_id"
						type="button"
						class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-pill border border-brand-border bg-brand-card text-brand-text text-xs font-semibold transition hover:bg-brand-bg-alt hover:border-brand-primary/40"
						@click="downloadLabel(item)">
						<UIcon name="mdi:download" class="w-3.5 h-3.5" />
						Etichetta
					</button>
					<select
						class="px-2.5 py-1.5 rounded-pill border border-brand-border bg-brand-card text-brand-text text-xs font-semibold cursor-pointer focus:outline-none focus:border-brand-primary"
						@change="emit('change-status', item.id, $event.target.value); $event.target.value = ''">
						<option value="" selected disabled>Cambia stato</option>
						<option v-for="s in getAvailableStatuses(item.status)" :key="s.value" :value="s.value">{{ s.label }}</option>
					</select>
				</div>
			</article>
		</template>

		<template #desktop-row="{ item }">
			<tr class="border-b border-brand-border last:border-0 hover:bg-brand-bg-alt transition">
				<td class="py-3 px-3">
					<span class="text-sm font-bold text-brand-primary tabular-nums">#{{ item.id }}</span>
				</td>
				<td class="py-3 px-3">
					<p class="m-0 text-sm font-semibold text-brand-text">{{ item.user?.name }} {{ item.user?.surname }}</p>
					<p class="m-0 text-xs text-brand-text-secondary truncate">{{ item.user?.email }}</p>
				</td>
				<td class="py-3 px-3">
					<span class="text-xs text-brand-text">{{ trattaLabel(item) }}</span>
				</td>
				<td class="py-3 px-3">
					<div class="flex flex-wrap items-center gap-1.5">
						<span v-if="item.brt_parcel_id" class="font-mono text-xs text-brand-text">{{ item.brt_parcel_id }}</span>
						<span v-else class="text-brand-text-muted">-</span>
						<span v-if="item.brt_pudo_id" class="inline-flex items-center px-2 py-0.5 rounded-full bg-brand-accent-surface text-brand-accent text-[0.6875rem] font-semibold">PUDO</span>
					</div>
				</td>
				<td class="py-3 px-3">
					<div class="flex flex-col gap-1">
						<AdminStatusBadge :status="item.status" type="order" />
						<span
							v-if="buildExecutionBadges(item).length"
							:title="buildExecutionBadges(item).map(b => b.label).join(' · ')"
							class="text-[10px] text-brand-text-muted cursor-help">
							+{{ buildExecutionBadges(item).length }} dettagli
						</span>
						<span
							v-if="executionErrorLabel(item)"
							:title="executionErrorLabel(item)"
							class="inline-flex max-w-fit items-center rounded-full bg-red-50 px-1.5 py-0.5 text-[10px] font-semibold text-red-700 cursor-help">
							⚠ Errore
						</span>
					</div>
				</td>
				<td class="py-3 px-3">
					<span class="text-xs text-brand-text-secondary whitespace-nowrap">{{ formatDate(item.created_at) }}</span>
				</td>
				<td class="py-3 px-3">
					<div class="flex items-center gap-1.5">
						<a
							v-if="trackingHref(item)"
							:href="trackingHref(item)"
							target="_blank"
							rel="noopener noreferrer"
							class="inline-flex items-center gap-1 px-2 py-1 rounded-pill border border-brand-border bg-brand-card text-brand-text text-[0.6875rem] font-semibold no-underline transition hover:bg-brand-bg-alt"
							aria-label="Apri tracking BRT">
							<UIcon name="mdi:truck-fast" class="w-3 h-3" />
							Track
						</a>
						<button
							v-if="item.brt_parcel_id"
							type="button"
							class="inline-flex items-center gap-1 px-2 py-1 rounded-pill border border-brand-border bg-brand-card text-brand-text text-[0.6875rem] font-semibold transition hover:bg-brand-bg-alt"
							aria-label="Scarica etichetta"
							@click="downloadLabel(item)">
							<UIcon name="mdi:download" class="w-3 h-3" />
							Etichetta
						</button>
						<select
							class="px-2 py-1 rounded-pill border border-brand-border bg-brand-card text-brand-text text-[0.6875rem] font-semibold cursor-pointer focus:outline-none focus:border-brand-primary"
							aria-label="Cambia stato spedizione"
							@change="emit('change-status', item.id, $event.target.value); $event.target.value = ''">
							<option value="" selected disabled>Stato</option>
							<option v-for="s in getAvailableStatuses(item.status)" :key="s.value" :value="s.value">{{ s.label }}</option>
						</select>
					</div>
				</td>
			</tr>
		</template>
	</AdminTableLayout>
</template>
