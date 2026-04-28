<script setup>
/** AdminConsoleAnalytics.vue (orchestrator) */
import { computed, ref, onMounted } from 'vue';
import { useChartLogic } from '~/composables/useChartLogic';
import AdminChartOrders from '~/components/admin/AdminChartOrders.vue';
import AdminChartRevenue from '~/components/admin/AdminChartRevenue.vue';
import AdminChartStatus from '~/components/admin/AdminChartStatus.vue';
import '~/assets/css/admin.css';

const {
	toNumber,
	formatCurrencyShort: formatEurShort,
	formatPercentage: formatPercent,
	formatInteger,
	computeSegments,
} = useChartLogic();

const props = defineProps({
	days: {
		type: Array,
		default: () => [],
	},
	today: {
		type: Number,
		default: 0,
	},
	week: {
		type: Number,
		default: 0,
	},
	month: {
		type: Number,
		default: 0,
	},
	dailyRevenue: {
		type: Array,
		default: () => [],
	},
	revenueToday: {
		type: Number,
		default: 0,
	},
	revenueWeek: {
		type: Number,
		default: 0,
	},
	revenueMonth: {
		type: Number,
		default: 0,
	},
	statusDistribution: {
		type: Array,
		default: () => [],
	},
});

const mounted = ref(false);
const activeChart = ref('ordini');

const chartTabs = [
	{ id: 'ordini', label: 'Ordini' },
	{ id: 'ricavi', label: 'Ricavi' },
	{ id: 'stati', label: 'Stati' },
];

onMounted(() => {
	requestAnimationFrame(() => {
		mounted.value = true;
	});
});

/* ---- Aggregates per summary (peak ordini + top status share) ---- */
const peakValue = computed(() => {
	const arr = Array.isArray(props.days) ? props.days : [];
	return arr.reduce((peak, item) => Math.max(peak, toNumber(item?.count ?? item?.value ?? item?.orders)), 0);
});

// Palette per i dot della summary "stati": deve combaciare con i segmenti donut.
const statusDotColors = {
	consegnato: 'var(--admin-status-success)',
	consegnati: 'var(--admin-status-success)',
	delivered: 'var(--admin-status-success)',
	paid: 'var(--admin-status-success)',
	pagato: 'var(--admin-status-success)',
	completed: 'var(--admin-status-success)',
	completato: 'var(--admin-status-success)',
	in_transito: '#095866',
	in_transit: '#095866',
	in_lavorazione: '#095866',
	processing: '#095866',
	pending: '#095866',
	in_attesa: '#095866',
	confermato: '#095866',
	in_giacenza: 'var(--admin-status-warning)',
	reso: 'var(--admin-status-warning)',
	returned: 'var(--admin-status-warning)',
	rimborsato: 'var(--admin-status-warning)',
	refunded: 'var(--admin-status-warning)',
	cancellato: 'var(--admin-status-danger)',
	cancelled: 'var(--admin-status-danger)',
	annullato: 'var(--admin-status-danger)',
	rifiutato: 'var(--admin-status-danger)',
	refused: 'var(--admin-status-danger)',
	fallito: 'var(--admin-status-danger)',
	payment_failed: 'var(--admin-status-danger)',
};

const statusFallbackColors = ['#095866', 'var(--admin-status-success)', '#0b6e80', 'var(--admin-status-danger)'];

const topStatusShares = computed(() => {
	const segs = computeSegments(props.statusDistribution);
	return segs.slice(0, 3).map((item, index) => ({
		...item,
		color: statusDotColors[item.key] || statusFallbackColors[index % statusFallbackColors.length],
	}));
});

/* ---- Summary cards per tab attivo ---- */
const summaryCards = computed(() => {
	if (activeChart.value === 'ricavi') {
		return [
			{ key: 'rev-today', label: 'Oggi', value: formatEurShort(props.revenueToday) },
			{ key: 'rev-week', label: '7 giorni', value: formatEurShort(props.revenueWeek) },
			{ key: 'rev-month', label: '30 giorni', value: formatEurShort(props.revenueMonth) },
		];
	}
	if (activeChart.value === 'stati') {
		return topStatusShares.value.map((item) => ({
			key: `st-${item.key}`,
			label: item.label,
			value: formatInteger(item.count),
			dotColor: item.color,
			hint: formatPercent(item.share),
		}));
	}
	return [
		{ key: 'today', label: 'Oggi', value: formatInteger(props.today), hint: 'ordini' },
		{ key: 'week', label: '7 giorni', value: formatInteger(props.week), hint: 'ordini' },
		{ key: 'month', label: '30 giorni', value: formatInteger(props.month), hint: 'ordini' },
		{ key: 'peak', label: 'Picco', value: formatInteger(peakValue.value), hint: 'giorno più pieno' },
	];
});

const chartTitle = computed(() => {
	if (activeChart.value === 'ricavi') return 'Ricavi ultimi 30 giorni';
	if (activeChart.value === 'stati') return 'Distribuzione stati';
	return 'Andamento ultimi 30 giorni';
});

const chartDescription = computed(() => {
	if (activeChart.value === 'ricavi') {
		return 'Ricavi giornalieri espressi in euro per capire subito i giorni più forti.';
	}
	if (activeChart.value === 'stati') {
		return 'Quota delle code attive per leggere dove si concentra l’operatività.';
	}
	return 'Ordini giornalieri con andamento, picchi e continuità delle ultime quattro settimane.';
});
</script>

<template>
	<section
		class="admin-console-analytics"
		:class="{ 'admin-console-analytics--mounted': mounted }">

		<div class="admin-console-analytics__header">
			<div class="min-w-0">
				<p class="admin-console-analytics__eyebrow">Analisi</p>
				<h3 class="admin-console-analytics__title">{{ chartTitle }}</h3>
			</div>
			<div class="admin-analytics-tabs">
				<button
					v-for="tab in chartTabs"
					:key="tab.id"
					:class="['admin-analytics-tab', { 'admin-analytics-tab--active': activeChart === tab.id }]"
					@click="activeChart = tab.id">
					{{ tab.label }}
				</button>
			</div>
		</div>

		<p class="admin-console-analytics__description">
			{{ chartDescription }}
		</p>

		<!-- Summary cards rimosse (P14): duplicavano i 4 stat-card sopra (Oggi/7gg/30gg vs Ordini attivi/Ricavi mese).
		     Il grafico parla da solo. Risparmio 80px verticali. -->

		<Transition name="chart-fade" mode="out-in">
			<AdminChartOrders
				v-if="activeChart === 'ordini'"
				key="ordini"
				:orders-data="props.days"
				period="30d" />

			<AdminChartRevenue
				v-else-if="activeChart === 'ricavi'"
				key="ricavi"
				:revenue-data="props.dailyRevenue"
				period="30d" />

			<AdminChartStatus
				v-else-if="activeChart === 'stati'"
				key="stati"
				:status-data="props.statusDistribution"
				legend-position="right" />
		</Transition>
	</section>
</template>
