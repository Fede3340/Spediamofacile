<script setup>

import { buildPaginationItems, paginationRange } from '~/utils/pagination';

definePageMeta({
	middleware: ['app-auth', 'admin'],
});

useSeoMeta({
	title: 'Admin · Ordini',
	robots: 'noindex, nofollow',
});

const sanctum = useSanctumClient();
const router = useRouter();
const route = useRoute();
const { formatCents, formatDate, orderStatusConfig, showSuccess, showError, actionMessage } = useAdmin();

const ordersData = ref({ data: [], current_page: 1, last_page: 1, per_page: 25, total: 0 });
const loading = ref(false);
const fetchError = ref('');
const exportLoading = ref(false);

const sort = ref({ key: 'created_at', dir: 'desc' });
const perPage = ref(25);
const page = ref(1);

const filters = ref({
	search: route.query.search || '',
	status: [],
	date_from: '',
	date_to: '',
	amount_min: '',
	amount_max: '',
	services: [],
});

const statusOptions = [
	{ value: 'pending', label: 'In attesa' },
	{ value: 'awaiting_bank_transfer', label: 'Bonifico' },
	{ value: 'processing', label: 'Lavorazione' },
	{ value: 'completed', label: 'Completato' },
	{ value: 'in_transit', label: 'In transito' },
	{ value: 'delivered', label: 'Consegnato' },
	{ value: 'cancelled', label: 'Annullato' },
	{ value: 'payment_failed', label: 'Pagamento fallito' },
	{ value: 'refunded', label: 'Rimborsato' },
];

const services = ['Standard', 'Express', 'Internazionale', 'Pallet', 'PUDO', 'Contrassegno'];

const kpi = ref({ orders7d: 0, revenue7d: 0, pending: 0, claimsOpen: 0 });
const kpiLoading = ref(false);

const fetchKpi = async () => {
	kpiLoading.value = true;
	try {
		const dash = await sanctum('/api/admin/dashboard', { method: 'GET' });
		kpi.value.orders7d = Number(dash?.orders?.week ?? 0);
		kpi.value.revenue7d = Number(dash?.revenue_month ?? 0);
		kpi.value.pending = Number(dash?.orders?.pending ?? 0);
		kpi.value.claimsOpen = Number(dash?.claims_open ?? 0);
	} catch (e) {
		console.warn('[admin/ordini] KPI dashboard non disponibile:', e?.message || e);
	} finally {
		kpiLoading.value = false;
	}
};

const buildQueryString = () => {
	const params = new URLSearchParams();
	params.set('page', String(page.value));
	params.set('per_page', String(perPage.value));
	params.set('sort_by', sort.value.key);
	params.set('sort_dir', sort.value.dir);
	if (filters.value.search) params.set('search', filters.value.search);
	if (Array.isArray(filters.value.status) && filters.value.status.length) {
		params.set('status', filters.value.status.join(','));
	}
	if (filters.value.date_from) params.set('date_from', filters.value.date_from);
	if (filters.value.date_to) params.set('date_to', filters.value.date_to);
	if (filters.value.amount_min) params.set('amount_min', filters.value.amount_min);
	if (filters.value.amount_max) params.set('amount_max', filters.value.amount_max);
	if (Array.isArray(filters.value.services) && filters.value.services.length) {
		params.set('services', filters.value.services.join(','));
	}
	return params;
};

const fetchOrders = async () => {
	loading.value = true;
	fetchError.value = '';
	try {
		const qs = buildQueryString().toString();
		const res = await sanctum(`/api/admin/orders?${qs}`, { method: 'GET' });
		if (res && typeof res === 'object' && Array.isArray(res.data)) {
			ordersData.value = {
				data: res.data,
				current_page: res.current_page || 1,
				last_page: res.last_page || 1,
				per_page: res.per_page || perPage.value,
				total: res.total || res.data.length,
			};
		} else if (Array.isArray(res)) {
			ordersData.value = { data: res, current_page: 1, last_page: 1, per_page: perPage.value, total: res.length };
		} else {
			ordersData.value = { data: [], current_page: 1, last_page: 1, per_page: perPage.value, total: 0 };
		}
	} catch (e) {
		fetchError.value = e?.response?._data?.message || e?.data?.message || 'Errore nel caricamento ordini.';
		ordersData.value = { data: [], current_page: 1, last_page: 1, per_page: perPage.value, total: 0 };
	} finally {
		loading.value = false;
	}
};

const onApplyFilters = () => { page.value = 1; fetchOrders(); };

const onResetFilters = () => {
	filters.value = { search: '', status: [], date_from: '', date_to: '', amount_min: '', amount_max: '', services: [] };
	page.value = 1;
	fetchOrders();
};

const onSortChange = (next) => { sort.value = next; fetchOrders(); };

const onPerPageChange = (e) => {
	perPage.value = Number(e.target.value) || 25;
	page.value = 1;
	fetchOrders();
};

const goToPage = (p) => {
	if (p < 1 || p > ordersData.value.last_page || p === page.value) return;
	page.value = p;
	fetchOrders();
};

const onTableAction = async ({ type, order }) => {
	if (type === 'detail') {
		await router.push(`/account/amministrazione/ordini/${order.id}`);
		return;
	}
	if (type === 'invoice') {
		try {
			const blob = await sanctum(`/api/orders/${order.id}/invoice.pdf`, { method: 'GET', responseType: 'blob' });
			const url = window.URL.createObjectURL(blob);
			const link = document.createElement('a');
			link.href = url;
			link.download = `fattura-ord-${order.id}.pdf`;
			document.body.appendChild(link);
			link.click();
			window.URL.revokeObjectURL(url);
			link.remove();
			showSuccess(`Fattura ordine #${order.id} scaricata.`);
		} catch (e) {
			showError(e, 'Fattura non disponibile per questo ordine.');
		}
		return;
	}
	if (type === 'bordero') {
		try {
			const blob = await sanctum(`/api/orders/${order.id}/bordero/download`, { method: 'GET', responseType: 'blob' });
			const url = window.URL.createObjectURL(blob);
			const link = document.createElement('a');
			link.href = url;
			link.download = `bordero-ord-${order.id}.pdf`;
			document.body.appendChild(link);
			link.click();
			window.URL.revokeObjectURL(url);
			link.remove();
			showSuccess(`Borderò ordine #${order.id} scaricato.`);
		} catch (e) {
			showError(e, 'Borderò non disponibile per questo ordine.');
		}
		return;
	}
	if (type === 'mark-paid') {
		try {
			await sanctum(`/api/admin/orders/${order.id}/confirm-bank-transfer`, { method: 'POST', body: { bank_transfer_reference: null } });
			showSuccess(`Ordine #${order.id} marcato come pagato.`);
			await fetchOrders();
			await fetchKpi();
		} catch (e) {
			showError(e, 'Impossibile marcare come pagato.');
		}
	}
};

const exportCsv = async () => {
	exportLoading.value = true;
	try {
		const qs = buildQueryString().toString();
		try {
			const blob = await sanctum(`/api/admin/orders/export?${qs}`, { method: 'GET', responseType: 'blob' });
			const url = window.URL.createObjectURL(blob);
			const link = document.createElement('a');
			link.href = url;
			link.download = `ordini-${new Date().toISOString().slice(0, 10)}.csv`;
			document.body.appendChild(link);
			link.click();
			window.URL.revokeObjectURL(url);
			link.remove();
			showSuccess('Export CSV scaricato.');
			return;
		} catch {
			// fallback client-side
		}

		const rows = [['ID', 'Data', 'Cliente', 'Email', 'Origine', 'Destinazione', 'Stato', 'Totale (EUR)', 'Tracking']];
		for (const o of ordersData.value.data) {
			const pkg = o?.packages?.[0] || {};
			const subAmount = (typeof o.subtotal === 'object' && o.subtotal?.amount) ? o.subtotal.amount : (Number(o.subtotal || 0) * 100);
			rows.push([
				o.id,
				o.created_at,
				`${o.user?.name || ''} ${o.user?.surname || ''}`.trim(),
				o.user?.email || '',
				pkg?.originAddress?.city || pkg?.origin_city || '',
				pkg?.destinationAddress?.city || pkg?.destination_city || '',
				o.status,
				(Number(subAmount) / 100).toFixed(2).replace('.', ','),
				o.brt_parcel_id || '',
			]);
		}
		const csv = rows.map(r => r.map(field => {
			const s = String(field ?? '');
			if (s.includes(',') || s.includes('"') || s.includes('\n')) return `"${s.replace(/"/g, '""')}"`;
			return s;
		}).join(',')).join('\n');
		const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' });
		const url = window.URL.createObjectURL(blob);
		const link = document.createElement('a');
		link.href = url;
		link.download = `ordini-pagina-${page.value}-${new Date().toISOString().slice(0, 10)}.csv`;
		document.body.appendChild(link);
		link.click();
		window.URL.revokeObjectURL(url);
		link.remove();
		showSuccess('Export CSV (pagina corrente) generato.');
	} finally {
		exportLoading.value = false;
	}
};

const pages = computed(() => buildPaginationItems(page.value, ordersData.value.last_page || 1));
const _range = computed(() => paginationRange(page.value, perPage.value, ordersData.value.total));
const fromIdx = computed(() => _range.value.from);
const toIdx = computed(() => _range.value.to);

const kpiCards = computed(() => [
	{ key: 'orders7d', label: 'Ordini ultimi 7 giorni', value: kpiLoading.value ? '…' : kpi.value.orders7d, icon: 'mdi:cube-outline', tone: 'primary', hint: 'Volume settimanale' },
	{ key: 'revenue7d', label: 'Incassato 7 giorni', value: kpiLoading.value ? '…' : formatCents(kpi.value.revenue7d), icon: 'mdi:bank-transfer-in', tone: 'accent', hint: 'Transazioni andate a buon fine' },
	{ key: 'pending', label: 'Ordini pending', value: kpiLoading.value ? '…' : kpi.value.pending, icon: 'mdi:clock-outline', tone: 'warning', hint: 'Da lavorare o pagare' },
	{ key: 'claims', label: 'Reclami aperti', value: kpiLoading.value ? '…' : kpi.value.claimsOpen, icon: 'mdi:alert-circle-outline', tone: 'primary', hint: 'Da valutare' },
]);

onMounted(async () => {
	await Promise.all([fetchOrders(), fetchKpi()]);
});
</script>

<template>
	<section class="sf-account-shell min-h-[600px] py-6 tablet:py-7">
		<div class="my-container">
			<AccountPageHeader
				eyebrow="Area amministrazione"
				title="Ordini"
				description="Filtri avanzati, ricerca per codice o cliente, KPI in tempo reale e gestione azioni in un'unica vista."
				back-to="/account/amministrazione"
				back-label="Torna al pannello admin"
				:crumbs="[
					{ label: 'Account', to: '/account' },
					{ label: 'Amministrazione', to: '/account/amministrazione' },
					{ label: 'Ordini' },
				]" />

			<AdminOrdersViewTabs />

			<SfAlert
				v-if="actionMessage?.text || fetchError"
				:tone="(actionMessage?.type === 'error' || fetchError) ? 'danger' : 'info'"
				class="mb-4">
				{{ actionMessage?.text || fetchError }}
			</SfAlert>

			<div class="grid grid-cols-2 desktop:grid-cols-4 gap-3 mb-4" aria-label="Indicatori chiave ordini">
				<SfStatCard
					v-for="card in kpiCards"
					:key="card.key"
					:label="card.label"
					:value="card.value"
					:icon="card.icon"
					:tone="card.tone"
					:trend-label="card.hint" />
			</div>

			<AdminOrderFiltersBar
				v-model:filters="filters"
				:services="services"
				:status-options="statusOptions"
				:visible-count="ordersData.data.length"
				:total-count="ordersData.total"
				:export-loading="exportLoading"
				class="mb-4"
				@apply="onApplyFilters"
				@reset="onResetFilters"
				@export-csv="exportCsv" />

			<div class="flex flex-col tablet:flex-row tablet:items-center tablet:justify-between gap-3 mb-4">
				<div class="text-sm text-brand-text-secondary">
					<span v-if="ordersData.total > 0">
						Mostrati <strong class="text-brand-text">{{ fromIdx }}-{{ toIdx }}</strong> di <strong class="text-brand-text">{{ ordersData.total }}</strong>
					</span>
					<span v-else>Nessun ordine</span>
				</div>
				<label class="inline-flex items-center gap-2 text-sm text-brand-text-secondary">
					<span>Per pagina</span>
					<select :value="perPage" class="h-9 px-2.5 rounded-control border border-brand-border bg-brand-card text-sm text-brand-text focus:outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20" @change="onPerPageChange">
						<option :value="25">25</option>
						<option :value="50">50</option>
						<option :value="100">100</option>
					</select>
				</label>
			</div>

			<div>
				<div v-if="loading" class="flex flex-col items-center gap-3 py-10 text-brand-text-secondary">
					<UIcon name="mdi:loading" class="w-8 h-8 text-brand-primary animate-spin" />
					<p>Caricamento ordini…</p>
				</div>

				<SfEmptyState
					v-else-if="!ordersData.data.length"
					icon="mdi:cube-outline"
					title="Nessun ordine trovato"
					description="Cambia i filtri o resetta per vedere tutti gli ordini.">
					<template #cta>
						<SfButton variant="secondary" @click="onResetFilters">Reset filtri</SfButton>
					</template>
				</SfEmptyState>

				<AdminOrderTable
					v-else
					:orders="ordersData.data"
					:sort="sort"
					:format-cents="formatCents"
					:format-date="formatDate"
					:status-config="orderStatusConfig"
					@sort="onSortChange"
					@action="onTableAction" />
			</div>

			<nav v-if="ordersData.last_page > 1" class="mt-5 flex flex-col tablet:flex-row tablet:items-center tablet:justify-center gap-3" aria-label="Paginazione ordini">
				<SfButton variant="secondary" size="sm" :disabled="page === 1" @click="goToPage(page - 1)">
					← Precedente
				</SfButton>
				<ul class="flex items-center gap-1 list-none m-0 p-0">
					<li v-for="(p, idx) in pages" :key="`p-${p}-${idx}`">
						<span v-if="p === '…'" class="inline-flex items-center justify-center w-9 h-9 text-brand-text-muted">…</span>
						<button
							v-else
							type="button"
							:class="[
								'inline-flex items-center justify-center w-9 h-9 rounded-control text-sm font-semibold transition',
								p === page
									? 'bg-brand-primary text-white shadow-sf-sm'
									: 'bg-brand-card text-brand-text-secondary border border-brand-border hover:bg-brand-bg-alt hover:text-brand-text',
							]"
							:aria-current="p === page ? 'page' : undefined"
							@click="goToPage(p)">
							{{ p }}
						</button>
					</li>
				</ul>
				<SfButton variant="secondary" size="sm" :disabled="page >= ordersData.last_page" @click="goToPage(page + 1)">
					Successiva →
				</SfButton>
			</nav>
		</div>
	</section>
</template>
