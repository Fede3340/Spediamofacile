<!-- FILE: pages/account/amministrazione/utenti.vue -->
<script setup>
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

const {
	activeSubTab,
	proRequests,
	pendingProRequestsCount,
	fetchProRequests,
	approveProRequest,
	rejectProRequest,
	proRequestStatusConfig,
} = useAdminUtenti();

const usersData = ref([]);
const loading = ref(false);
const total = ref(0);

const search = ref('');
const roleFilter = ref('');
const statusFilter = ref('');
const onlyVerified = ref(false);

const currentPage = ref(1);
const perPage = 20;
const serverPagination = ref(false);
const totalPages = ref(1);

const drawerOpen = ref(false);
const drawerUserId = ref(null);

const auth = (typeof useSanctumAuth === 'function') ? useSanctumAuth() : { user: ref(null) };
const currentUser = computed(() => auth?.user?.value || null);
const canMaster = computed(() => Boolean(currentUser.value?.is_master_admin || currentUser.value?.role === 'Admin'));

const fetchUsers = async () => {
	loading.value = true;
	try {
		const params = new URLSearchParams();
		if (search.value) params.set('search', search.value);
		if (roleFilter.value) params.set('role', roleFilter.value);
		if (statusFilter.value) params.set('status', statusFilter.value);
		params.set('page', String(currentPage.value));

		const res = await sanctum(`/api/admin/users?${params.toString()}`);
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

const filteredClient = computed(() => {
	let list = usersData.value || [];
	if (!serverPagination.value) {
		if (search.value) {
			const s = search.value.toLowerCase();
			list = list.filter(u =>
				`${u.name || ''} ${u.surname || ''}`.toLowerCase().includes(s)
				|| (u.email || '').toLowerCase().includes(s),
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
		return !Number.isNaN(ts) && (now - ts) <= sevenDays;
	}).length;
	return {
		total: total.value || list.length,
		pro: proCount,
		azienda: aziendaCount,
		newWeek,
	};
});

watch([search, roleFilter, statusFilter], () => {
	currentPage.value = 1;
	if (serverPagination.value) fetchUsers();
});

watch(currentPage, () => {
	if (serverPagination.value) fetchUsers();
});

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
	const blob = new Blob(['﻿' + lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
	const url = URL.createObjectURL(blob);
	const a = document.createElement('a');
	a.href = url;
	a.download = `utenti-${new Date().toISOString().slice(0, 10)}.csv`;
	a.click();
	URL.revokeObjectURL(url);
	showSuccess('Export CSV scaricato.');
};

const subTabs = computed(() => [
	{ key: 'users', label: 'Utenti' },
	{ key: 'pro_requests', label: 'Richieste Pro', count: pendingProRequestsCount.value || undefined },
]);

const statsCards = computed(() => [
	{ key: 'total', label: 'Totale utenti', value: stats.value.total, icon: 'mdi:account-group', tone: 'primary' },
	{ key: 'pro', label: 'Partner Pro', value: stats.value.pro, icon: 'mdi:star', tone: 'accent' },
	{ key: 'azienda', label: 'Aziendali', value: stats.value.azienda, icon: 'mdi:domain', tone: 'success' },
	{ key: 'newWeek', label: 'Nuovi (7gg)', value: stats.value.newWeek, icon: 'mdi:calendar-plus', tone: 'warning' },
]);

const roleOptions = [
	{ value: '', label: 'Tutti i ruoli' },
	{ value: 'Admin', label: 'Admin' },
	{ value: 'Partner Pro', label: 'Partner Pro' },
	{ value: 'Partner', label: 'Partner' },
	{ value: 'User', label: 'Privato' },
];

const statusOptions = [
	{ value: '', label: 'Tutti gli stati' },
	{ value: 'active', label: 'Attivo' },
	{ value: 'pending-verification', label: 'In verifica' },
	{ value: 'banned', label: 'Bannato' },
];

onMounted(() => {
	fetchUsers();
	fetchProRequests();
});
</script>

<template>
	<section class="sf-account-shell min-h-[600px] py-6 md:py-8">
		<div class="max-w-7xl mx-auto px-4 md:px-6 space-y-6 md:space-y-8">
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

			<div class="grid grid-cols-2 desktop:grid-cols-4 gap-3">
				<SfStatCard
					v-for="card in statsCards"
					:key="card.key"
					:label="card.label"
					:value="card.value"
					:icon="card.icon"
					:tone="card.tone" />
			</div>

			<AdminFilterBar
				:filters="subTabs"
				:active-filter="activeSubTab"
				@change="(key) => activeSubTab = key" />

			<div v-if="activeSubTab === 'users'" class="space-y-6">
				<SfCard padding="md">
					<div class="grid grid-cols-1 tablet:grid-cols-[minmax(0,1fr)_180px_180px_auto] gap-3 items-end">
						<SfFormGroup label="Cerca utente">
							<SfInput
								v-model="search"
								type="search"
								placeholder="Cerca per nome o email..."
								leading-icon="mdi:magnify" />
						</SfFormGroup>

						<SfFormGroup label="Ruolo">
							<SfSelect v-model="roleFilter" :options="roleOptions" />
						</SfFormGroup>

						<SfFormGroup label="Stato">
							<SfSelect v-model="statusFilter" :options="statusOptions" />
						</SfFormGroup>

						<SfCheckbox v-model="onlyVerified" label="Solo verificati" />
					</div>
					<div class="flex flex-wrap items-center gap-2 mt-4">
						<SfButton variant="secondary" size="sm" @click="resetFilters">Pulisci</SfButton>
						<SfButton variant="secondary" size="sm" :disabled="loading" @click="fetchUsers">
							<template #leading><UIcon name="mdi:refresh" class="w-3.5 h-3.5" /></template>
							Ricarica
						</SfButton>
						<SfButton size="sm" @click="exportCsv">
							<template #leading><UIcon name="mdi:download" class="w-3.5 h-3.5" /></template>
							Esporta CSV
						</SfButton>
					</div>
				</SfCard>

				<div v-if="loading" class="flex flex-col items-center gap-3 py-10 text-brand-text-secondary">
					<UIcon name="mdi:loading" class="w-8 h-8 text-brand-primary animate-spin" />
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

				<nav v-if="effectiveTotalPages > 1" class="flex items-center justify-center gap-3" aria-label="Paginazione utenti">
					<SfButton variant="secondary" size="sm" :disabled="currentPage <= 1" @click="goToPage(currentPage - 1)">
						<template #leading><UIcon name="mdi:chevron-left" class="w-3.5 h-3.5" /></template>
						Precedente
					</SfButton>
					<span class="text-sm text-brand-text-secondary">
						Pagina <strong class="text-brand-text">{{ currentPage }}</strong> di <strong class="text-brand-text">{{ effectiveTotalPages }}</strong>
					</span>
					<SfButton variant="secondary" size="sm" :disabled="currentPage >= effectiveTotalPages" @click="goToPage(currentPage + 1)">
						Successiva
						<template #trailing><UIcon name="mdi:chevron-right" class="w-3.5 h-3.5" /></template>
					</SfButton>
				</nav>
			</div>

			<div v-if="activeSubTab === 'pro_requests'">
				<AdminProRequestsList
					:requests="proRequests"
					:pro-request-status-config="proRequestStatusConfig"
					:action-loading="actionLoading"
					:format-date="formatDate"
					@approve="approveProRequest"
					@reject="rejectProRequest" />
			</div>
		</div>

		<AdminUserDetailDrawer
			v-model:open="drawerOpen"
			:user-id="drawerUserId"
			:can-master="canMaster"
			@updated="onUserUpdated"
			@impersonate="handleImpersonate" />
	</section>
</template>
