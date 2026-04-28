<script setup>
import '~/assets/css/admin.css';
definePageMeta({
	middleware: ['app-auth', 'admin'],
});

useSeoMeta({
	title: 'Console amministrazione',
	ogTitle: 'Console amministrazione',
	description: 'Priorita operative, KPI essenziali, andamento ordini e attivita recente in una console amministrativa piu ordinata.',
	ogDescription: 'Console amministrazione SpediamoFacile con priorita operative, KPI essenziali, andamento ordini e attivita recente.',
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

onMounted(async () => {
	await fetchDashboard();
	isLoading.value = false;
});

const pendingOrders = computed(() => dashboardData.value?.orders?.pending ?? 0);
const failedPayments = computed(() => dashboardData.value?.orders?.payment_failed ?? 0);
const inTransitShipments = computed(() => dashboardData.value?.shipments?.in_transit ?? 0);
const deliveredShipments = computed(() => dashboardData.value?.shipments?.delivered ?? 0);
const shipmentsWithoutLabel = computed(() => dashboardData.value?.shipments?.without_label ?? 0);
const monthOrders = computed(() => dashboardData.value?.orders?.month ?? 0);
const todayOrders = computed(() => dashboardData.value?.orders?.today ?? 0);
const weekOrders = computed(() => dashboardData.value?.orders?.week ?? 0);
const totalUsers = computed(() => dashboardData.value?.users?.total ?? 0);
const proUsers = computed(() => dashboardData.value?.users?.pro ?? 0);
const revenueMonth = computed(() => dashboardData.value?.revenue_month ?? 0);
const recentOrders = computed(() => dashboardData.value?.recent_orders || []);
const dailyOrders = computed(() => dashboardData.value?.daily_orders || []);
const unreadMessages = computed(() => dashboardData.value?.unread_messages ?? 0);
const pendingWithdrawals = computed(() => dashboardData.value?.pending_withdrawals ?? 0);
const pendingProRequests = computed(() => dashboardData.value?.pending_pro_requests ?? 0);

const urgentActions = computed(() => {
	const items = [];
	if (pendingOrders.value > 0) {
		items.push({ key: 'orders', label: `${pendingOrders.value} ordini in attesa`, to: '/account/amministrazione/ordini', tone: 'accent', cta: 'Apri ordini' });
	}
	if (shipmentsWithoutLabel.value > 0) {
		items.push({ key: 'labels', label: `${shipmentsWithoutLabel.value} etichette da generare`, to: '/account/amministrazione/spedizioni', tone: 'primary', cta: 'Apri spedizioni' });
	}
	if (pendingWithdrawals.value > 0) {
		items.push({ key: 'withdrawals', label: `${pendingWithdrawals.value} prelievi in attesa`, to: '/account/amministrazione/prelievi', tone: 'accent', cta: 'Apri prelievi' });
	}
	// -- ARCHIVIATO 2026-04-20: Messaggi (admin-messaggi-sistema) — sostituito da email/contatto esterno --
	// if (unreadMessages.value > 0) {
	// 	items.push({ key: 'messages', label: `${unreadMessages.value} messaggi da leggere`, to: '/account/amministrazione/messaggi', tone: 'primary', cta: 'Apri messaggi' });
	// }
	if (pendingProRequests.value > 0) {
		items.push({ key: 'pro', label: `${pendingProRequests.value} richieste Pro`, to: '/account/amministrazione/utenti', tone: 'accent', cta: 'Apri utenti' });
	}
	if (failedPayments.value > 0) {
		items.push({ key: 'payments', label: `${failedPayments.value} pagamenti falliti`, to: '/account/amministrazione/ordini', tone: 'accent', cta: 'Apri ordini' });
	}
	if (inTransitShipments.value > 0) {
		items.push({ key: 'transit', label: `${inTransitShipments.value} spedizioni in transito`, to: '/account/amministrazione/spedizioni', tone: 'primary', cta: 'Apri spedizioni' });
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
		icon: 'M4 6h2v12H4V6zm4 2h2v10H8V8zm4-4h2v14h-2V4zm4 6h2v8h-2v-8z',
	},
	{
		key: 'ricavi',
		label: 'Ricavi mese',
		value: formatCents(revenueMonth.value),
		meta: `${todayOrders.value} ordini registrati oggi`,
		icon: 'M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z',
	},
	{
		key: 'utenti',
		label: 'Clienti registrati',
		value: totalUsers.value,
		meta: `${proUsers.value} account Pro attivi`,
		icon: 'M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5z',
	},
	{
		key: 'spedizioni',
		label: 'Spedizioni BRT',
		value: inTransitShipments.value,
		meta: `${shipmentsWithoutLabel.value} da etichettare`,
		icon: 'M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4z',
	},
]);

// Shortcuts rimossi: duplicavano la sidebar amministrativa (Ordini, Spedizioni,
// Messaggi, Utenti). La console resta focalizzata su urgenze, KPI e attivita' recente.

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

// Palette unificata via useStatusBadge composable (P5 design system)
const statusBadgeStyle = (status) => useStatusBadgeStyle(status);
</script>

<template>
	<section class="sf-account-shell min-h-[600px] py-[24px] sm:py-[28px] lg:py-[32px]">
		<div class="my-container space-y-[16px]">
			<AccountPageHeader
				class="sf-account-shell-hero--compact sf-admin-console-header"
				:crumbs="[{ label: 'Account', to: '/account' }, { label: 'Amministrazione' }]"
				title="Console amministrazione"
				description="Priorita operative, KPI essenziali, andamento ordini e attivita recente in una console piu ordinata.">
				<template #actions>
					<div class="flex items-center gap-[8px] shrink-0">
						<NuxtLink to="/account/amministrazione/ordini" class="sf-admin-btn-primary">
							<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
								<path d="M4 6h2v12H4V6zm4 2h2v10H8V8zm4-4h2v14h-2V4zm4 6h2v8h-2v-8z" />
							</svg>
							Gestisci ordini
						</NuxtLink>
					</div>
				</template>
			</AccountPageHeader>

			<template v-if="dashboardData || isLoading">
				<div v-if="dashboardData || isLoading" class="space-y-[16px]">
					<!-- Hub wrapper rimosso (P13: era contenitore semantico vuoto + intro duplicato del page header).
					     Le 3 sezioni interne ora sono dirette figlie della console, riducendo annidamento e altezza pagina. -->
					<section class="sf-admin-console-notice" :class="primaryUrgentAction ? 'sf-admin-console-notice--alert' : 'sf-admin-console-notice--calm'">
							<div class="sf-admin-console-notice__body">
								<div class="sf-admin-console-notice__copy">
									<p class="sf-admin-console-notice__eyebrow">Notifiche</p>
									<h2 class="sf-admin-console-notice__title">{{ notificationTitle }}</h2>
									<p v-if="primaryUrgentAction" class="sf-admin-console-notice__description">{{ notificationDescription }}</p>
								</div>
								<div class="sf-admin-console-notice__actions">
									<NuxtLink
										v-if="primaryUrgentAction"
										:to="primaryUrgentAction.to"
										class="sf-admin-btn-secondary sf-admin-console-notice__cta">
										{{ primaryUrgentAction.cta }}
									</NuxtLink>
									<span v-else class="sf-admin-console-notice__status">Allineato</span>
								</div>
							</div>
						</section>

						<section class="sf-admin-stats-grid">
							<article v-for="card in statsCards" :key="card.key" class="sf-admin-stat-card">
								<div class="sf-admin-stat-card__top">
									<span class="sf-admin-stat-card__icon">
										<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
											<path :d="card.icon" />
										</svg>
									</span>
									<span class="sf-admin-stat-card__label">{{ card.label }}</span>
								</div>
								<p class="sf-admin-stat-card__value">{{ card.value }}</p>
								<p class="sf-admin-stat-card__meta">{{ card.meta }}</p>
							</article>
						</section>

						<!-- W5.1 perf: bundle analytics (754 righe + chart deps) lazy-loaded
							 per abbattere il JS iniziale della dashboard admin. -->
						<LazyAdminConsoleAnalytics class="sf-admin-console-analytics" v-bind="analyticsProps" />

						<section class="sf-admin-console-subsection">
							<div class="flex items-center justify-between gap-[12px]">
								<div class="min-w-0">
									<h2
										class="text-[1.2rem] font-[800] leading-[1.08] text-[var(--color-brand-text)]"
										style="font-family: var(--font-montserrat)">
										Ultimi aggiornamenti
									</h2>
									<p class="mt-[6px] text-[0.9rem] leading-[1.5] text-[var(--color-brand-text-secondary)]">
										Le ultime operazioni, con il dettaglio che serve per aprire subito la coda giusta.
									</p>
								</div>
								<NuxtLink to="/account/amministrazione/ordini" class="text-[0.85rem] font-[700] text-[var(--color-brand-primary)] hover:text-[#0b6c7f]">
									Tutti gli ordini
								</NuxtLink>
							</div>

							<div v-if="!activityItems.length" class="mt-[18px] rounded-[16px] border border-[var(--color-brand-border)] bg-[#FAFBFC] px-[16px] py-[18px] text-[0.9rem] text-[var(--color-brand-text-secondary)]">
								Nessun aggiornamento recente disponibile.
							</div>

							<div v-else class="sf-admin-console-feed">
								<NuxtLink
									v-for="item in activityItems.slice(0, 5)"
									:key="item.id"
									to="/account/amministrazione/ordini"
									class="sf-admin-console-feed__item"
								>
										<div class="flex items-start justify-between gap-[12px]">
											<div class="min-w-0">
												<p class="text-[0.72rem] font-[800] tracking-[0.14em] uppercase text-[var(--color-brand-text-muted)]">
													Ordine #{{ item.id }}
												</p>
												<p class="mt-[6px] text-[0.95rem] font-[800] leading-[1.2] text-[var(--color-brand-text)]">
													{{ item.customerName }}
												</p>
											</div>
										<span
											class="inline-flex shrink-0 items-center rounded-full px-[10px] py-[5px] text-[0.72rem] font-[800]"
											:style="statusBadgeStyle(item.statusKey)">
											{{ item.status }}
										</span>
									</div>
										<div class="mt-[8px] flex flex-wrap items-center justify-between gap-[8px] text-[0.82rem] text-[var(--color-brand-text-secondary)]">
											<span>{{ item.amount }} &euro;</span>
											<span>{{ item.date }}</span>
										</div>
									</NuxtLink>
							</div>
						</section>
				</div>

				<div v-else class="py-[32px]">
					<div class="space-y-[12px]">
						<AdminActionBanner :message="loadError || 'Impossibile caricare i dati della console.'" tone="error" />
						<div class="flex justify-center">
							<button
								type="button"
								class="sf-admin-btn-secondary"
								@click="isLoading = true; fetchDashboard().then(() => { isLoading = false; })"
							>
								Riprova
							</button>
						</div>
					</div>
				</div>
			</template>
		</div>
	</section>
</template>

