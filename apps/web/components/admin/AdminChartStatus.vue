<script setup>
/** AdminChartStatus.vue */
import { computed } from 'vue';
import { useChartLogic } from '~/composables/useChartLogic';

const props = defineProps({
	statusData: {
		type: Array,
		default: () => [],
	},
	// Riservato per futuri layout alternativi (default: legenda a destra).
	legendPosition: {
		type: String,
		default: 'right',
		validator: (v) => ['right', 'bottom'].includes(v),
	},
});

const { toNumber, formatInteger, formatPercentage: formatPercent, computeSegments } = useChartLogic();

/**
 * Palette status mappata sui token admin-theme.css.
 * - success (consegnato/pagato/completato) -> teal primary
 * - info (in transito/in lavorazione/pending) -> teal variant
 * - in_consegna (out_for_delivery) -> teal scuro
 * - warning (in_giacenza/reso/rimborsato) -> arancione brand
 * - danger (cancellato/rifiutato/fallito) -> rosso
 * Nessun blu. Rispetta feedback_no_blue_ever.
 */
const statusColors = {
	consegnato: 'var(--admin-status-success)',
	consegnati: 'var(--admin-status-success)',
	delivered: 'var(--admin-status-success)',
	paid: 'var(--admin-status-success)',
	pagato: 'var(--admin-status-success)',
	completed: 'var(--admin-status-success)',
	completato: 'var(--admin-status-success)',
	in_transito: '#095866',
	in_transit: '#095866',
	in_transito_ritardo: '#095866',
	in_lavorazione: '#095866',
	processing: '#095866',
	label_generated: '#095866',
	pending: '#095866',
	in_attesa: '#095866',
	confermato: '#095866',
	out_for_delivery: '#074a56',
	in_consegna: '#074a56',
	in_giacenza: 'var(--admin-status-warning)',
	returned: 'var(--admin-status-warning)',
	reso: 'var(--admin-status-warning)',
	refunded: 'var(--admin-status-warning)',
	rimborsato: 'var(--admin-status-warning)',
	cancellato: 'var(--admin-status-danger)',
	cancelled: 'var(--admin-status-danger)',
	annullato: 'var(--admin-status-danger)',
	rifiutato: 'var(--admin-status-danger)',
	refused: 'var(--admin-status-danger)',
	payment_failed: 'var(--admin-status-danger)',
	fallito: 'var(--admin-status-danger)',
};

const statusLabels = {
	consegnato: 'Consegnati',
	consegnati: 'Consegnati',
	delivered: 'Consegnati',
	in_transito: 'In transito',
	in_transit: 'In transito',
	in_lavorazione: 'In lavorazione',
	processing: 'In lavorazione',
	label_generated: 'Etichetta generata',
	out_for_delivery: 'In consegna',
	in_consegna: 'In consegna',
	pending: 'In attesa',
	in_attesa: 'In attesa',
	paid: 'Pagati',
	pagato: 'Pagati',
	confermato: 'Confermati',
	completed: 'Completati',
	completato: 'Completati',
	in_giacenza: 'In giacenza',
	cancellato: 'Annullati',
	cancelled: 'Annullati',
	annullato: 'Annullati',
	rifiutato: 'Rifiutati',
	refused: 'Rifiutati',
	returned: 'Resi',
	reso: 'Resi',
	refunded: 'Rimborsati',
	rimborsato: 'Rimborsati',
	payment_failed: 'Pagamento fallito',
	fallito: 'Pagamento fallito',
};

const fallbackColors = [
	'#095866',
	'var(--admin-status-success)',
	'#0b6e80',
	'var(--admin-status-danger)',
	'var(--admin-status-neutral)',
	'var(--admin-status-warning)',
];

/* ---- Data pipeline: riusa computeSegments per shares + arricchisce con label/color ---- */
const normalizedStatuses = computed(() => {
	const base = computeSegments(props.statusData);
	const raw = Array.isArray(props.statusData) ? props.statusData : [];
	return base.map((item, index) => ({
		...item,
		label: statusLabels[item.key] || raw[index]?.label || raw[index]?.status || item.label,
		color: statusColors[item.key] || fallbackColors[index % fallbackColors.length],
	}));
});

const statusTotal = computed(() =>
	normalizedStatuses.value.reduce((sum, item) => sum + toNumber(item.count), 0),
);

/* ---- Donut geometry: arco sui 40px radius, stroke 12px ---- */
const donutSegments = computed(() => {
	if (!normalizedStatuses.value.length || statusTotal.value === 0) return [];
	const radius = 40;
	const circumference = 2 * Math.PI * radius;
	let offset = 0;

	return normalizedStatuses.value.map((item) => {
		const fraction = item.share;
		const dashLength = fraction * circumference;
		const dashGap = circumference - dashLength;
		const segment = {
			...item,
			fraction,
			dashArray: `${dashLength} ${dashGap}`,
			dashOffset: -offset,
			radius,
		};
		offset += dashLength;
		return segment;
	});
});

const hasStatusData = computed(() => normalizedStatuses.value.length > 0 && statusTotal.value > 0);
</script>

<template>
	<div
		v-if="hasStatusData"
		class="admin-console-analytics__donut-area"
		:class="{ 'admin-console-analytics__donut-area--legend-bottom': legendPosition === 'bottom' }">
		<svg
			viewBox="0 0 120 120"
			class="admin-console-analytics__donut-svg"
			role="img"
			aria-label="Distribuzione stati spedizioni">
			<title>Distribuzione stati spedizioni</title>

			<circle
				cx="60"
				cy="60"
				r="40"
				fill="none"
				stroke="#E6E9EE"
				stroke-width="12" />

			<circle
				v-for="segment in donutSegments"
				:key="`donut-${segment.key}`"
				cx="60"
				cy="60"
				:r="segment.radius"
				fill="none"
				:stroke="segment.color"
				stroke-width="12"
				:stroke-dasharray="segment.dashArray"
				:stroke-dashoffset="segment.dashOffset"
				stroke-linecap="butt"
				transform="rotate(-90 60 60)"
				class="admin-console-analytics__donut-segment" />

			<text
				x="60"
				y="56"
				text-anchor="middle"
				class="admin-console-analytics__donut-total-value">
				{{ formatInteger(statusTotal) }}
			</text>
			<text
				x="60"
				y="68"
				text-anchor="middle"
				class="admin-console-analytics__donut-total-label">
				totali
			</text>
		</svg>

		<div class="admin-console-analytics__donut-legend">
			<div
				v-for="item in normalizedStatuses"
				:key="`legend-${item.key}`"
				class="admin-console-analytics__legend-item">
				<span
					class="admin-console-analytics__legend-swatch"
					:style="{ background: item.color }"></span>
				<span class="admin-console-analytics__legend-label">{{ item.label }}</span>
				<strong class="admin-console-analytics__legend-count">{{ formatInteger(item.count) }}</strong>
				<span class="admin-console-analytics__legend-share">{{ formatPercent(item.share) }}</span>
			</div>
		</div>
	</div>

	<div v-else class="admin-console-analytics__empty">
		<div class="admin-console-analytics__empty-dot"></div>
		<p>Nessun dato stati disponibile.</p>
	</div>
</template>
