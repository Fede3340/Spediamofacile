<script setup>
definePageMeta({
	middleware: ['app-auth', 'admin'],
});

useSeoMeta({
	title: 'Console amministrazione',
	ogTitle: 'Console amministrazione',
	description: 'Priorita operative, KPI essenziali, andamento ordini e attività recente in una console amministrativa più ordinata.',
	ogDescription: 'Console amministrazione SpediamoFacile con priorità operative, KPI essenziali, andamento ordini e attività recente.',
	robots: 'noindex, nofollow',
});

const sanctum = useSanctumClient();
const { formatCents, formatDate, orderStatusConfig } = useAdmin();

const isLoading = ref(true);
const dashboardData = ref(null);
const loadError = ref('');

const fetchDashboard = async () => {
	loadError.value = '';
	try {
		dashboardData.value = await sanctum('/api/admin/dashboard');
	} catch {
		dashboardData.value = null;
		loadError.value = 'Impossibile sincronizzare la console amministrativa.';
	}
};

const reloadDashboard = async () => {
	isLoading.value = true;
	await fetchDashboard();
	isLoading.value = false;
};

onMounted(reloadDashboard);

const pendingOrders = computed(() => dashboardData.value?.orders?.pending ?? 0);
const failedPayments = computed(() => dashboardData.value?.orders?.payment_failed ?? 0);
const inTransitShipments = computed(() => dashboardData.value?.shipments?.in_transit ?? 0);
const shipmentsWithoutLabel = computed(() => dashboardData.value?.shipments?.without_label ?? 0);
const monthOrders = computed(() => dashboardData.value?.orders?.month ?? 0);
const todayOrders = computed(() => dashboardData.value?.orders?.today ?? 0);
const weekOrders = computed(() => dashboardData.value?.orders?.week ?? 0);
const totalUsers = computed(() => dashboardData.value?.users?.total ?? 0);
const proUsers = computed(() => dashboardData.value?.users?.pro ?? 0);
const revenueMonth = computed(() => dashboardData.value?.revenue_month ?? 0);
const recentOrders = computed(() => dashboardData.value?.recent_orders || []);
const dailyOrders = computed(() => dashboardData.value?.daily_orders || []);
const pendingWithdrawals = computed(() => dashboardData.value?.pending_withdrawals ?? 0);
const pendingProRequests = computed(() => dashboardData.value?.pending_pro_requests ?? 0);

const urgentActions = computed(() => {
	const items = [];
	if (pendingOrders.value > 0) {
		items.push({ key: 'orders', label: `${pendingOrders.value} ordini in attesa`, to: '/account/amministrazione/ordini', cta: 'Apri ordini' });
	}
	if (shipmentsWithoutLabel.value > 0) {
		items.push({ key: 'labels', label: `${shipmentsWithoutLabel.value} etichette da generare`, to: '/account/amministrazione/spedizioni', cta: 'Apri spedizioni' });
	}
	if (pendingWithdrawals.value > 0) {
		items.push({ key: 'withdrawals', label: `${pendingWithdrawals.value} prelievi in attesa`, to: '/account/amministrazione/prelievi', cta: 'Apri prelievi' });
	}
	if (pendingProRequests.value > 0) {
		items.push({ key: 'pro', label: `${pendingProRequests.value} richieste Pro`, to: '/account/amministrazione/utenti', cta: 'Apri utenti' });
	}
	if (failedPayments.value > 0) {
		items.push({ key: 'payments', label: `${failedPayments.value} pagamenti falliti`, to: '/account/amministrazione/ordini', cta: 'Apri ordini' });
	}
	if (inTransitShipments.value > 0) {
		items.push({ key: 'transit', label: `${inTransitShipments.value} spedizioni in transito`, to: '/account/amministrazione/spedizioni', cta: 'Apri spedizioni' });
	}
	return items.slice(0, 3);
});

const primaryUrgentAction = computed(() => urgentActions.value[0] || null);
const notificationTitle = computed(() => primaryUrgentAction.value?.label || 'Nessuna urgenza aperta');
const notificationDescription = computed(() => {
	if (!primaryUrgentAction.value) {
		return 'Console allineata: puoi leggere andamento, ordini e aggiornamenti senza code urgenti aperte.';
	}
	return 'Apri solo la coda che richiede davvero intervento.';
});

const statsCards = computed(() => [
	{
		key: 'ordini',
		label: 'Ordini attivi',
		value: pendingOrders.value,
		meta: `${monthOrders.value} registrati nel mese corrente`,
		icon: 'mdi:chart-bar',
	},
	{
		key: 'ricavi',
		label: 'Ricavi mese',
		value: formatCents(revenueMonth.value),
		meta: `${todayOrders.value} ordini registrati oggi`,
		icon: 'mdi:currency-eur',
	},
	{
		key: 'utenti',
		label: 'Clienti registrati',
		value: totalUsers.value,
		meta: `${proUsers.value} account Pro attivi`,
		icon: 'mdi:account-group',
	},
	{
		key: 'spedizioni',
		label: 'Spedizioni BRT',
		value: inTransitShipments.value,
		meta: `${shipmentsWithoutLabel.value} da etichettare`,
		icon: 'mdi:truck-fast',
	},
]);

const analyticsProps = computed(() => ({
	days: dailyOrders.value,
	today: todayOrders.value,
	week: weekOrders.value,
	month: monthOrders.value,
	dailyRevenue: dashboardData.value?.daily_revenue ?? dashboardData.value?.dailyRevenue ?? [],
	revenueToday: dashboardData.value?.revenue_today ?? 0,
	revenueWeek: dashboardData.value?.revenue_week ?? 0,
	revenueMonth: revenueMonth.value,
	statusDistribution: dashboardData.value?.status_distribution ?? dashboardData.value?.orders_status_distribution ?? dashboardData.value?.shipment_status_distribution ?? [],
}));

const activityItems = computed(() =>
	recentOrders.value.slice(0, 5).map((order) => ({
		id: order.id,
		customerName: [order.user?.name, order.user?.surname].filter(Boolean).join(' ') || order.user?.email || 'Cliente',
		status: orderStatusConfig[order.status]?.label || order.status || 'Aggiornamento',
		statusKey: order.status || 'pending',
		amount: formatCents(order.total ?? order.total_amount ?? order.subtotal ?? 0),
		date: formatDate(order.created_at),
	})),
);

const statusBadgeStyle = (status) => useStatusBadgeStyle(status);
</script>

<template>
	<AccountPageSection spacing="space-y-4">
		<AccountPageHeader
				:crumbs="[{ label: 'Account', to: '/account' }, { label: 'Amministrazione' }]"
				title="Console amministrazione"
				description="Priorita operative, KPI essenziali, andamento ordini e attività recente in una console più ordinata.">
				<template #actions>
					<div class="flex items-center gap-2 shrink-0">
						<SfButton to="/account/amministrazione/ordini">
							<template #leading><UIcon name="mdi:chart-bar" class="w-4 h-4" /></template>
							Gestisci ordini
						</SfButton>
					</div>
				</template>
			</AccountPageHeader>

			<template v-if="dashboardData || isLoading">
				<div v-if="dashboardData || isLoading" class="space-y-4">
					<section
						:class="[
							'rounded-card border p-5 flex flex-col tablet:flex-row tablet:items-center tablet:justify-between gap-4',
							primaryUrgentAction
								? 'border-amber-200 bg-amber-50'
								: 'border-brand-success/30 bg-brand-success-bg',
						]">
						<div class="flex flex-col gap-1.5 min-w-0">
							<p class="text-xs font-extrabold uppercase tracking-wider text-brand-text-muted">Notifiche</p>
							<h2 class="font-display text-lg font-bold text-brand-text leading-tight">{{ notificationTitle }}</h2>
							<p v-if="primaryUrgentAction" class="text-sm text-brand-text-secondary">{{ notificationDescription }}</p>
						</div>
						<div class="shrink-0">
							<SfButton
								v-if="primaryUrgentAction"
								variant="secondary"
								size="sm"
								:to="primaryUrgentAction.to">
								{{ primaryUrgentAction.cta }}
							</SfButton>
							<span v-else class="inline-flex items-center px-3 py-1 rounded-pill bg-brand-success-bg text-brand-success-fg text-xs font-bold">Allineato</span>
						</div>
					</section>

					<section class="grid grid-cols-1 tablet:grid-cols-2 desktop:grid-cols-4 gap-4">
						<SfStatCard
							v-for="card in statsCards"
							:key="card.key"
							:label="card.label"
							:value="card.value"
							:icon="card.icon"
							tone="primary"
							:trend-label="card.meta" />
					</section>

					<AdminConsoleKpiInsights
						:revenue-month="revenueMonth"
						:revenue-week="dashboardData?.revenue_week ?? 0"
						:month-orders="monthOrders"
						:week-orders="weekOrders"
						:status-distribution="analyticsProps.statusDistribution"
						:daily-orders="dailyOrders" />

					<LazyAdminConsoleAnalytics v-bind="analyticsProps" />

					<section class="space-y-3">
						<div class="flex items-center justify-between gap-3">
							<div class="min-w-0">
								<h2 class="font-display text-xl font-extrabold leading-tight text-brand-text">
									Ultimi aggiornamenti
								</h2>
								<p class="mt-1.5 text-sm leading-relaxed text-brand-text-secondary">
									Le ultime operazioni, con il dettaglio che serve per aprire subito la coda giusta.
								</p>
							</div>
							<NuxtLink to="/account/amministrazione/ordini" class="text-sm font-bold text-brand-primary hover:text-brand-primary-hover">
								Tutti gli ordini
							</NuxtLink>
						</div>

						<SfEmptyState
							v-if="!activityItems.length"
							icon="mdi:inbox-outline"
							title="Nessun aggiornamento recente"
							variant="compact" />

						<div v-else class="grid grid-cols-1 tablet:grid-cols-2 desktop:grid-cols-3 gap-3">
							<NuxtLink
								v-for="item in activityItems.slice(0, 5)"
								:key="item.id"
								to="/account/amministrazione/ordini"
								class="block p-4 rounded-card border border-brand-border bg-brand-card transition hover:border-brand-primary/40 hover:shadow-sf-sm no-underline">
								<div class="flex items-start justify-between gap-3">
									<div class="min-w-0">
										<p class="text-xs font-extrabold tracking-wider uppercase text-brand-text-muted">
											Ordine #{{ item.id }}
										</p>
										<p class="mt-1.5 text-base font-extrabold leading-tight text-brand-text">
											{{ item.customerName }}
										</p>
									</div>
									<span
										class="inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-xs font-extrabold"
										:style="statusBadgeStyle(item.statusKey)">
										{{ item.status }}
									</span>
								</div>
								<div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-sm text-brand-text-secondary">
									<span>{{ item.amount }} €</span>
									<span>{{ item.date }}</span>
								</div>
							</NuxtLink>
						</div>
					</section>
				</div>

				<div v-else class="py-8">
					<div class="space-y-3">
						<SfActionBanner :message="loadError || 'Impossibile caricare i dati della console.'" tone="danger" />
						<div class="flex justify-center">
							<SfButton variant="secondary" @click="reloadDashboard">Riprova</SfButton>
						</div>
					</div>
				</div>
			</template>
	</AccountPageSection>
</template>
