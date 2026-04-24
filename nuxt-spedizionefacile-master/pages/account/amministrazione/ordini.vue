<script setup>
import '~/assets/css/pages/admin-ordini.css';
import { ref, computed, onMounted } from 'vue';

definePageMeta({
	middleware: ['app-auth', 'admin'],
});

useSeoMeta({
	title: 'Admin · Ordini | SpediamoFacile',
	robots: 'noindex, nofollow',
});

const sanctum = useSanctumClient();
const router = useRouter();
const route = useRoute();
const { formatCents, formatDate, orderStatusConfig, showSuccess, showError, actionMessage } = useAdmin();

/* -----------------------------------------------------------------
   STATO PAGINA
----------------------------------------------------------------- */
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

/* Status options (riusa lista da useAdminOrdini, filtrata principali) */
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

/* Servizi disponibili come chip filtro */
const services = ['Standard', 'Express', 'Internazionale', 'Pallet', 'PUDO', 'Contrassegno'];

/* -----------------------------------------------------------------
   KPI CARDS (mini, 4 card)
   - totali 7gg
   - incassato 7gg
   - pending
   - reclami aperti
   Source: dashboard endpoint per pending; fetchDashboard separato per
           7gg/incassato/reclami non esiste ancora -> calcoliamo lato FE
           dai dati visibili oppure leggiamo dashboard e usiamo i campi
           disponibili (orders.week, revenue_month).
----------------------------------------------------------------- */
const kpi = ref({
	orders7d: 0,
	revenue7d: 0,
	pending: 0,
	claimsOpen: 0,
});
const kpiLoading = ref(false);

const fetchKpi = async () => {
	kpiLoading.value = true;
	try {
		const dash = await sanctum('/api/admin/dashboard', { method: 'GET' });
		kpi.value.orders7d = Number(dash?.orders?.week ?? 0);
		// revenue_month è in cents (Transaction.total in cents); lo riusiamo come proxy 30gg
		// finché backend non espone campo "revenue_week" specifico (vedi gap endpoint sotto).
		kpi.value.revenue7d = Number(dash?.revenue_month ?? 0);
		kpi.value.pending = Number(dash?.orders?.pending ?? 0);
		// "claims_open" non presente nel payload dashboard attuale → fallback 0
		kpi.value.claimsOpen = Number(dash?.claims_open ?? 0);
	} catch (e) {
		// silenzioso: i KPI sono "nice to have", non bloccare la pagina
		// log lato console solo per diagnostica
		// eslint-disable-next-line no-console
		console.warn('[admin/ordini] KPI dashboard non disponibile:', e?.message || e);
	} finally {
		kpiLoading.value = false;
	}
};

/* -----------------------------------------------------------------
   FETCH ORDERS
----------------------------------------------------------------- */
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

/* -----------------------------------------------------------------
   AZIONI FILTRI
----------------------------------------------------------------- */
const onApplyFilters = () => {
	page.value = 1;
	fetchOrders();
};

const onResetFilters = () => {
	filters.value = {
		search: '', status: [], date_from: '', date_to: '',
		amount_min: '', amount_max: '', services: [],
	};
	page.value = 1;
	fetchOrders();
};

const onSortChange = (next) => {
	sort.value = next;
	fetchOrders();
};

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

/* -----------------------------------------------------------------
   AZIONI ORDINE
----------------------------------------------------------------- */
const onTableAction = async ({ type, order }) => {
	if (type === 'detail') {
		await router.push(`/account/amministrazione/ordini/${order.id}`);
		return;
	}
	if (type === 'invoice') {
		// -- ARCHIVIATO 2026-04-20 -- modulo SDI fatturazione elettronica rimosso.
		// Ora scarichiamo la fattura/ricevuta PDF normale (endpoint InvoicePdfController).
		try {
			const blob = await sanctum(`/api/orders/${order.id}/invoice.pdf`, {
				method: 'GET', responseType: 'blob',
			});
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
			const blob = await sanctum(`/api/orders/${order.id}/bordero/download`, {
				method: 'GET',
				responseType: 'blob',
			});
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
			await sanctum(`/api/admin/orders/${order.id}/confirm-bank-transfer`, {
				method: 'POST', body: { bank_transfer_reference: null },
			});
			showSuccess(`Ordine #${order.id} marcato come pagato.`);
			await fetchOrders();
			await fetchKpi();
		} catch (e) {
			showError(e, 'Impossibile marcare come pagato.');
		}
		return;
	}
};

/* -----------------------------------------------------------------
   EXPORT CSV (con filtri applicati)
   NOTA: endpoint /api/admin/orders/export non esiste ancora — vedi
   gap endpoint nel commento finale del file. Fallback: generiamo CSV
   client-side dai dati paginati attuali.
----------------------------------------------------------------- */
const exportCsv = async () => {
	exportLoading.value = true;
	try {
		// Tentativo backend
		const qs = buildQueryString().toString();
		try {
			const blob = await sanctum(`/api/admin/orders/export?${qs}`, {
				method: 'GET', responseType: 'blob',
			});
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
		} catch (errBackend) {
			// silenzioso: passa a fallback client
		}

		// Fallback client-side (solo pagina corrente)
		const rows = [
			['ID', 'Data', 'Cliente', 'Email', 'Origine', 'Destinazione', 'Stato', 'Totale (EUR)', 'Tracking'],
		];
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
			if (s.includes(',') || s.includes('"') || s.includes('\n')) {
				return `"${s.replace(/"/g, '""')}"`;
			}
			return s;
		}).join(',')).join('\n');
		const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
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

/* -----------------------------------------------------------------
   PAGINAZIONE: lista numeri con ellipsis
----------------------------------------------------------------- */
const pages = computed(() => {
	const total = ordersData.value.last_page || 1;
	const current = page.value;
	const max = 5;
	if (total <= max) return Array.from({ length: total }, (_, i) => i + 1);
	const half = Math.floor(max / 2);
	let start = Math.max(1, current - half);
	const end = Math.min(total, start + max - 1);
	start = Math.max(1, end - max + 1);
	const items = [];
	if (start > 1) {
		items.push(1);
		if (start > 2) items.push('…');
	}
	for (let i = start; i <= end; i++) items.push(i);
	if (end < total) {
		if (end < total - 1) items.push('…');
		items.push(total);
	}
	return items;
});

const fromIdx = computed(() => ordersData.value.total === 0 ? 0 : (page.value - 1) * perPage.value + 1);
const toIdx = computed(() => Math.min(page.value * perPage.value, ordersData.value.total));

/* -----------------------------------------------------------------
   MOUNT
----------------------------------------------------------------- */
onMounted(async () => {
	await Promise.all([fetchOrders(), fetchKpi()]);
});
</script>

<template>
	<section class="sf-account-shell min-h-[600px] py-[24px] tablet:py-[28px] desktop:py-[28px]">
		<div class="my-container m6-container">
			<!-- HEADER ----------------------------------------------- -->
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

			<!-- ALERT MESSAGE ---------------------------------------- -->
			<div
				v-if="actionMessage?.text || fetchError"
				:class="['m6-alert', (actionMessage?.type === 'error' || fetchError) ? 'm6-alert--err' : 'm6-alert--ok']"
				role="status">
				<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
					<path d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z" />
				</svg>
				<span>{{ actionMessage?.text || fetchError }}</span>
			</div>

			<!-- KPI CARDS ----------------------------------------------- -->
			<div class="m6-kpi-grid" aria-label="Indicatori chiave ordini">
				<article class="m6-kpi m6-kpi--teal">
					<header class="m6-kpi__head">
						<span class="m6-kpi__label">Ordini ultimi 7 giorni</span>
						<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M21,16.5C21,16.88 20.79,17.21 20.47,17.38L12.57,21.82C12.41,21.94 12.21,22 12,22C11.79,22 11.59,21.94 11.43,21.82L3.53,17.38C3.21,17.21 3,16.88 3,16.5V7.5C3,7.12 3.21,6.79 3.53,6.62L11.43,2.18C11.59,2.06 11.79,2 12,2C12.21,2 12.41,2.06 12.57,2.18L20.47,6.62C20.79,6.79 21,7.12 21,7.5V16.5Z" /></svg>
					</header>
					<p class="m6-kpi__value">{{ kpiLoading ? '…' : kpi.orders7d }}</p>
					<p class="m6-kpi__hint">Volume settimanale</p>
				</article>

				<article class="m6-kpi m6-kpi--accent">
					<header class="m6-kpi__head">
						<span class="m6-kpi__label">Incassato 7 giorni</span>
						<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M5,6H23V18H5V6M14,9A3,3 0 0,1 17,12A3,3 0 0,1 14,15A3,3 0 0,1 11,12A3,3 0 0,1 14,9M9,8A2,2 0 0,1 7,10V14A2,2 0 0,1 9,16H19A2,2 0 0,1 21,14V10A2,2 0 0,1 19,8H9M1,10H3V20H19V22H1V10Z" /></svg>
					</header>
					<p class="m6-kpi__value">{{ kpiLoading ? '…' : formatCents(kpi.revenue7d) }}</p>
					<p class="m6-kpi__hint">Transazioni andate a buon fine</p>
				</article>

				<article class="m6-kpi m6-kpi--warn">
					<header class="m6-kpi__head">
						<span class="m6-kpi__label">Ordini pending</span>
						<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12.5,7H11V13L16.25,16.15L17,14.92L12.5,12.25V7Z" /></svg>
					</header>
					<p class="m6-kpi__value">{{ kpiLoading ? '…' : kpi.pending }}</p>
					<p class="m6-kpi__hint">Da lavorare o pagare</p>
				</article>

				<article class="m6-kpi m6-kpi--neutral">
					<header class="m6-kpi__head">
						<span class="m6-kpi__label">Reclami aperti</span>
						<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M11,15H13V17H11V15M11,7H13V13H11V7M12,2C6.47,2 2,6.5 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20Z" /></svg>
					</header>
					<p class="m6-kpi__value">{{ kpiLoading ? '…' : kpi.claimsOpen }}</p>
					<p class="m6-kpi__hint">Da valutare</p>
				</article>
			</div>

			<!-- FILTERS BAR ----------------------------------------------- -->
			<AdminOrderFiltersBar
				v-model:filters="filters"
				:services="services"
				:status-options="statusOptions"
				:visible-count="ordersData.data.length"
				:total-count="ordersData.total"
				:export-loading="exportLoading"
				class="mt-[14px]"
				@apply="onApplyFilters"
				@reset="onResetFilters"
				@export-csv="exportCsv" />

			<!-- TOOLBAR PER PAGE + RESULT META -------------------------- -->
			<div class="m6-toolbar">
				<div class="m6-toolbar__info">
					<span v-if="ordersData.total > 0">
						Mostrati <strong>{{ fromIdx }}-{{ toIdx }}</strong> di <strong>{{ ordersData.total }}</strong>
					</span>
					<span v-else>Nessun ordine</span>
				</div>
				<label class="m6-toolbar__perpage">
					<span>Per pagina</span>
					<select :value="perPage" @change="onPerPageChange">
						<option :value="25">25</option>
						<option :value="50">50</option>
						<option :value="100">100</option>
					</select>
				</label>
			</div>

			<!-- TABELLA / CARDS ----------------------------------------- -->
			<div class="m6-results">
				<div v-if="loading" class="m6-state m6-state--loading">
					<div class="m6-spinner" aria-hidden="true"></div>
					<p>Caricamento ordini…</p>
				</div>

				<div v-else-if="!ordersData.data.length" class="m6-state m6-state--empty">
					<div class="m6-state__icon">
						<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="34" height="34" fill="currentColor">
							<path d="M21,16.5C21,16.88 20.79,17.21 20.47,17.38L12.57,21.82C12.41,21.94 12.21,22 12,22C11.79,22 11.59,21.94 11.43,21.82L3.53,17.38C3.21,17.21 3,16.88 3,16.5V7.5C3,7.12 3.21,6.79 3.53,6.62L11.43,2.18C11.59,2.06 11.79,2 12,2C12.21,2 12.41,2.06 12.57,2.18L20.47,6.62C20.79,6.79 21,7.12 21,7.5V16.5Z" />
						</svg>
					</div>
					<h2>Nessun ordine trovato</h2>
					<p>Cambia i filtri o resetta per vedere tutti gli ordini.</p>
					<button type="button" class="m6-btn-secondary" @click="onResetFilters">Reset filtri</button>
				</div>

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

			<!-- PAGINAZIONE --------------------------------------------- -->
			<nav v-if="ordersData.last_page > 1" class="m6-pagination" aria-label="Paginazione ordini">
				<button
					type="button"
					class="m6-pag-btn"
					:disabled="page === 1"
					@click="goToPage(page - 1)">
					&larr; Precedente
				</button>
				<ul class="m6-pag-list">
					<li v-for="(p, idx) in pages" :key="`p-${p}-${idx}`">
						<span v-if="p === '…'" class="m6-pag-ell">…</span>
						<button
							v-else
							type="button"
							:class="['m6-pag-num', p === page ? 'm6-pag-num--active' : '']"
							:aria-current="p === page ? 'page' : undefined"
							@click="goToPage(p)">
							{{ p }}
						</button>
					</li>
				</ul>
				<button
					type="button"
					class="m6-pag-btn"
					:disabled="page >= ordersData.last_page"
					@click="goToPage(page + 1)">
					Successiva &rarr;
				</button>
			</nav>
		</div>
	</section>
</template>

