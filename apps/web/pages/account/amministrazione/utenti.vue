<!-- FILE: pages/account/amministrazione/utenti.vue -->
<script setup>
import '~/assets/css/admin.css';
import { ref, computed, onMounted, watch } from 'vue';

definePageMeta({
	middleware: ['app-auth', 'admin'],
});

useSeoMeta({
	title: 'Utenti admin',
	ogTitle: 'Utenti admin',
	description: 'Console di gestione utenti del pannello amministrativo SpediamoFacile.',
	ogDescription: 'Gestione utenti, ruoli, stati e azioni amministrative.',
	robots: 'noindex, nofollow',
});

const sanctum = useSanctumClient();
const { showSuccess, showError, formatDate, actionLoading, actionMessage } = useAdmin();

/* === Composable legacy per Richieste Pro + alcune azioni === */
const {
	activeSubTab,
	proRequests,
	pendingProRequestsCount,
	fetchProRequests,
	approveProRequest,
	rejectProRequest,
	proRequestStatusConfig,
} = useAdminUtenti();

/* === State principale lista utenti === */
const usersData = ref([]);
const loading = ref(false);
const total = ref(0);

/* Filtri */
const search = ref('');
const roleFilter = ref('');
const statusFilter = ref('');
const onlyVerified = ref(false);

/* Paginazione (server quando supportato, altrimenti client) */
const currentPage = ref(1);
const perPage = 20;
const serverPagination = ref(false);
const totalPages = ref(1);

/* Drawer dettaglio */
const drawerOpen = ref(false);
const drawerUserId = ref(null);

/* Capability admin-master (per impersonate / cambio email) */
const auth = (typeof useSanctumAuth === 'function') ? useSanctumAuth() : { user: ref(null) };
const currentUser = computed(() => auth?.user?.value || null);
const canMaster = computed(() => Boolean(currentUser.value?.is_master_admin || currentUser.value?.role === 'Admin'));

/* === Fetch lista === */
const fetchUsers = async () => {
	loading.value = true;
	try {
		const params = new URLSearchParams();
		if (search.value) params.set('search', search.value);
		if (roleFilter.value) params.set('role', roleFilter.value);
		if (statusFilter.value) params.set('status', statusFilter.value);
		params.set('page', String(currentPage.value));

		const res = await sanctum(`/api/admin/users?${params.toString()}`);
		// Compatibilita con paginator Laravel { data, current_page, last_page, total }
		if (res && typeof res === 'object' && Array.isArray(res.data) && res.last_page !== undefined) {
			usersData.value = res.data;
			total.value = res.total || res.data.length;
			totalPages.value = res.last_page || 1;
			serverPagination.value = true;
		} else if (res && typeof res === 'object' && Array.isArray(res.data)) {
			usersData.value = res.data;
			total.value = res.data.length;
			serverPagination.value = false;
		} else if (Array.isArray(res)) {
			usersData.value = res;
			total.value = res.length;
			serverPagination.value = false;
		} else {
			usersData.value = [];
			total.value = 0;
		}
	} catch (e) {
		showError(e, 'Errore nel caricamento degli utenti.');
		usersData.value = [];
	} finally {
		loading.value = false;
	}
};

/* === Filtro client-side (fallback) === */
const filteredClient = computed(() => {
	let list = usersData.value || [];
	if (!serverPagination.value) {
		if (search.value) {
			const s = search.value.toLowerCase();
			list = list.filter(u =>
				`${u.name || ''} ${u.surname || ''}`.toLowerCase().includes(s) ||
				(u.email || '').toLowerCase().includes(s),
			);
		}
		if (roleFilter.value) {
			list = list.filter(u => (u.role || 'User') === roleFilter.value);
		}
		if (statusFilter.value) {
			list = list.filter(u => {
				const st = u.status || (u.banned_at ? 'banned' : (u.email_verified_at ? 'active' : 'pending-verification'));
				return st === statusFilter.value;
			});
		}
	}
	if (onlyVerified.value) list = list.filter(u => Boolean(u.email_verified_at));
	return list;
});

const clientTotalPages = computed(() => Math.max(1, Math.ceil(filteredClient.value.length / perPage)));
const effectiveTotalPages = computed(() => serverPagination.value ? totalPages.value : clientTotalPages.value);

const paginatedUsers = computed(() => {
	if (serverPagination.value) return filteredClient.value;
	const start = (currentPage.value - 1) * perPage;
	return filteredClient.value.slice(start, start + perPage);
});

/* === Mini-stats (calcolate sul dataset corrente) === */
const stats = computed(() => {
	const list = usersData.value || [];
	const now = Date.now();
	const sevenDays = 7 * 24 * 60 * 60 * 1000;
	const proCount = list.filter(u => u.role === 'Partner Pro' || u.is_pro).length;
	const aziendaCount = list.filter(u => {
		const t = (u.user_type || '').toLowerCase();
		return t === 'commerciante' || t === 'azienda';
	}).length;
	const newWeek = list.filter(u => {
		if (!u.created_at) return false;
		const ts = new Date(u.created_at).getTime();
		return !isNaN(ts) && (now - ts) <= sevenDays;
	}).length;
	return {
		total: total.value || list.length,
		pro: proCount,
		azienda: aziendaCount,
		newWeek,
	};
});

/* === Watchers === */
watch([search, roleFilter, statusFilter], () => {
	currentPage.value = 1;
	if (serverPagination.value) fetchUsers();
});

watch(currentPage, () => {
	if (serverPagination.value) fetchUsers();
});

/* === Handlers === */
const handleViewUser = (user) => {
	drawerUserId.value = user.id;
	drawerOpen.value = true;
};

const handleEditUser = (user) => {
	drawerUserId.value = user.id;
	drawerOpen.value = true;
};

const handleImpersonate = async (user) => {
	if (!user) return;
	try {
		await sanctum(`/api/admin/users/${user.id}/impersonate`, { method: 'POST' });
		showSuccess(`Impersonazione attiva: ${user.name} ${user.surname}.`);
		setTimeout(() => { window.location.href = '/account'; }, 600);
	} catch (e) {
		showError(e, "Errore durante l'impersona.");
	}
};

const onUserUpdated = () => fetchUsers();

const goToPage = (p) => {
	if (p < 1 || p > effectiveTotalPages.value) return;
	currentPage.value = p;
};

const resetFilters = () => {
	search.value = '';
	roleFilter.value = '';
	statusFilter.value = '';
	onlyVerified.value = false;
	currentPage.value = 1;
	if (serverPagination.value) fetchUsers();
};

/* === Export CSV (dataset filtrato) === */
const exportCsv = () => {
	const list = filteredClient.value;
	if (!list.length) {
		showError(null, 'Nessun utente da esportare.');
		return;
	}
	const headers = ['ID', 'Nome', 'Cognome', 'Email', 'Telefono', 'Ruolo', 'Tipo', 'Stato', 'Ordini', 'Registrato', 'Ultimo accesso'];
	const lines = [headers.join(';')];
	for (const u of list) {
		const status = u.status || (u.banned_at ? 'banned' : (u.email_verified_at ? 'active' : 'pending-verification'));
		const row = [
			u.id,
			(u.name || '').replace(/[;\n\r]/g, ' '),
			(u.surname || '').replace(/[;\n\r]/g, ' '),
			(u.email || '').replace(/[;\n\r]/g, ' '),
			(u.telephone_number || '').replace(/[;\n\r]/g, ' '),
			u.role || 'User',
			u.user_type || 'privato',
			status,
			u.orders_count ?? 0,
			u.created_at || '',
			u.last_login_at || u.last_seen_at || u.updated_at || '',
		];
		lines.push(row.join(';'));
	}
	const blob = new Blob(['\ufeff' + lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
	const url = URL.createObjectURL(blob);
	const a = document.createElement('a');
	a.href = url;
	a.download = `utenti-${new Date().toISOString().slice(0, 10)}.csv`;
	a.click();
	URL.revokeObjectURL(url);
	showSuccess('Export CSV scaricato.');
};

/* === Sub-tabs === */
const subTabs = computed(() => [
	{ key: 'users', label: 'Utenti' },
	{ key: 'pro_requests', label: 'Richieste Pro', count: pendingProRequestsCount.value || undefined },
]);

onMounted(() => {
	fetchUsers();
	fetchProRequests();
});
</script>

<template>
	<section class="sf-account-shell admin-utenti-page">
		<div class="my-container admin-utenti-container">
			<AccountPageHeader
				eyebrow="Area amministrazione"
				title="Utenti"
				description="Console di gestione utenti: ruoli, stati, azioni rapide e dettaglio completo."
				back-to="/account/amministrazione"
				back-label="Torna al pannello admin"
				:crumbs="[
					{ label: 'Account', to: '/account' },
					{ label: 'Amministrazione', to: '/account/amministrazione' },
					{ label: 'Utenti' },
				]" />

			<AdminActionBanner :message="actionMessage?.text || ''" :tone="actionMessage?.type || ''" />

			<!-- ====== Mini stats ====== -->
			<div class="admin-utenti-stats">
				<article class="admin-utenti-stat">
					<div class="admin-utenti-stat__icon admin-utenti-stat__icon--primary" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z"/></svg>
					</div>
					<div class="admin-utenti-stat__copy">
						<p class="admin-utenti-stat__label">Totale utenti</p>
						<p class="admin-utenti-stat__value">{{ stats.total }}</p>
					</div>
				</article>
				<article class="admin-utenti-stat">
					<div class="admin-utenti-stat__icon admin-utenti-stat__icon--accent" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 2 8.61 8.59 1 9.27l5.5 4.27L4.91 21 12 16.9 19.09 21l-1.59-7.46L23 9.27l-7.61-.68z"/></svg>
					</div>
					<div class="admin-utenti-stat__copy">
						<p class="admin-utenti-stat__label">Partner Pro</p>
						<p class="admin-utenti-stat__value">{{ stats.pro }}</p>
					</div>
				</article>
				<article class="admin-utenti-stat">
					<div class="admin-utenti-stat__icon admin-utenti-stat__icon--success" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12,3L1,9L12,15L21,10.09V17H23V9M5,13.18V17.18L12,21L19,17.18V13.18L12,17L5,13.18Z"/></svg>
					</div>
					<div class="admin-utenti-stat__copy">
						<p class="admin-utenti-stat__label">Aziendali</p>
						<p class="admin-utenti-stat__value">{{ stats.azienda }}</p>
					</div>
				</article>
				<article class="admin-utenti-stat">
					<div class="admin-utenti-stat__icon admin-utenti-stat__icon--warning" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M19,3H18V1H16V3H8V1H6V3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5A2,2 0 0,0 19,3M19,19H5V8H19V19Z"/></svg>
					</div>
					<div class="admin-utenti-stat__copy">
						<p class="admin-utenti-stat__label">Nuovi (7gg)</p>
						<p class="admin-utenti-stat__value">{{ stats.newWeek }}</p>
					</div>
				</article>
			</div>

			<!-- Sub-tabs -->
			<div class="admin-utenti-subtabs-wrap">
				<AdminFilterBar
					:filters="subTabs"
					:active-filter="activeSubTab"
					@change="(key) => activeSubTab = key" />
			</div>

			<!-- ===== USERS SUB-TAB ===== -->
			<div v-if="activeSubTab === 'users'" class="admin-utenti-panel">
				<!-- Filter bar -->
				<div class="admin-utenti-filterbar">
					<label class="admin-utenti-search">
						<span class="sr-only">Cerca utente</span>
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3M9.5,5C7,5 5,7 5,9.5C5,12 7,14 9.5,14C12,14 14,12 14,9.5C14,7 12,5 9.5,5Z"/></svg>
						<input
							v-model="search"
							type="search"
							placeholder="Cerca per nome o email..."
							class="admin-utenti-search__input" />
					</label>

					<select v-model="roleFilter" class="admin-utenti-select" aria-label="Filtra per ruolo">
						<option value="">Tutti i ruoli</option>
						<option value="Admin">Admin</option>
						<option value="Partner Pro">Partner Pro</option>
						<option value="Partner">Partner</option>
						<option value="User">Privato</option>
					</select>

					<select v-model="statusFilter" class="admin-utenti-select" aria-label="Filtra per stato">
						<option value="">Tutti gli stati</option>
						<option value="active">Attivo</option>
						<option value="pending-verification">In verifica</option>
						<option value="banned">Bannato</option>
					</select>

					<label class="admin-utenti-toggle">
						<input v-model="onlyVerified" type="checkbox" />
						<span class="admin-utenti-toggle__track"><span class="admin-utenti-toggle__thumb" /></span>
						<span class="admin-utenti-toggle__label">Solo verificati</span>
					</label>

					<div class="admin-utenti-filterbar__actions">
						<button type="button" class="admin-utenti-btn-ghost" @click="resetFilters">Pulisci</button>
						<button type="button" class="admin-utenti-btn-ghost" :disabled="loading" @click="fetchUsers">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M17.65,6.35C16.2,4.9 14.21,4 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20C15.73,20 18.84,17.45 19.73,14H17.65C16.83,16.33 14.61,18 12,18A6,6 0 0,1 6,12A6,6 0 0,1 12,6C13.66,6 15.14,6.69 16.22,7.78L13,11H20V4L17.65,6.35Z"/></svg>
							Ricarica
						</button>
						<button type="button" class="admin-utenti-btn-primary" @click="exportCsv">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M5,20H19V18H5M19,9H15V3H9V9H5L12,16L19,9Z"/></svg>
							Esporta CSV
						</button>
					</div>
				</div>

				<!-- Tabella -->
				<div v-if="loading" class="admin-utenti-loading">
					<div class="admin-drawer__spinner" />
					<p>Caricamento utenti...</p>
				</div>

				<AdminUserTable
					v-else
					:users="paginatedUsers"
					:action-loading="actionLoading"
					:can-impersonate="canMaster"
					:format-date="formatDate"
					@view="handleViewUser"
					@edit="handleEditUser"
					@impersonate="handleImpersonate" />

				<!-- Paginazione -->
				<nav v-if="effectiveTotalPages > 1" class="admin-utenti-pager" aria-label="Paginazione utenti">
					<button
						type="button"
						class="admin-utenti-pager__btn"
						:disabled="currentPage <= 1"
						@click="goToPage(currentPage - 1)">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M15.41,16.58L10.83,12L15.41,7.41L14,6L8,12L14,18L15.41,16.58Z"/></svg>
						Precedente
					</button>
					<span class="admin-utenti-pager__info">
						Pagina <strong>{{ currentPage }}</strong> di <strong>{{ effectiveTotalPages }}</strong>
					</span>
					<button
						type="button"
						class="admin-utenti-pager__btn"
						:disabled="currentPage >= effectiveTotalPages"
						@click="goToPage(currentPage + 1)">
						Successiva
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M8.59,16.58L13.17,12L8.59,7.41L10,6L16,12L10,18L8.59,16.58Z"/></svg>
					</button>
				</nav>
			</div>

			<!-- ===== PRO REQUESTS SUB-TAB ===== -->
			<div v-if="activeSubTab === 'pro_requests'" class="admin-utenti-panel">
				<AdminProRequestsList
					:requests="proRequests"
					:pro-request-status-config="proRequestStatusConfig"
					:action-loading="actionLoading"
					:format-date="formatDate"
					@approve="approveProRequest"
					@reject="rejectProRequest" />
			</div>
		</div>

		<!-- Drawer dettaglio -->
		<AdminUserDetailDrawer
			v-model:open="drawerOpen"
			:user-id="drawerUserId"
			:can-master="canMaster"
			@updated="onUserUpdated"
			@impersonate="handleImpersonate" />
	</section>
</template>

