<script setup>
/** AdminChartOrders.vue */
import { computed, ref } from 'vue';
import { useChartLogic } from '~/composables/useChartLogic';

const props = defineProps({
	ordersData: {
		type: Array,
		default: () => [],
	},
	// Riservato per future finestre temporali dinamiche (default 30d).
	period: {
		type: String,
		default: '30d',
		validator: (v) => ['7d', '30d', '90d'].includes(v),
	},
});

const { toNumber, formatInteger, formatDateShort: shortDate, formatDate: fullDate } = useChartLogic();

const hoveredIndex = ref(-1);

const chartGeometry = {
	width: 400,
	height: 200,
	top: 16,
	right: 20,
	bottom: 28,
	left: 16,
};

/* ---- Data pipeline ---- */
const normalizedDays = computed(() =>
	(Array.isArray(props.ordersData) ? props.ordersData : [])
		.slice(-30)
		.map((item, index) => ({
			key: item?.date || `day-${index}`,
			label: shortDate(item?.date, index),
			fullLabel: fullDate(item?.date, index),
			value: toNumber(item?.count ?? item?.value ?? item?.orders),
			date: item?.date || null,
		})),
);

const peakValue = computed(() => Math.max(...normalizedDays.value.map((item) => item.value), 0));

const chartMax = computed(() => {
	if (!normalizedDays.value.length) return 4;
	const peak = Math.max(peakValue.value, 1);
	return Math.max(Math.ceil((peak * 1.15) / 2) * 2, 4);
});

/* ---- Geometry ---- */
const buildPoints = (series, maxValue) => {
	if (!series.length) return [];
	const safeMax = Math.max(maxValue, 1);
	const width = chartGeometry.width - chartGeometry.left - chartGeometry.right;
	const height = chartGeometry.height - chartGeometry.top - chartGeometry.bottom;

	return series.map((item, index) => ({
		...item,
		index,
		x: chartGeometry.left + (series.length === 1 ? width / 2 : (width / (series.length - 1)) * index),
		y: chartGeometry.top + height - ((item.value / safeMax) * height),
	}));
};

const buildCurve = (points) => {
	if (points.length < 2) return '';
	return points.slice(1).map((point, index) => {
		const previous = points[index];
		const midX = (previous.x + point.x) / 2;
		return `C ${midX} ${previous.y} ${midX} ${point.y} ${point.x} ${point.y}`;
	}).join(' ');
};

const buildLinePath = (points) => {
	if (!points.length) return '';
	if (points.length === 1) return `M ${points[0].x} ${points[0].y}`;
	return `M ${points[0].x} ${points[0].y} ${buildCurve(points)}`;
};

const buildAreaPath = (points) => {
	if (!points.length) return '';
	const baseline = chartGeometry.height - chartGeometry.bottom;
	return `${buildLinePath(points)} L ${points.at(-1).x} ${baseline} L ${points[0].x} ${baseline} Z`;
};

const primaryPoints = computed(() => buildPoints(normalizedDays.value, chartMax.value));
const linePath = computed(() => buildLinePath(primaryPoints.value));
const areaPath = computed(() => buildAreaPath(primaryPoints.value));

const linePathLength = computed(() => {
	if (!primaryPoints.value.length) return 0;
	let length = 0;
	for (let i = 1; i < primaryPoints.value.length; i++) {
		const dx = primaryPoints.value[i].x - primaryPoints.value[i - 1].x;
		const dy = primaryPoints.value[i].y - primaryPoints.value[i - 1].y;
		length += Math.sqrt(dx * dx + dy * dy);
	}
	return Math.ceil(length * 1.5);
});

const activeIndex = computed(() => {
	if (!primaryPoints.value.length) return -1;
	if (hoveredIndex.value >= 0) return hoveredIndex.value;
	return primaryPoints.value.length - 1;
});

const activePoint = computed(() => (
	activeIndex.value >= 0 ? primaryPoints.value[activeIndex.value] : null
));

const axisLabels = computed(() => {
	if (!primaryPoints.value.length) return [];
	const source = primaryPoints.value;
	const indexes = [
		0,
		Math.floor((source.length - 1) / 3),
		Math.floor(((source.length - 1) * 2) / 3),
		source.length - 1,
	];
	const uniqueIndexes = [...new Set(indexes)].filter((index) => index >= 0 && index < source.length);
	return uniqueIndexes.map((index) => ({
		x: source[index].x,
		label:
			index === 0
				? '30 giorni fa'
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

const tooltipStyle = computed(() => {
	if (!activePoint.value) return {};
	const left = Math.min(Math.max((activePoint.value.x / chartGeometry.width) * 100, 10), 90);
	const top = Math.max(activePoint.value.y - 10, 12);
	return {
		left: `${left}%`,
		top: `${(top / chartGeometry.height) * 100}%`,
	};
});

const hasData = computed(() => primaryPoints.value.length > 0);
</script>

<template>
	<div v-if="hasData" class="admin-console-analytics__chart-area" @mouseleave="hoveredIndex = -1">
		<div
			v-if="activePoint"
			class="admin-console-analytics__tooltip"
			:style="tooltipStyle">
			<span class="admin-console-analytics__tooltip-label">{{ activePoint.fullLabel }}</span>
			<span class="admin-console-analytics__tooltip-sep">&middot;</span>
			<strong class="admin-console-analytics__tooltip-value">{{ formatInteger(activePoint.value) }} ordini</strong>
		</div>

		<svg
			:viewBox="`0 0 ${chartGeometry.width} ${chartGeometry.height}`"
			class="admin-console-analytics__svg"
			preserveAspectRatio="none"
			role="img"
			aria-label="Grafico ordini ultimi 30 giorni">
			<title>Grafico ordini ultimi 30 giorni</title>
			<desc>Curva che mostra l'andamento degli ordini negli ultimi 30 giorni.</desc>

			<defs>
				<linearGradient id="admin-chart-area-fill" x1="0" y1="0" x2="0" y2="1">
					<stop offset="0%" stop-color="#095866" stop-opacity="0.25" />
					<stop offset="50%" stop-color="#095866" stop-opacity="0.10" />
					<stop offset="100%" stop-color="#095866" stop-opacity="0.0" />
				</linearGradient>
			</defs>

			<g v-for="line in gridLines" :key="`grid-${line.y}`">
				<line
					:x1="chartGeometry.left"
					:y1="line.y"
					:x2="chartGeometry.width - chartGeometry.right"
					:y2="line.y"
					class="admin-console-analytics__grid-line" />
			</g>

			<path :d="areaPath" class="admin-console-analytics__area" fill="url(#admin-chart-area-fill)" />

			<path
				:d="linePath"
				class="admin-console-analytics__line"
				:style="{ '--path-length': linePathLength }" />

			<g v-if="activePoint">
				<line
					:x1="activePoint.x"
					:y1="chartGeometry.top"
					:x2="activePoint.x"
					:y2="chartGeometry.height - chartGeometry.bottom"
					class="admin-console-analytics__guide-line" />
			</g>

			<g v-for="point in primaryPoints" :key="point.key">
				<circle
					:cx="point.x"
					:cy="point.y"
					r="4"
					class="admin-console-analytics__hit-area"
					@mouseenter="hoveredIndex = point.index" />
			</g>

			<g v-if="activePoint">
				<circle
					:cx="activePoint.x"
					:cy="activePoint.y"
					r="3"
					class="admin-console-analytics__active-dot" />
				<circle
					:cx="activePoint.x"
					:cy="activePoint.y"
					r="6"
					class="admin-console-analytics__active-ring" />
			</g>

			<text
				v-for="label in axisLabels"
				:key="`axis-${label.x}`"
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
		<p>Nessun dato ordini disponibile per il periodo selezionato.</p>
	</div>
</template>
