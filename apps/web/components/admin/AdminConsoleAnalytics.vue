<script setup>

import AdminChartOrders from '~/components/admin/AdminChartOrders.vue';
import AdminChartRevenue from '~/components/admin/AdminChartRevenue.vue';
import AdminChartStatus from '~/components/admin/AdminChartStatus.vue';

const props = defineProps({
	days: { type: Array, default: () => [] },
	dailyRevenue: { type: Array, default: () => [] },
	statusDistribution: { type: Array, default: () => [] },
});

const mounted = ref(false);
const activeChart = ref('ordini');
onMounted(() => requestAnimationFrame(() => { mounted.value = true; }));

const chartTabs = [
	{ id: 'ordini', label: 'Ordini' },
	{ id: 'ricavi', label: 'Ricavi' },
	{ id: 'stati', label: 'Stati' },
];

const chartMeta = {
	ordini: { title: 'Andamento ultimi 30 giorni', desc: 'Ordini giornalieri con andamento, picchi e continuità delle ultime quattro settimane.' },
	ricavi: { title: 'Ricavi ultimi 30 giorni', desc: 'Ricavi giornalieri espressi in euro per capire subito i giorni più forti.' },
	stati: { title: 'Distribuzione stati', desc: 'Quota delle code attive per leggere dove si concentra l’operatività.' },
};
const chartTitle = computed(() => chartMeta[activeChart.value].title);
const chartDescription = computed(() => chartMeta[activeChart.value].desc);
</script>

<template>
	<section class="admin-console-analytics" :class="{ 'admin-console-analytics--mounted': mounted }">
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

		<p class="admin-console-analytics__description">{{ chartDescription }}</p>

		<Transition name="chart-fade" mode="out-in">
			<AdminChartOrders v-if="activeChart === 'ordini'" key="ordini" :orders-data="props.days" period="30d" />
			<AdminChartRevenue v-else-if="activeChart === 'ricavi'" key="ricavi" :revenue-data="props.dailyRevenue" period="30d" />
			<AdminChartStatus v-else-if="activeChart === 'stati'" key="stati" :status-data="props.statusDistribution" legend-position="right" />
		</Transition>
	</section>
</template>

<!-- Stili non-scoped: classi namespaced condivise dai 3 children chart. -->
<style>
.admin-console-analytics {
	display: grid;
	gap: 14px;
	padding: 18px;
	border-radius: 20px;
	border: 1px solid rgba(9, 88, 102, 0.08);
	background: linear-gradient(180deg, rgba(255, 255, 255, 0.995) 0%, rgba(249, 251, 252, 0.995) 100%);
	box-shadow: 0 18px 40px rgba(12, 27, 34, 0.06);
}

.admin-console-analytics__header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
	flex-wrap: wrap;
}

.admin-console-analytics__eyebrow {
	margin: 0 0 5px;
	font-size: 0.72rem;
	font-weight: 800;
	letter-spacing: 0.14em;
	text-transform: uppercase;
	color: var(--color-brand-text-muted);
}

.admin-console-analytics__title {
	font-size: 1.125rem;
	font-weight: 700;
	letter-spacing: -0.02em;
	color: var(--color-brand-primary);
	margin: 0;
}

.admin-console-analytics__description {
	margin: 0;
	font-size: 0.9rem;
	line-height: 1.5;
	color: var(--color-brand-text-secondary);
	max-width: 68ch;
}

.admin-analytics-tabs {
	display: flex;
	gap: 4px;
	padding: 3px;
	border-radius: 999px;
	background: rgba(9, 88, 102, 0.06);
}

.admin-analytics-tab {
	padding: 6px 14px;
	border-radius: 999px;
	font-size: 0.75rem;
	font-weight: 600;
	color: var(--color-brand-text-muted);
	cursor: pointer;
	transition: color 180ms ease, background-color 180ms ease, border-color 180ms ease, box-shadow 180ms ease, transform 180ms ease;
	border: none;
	background: transparent;
}

.admin-analytics-tab:hover:not(.admin-analytics-tab--active) {
	color: var(--color-brand-primary);
	background: rgba(9, 88, 102, 0.06);
}

.admin-analytics-tab--active {
	background: var(--color-brand-primary);
	color: white;
	font-weight: 700;
}

.admin-console-analytics__chart-area {
	position: relative;
	width: 100%;
	min-height: 120px;
	padding: 10px;
	border-radius: 12px;
	border: 1px solid rgba(9, 88, 102, 0.08);
	background: var(--color-brand-card);
}

.admin-console-analytics__svg {
	display: block;
	width: 100%;
	height: auto;
	min-height: 100px;
	max-height: 160px;
}

.admin-console-analytics__grid-line { stroke: var(--color-border-soft, #E6E9EE); stroke-width: 0.6; }
.admin-console-analytics__area { opacity: 0; animation: admin-analytics-area-fade 400ms ease 600ms forwards; }

.admin-console-analytics__line {
	fill: none;
	stroke: var(--color-brand-primary);
	stroke-width: 2.5;
	stroke-linecap: round;
	stroke-linejoin: round;
	stroke-dasharray: var(--path-length);
	stroke-dashoffset: var(--path-length);
	animation: admin-analytics-line-draw 800ms cubic-bezier(0.22, 1, 0.36, 1) 120ms forwards;
}

.admin-console-analytics__guide-line { stroke: rgba(9, 88, 102, 0.14); stroke-width: 0.5; stroke-dasharray: 2 3; }
.admin-console-analytics__hit-area { fill: transparent; cursor: pointer; }
.admin-console-analytics__active-dot { fill: var(--color-brand-primary); stroke: var(--color-brand-card); stroke-width: 2; }
.admin-console-analytics__active-ring { fill: none; stroke: rgba(9, 88, 102, 0.16); stroke-width: 1; }
.admin-console-analytics__axis-label { font-size: 5.25px; font-weight: 600; fill: #999; }

.admin-console-analytics__tooltip {
	position: absolute;
	z-index: 2;
	display: inline-flex;
	align-items: center;
	gap: 5px;
	padding: 5px 10px;
	border-radius: 8px;
	background: var(--color-brand-primary);
	color: var(--color-brand-card);
	font-size: 11px;
	line-height: 1;
	white-space: nowrap;
	transform: translate(-50%, -100%);
	pointer-events: none;
	animation: admin-analytics-tooltip-rise 180ms ease-out both;
	box-shadow: 0 4px 12px rgba(9, 88, 102, 0.25);
}

.admin-console-analytics__tooltip-label { font-weight: 500; opacity: 0.85; }
.admin-console-analytics__tooltip-sep { opacity: 0.5; }
.admin-console-analytics__tooltip-value { font-weight: 700; }

.admin-console-analytics__bar {
	fill: var(--color-brand-primary);
	opacity: 0.7;
	cursor: pointer;
	transition: opacity 180ms ease, fill 180ms ease;
}

.admin-console-analytics__bar:hover,
.admin-console-analytics__bar--active { opacity: 1; fill: var(--color-brand-primary); }

.admin-console-analytics__donut-area {
	display: flex;
	align-items: center;
	gap: 24px;
	min-height: 192px;
	padding: 16px;
	border-radius: 18px;
	border: 1px solid rgba(9, 88, 102, 0.08);
	background: linear-gradient(180deg, rgba(248, 251, 252, 0.98) 0%, rgba(255, 255, 255, 0.98) 100%);
	box-shadow: 0 8px 18px rgba(20, 37, 48, 0.03);
}

.admin-console-analytics__donut-area--legend-bottom { flex-direction: column; align-items: flex-start; }
.admin-console-analytics__donut-svg { width: 148px; height: 148px; flex-shrink: 0; }
.admin-console-analytics__donut-segment { transition: opacity 180ms ease; }
.admin-console-analytics__donut-total-value { font-size: 14px; font-weight: 800; fill: var(--color-brand-primary); letter-spacing: -0.04em; }
.admin-console-analytics__donut-total-label { font-size: 5.5px; font-weight: 600; fill: #999; text-transform: uppercase; letter-spacing: 0.5px; }
.admin-console-analytics__donut-legend { display: grid; gap: 8px; flex: 1; }

.admin-console-analytics__legend-item {
	display: grid;
	grid-template-columns: auto minmax(0, 1fr) auto auto;
	align-items: center;
	gap: 8px;
	padding: 10px 12px;
	border-radius: 14px;
	border: 1px solid rgba(9, 88, 102, 0.08);
	background: rgba(255, 255, 255, 0.88);
}

.admin-console-analytics__legend-swatch { width: 10px; height: 10px; border-radius: 999px; flex-shrink: 0; }
.admin-console-analytics__legend-label { font-size: 0.86rem; font-weight: 700; color: var(--color-brand-text); flex: 1; }
.admin-console-analytics__legend-count { font-size: 0.9rem; font-weight: 700; color: var(--color-brand-text); font-variant-numeric: tabular-nums; }
.admin-console-analytics__legend-share { font-size: 0.78rem; font-weight: 700; color: var(--color-brand-text-secondary); font-variant-numeric: tabular-nums; }

.admin-console-analytics__empty {
	display: flex;
	align-items: center;
	gap: 10px;
	padding: 16px 18px;
	border-radius: 18px;
	border: 1px dashed rgba(9, 88, 102, 0.16);
	background: rgba(248, 252, 252, 0.9);
	color: var(--color-brand-text-secondary);
}

.admin-console-analytics__empty-dot { width: 10px; height: 10px; border-radius: 999px; background: rgba(9, 88, 102, 0.2); }
.admin-console-analytics__empty p { margin: 0; font-size: 0.9rem; line-height: 1.45; }

@keyframes admin-analytics-line-draw { to { stroke-dashoffset: 0; } }
@keyframes admin-analytics-area-fade { from { opacity: 0; } to { opacity: 1; } }
@keyframes admin-analytics-tooltip-rise {
	from { opacity: 0; transform: translate(-50%, calc(-100% + 4px)); }
	to { opacity: 1; transform: translate(-50%, -100%); }
}

@media (min-width: 768px) {
	.admin-console-analytics { padding: 20px; }
	.admin-console-analytics__chart-area { min-height: 226px; padding: 18px; }
	.admin-console-analytics__svg { min-height: 190px; }
	.admin-console-analytics__donut-area { padding: 18px; }
}

@media (max-width: 767px) {
	.admin-console-analytics { padding: 16px; }
	.admin-console-analytics__header { align-items: flex-start; }
	.admin-analytics-tabs { width: 100%; justify-content: space-between; }
	.admin-analytics-tab { flex: 1 1 0; text-align: center; padding-inline: 10px; }
	.admin-console-analytics__chart-area { min-height: 180px; padding: 12px; }
	.admin-console-analytics__svg { min-height: 166px; }
	.admin-console-analytics__donut-area { flex-direction: column; align-items: flex-start; gap: 16px; padding: 12px; }
	.admin-console-analytics__donut-svg { width: 124px; height: 124px; align-self: center; }
	.admin-console-analytics__legend-item { grid-template-columns: auto minmax(0, 1fr) auto; }
	.admin-console-analytics__legend-share { justify-self: end; }
	.admin-console-analytics__donut-legend { width: 100%; }
}

.chart-fade-enter-active, .chart-fade-leave-active { transition: opacity 200ms ease; }
.chart-fade-enter-from, .chart-fade-leave-to { opacity: 0; }
</style>
