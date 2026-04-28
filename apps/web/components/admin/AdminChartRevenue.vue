<script setup>
/** AdminChartRevenue.vue */
import { computed, ref } from 'vue';
import { useChartLogic } from '~/composables/useChartLogic';

const props = defineProps({
	revenueData: {
		type: Array,
		default: () => [],
	},
	period: {
		type: String,
		default: '30d',
		validator: (v) => ['7d', '30d', '90d'].includes(v),
	},
});

const {
	toNumber,
	formatCurrency: formatEur,
	formatDateShort: shortDate,
	formatDate: fullDate,
} = useChartLogic();

const hoveredBarIndex = ref(-1);

const chartGeometry = {
	width: 400,
	height: 200,
	top: 16,
	right: 20,
	bottom: 28,
	left: 16,
};

/* ---- Data pipeline ---- */
const normalizedRevenue = computed(() =>
	(Array.isArray(props.revenueData) ? props.revenueData : [])
		.slice(-30)
		.map((item, index) => ({
			key: item?.date || `rev-${index}`,
			label: shortDate(item?.date, index),
			fullLabel: fullDate(item?.date, index),
			value: toNumber(item?.amount ?? item?.revenue ?? item?.value ?? 0),
			date: item?.date || null,
		})),
);

const revenueMax = computed(() => {
	if (!normalizedRevenue.value.length) return 400;
	const peak = Math.max(...normalizedRevenue.value.map((item) => item.value), 1);
	return Math.max(Math.ceil((peak * 1.15) / 100) * 100, 400);
});

/* ---- Geometry: barre larghezza adattiva, gap calcolato per fill plotArea ---- */
const revenueBars = computed(() => {
	if (!normalizedRevenue.value.length) return [];
	const data = normalizedRevenue.value;
	const safeMax = Math.max(revenueMax.value, 1);
	const plotWidth = chartGeometry.width - chartGeometry.left - chartGeometry.right;
	const plotHeight = chartGeometry.height - chartGeometry.top - chartGeometry.bottom;
	const barWidth = Math.min(10, Math.max(4, plotWidth / data.length - 2));
	const gap = (plotWidth - barWidth * data.length) / Math.max(data.length - 1, 1);
	const baseline = chartGeometry.top + plotHeight;

	return data.map((item, index) => {
		const barHeight = Math.max((item.value / safeMax) * plotHeight, 1);
		const x = chartGeometry.left + index * (barWidth + gap);
		return {
			...item,
			index,
			x,
			y: baseline - barHeight,
			width: barWidth,
			height: barHeight,
			baseline,
		};
	});
});

const revenueAxisLabels = computed(() => {
	if (!revenueBars.value.length) return [];
	const source = revenueBars.value;
	const indexes = [
		0,
		Math.floor((source.length - 1) / 3),
		Math.floor(((source.length - 1) * 2) / 3),
		source.length - 1,
	];
	const uniqueIndexes = [...new Set(indexes)].filter((index) => index >= 0 && index < source.length);
	return uniqueIndexes.map((index) => ({
		x: source[index].x + source[index].width / 2,
		label:
			index === 0
				? '30gg fa'
				: index === source.length - 1
					? 'Oggi'
					: source[index].label,
	}));
});

const gridLines = computed(() => {
	const steps = 4;
	const height = chartGeometry.height - chartGeometry.top - chartGeometry.bottom;
	return Array.from({ length: steps }, (_, index) => ({
		y: chartGeometry.top + ((height / (steps - 1)) * index),
	}));
});

const activeBar = computed(() => {
	if (hoveredBarIndex.value < 0 || !revenueBars.value.length) return null;
	return revenueBars.value[hoveredBarIndex.value] || null;
});

const barTooltipStyle = computed(() => {
	if (!activeBar.value) return {};
	const left = Math.min(Math.max(((activeBar.value.x + activeBar.value.width / 2) / chartGeometry.width) * 100, 10), 90);
	const top = Math.max(activeBar.value.y - 10, 12);
	return {
		left: `${left}%`,
		top: `${(top / chartGeometry.height) * 100}%`,
	};
});

const hasRevenueData = computed(() => revenueBars.value.length > 0);
</script>

<template>
	<div v-if="hasRevenueData" class="admin-console-analytics__chart-area" @mouseleave="hoveredBarIndex = -1">
		<div
			v-if="activeBar"
			class="admin-console-analytics__tooltip"
			:style="barTooltipStyle">
			<span class="admin-console-analytics__tooltip-label">{{ activeBar.fullLabel }}</span>
			<span class="admin-console-analytics__tooltip-sep">&middot;</span>
			<strong class="admin-console-analytics__tooltip-value">{{ formatEur(activeBar.value) }}</strong>
		</div>

		<svg
			:viewBox="`0 0 ${chartGeometry.width} ${chartGeometry.height}`"
			class="admin-console-analytics__svg"
			preserveAspectRatio="none"
			role="img"
			aria-label="Grafico ricavi ultimi 30 giorni">
			<title>Grafico ricavi ultimi 30 giorni</title>
			<desc>Barre verticali che mostrano i ricavi giornalieri negli ultimi 30 giorni.</desc>

			<g v-for="line in gridLines" :key="`rev-grid-${line.y}`">
				<line
					:x1="chartGeometry.left"
					:y1="line.y"
					:x2="chartGeometry.width - chartGeometry.right"
					:y2="line.y"
					class="admin-console-analytics__grid-line" />
			</g>

			<g v-for="bar in revenueBars" :key="bar.key">
				<rect
					:x="bar.x"
					:y="bar.y"
					:width="bar.width"
					:height="bar.height"
					rx="3"
					ry="3"
					:class="[
						'admin-console-analytics__bar',
						{ 'admin-console-analytics__bar--active': hoveredBarIndex === bar.index }
					]"
					@mouseenter="hoveredBarIndex = bar.index" />
			</g>

			<text
				v-for="label in revenueAxisLabels"
				:key="`rev-axis-${label.x}`"
				:x="label.x"
				:y="chartGeometry.height - 4"
				text-anchor="middle"
				class="admin-console-analytics__axis-label">
				{{ label.label }}
			</text>
		</svg>
	</div>

	<div v-else class="admin-console-analytics__empty">
		<div class="admin-console-analytics__empty-dot"></div>
		<p>Nessun dato ricavi disponibile per il periodo selezionato.</p>
	</div>
</template>
