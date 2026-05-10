<script setup>
/**
 * Pagina admin Audit Log.
 * Mostra storico azioni admin (chi/quando/cosa/da-dove) con filtri + dettaglio + CSV export.
 */
import { formatDateTimeIt } from '~/utils/date.js';

definePageMeta({
	middleware: ['app-auth', 'admin'],
});

useSeoMeta({
	title: 'Admin · Storico azioni',
	robots: 'noindex, nofollow',
});

const sanctum = useSanctumClient();
const { message: feedback, showError } = useFlashMessage();

// Stato locale
const logs = ref([]);
const totalLogs = ref(0);
const currentPage = ref(1);
const lastPage = ref(1);
const perPage = ref(50);
const isLoading = ref(true);
const availableActions = ref([]);
const selectedLog = ref(null);

// Filtri
const filters = ref({
	action: '',
	actor_type: '',
	user_id: '',
	target_type: '',
	ip: '',
	date_from: '',
	date_to: '',
});

const actorTypeOptions = [
	{ value: '', label: 'Tutti i tipi' },
	{ value: 'admin', label: 'Admin' },
	{ value: 'user', label: 'Utente' },
	{ value: 'system', label: 'Sistema' },
];

const targetTypeOptions = [
	{ value: '', label: 'Tutti gli oggetti' },
	{ value: 'order', label: 'Ordine' },
	{ value: 'user', label: 'Utente' },
	{ value: 'service', label: 'Servizio' },
	{ value: 'price_band', label: 'Fascia prezzo' },
	{ value: 'coupon', label: 'Coupon' },
];

// Query string per chiamata API
const queryParams = computed(() => {
	const p = { page: currentPage.value, per_page: perPage.value };
	for (const [k, v] of Object.entries(filters.value)) {
		if (v) p[k] = v;
	}
	return p;
});

// Helper per formato data leggibile
const formatDate = (val) => formatDateTimeIt(val, '—');

// Mappatura action → label leggibile
const actionLabel = (action) => {
	const map = {
		'admin.price.update': 'Aggiornato listino prezzi',
		'admin.user.role_change': 'Cambio ruolo utente',
		'admin.user.ban': 'Ban utente',
		'admin.user.unban': 'Sblocco utente',
		'admin.setting.update': 'Modifica impostazioni',
		'admin.coupon.create': 'Creato coupon',
		'admin.coupon.update': 'Modificato coupon',
		'admin.coupon.delete': 'Eliminato coupon',
		'admin.service.create': 'Creato servizio',
		'admin.service.update': 'Modificato servizio',
		'admin.service.delete': 'Eliminato servizio',
		'admin.article.create': 'Creato articolo',
		'admin.article.update': 'Modificato articolo',
		'admin.article.delete': 'Eliminato articolo',
		'admin.order.cancel': 'Annullato ordine',
		'admin.order.refund': 'Rimborso ordine',
		'admin.bank_transfer.confirm': 'Confermato bonifico',
		'admin.audit_log.export': 'Esportato audit log',
		'gdpr.export.download': 'Download dati GDPR',
		'admin.2fa.enable': 'Attivato 2FA',
		'admin.2fa.disable': 'Disattivato 2FA',
	};
	return map[action] || action;
};

const actionTone = (action) => {
	if (action.includes('delete') || action.includes('ban') || action.includes('cancel')) return 'danger';
	if (action.includes('create') || action.includes('confirm') || action.includes('enable')) return 'success';
	if (action.includes('update') || action.includes('change')) return 'info';
	return 'neutral';
};

const fetchLogs = async () => {
	isLoading.value = true;
	try {
		const res = await sanctum('/api/admin/audit-logs', { method: 'GET', query: queryParams.value });
		logs.value = res.data || [];
		totalLogs.value = res.total || 0;
		currentPage.value = res.current_page || 1;
		lastPage.value = res.last_page || 1;
	} catch (e) {
		showError(e, 'Impossibile caricare il registro azioni.');
	} finally {
		isLoading.value = false;
	}
};

const fetchActions = async () => {
	try {
		const res = await sanctum('/api/admin/audit-logs/actions', { method: 'GET' });
		availableActions.value = res.actions || [];
	} catch {
		// silent: filtro action sarà comunque text input
	}
};

const applyFilters = () => {
	currentPage.value = 1;
	fetchLogs();
};

const resetFilters = () => {
	filters.value = { action: '', actor_type: '', user_id: '', target_type: '', ip: '', date_from: '', date_to: '' };
	currentPage.value = 1;
	fetchLogs();
};

const goToPage = (page) => {
	if (page < 1 || page > lastPage.value || page === currentPage.value) return;
	currentPage.value = page;
	fetchLogs();
};

const showDetail = (log) => {
	selectedLog.value = log;
};

const closeDetail = () => {
	selectedLog.value = null;
};

const exportCsv = () => {
	const params = new URLSearchParams();
	for (const [k, v] of Object.entries(filters.value)) {
		if (v) params.append(k, v);
	}
	window.open(`/api/admin/audit-logs/export?${params.toString()}`, '_blank');
};

onMounted(() => {
	fetchLogs();
	fetchActions();
});
</script>

<template>
	<AccountPageSection>
		<AccountPageHeader
			eyebrow="Area amministrazione"
			title="Storico azioni admin"
			description="Tracciamento completo delle modifiche fatte dagli amministratori: chi, quando, cosa, da dove. Conforme audit GDPR e ISO 27001."
			:crumbs="[{ label: 'Account', to: '/account' }, { label: 'Amministrazione', to: '/account/amministrazione' }, { label: 'Storico azioni' }]"
			back-to="/account/amministrazione"
			back-label="Torna al pannello admin">
			<template #actions>
				<SfButton variant="secondary" size="sm" @click="exportCsv">
					<template #leading><UIcon name="mdi:download" class="h-4 w-4" /></template>
					Esporta CSV
				</SfButton>
			</template>
		</AccountPageHeader>

		<SfActionBanner :message="feedback" />

		<!-- KPI summary -->
		<div class="grid grid-cols-2 tablet:grid-cols-4 gap-3">
			<SfStatCard label="Totale azioni" :value="String(totalLogs)" icon="mdi:history" tone="primary" trend-label="In tutto il periodo" />
			<SfStatCard label="Pagina corrente" :value="`${currentPage} / ${lastPage}`" icon="mdi:file-document-multiple" tone="primary" trend-label="Naviga con i bottoni in basso" />
			<SfStatCard label="Per pagina" :value="String(perPage)" icon="mdi:counter" tone="primary" trend-label="Configurabile" />
			<SfStatCard label="Tipi azione" :value="String(availableActions.length)" icon="mdi:tag-multiple" tone="primary" trend-label="Distinti registrati" />
		</div>

		<!-- Filtri -->
		<SfCard padding="md">
			<template #header>
				<div class="flex items-center gap-2">
					<UIcon name="mdi:filter-variant" class="h-5 w-5 text-brand-primary" />
					<h2 class="font-display text-lg font-bold text-brand-text">Filtri</h2>
				</div>
			</template>

			<div class="grid grid-cols-1 tablet:grid-cols-2 desktop:grid-cols-3 gap-4">
				<SfFormGroup label="Azione">
					<SfSelect v-model="filters.action">
						<option value="">Tutte</option>
						<option v-for="a in availableActions" :key="a" :value="a">{{ actionLabel(a) }}</option>
					</SfSelect>
				</SfFormGroup>

				<SfFormGroup label="Tipo attore">
					<SfSelect v-model="filters.actor_type">
						<option v-for="opt in actorTypeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
					</SfSelect>
				</SfFormGroup>

				<SfFormGroup label="Tipo oggetto">
					<SfSelect v-model="filters.target_type">
						<option v-for="opt in targetTypeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
					</SfSelect>
				</SfFormGroup>

				<SfFormGroup label="ID utente">
					<SfInput id="al-user-id" v-model="filters.user_id" type="text" inputmode="numeric" placeholder="es. 5" />
				</SfFormGroup>

				<SfFormGroup label="IP">
					<SfInput id="al-ip" v-model="filters.ip" type="text" placeholder="es. 192.168.1.10" />
				</SfFormGroup>

				<SfFormGroup label="Da data">
					<SfInput id="al-date-from" v-model="filters.date_from" type="date" />
				</SfFormGroup>

				<SfFormGroup label="A data">
					<SfInput id="al-date-to" v-model="filters.date_to" type="date" />
				</SfFormGroup>
			</div>

			<div class="mt-4 flex flex-col tablet:flex-row gap-3 justify-end">
				<SfButton variant="secondary" @click="resetFilters">
					<template #leading><UIcon name="mdi:refresh" class="h-4 w-4" /></template>
					Pulisci filtri
				</SfButton>
				<SfButton @click="applyFilters">
					<template #leading><UIcon name="mdi:magnify" class="h-4 w-4" /></template>
					Applica filtri
				</SfButton>
			</div>
		</SfCard>

		<!-- Tabella log -->
		<SfCard padding="none">
			<template #header>
				<div class="flex items-center justify-between p-4">
					<h2 class="font-display text-lg font-bold text-brand-text">Registro azioni</h2>
					<span class="text-sm text-brand-text-secondary">{{ totalLogs }} totali</span>
				</div>
			</template>

			<div v-if="isLoading" class="p-6">
				<SfSkeleton v-for="n in 5" :key="n" height="56px" />
			</div>

			<SfEmptyState
				v-else-if="!logs.length"
				icon="mdi:history"
				title="Nessuna azione registrata"
				description="Non ci sono azioni admin che corrispondono ai filtri selezionati. Modifica i filtri o cancella per vedere tutto." />

			<div v-else class="overflow-x-auto">
				<table class="w-full text-sm">
					<thead class="bg-brand-bg-alt border-b border-brand-border">
						<tr>
							<th class="text-left font-semibold text-brand-text-muted uppercase text-xs tracking-wide px-4 py-3">Quando</th>
							<th class="text-left font-semibold text-brand-text-muted uppercase text-xs tracking-wide px-4 py-3">Chi</th>
							<th class="text-left font-semibold text-brand-text-muted uppercase text-xs tracking-wide px-4 py-3">Azione</th>
							<th class="text-left font-semibold text-brand-text-muted uppercase text-xs tracking-wide px-4 py-3">Oggetto</th>
							<th class="text-left font-semibold text-brand-text-muted uppercase text-xs tracking-wide px-4 py-3">IP</th>
							<th class="text-right font-semibold text-brand-text-muted uppercase text-xs tracking-wide px-4 py-3">Dettaglio</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="log in logs" :key="log.id" class="border-b border-brand-border last:border-b-0 hover:bg-brand-bg-alt/40 transition">
							<td class="px-4 py-3 text-brand-text whitespace-nowrap">{{ formatDate(log.created_at) }}</td>
							<td class="px-4 py-3">
								<div v-if="log.user" class="flex flex-col">
									<span class="font-semibold text-brand-text">{{ log.user.name }} {{ log.user.surname }}</span>
									<span class="text-xs text-brand-text-muted">{{ log.user.email }}</span>
								</div>
								<span v-else class="text-brand-text-muted">{{ log.actor_type || 'system' }}</span>
							</td>
							<td class="px-4 py-3">
								<span
:class="[
									'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold',
									actionTone(log.action) === 'danger' ? 'bg-brand-error/10 text-brand-error' :
									actionTone(log.action) === 'success' ? 'bg-brand-success-bg text-brand-success-fg' :
									actionTone(log.action) === 'info' ? 'bg-brand-primary/10 text-brand-primary' :
									'bg-brand-bg-alt text-brand-text-muted'
								]">
									{{ actionLabel(log.action) }}
								</span>
							</td>
							<td class="px-4 py-3 text-brand-text-secondary">
								<template v-if="log.target_type">{{ log.target_type }} #{{ log.target_id }}</template>
								<template v-else>—</template>
							</td>
							<td class="px-4 py-3 font-mono text-xs text-brand-text-muted">{{ log.ip || '—' }}</td>
							<td class="px-4 py-3 text-right">
								<button type="button" class="text-brand-primary hover:text-brand-primary-hover" :aria-label="'Dettaglio azione ' + log.id" @click="showDetail(log)">
									<UIcon name="mdi:eye" class="h-5 w-5" />
								</button>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<!-- Paginazione -->
			<div v-if="lastPage > 1" class="flex items-center justify-between border-t border-brand-border p-4">
				<span class="text-sm text-brand-text-secondary">
					Pagina {{ currentPage }} di {{ lastPage }}
				</span>
				<div class="flex gap-2">
					<SfButton variant="secondary" size="sm" :disabled="currentPage <= 1" @click="goToPage(currentPage - 1)">
						<template #leading><UIcon name="mdi:chevron-left" class="h-4 w-4" /></template>
						Precedente
					</SfButton>
					<SfButton variant="secondary" size="sm" :disabled="currentPage >= lastPage" @click="goToPage(currentPage + 1)">
						Successiva
						<template #trailing><UIcon name="mdi:chevron-right" class="h-4 w-4" /></template>
					</SfButton>
				</div>
			</div>
		</SfCard>

		<!-- Modal dettaglio -->
		<SfModal v-if="selectedLog" :open="!!selectedLog" title="Dettaglio azione" size="lg" @close="closeDetail">
			<div class="space-y-4">
				<div class="grid grid-cols-2 gap-4 text-sm">
					<div>
						<p class="text-xs font-semibold uppercase tracking-wide text-brand-text-muted">ID</p>
						<p class="font-mono text-brand-text">{{ selectedLog.id }}</p>
					</div>
					<div>
						<p class="text-xs font-semibold uppercase tracking-wide text-brand-text-muted">Quando</p>
						<p class="text-brand-text">{{ formatDate(selectedLog.created_at) }}</p>
					</div>
					<div>
						<p class="text-xs font-semibold uppercase tracking-wide text-brand-text-muted">Azione</p>
						<p class="font-semibold text-brand-text">{{ actionLabel(selectedLog.action) }}</p>
						<p class="font-mono text-xs text-brand-text-muted">{{ selectedLog.action }}</p>
					</div>
					<div>
						<p class="text-xs font-semibold uppercase tracking-wide text-brand-text-muted">Tipo attore</p>
						<p class="text-brand-text">{{ selectedLog.actor_type || '—' }}</p>
					</div>
					<div v-if="selectedLog.user">
						<p class="text-xs font-semibold uppercase tracking-wide text-brand-text-muted">Utente</p>
						<p class="text-brand-text">{{ selectedLog.user.name }} {{ selectedLog.user.surname }}</p>
						<p class="text-xs text-brand-text-muted">{{ selectedLog.user.email }}</p>
					</div>
					<div v-if="selectedLog.target_type">
						<p class="text-xs font-semibold uppercase tracking-wide text-brand-text-muted">Oggetto</p>
						<p class="text-brand-text">{{ selectedLog.target_type }} #{{ selectedLog.target_id }}</p>
					</div>
					<div>
						<p class="text-xs font-semibold uppercase tracking-wide text-brand-text-muted">IP</p>
						<p class="font-mono text-brand-text">{{ selectedLog.ip || '—' }}</p>
					</div>
					<div>
						<p class="text-xs font-semibold uppercase tracking-wide text-brand-text-muted">User Agent</p>
						<p class="text-xs text-brand-text-muted break-all">{{ selectedLog.user_agent || '—' }}</p>
					</div>
				</div>

				<div v-if="selectedLog.context && Object.keys(selectedLog.context || {}).length">
					<p class="text-xs font-semibold uppercase tracking-wide text-brand-text-muted mb-2">Dettaglio contesto</p>
					<pre class="rounded-card border border-brand-border bg-brand-bg-alt p-4 text-xs font-mono text-brand-text whitespace-pre-wrap break-all overflow-x-auto">{{ JSON.stringify(selectedLog.context, null, 2) }}</pre>
				</div>
			</div>

			<template #footer>
				<SfButton variant="secondary" @click="closeDetail">Chiudi</SfButton>
			</template>
		</SfModal>
	</AccountPageSection>
</template>
