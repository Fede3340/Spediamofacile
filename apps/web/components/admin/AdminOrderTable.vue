<!-- AdminOrderTable.vue — Tabella ordini admin (mobile cards + desktop table) -->
<script setup>
import { getBrtTrackingLabel, getBrtTrackingUrl } from '~/utils/brtTracking';

const props = defineProps({
	orders: { type: Array, default: () => [] },
	sort: {
		type: Object,
		default: () => ({ key: 'created_at', dir: 'desc' }),
	},
	formatCents: { type: Function, required: true },
	formatDate: { type: Function, required: true },
	statusConfig: { type: Object, required: true },
	// Selezione multipla — bulk actions
	selectedIds: { type: Array, default: () => [] },
	selectable: { type: Boolean, default: false },
});

const emit = defineEmits(['sort', 'action', 'toggle-select', 'toggle-select-all']);

const selectedSet = computed(() => new Set(props.selectedIds));
const isSelected = (id) => selectedSet.value.has(id);
const allSelected = computed(() => props.orders.length > 0 && props.orders.every(o => selectedSet.value.has(o.id)));
const someSelected = computed(() => props.orders.some(o => selectedSet.value.has(o.id)) && !allSelected.value);

const onToggleRow = (order, evt) => {
	evt?.stopPropagation();
	emit('toggle-select', order.id);
};
const onToggleAll = () => {
	emit('toggle-select-all', !allSelected.value);
};

const onSort = (key) => {
	const newDir = props.sort.key === key && props.sort.dir === 'desc' ? 'asc' : 'desc';
	emit('sort', { key, dir: newDir });
};

const sortIcon = (key) => {
	if (props.sort.key !== key) return '↕';
	return props.sort.dir === 'desc' ? '↓' : '↑';
};

const orderTotalCents = (order) => {
	const raw = order?.subtotal;
	if (raw && typeof raw === 'object' && 'amount' in raw) return Number(raw.amount || 0);
	return Number(raw || 0) * 100;
};

const resolvedOrderRoute = (order) => {
	const pkg = order?.packages?.[0];
	const origin = pkg?.originAddress?.city || pkg?.origin_address?.city || pkg?.origin_city || '—';
	const dest = pkg?.destinationAddress?.city || pkg?.destination_address?.city || pkg?.destination_city || '—';
	return { origin, dest };
};

const badgeFor = (status) => props.statusConfig?.[status] || { label: status, bg: 'bg-brand-bg-alt', text: 'text-brand-text-secondary' };

const onRowClick = (order, evt) => {
	if (evt.target.closest('button, a')) return;
	emit('action', { type: 'detail', order });
};

const canMarkPaid = (order) => order.status === 'pending_transfer' || order.status === 'awaiting_bank_transfer';

const hasBordero = (order) => Boolean(
	order?.bordero_status === 'completed'
	|| order?.bordero_document_filename
	|| order?.bordero_reference,
);

const trackingHref = (order) => getBrtTrackingUrl(order);
const trackingLabel = (order) => getBrtTrackingLabel(order);
</script>

<template>
	<div class="w-full">
		<div class="hidden tablet:block w-full overflow-x-auto bg-brand-card rounded-card border border-brand-border" role="region" aria-label="Tabella ordini">
			<table class="w-full">
				<thead class="bg-brand-bg-alt border-b border-brand-border">
					<tr>
						<th v-if="selectable" scope="col" class="px-3 py-3 text-left w-1">
							<input
								type="checkbox"
								:checked="allSelected"
								:indeterminate.prop="someSelected"
								class="h-4 w-4 cursor-pointer rounded border-brand-border text-brand-primary focus:ring-brand-primary/30"
								aria-label="Seleziona tutti gli ordini visibili"
								@change="onToggleAll" />
						</th>
						<th scope="col" class="px-3 py-3 text-left text-[0.6875rem] font-bold uppercase tracking-wider text-brand-text-muted">
							<button type="button" class="inline-flex items-center gap-1 hover:text-brand-primary transition" @click="onSort('id')">
								Codice {{ sortIcon('id') }}
							</button>
						</th>
						<th scope="col" class="px-3 py-3 text-left text-[0.6875rem] font-bold uppercase tracking-wider text-brand-text-muted">Cliente</th>
						<th scope="col" class="px-3 py-3 text-left text-[0.6875rem] font-bold uppercase tracking-wider text-brand-text-muted">Tratta</th>
						<th scope="col" class="px-3 py-3 text-left text-[0.6875rem] font-bold uppercase tracking-wider text-brand-text-muted">
							<button type="button" class="inline-flex items-center gap-1 hover:text-brand-primary transition" @click="onSort('status')">
								Stato {{ sortIcon('status') }}
							</button>
						</th>
						<th scope="col" class="px-3 py-3 text-left text-[0.6875rem] font-bold uppercase tracking-wider text-brand-text-muted">Tracking</th>
						<th scope="col" class="px-3 py-3 text-right text-[0.6875rem] font-bold uppercase tracking-wider text-brand-text-muted">
							<button type="button" class="inline-flex items-center gap-1 hover:text-brand-primary transition" @click="onSort('total')">
								Totale {{ sortIcon('total') }}
							</button>
						</th>
						<th scope="col" class="px-3 py-3 text-left text-[0.6875rem] font-bold uppercase tracking-wider text-brand-text-muted">
							<button type="button" class="inline-flex items-center gap-1 hover:text-brand-primary transition" @click="onSort('created_at')">
								Data {{ sortIcon('created_at') }}
							</button>
						</th>
						<th scope="col" class="px-3 py-3 text-center text-[0.6875rem] font-bold uppercase tracking-wider text-brand-text-muted w-1">Azioni</th>
					</tr>
				</thead>
				<tbody>
					<tr
						v-for="order in orders"
						:key="order.id"
						:class="[
							'border-b border-brand-border last:border-0 cursor-pointer hover:bg-brand-bg-alt transition focus:outline-none focus-visible:bg-brand-soft-bg',
							isSelected(order.id) ? 'bg-brand-primary/[0.04]' : '',
						]"
						tabindex="0"
						@click="onRowClick(order, $event)"
						@keydown.enter="onRowClick(order, $event)">
						<td v-if="selectable" class="px-3 py-3 w-1">
							<input
								type="checkbox"
								:checked="isSelected(order.id)"
								class="h-4 w-4 cursor-pointer rounded border-brand-border text-brand-primary focus:ring-brand-primary/30"
								:aria-label="`Seleziona ordine ${order.id}`"
								@click.stop
								@change="(e) => onToggleRow(order, e)" />
						</td>
						<td class="px-3 py-3 whitespace-nowrap w-1">
							<span class="text-sm font-bold text-brand-primary tabular-nums">#{{ order.id }}</span>
						</td>
						<td class="px-3 py-3">
							<div class="flex flex-col gap-0.5 min-w-[160px]">
								<span class="text-sm font-semibold text-brand-text">{{ order.user?.name || '—' }} {{ order.user?.surname || '' }}</span>
								<span class="text-xs text-brand-text-secondary">{{ order.user?.email || '—' }}</span>
							</div>
						</td>
						<td class="px-3 py-3">
							<div class="inline-flex items-center gap-1.5 text-xs text-brand-text">
								<span>{{ resolvedOrderRoute(order).origin }}</span>
								<UIcon name="mdi:arrow-right" class="w-3.5 h-3.5 text-brand-text-muted shrink-0" />
								<span>{{ resolvedOrderRoute(order).dest }}</span>
							</div>
						</td>
						<td class="px-3 py-3">
							<span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-[0.6875rem] font-semibold', badgeFor(order.status).bg, badgeFor(order.status).text]">
								{{ badgeFor(order.status).label }}
							</span>
						</td>
						<td class="px-3 py-3">
							<a
								v-if="trackingHref(order)"
								:href="trackingHref(order)"
								target="_blank"
								rel="noopener noreferrer"
								class="inline-flex items-center gap-1 text-xs font-semibold text-brand-primary hover:underline"
								@click.stop>
								<UIcon name="mdi:open-in-new" class="w-3.5 h-3.5" />
								{{ trackingLabel(order) }}
							</a>
							<span v-else class="text-xs text-brand-text-muted">—</span>
						</td>
						<td class="px-3 py-3 text-right whitespace-nowrap">
							<strong class="text-sm font-bold text-brand-primary tabular-nums">{{ formatCents(orderTotalCents(order)) }}</strong>
						</td>
						<td class="px-3 py-3 text-xs text-brand-text-secondary whitespace-nowrap">
							{{ formatDate(order.created_at) }}
						</td>
						<td class="px-3 py-3 text-center">
							<div class="inline-flex items-center gap-1">
								<button
									type="button"
									class="inline-flex items-center justify-center w-8 h-8 rounded-control border border-brand-border bg-brand-card text-brand-text-secondary cursor-pointer transition hover:bg-brand-bg-alt hover:text-brand-text"
									title="Vedi dettaglio"
									aria-label="Vedi dettaglio ordine"
									@click.stop="$emit('action', { type: 'detail', order })">
									<UIcon name="mdi:eye-outline" class="w-4 h-4" />
								</button>
								<button
									type="button"
									class="inline-flex items-center justify-center w-8 h-8 rounded-control border border-brand-border bg-brand-card text-brand-text-secondary cursor-pointer transition hover:bg-brand-bg-alt hover:text-brand-text"
									title="Fattura"
									aria-label="Scarica fattura"
									@click.stop="$emit('action', { type: 'invoice', order })">
									<UIcon name="mdi:file-document-outline" class="w-4 h-4" />
								</button>
								<button
									v-if="hasBordero(order)"
									type="button"
									class="inline-flex items-center justify-center w-8 h-8 rounded-control border border-brand-border bg-brand-card text-brand-text-secondary cursor-pointer transition hover:bg-brand-bg-alt hover:text-brand-text"
									title="Borderò"
									aria-label="Scarica borderò"
									@click.stop="$emit('action', { type: 'bordero', order })">
									<UIcon name="mdi:file-document-multiple-outline" class="w-4 h-4" />
								</button>
								<button
									v-if="canMarkPaid(order)"
									type="button"
									class="inline-flex items-center gap-1 px-2 h-8 rounded-control border border-brand-accent bg-brand-accent text-white text-xs font-semibold cursor-pointer transition hover:brightness-95"
									title="Marca come pagato"
									@click.stop="$emit('action', { type: 'mark-paid', order })">
									<UIcon name="mdi:check" class="w-3.5 h-3.5" />
									Pagato
								</button>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<ul class="tablet:hidden flex flex-col gap-3 list-none m-0 p-0" aria-label="Lista ordini (mobile)">
			<li
				v-for="order in orders"
				:key="`mob-${order.id}`"
				class="rounded-card border border-brand-border bg-brand-card p-4 cursor-pointer transition hover:border-brand-primary/40"
				tabindex="0"
				@click="onRowClick(order, $event)"
				@keydown.enter="onRowClick(order, $event)">
				<header class="flex items-center justify-between gap-2 mb-2">
					<span class="text-sm font-bold text-brand-primary tabular-nums">#{{ order.id }}</span>
					<span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-[0.6875rem] font-semibold', badgeFor(order.status).bg, badgeFor(order.status).text]">
						{{ badgeFor(order.status).label }}
					</span>
				</header>
				<div class="flex flex-col gap-1">
					<p class="m-0 text-sm font-semibold text-brand-text">{{ order.user?.name || '—' }} {{ order.user?.surname || '' }}</p>
					<p class="m-0 text-xs text-brand-text-secondary">{{ order.user?.email || '—' }}</p>
					<p class="m-0 text-xs text-brand-text inline-flex items-center gap-1.5">
						<span>{{ resolvedOrderRoute(order).origin }}</span>
						<span aria-hidden="true">→</span>
						<span>{{ resolvedOrderRoute(order).dest }}</span>
					</p>
					<p class="m-0 text-xs text-brand-text-muted">{{ formatDate(order.created_at) }}</p>
				</div>
				<footer class="flex items-center justify-between gap-2 mt-3 pt-3 border-t border-brand-border">
					<strong class="text-base font-bold text-brand-primary tabular-nums">{{ formatCents(orderTotalCents(order)) }}</strong>
					<div class="flex items-center gap-1">
						<a
							v-if="trackingHref(order)"
							:href="trackingHref(order)"
							target="_blank"
							rel="noopener noreferrer"
							class="inline-flex items-center justify-center w-8 h-8 rounded-control border border-brand-border bg-brand-card text-brand-text-secondary transition hover:bg-brand-bg-alt"
							title="Tracking BRT"
							@click.stop>
							<UIcon name="mdi:truck-fast" class="w-4 h-4" />
						</a>
						<button
							type="button"
							class="inline-flex items-center justify-center w-8 h-8 rounded-control border border-brand-border bg-brand-card text-brand-text-secondary cursor-pointer transition hover:bg-brand-bg-alt"
							title="Fattura"
							@click.stop="$emit('action', { type: 'invoice', order })">
							<UIcon name="mdi:file-document-outline" class="w-4 h-4" />
						</button>
						<button
							v-if="hasBordero(order)"
							type="button"
							class="inline-flex items-center justify-center w-8 h-8 rounded-control border border-brand-border bg-brand-card text-brand-text-secondary cursor-pointer transition hover:bg-brand-bg-alt"
							title="Borderò"
							@click.stop="$emit('action', { type: 'bordero', order })">
							<UIcon name="mdi:file-document-multiple-outline" class="w-4 h-4" />
						</button>
						<button
							v-if="canMarkPaid(order)"
							type="button"
							class="inline-flex items-center gap-1 px-2.5 h-8 rounded-control border border-brand-accent bg-brand-accent text-white text-xs font-semibold cursor-pointer transition hover:brightness-95"
							@click.stop="$emit('action', { type: 'mark-paid', order })">
							Pagato
						</button>
					</div>
				</footer>
			</li>
		</ul>
	</div>
</template>
