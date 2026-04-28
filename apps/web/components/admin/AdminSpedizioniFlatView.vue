<!-- AdminSpedizioniFlatView.vue - Vista lista spedizioni BRT admin. -->
<script setup>
import '~/assets/css/admin.css';
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
			<article class="admin-card admin-spedizioni-card">
				<div class="admin-spedizioni-card__head">
					<div class="min-w-0">
						<div class="mb-[6px] flex flex-wrap items-center gap-[8px]">
							<span class="admin-spedizioni-id">#{{ item.id }}</span>
							<AdminStatusBadge :status="item.status" type="order" />
						</div>
						<p class="admin-spedizioni-user-name">{{ item.user?.name }} {{ item.user?.surname }}</p>
						<p class="admin-spedizioni-user-email">{{ item.user?.email }}</p>
					</div>
					<div class="shrink-0 text-right">
						<p class="admin-spedizioni-date">{{ formatDate(item.created_at) }}</p>
						<span v-if="item.brt_pudo_id" class="admin-spedizioni-pudo-chip">PUDO</span>
					</div>
				</div>

				<div class="admin-spedizioni-meta">
					<span class="admin-spedizioni-meta-label">Tratta</span>
					<span class="admin-spedizioni-meta-value">{{ trattaLabel(item) }}</span>
				</div>

				<div class="admin-spedizioni-meta">
					<span class="admin-spedizioni-meta-label">Parcel ID</span>
					<span v-if="item.brt_parcel_id" class="admin-spedizioni-parcel">{{ item.brt_parcel_id }}</span>
					<span v-else class="admin-spedizioni-meta-value">-</span>
				</div>

				<div v-if="buildExecutionBadges(item).length" class="mt-[10px] flex flex-wrap gap-[8px]">
					<span
						v-for="badge in buildExecutionBadges(item)"
						:key="`${item.id}-${badge.label}`"
						:class="['admin-status-badge', `admin-status-badge--${badge.tone}`]">
						{{ badge.label }}
					</span>
				</div>

				<p
					v-if="executionErrorLabel(item)"
					class="mt-[8px] text-[11px] leading-[1.45] text-[var(--admin-status-danger-text)]">
					{{ executionErrorLabel(item) }}
				</p>

				<div class="admin-spedizioni-actions">
					<a
						v-if="trackingHref(item)"
						:href="trackingHref(item)"
						target="_blank"
						rel="noopener noreferrer"
						class="admin-spedizioni-btn">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[13px] w-[13px]" fill="currentColor"><path d="M18,15A3,3 0 0,1 21,18A3,3 0 0,1 18,21C16.69,21 15.58,20.17 15.17,19H14V17H15.17C15.58,15.83 16.69,15 18,15M18,17A1,1 0 0,0 17,18A1,1 0 0,0 18,19A1,1 0 0,0 19,18A1,1 0 0,0 18,17M6,15A3,3 0 0,1 9,18A3,3 0 0,1 6,21A3,3 0 0,1 3,18A3,3 0 0,1 6,15M6,17A1,1 0 0,0 5,18A1,1 0 0,0 6,19A1,1 0 0,0 7,18A1,1 0 0,0 6,17M11,7L9.5,13H13.5L12,7M9,3H14L18,17H12.5L12,15H11L10.5,17H5L9,3Z" /></svg>
						Tracking
					</a>
					<button
						v-if="item.brt_parcel_id"
						type="button"
						@click="downloadLabel(item)"
						class="admin-spedizioni-btn">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[13px] w-[13px]" fill="currentColor"><path d="M5,20H19V18H5M19,9H15V3H9V9H5L12,16L19,9Z" /></svg>
						Etichetta
					</button>
					<select
						@change="emit('change-status', item.id, $event.target.value); $event.target.value = ''"
						class="admin-spedizioni-select">
						<option value="" selected disabled>Cambia stato</option>
						<option v-for="s in getAvailableStatuses(item.status)" :key="s.value" :value="s.value">{{ s.label }}</option>
					</select>
				</div>
			</article>
		</template>

		<template #desktop-row="{ item }">
			<tr class="admin-row admin-spedizioni-row">
				<td class="admin-spedizioni-td">
					<span class="admin-spedizioni-id">#{{ item.id }}</span>
				</td>
				<td class="admin-spedizioni-td">
					<p class="admin-spedizioni-user-name">{{ item.user?.name }} {{ item.user?.surname }}</p>
					<p class="admin-spedizioni-user-email">{{ item.user?.email }}</p>
				</td>
				<td class="admin-spedizioni-td">
					<span class="admin-spedizioni-tratta">{{ trattaLabel(item) }}</span>
				</td>
				<td class="admin-spedizioni-td">
					<div class="flex flex-wrap items-center gap-[6px]">
						<span v-if="item.brt_parcel_id" class="admin-spedizioni-parcel">{{ item.brt_parcel_id }}</span>
						<span v-else class="admin-spedizioni-muted">-</span>
						<span v-if="item.brt_pudo_id" class="admin-spedizioni-pudo-chip">PUDO</span>
					</div>
				</td>
				<td class="admin-spedizioni-td">
					<!-- P14: solo status principale + chip errore se presente. Sub-badges (Bordero ok,
					     Documenti inviati ecc.) in tooltip al hover invece di accatastati. -->
					<div class="flex flex-col gap-[4px]">
						<AdminStatusBadge :status="item.status" type="order" />
						<span
							v-if="buildExecutionBadges(item).length"
							:title="buildExecutionBadges(item).map(b => b.label).join(' · ')"
							class="text-[10px] text-[var(--color-brand-text-muted)] cursor-help">
							+{{ buildExecutionBadges(item).length }} dettagli
						</span>
						<span
							v-if="executionErrorLabel(item)"
							:title="executionErrorLabel(item)"
							class="inline-flex max-w-fit items-center rounded-full bg-[#FEF2F2] px-[6px] py-[1px] text-[10px] font-semibold text-[#B91C1C] cursor-help">
							⚠ Errore
						</span>
					</div>
				</td>
				<td class="admin-spedizioni-td">
					<span class="admin-spedizioni-date">{{ formatDate(item.created_at) }}</span>
				</td>
				<td class="admin-spedizioni-td">
					<div class="admin-spedizioni-td-actions">
						<a
							v-if="trackingHref(item)"
							:href="trackingHref(item)"
							target="_blank"
							rel="noopener noreferrer"
							class="admin-spedizioni-btn admin-spedizioni-btn--compact"
							aria-label="Apri tracking BRT">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[13px] w-[13px]" fill="currentColor"><path d="M18,15A3,3 0 0,1 21,18A3,3 0 0,1 18,21C16.69,21 15.58,20.17 15.17,19H14V17H15.17C15.58,15.83 16.69,15 18,15M18,17A1,1 0 0,0 17,18A1,1 0 0,0 18,19A1,1 0 0,0 19,18A1,1 0 0,0 18,17M6,15A3,3 0 0,1 9,18A3,3 0 0,1 6,21A3,3 0 0,1 3,18A3,3 0 0,1 6,15M6,17A1,1 0 0,0 5,18A1,1 0 0,0 6,19A1,1 0 0,0 7,18A1,1 0 0,0 6,17M11,7L9.5,13H13.5L12,7M9,3H14L18,17H12.5L12,15H11L10.5,17H5L9,3Z" /></svg>
							Track
						</a>
						<button
							v-if="item.brt_parcel_id"
							type="button"
							@click="downloadLabel(item)"
							class="admin-spedizioni-btn admin-spedizioni-btn--compact"
							aria-label="Scarica etichetta">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[13px] w-[13px]" fill="currentColor"><path d="M5,20H19V18H5M19,9H15V3H9V9H5L12,16L19,9Z" /></svg>
							Etichetta
						</button>
						<select
							@change="emit('change-status', item.id, $event.target.value); $event.target.value = ''"
							class="admin-spedizioni-select admin-spedizioni-select--compact"
							aria-label="Cambia stato spedizione">
							<option value="" selected disabled>Stato</option>
							<option v-for="s in getAvailableStatuses(item.status)" :key="s.value" :value="s.value">{{ s.label }}</option>
						</select>
					</div>
				</td>
			</tr>
		</template>
	</AdminTableLayout>
</template>
