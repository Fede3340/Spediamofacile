<!-- COMPONENTE: AdminOrderTable.vue -->
<script setup>
import '~/assets/css/admin.css';
import { computed } from 'vue';
import { getBrtTrackingLabel, getBrtTrackingUrl } from '~/utils/brtTracking';

const props = defineProps({
	orders: { type: Array, default: () => [] },
	/* sort = { key: 'created_at' | 'total' | 'status', dir: 'asc' | 'desc' } */
	sort: {
		type: Object,
		default: () => ({ key: 'created_at', dir: 'desc' }),
	},
	formatCents: { type: Function, required: true },
	formatDate: { type: Function, required: true },
	statusConfig: { type: Object, required: true },
});

const emit = defineEmits(['sort', 'action']);

const onSort = (key) => {
	const newDir = props.sort.key === key && props.sort.dir === 'desc' ? 'asc' : 'desc';
	emit('sort', { key, dir: newDir });
};

const sortIcon = (key) => {
	if (props.sort.key !== key) return '↕';
	return props.sort.dir === 'desc' ? '↓' : '↑';
};

/* Helper: estrai importo (cents) da subtotal (oggetto MyMoney o numero) */
const orderTotalCents = (order) => {
	const raw = order?.subtotal;
	if (raw && typeof raw === 'object' && 'amount' in raw) return Number(raw.amount || 0);
	return Number(raw || 0) * 100; // se in euro
};

/* Helper: stringa origine→destinazione abbreviata */
const orderRoute = (order) => {
	const pkg = order?.packages?.[0];
	const origin = pkg?.originAddress?.city || pkg?.origin_city || '—';
	const dest = pkg?.destinationAddress?.city || pkg?.destination_city || '—';
	return { origin, dest };
};

/* Stato badge config */
const resolvedOrderRoute = (order) => {
	const pkg = order?.packages?.[0];
	const origin = pkg?.originAddress?.city || pkg?.origin_address?.city || pkg?.origin_city || 'â€”';
	const dest = pkg?.destinationAddress?.city || pkg?.destination_address?.city || pkg?.destination_city || 'â€”';
	return { origin, dest };
};

const badgeFor = (status) => props.statusConfig?.[status] || { label: status, bg: 'bg-gray-100', text: 'text-gray-600' };

/* Click row naviga a dettaglio */
const onRowClick = (order, evt) => {
	// Evita navigazione se click su un bottone/link
	if (evt.target.closest('button, a')) return;
	emit('action', { type: 'detail', order });
};

/* Determina se mostrare "marca come pagato" */
const canMarkPaid = (order) => {
	return order.status === 'pending_transfer' || order.status === 'awaiting_bank_transfer';
};

const hasBordero = (order) => {
	return Boolean(
		order?.bordero_status === 'completed'
		|| order?.bordero_document_filename
		|| order?.bordero_reference,
	);
};

const trackingHref = (order) => getBrtTrackingUrl(order);
const trackingLabel = (order) => getBrtTrackingLabel(order);
</script>

<template>
	<div class="m6-order-table-wrap">
		<!-- DESKTOP: tabella sticky ----------------------------------- -->
		<div class="m6-order-table m6-only-desktop" role="region" aria-label="Tabella ordini">
			<table class="m6-order-table__table">
				<thead class="m6-order-table__head">
					<tr>
						<th scope="col" class="m6-order-table__th">
							<button type="button" class="m6-order-table__sort" @click="onSort('id')">
								Codice {{ sortIcon('id') }}
							</button>
						</th>
						<th scope="col" class="m6-order-table__th">Cliente</th>
						<th scope="col" class="m6-order-table__th">Tratta</th>
						<th scope="col" class="m6-order-table__th">
							<button type="button" class="m6-order-table__sort" @click="onSort('status')">
								Stato {{ sortIcon('status') }}
							</button>
						</th>
						<th scope="col" class="m6-order-table__th">Tracking</th>
						<th scope="col" class="m6-order-table__th m6-order-table__th--right">
							<button type="button" class="m6-order-table__sort" @click="onSort('total')">
								Totale {{ sortIcon('total') }}
							</button>
						</th>
						<th scope="col" class="m6-order-table__th">
							<button type="button" class="m6-order-table__sort" @click="onSort('created_at')">
								Data {{ sortIcon('created_at') }}
							</button>
						</th>
						<th scope="col" class="m6-order-table__th m6-order-table__th--actions">Azioni</th>
					</tr>
				</thead>
				<tbody>
					<tr
						v-for="order in orders"
						:key="order.id"
						class="m6-order-table__row"
						tabindex="0"
						@click="onRowClick(order, $event)"
						@keydown.enter="onRowClick(order, $event)">
						<td class="m6-order-table__td m6-order-table__td--code">
							<span class="m6-order-table__code">#{{ order.id }}</span>
						</td>
						<td class="m6-order-table__td">
							<div class="m6-order-table__cust">
								<span class="m6-order-table__cust-name">{{ order.user?.name || '—' }} {{ order.user?.surname || '' }}</span>
								<span class="m6-order-table__cust-email">{{ order.user?.email || '—' }}</span>
							</div>
						</td>
						<td class="m6-order-table__td">
							<div class="m6-order-table__route">
								<span class="m6-order-table__route-from">{{ resolvedOrderRoute(order).origin }}</span>
								<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" fill="currentColor" class="m6-order-table__route-arrow">
									<path d="M4,11V13H16L10.5,18.5L11.92,19.92L19.84,12L11.92,4.08L10.5,5.5L16,11H4Z" />
								</svg>
								<span class="m6-order-table__route-to">{{ resolvedOrderRoute(order).dest }}</span>
							</div>
						</td>
						<td class="m6-order-table__td">
							<span :class="['m6-status', badgeFor(order.status).bg, badgeFor(order.status).text]">
								{{ badgeFor(order.status).label }}
							</span>
						</td>
						<td class="m6-order-table__td">
							<a
								v-if="trackingHref(order)"
								:href="trackingHref(order)"
								target="_blank"
								rel="noopener noreferrer"
								class="m6-order-table__tracking"
								@click.stop>
								<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="13" height="13" fill="currentColor"><path d="M14,3V5H17.59L7.76,14.83L9.17,16.24L19,6.41V10H21V3M19,19H5V5H12V3H5C3.89,3 3,3.9 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V12H19V19Z" /></svg>
								{{ trackingLabel(order) }}
							</a>
							<span v-else class="m6-order-table__tracking m6-order-table__tracking--empty">—</span>
						</td>
						<td class="m6-order-table__td m6-order-table__td--right">
							<strong class="m6-order-table__amount">{{ formatCents(orderTotalCents(order)) }}</strong>
						</td>
						<td class="m6-order-table__td m6-order-table__td--date">
							{{ formatDate(order.created_at) }}
						</td>
						<td class="m6-order-table__td m6-order-table__td--actions">
							<div class="m6-order-table__actions">
								<button
									type="button"
									class="m6-act m6-act--ghost"
									title="Vedi dettaglio"
									aria-label="Vedi dettaglio ordine"
									@click.stop="$emit('action', { type: 'detail', order })">
									<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="15" height="15" fill="currentColor"><path d="M12,9A3,3 0 0,0 9,12A3,3 0 0,0 12,15A3,3 0 0,0 15,12A3,3 0 0,0 12,9M12,17A5,5 0 0,1 7,12A5,5 0 0,1 12,7A5,5 0 0,1 17,12A5,5 0 0,1 12,17M12,4.5C7,4.5 2.73,7.61 1,12C2.73,16.39 7,19.5 12,19.5C17,19.5 21.27,16.39 23,12C21.27,7.61 17,4.5 12,4.5Z" /></svg>
								</button>
								<button
									type="button"
									class="m6-act m6-act--ghost"
									title="Fattura"
									aria-label="Scarica fattura"
									@click.stop="$emit('action', { type: 'invoice', order })">
									<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="15" height="15" fill="currentColor"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z" /></svg>
								</button>
								<button
									v-if="hasBordero(order)"
									type="button"
									class="m6-act m6-act--ghost"
									title="Borderò"
									aria-label="Scarica borderò"
									@click.stop="$emit('action', { type: 'bordero', order })">
									<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="15" height="15" fill="currentColor"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M13,9V3.5L18.5,9M8,13H16V15H8M8,17H13V19H8Z" /></svg>
								</button>
								<button
									v-if="canMarkPaid(order)"
									type="button"
									class="m6-act m6-act--accent"
									title="Marca come pagato"
									@click.stop="$emit('action', { type: 'mark-paid', order })">
									<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M21,7L9,19L3.5,13.5L4.91,12.09L9,16.17L19.59,5.59L21,7Z" /></svg>
									Pagato
								</button>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<!-- MOBILE/TABLET: cards stack -------------------------------- -->
		<ul class="m6-order-cards m6-only-mobile" aria-label="Lista ordini (mobile)">
			<li
				v-for="order in orders"
				:key="`mob-${order.id}`"
				class="m6-order-card"
				tabindex="0"
				@click="onRowClick(order, $event)"
				@keydown.enter="onRowClick(order, $event)">
				<header class="m6-order-card__head">
					<span class="m6-order-card__code">#{{ order.id }}</span>
					<span :class="['m6-status', badgeFor(order.status).bg, badgeFor(order.status).text]">
						{{ badgeFor(order.status).label }}
					</span>
				</header>
				<div class="m6-order-card__body">
					<p class="m6-order-card__cust-name">{{ order.user?.name || '—' }} {{ order.user?.surname || '' }}</p>
					<p class="m6-order-card__cust-email">{{ order.user?.email || '—' }}</p>
					<p class="m6-order-card__route">
						<span>{{ resolvedOrderRoute(order).origin }}</span>
						<span aria-hidden="true">→</span>
						<span>{{ resolvedOrderRoute(order).dest }}</span>
					</p>
					<p class="m6-order-card__date">{{ formatDate(order.created_at) }}</p>
				</div>
				<footer class="m6-order-card__foot">
					<strong class="m6-order-card__amount">{{ formatCents(orderTotalCents(order)) }}</strong>
					<div class="m6-order-card__actions">
						<a
							v-if="trackingHref(order)"
							:href="trackingHref(order)"
							target="_blank"
							rel="noopener noreferrer"
							class="m6-act m6-act--ghost"
							title="Tracking BRT"
							@click.stop>
							<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M14,3V5H17.59L7.76,14.83L9.17,16.24L19,6.41V10H21V3M19,19H5V5H12V3H5C3.89,3 3,3.9 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V12H19V19Z" /></svg>
						</a>
						<button
							type="button"
							class="m6-act m6-act--ghost"
							title="Fattura"
							@click.stop="$emit('action', { type: 'invoice', order })">
							<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z" /></svg>
						</button>
						<button
							v-if="hasBordero(order)"
							type="button"
							class="m6-act m6-act--ghost"
							title="Borderò"
							@click.stop="$emit('action', { type: 'bordero', order })">
							<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M13,9V3.5L18.5,9M8,13H16V15H8M8,17H13V19H8Z" /></svg>
						</button>
						<button
							v-if="canMarkPaid(order)"
							type="button"
							class="m6-act m6-act--accent"
							@click.stop="$emit('action', { type: 'mark-paid', order })">
							Pagato
						</button>
					</div>
				</footer>
			</li>
		</ul>
	</div>
</template>
