import { computed, onMounted, ref, watch } from 'vue';
import { useAuthUiSnapshotPersistence } from '~/composables/useAuth';
import useOrdersList from '~/composables/useOrdersList';
import { createAccountSections } from '~/utils/account';

/**
 * Composable che aggrega la logica dashboard account:
 * - Risoluzione ruolo (admin/pro/client)
 * - Caricamento ordini cliente (via useOrdersList)
 * - Caricamento dashboard admin (/api/admin/dashboard)
 * - Computed per highlights cliente e KPI/alerts/recent admin
 * - handleLogout condiviso
 * @returns {object}
 */
export function useAccountDashboard() {
	const sanctum = useSanctumClient();
	const { user, logout } = useSanctumAuth();
	const { formatCents, formatDate, orderStatusConfig } = useAdmin();
	const { clearSnapshot } = useAuthUiSnapshotPersistence();
	const { uiSnapshot } = useAuthUiState();
	const {
		orders,
		refresh: refreshOrders,
		ordersStatus,
		orderStats,
		statusRaw,
		getRouteLabel,
		getOrderDateLabel,
		getOrderReferenceLabel,
	} = useOrdersList();

	const resolveRoleKey = (...candidates) => {
		for (const candidate of candidates) {
			const normalized = String(candidate || '').trim().toLowerCase();
			if (!normalized) continue;
			if (normalized.includes('admin')) return 'admin';
			if (normalized.includes('partner pro') || normalized === 'pro' || normalized.includes(' pro')) return 'pro';
			if (normalized.includes('cliente') || normalized.includes('client')) return 'client';
		}

		return 'client';
	};

	// La dashboard deve leggere prima l'utente live, non lo snapshot UI, per
	// evitare drift SSR/client nelle superfici account/admin.
	const roleKey = computed(() => resolveRoleKey(user.value?.role, uiSnapshot.value.role));
	const effectiveRole = computed(() => {
		if (roleKey.value === 'admin') return 'Admin';
		if (roleKey.value === 'pro') return 'Partner Pro';
		return 'Cliente';
	});
	const isAdmin = computed(() => roleKey.value === 'admin');
	const isPro = computed(() => roleKey.value === 'pro');

	const sections = computed(() =>
		createAccountSections({
			isAdmin: isAdmin.value,
			isPro: isPro.value,
		}),
	);

	const visibleSections = computed(() =>
		sections.value
			.map((section) => ({
				...section,
				pages: section.pages.filter((page) => page.visible),
			}))
			.filter((section) => section.pages.length > 0),
	);

	const allVisiblePages = computed(() => visibleSections.value.flatMap((section) => section.pages));

	// -- ARCHIVIATO 2026-04-20: bonusPage (_archive/frontend-simplification-2026-04-20/features/bonus-fedelta) --
	// const bonusPage = computed(() => allVisiblePages.value.find((page) => page.url === '/bonus') || null);

	/* ---------------------- CLIENT COMPUTED ---------------------- */
	const customerOrdersLoading = computed(() => ordersStatus.value === 'pending');
	const customerOrders = computed(() => (((orders.value || {})).data || []));
	const customerActiveStatusIds = ['pending', 'processing', 'payed', 'label_generated', 'in_transit', 'out_for_delivery'];
	const customerCompletedStatusIds = ['completed', 'delivered'];
	const customerCompletedCount = computed(() =>
		(Array.isArray(customerOrders.value) ? customerOrders.value : []).filter((order) =>
			customerCompletedStatusIds.includes(statusRaw(order?.status)),
		).length,
	);
	const highlightedCustomerOrders = computed(() => {
		const list = Array.isArray(customerOrders.value) ? customerOrders.value : [];
		const openOrders = list.filter((order) => customerActiveStatusIds.includes(statusRaw(order?.status)));
		const source = (openOrders.length ? openOrders : list).slice(0, 3);

		return source.map((order) => {
			const rawStatus = statusRaw(order?.status);
			const statusLabel = (orderStatusConfig)[rawStatus]?.label || order?.status || 'Aggiornato';
			const tone = rawStatus === 'delivered' || rawStatus === 'completed'
				? { bg: 'rgba(5, 150, 105, 0.1)', color: '#047857' }
				: rawStatus === 'payment_failed' || rawStatus === 'cancelled' || rawStatus === 'refused'
					? { bg: 'rgba(220, 38, 38, 0.1)', color: '#B91C1C' }
					: rawStatus === 'pending'
						? { bg: 'rgba(228, 66, 3, 0.08)', color: '#E44203' }
						: { bg: 'rgba(9, 88, 102, 0.08)', color: '#095866' };

			return {
				id: order?.id,
				url: '/account/spedizioni',
				title: getRouteLabel(order),
				meta: `${getOrderReferenceLabel(order)} · ${getOrderDateLabel(order)}`,
				statusLabel,
				tone,
			};
		});
	});

	const recentCompletedCustomerOrders = computed(() => {
		const list = Array.isArray(customerOrders.value) ? customerOrders.value : [];
		return list
			.filter((order) => customerCompletedStatusIds.includes(statusRaw(order?.status)))
			.slice(0, 3)
			.map((order) => ({
				id: order?.id,
				url: '/account/spedizioni',
				title: getRouteLabel(order),
				meta: `${getOrderReferenceLabel(order)} · ${getOrderDateLabel(order)}`,
			}));
	});

	const personalHighlights = computed(() => [
		{
			label: 'Spedite',
			value: String(orderStats.value.total || 0),
			meta: 'Tutte le spedizioni create dal tuo account.',
			iconKey: 'package',
			iconBg: '#ECF8F8',
			iconColor: '#095866',
		},
		{
			label: 'Da seguire',
			value: String(orderStats.value.open || 0),
			meta: 'Ordini ancora in lavorazione o in consegna.',
			iconKey: 'truck-fast',
			iconBg: '#ECF8F8',
			iconColor: '#095866',
		},
		{
			label: 'Consegnate',
			value: String(customerCompletedCount.value),
			meta: 'Spedizioni concluse e pronte da consultare.',
			iconKey: 'check-circle',
			iconBg: '#F0FDF4',
			iconColor: '#047857',
		},
	]);

	/* ---------------------- ADMIN DASHBOARD ---------------------- */
	const adminDashboardData = ref(null);
	const adminDashboardLoading = ref(false);
	const adminDashboardError = ref('');

	const fetchAdminDashboard = async () => {
		if (!isAdmin.value || adminDashboardLoading.value) return;

		adminDashboardLoading.value = true;
		adminDashboardError.value = '';

		try {
			adminDashboardData.value = await sanctum('/api/admin/dashboard');
		} catch {
			adminDashboardData.value = null;
			adminDashboardError.value = 'Impossibile sincronizzare i dati della dashboard admin.';
		} finally {
			adminDashboardLoading.value = false;
		}
	};

	onMounted(async () => {
		if (isAdmin.value) {
			await fetchAdminDashboard();
			return;
		}

		await refreshOrders();
	});

	watch(isAdmin, async (value) => {
		if (!import.meta.client || !value || adminDashboardData.value || adminDashboardLoading.value) return;
		await fetchAdminDashboard();
	});

	const adminAlerts = computed(() => {
		if (!isAdmin.value) return [];

		const o = adminDashboardData.value?.orders || {};
		const s = adminDashboardData.value?.shipments || {};
		const items = [];

		if ((o.pending ?? 0) > 0) {
			items.push({
				label: `${o.pending} ${o.pending === 1 ? 'ordine in attesa di elaborazione' : 'ordini in attesa di elaborazione'}`,
				meta: 'Apri subito la coda ordini e chiudi le richieste rimaste aperte.',
				action: 'Gestisci',
				url: '/account/amministrazione/ordini',
				iconKey: 'clipboard-list',
				iconBg: '#FFF4EE',
				iconColor: '#E44203',
			});
		}

		if ((o.payment_failed ?? 0) > 0) {
			items.push({
				label: `${o.payment_failed} ${o.payment_failed === 1 ? 'pagamento da verificare' : 'pagamenti da verificare'}`,
				meta: 'Controlla gli ordini con errore di pagamento e riallinea il flusso.',
				action: 'Controlla',
				url: '/account/amministrazione/ordini',
				iconKey: 'credit-card',
				iconBg: '#FFF4EE',
				iconColor: '#E44203',
			});
		}

		if ((s.in_transit ?? 0) > 0) {
			items.push({
				label: `${s.in_transit} ${s.in_transit === 1 ? 'spedizione in transito' : 'spedizioni in transito'}`,
				meta: 'Monitora le consegne attive e intercetta subito eventuali anomalie.',
				action: 'Apri spedizioni',
				url: '/account/amministrazione/spedizioni',
				iconKey: 'truck-delivery',
				iconBg: '#ECF8F8',
				iconColor: '#095866',
			});
		}

		if (!items.length) {
			items.push({
				label: 'Console operativa allineata',
				meta: 'Nessuna urgenza bloccante: puoi passare direttamente alla console completa.',
				action: 'Apri console',
				url: '/account/amministrazione',
				iconKey: 'chart-box',
				iconBg: '#ECF8F8',
				iconColor: '#095866',
			});
		}

		return items;
	});

	const adminKpis = computed(() => {
		if (!isAdmin.value) return [];

		const o = adminDashboardData.value?.orders || {};
		const u = adminDashboardData.value?.users || {};
		const s = adminDashboardData.value?.shipments || {};
		const revenue = adminDashboardData.value?.revenue ?? 0;
		const revenueMonth = adminDashboardData.value?.revenue_month ?? 0;

		return [
			{
				key: 'ordini',
				value: String(o.total ?? 0),
				label: 'Ordini totali',
				meta: `${o.month ?? 0} questo mese`,
				url: '/account/amministrazione/ordini',
				iconKey: 'clipboard-list',
				iconBg: '#ECF8F8',
				iconColor: '#095866',
			},
			{
				key: 'ricavi',
				value: `EUR ${formatCents(revenue)}`,
				label: 'Ricavi',
				meta: `EUR ${formatCents(revenueMonth)} nel mese`,
				// -- ARCHIVIATO 2026-04-20: era /account/amministrazione/portafogli (admin-portafogli-duplicato) --
				url: '/account/amministrazione/ordini',
				iconKey: 'wallet',
				iconBg: '#FFF4EE',
				iconColor: '#E44203',
			},
			{
				key: 'utenti',
				value: String(u.total ?? 0),
				label: 'Utenti registrati',
				meta: `${u.pro ?? 0} account Pro`,
				url: '/account/amministrazione/utenti',
				iconKey: 'account-group',
				iconBg: '#F4F6F8',
				iconColor: '#52606D',
			},
			{
				key: 'spedizioni',
				value: String(s.in_transit ?? 0),
				label: 'In transito',
				meta: `${s.delivered ?? 0} consegnati`,
				url: '/account/amministrazione/spedizioni',
				iconKey: 'truck-delivery',
				iconBg: '#ECF8F8',
				iconColor: '#095866',
			},
		];
	});

	const getStatusTone = (status) => {
		switch (status) {
			case 'pending':
				return { bg: 'rgba(228, 66, 3, 0.08)', color: '#E44203' };
			case 'payment_failed':
			case 'cancelled':
				return { bg: 'rgba(220, 38, 38, 0.1)', color: '#B91C1C' };
			case 'completed':
			case 'payed':
			case 'delivered':
				return { bg: 'rgba(5, 150, 105, 0.1)', color: '#047857' };
			default:
				return { bg: 'rgba(9, 88, 102, 0.08)', color: '#095866' };
		}
	};

	const adminRecentItems = computed(() => {
		if (!isAdmin.value) return [];

		const recentOrders = adminDashboardData.value?.recent_orders || [];
		if (!recentOrders.length) {
			return [
				{
					id: 'empty-console',
					title: 'Nessuna attivita recente disponibile',
					meta: 'Appena arrivano nuovi ordini o variazioni di stato, il feed si popola qui.',
					statusLabel: 'Console',
					statusTone: { bg: 'rgba(9, 88, 102, 0.08)', color: '#095866' },
					url: '/account/amministrazione',
				},
			];
		}

		return recentOrders.slice(0, 6).map((order) => {
			const customerName = [order.user?.name, order.user?.surname].filter(Boolean).join(' ').trim() || 'Cliente';
			const statusLabel = (orderStatusConfig)[order.status]?.label || order.status || 'Aggiornato';
			const amountLabel = `EUR ${formatCents(order.subtotal)}`;
			const timeLabel = formatDate(order.created_at);

			return {
				id: order.id,
				title: `Ordine #${order.id} da ${customerName}`,
				meta: `${amountLabel} - ${timeLabel}`,
				statusLabel,
				statusTone: getStatusTone(order.status),
				url: '/account/amministrazione/ordini',
			};
		});
	});

	/* ---------------------- LOGOUT ---------------------- */
	const isLoggingOut = ref(false);

	const handleLogout = async () => {
		isLoggingOut.value = true;
		try {
			clearSnapshot();
			await logout();
			await navigateTo('/');
		} catch {
			await navigateTo('/');
		} finally {
			isLoggingOut.value = false;
		}
	};

	return {
		// role
		roleKey,
		effectiveRole,
		isAdmin,
		isPro,
		// client
		customerOrdersLoading,
		highlightedCustomerOrders,
		recentCompletedCustomerOrders,
		personalHighlights,
		// bonusPage -- ARCHIVIATO 2026-04-20
		// admin
		adminAlerts,
		adminKpis,
		adminRecentItems,
		adminDashboardError,
		// logout
		isLoggingOut,
		handleLogout,
	};
}

export default useAccountDashboard;
