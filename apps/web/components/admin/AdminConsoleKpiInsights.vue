<!--
  AdminConsoleKpiInsights.vue — Riga di KPI avanzati derivati dai dati dashboard.
  Mostra: AOV (Average Order Value), tasso completamento, distribuzione metodo pagamento,
  growth rate vs settimana scorsa. Visibile solo se ci sono dati sufficienti.
-->
<script setup>
const props = defineProps({
	revenueMonth: { type: Number, default: 0 },
	revenueWeek: { type: Number, default: 0 },
	monthOrders: { type: Number, default: 0 },
	weekOrders: { type: Number, default: 0 },
	statusDistribution: { type: Array, default: () => [] },
	dailyOrders: { type: Array, default: () => [] },
});

const formatCents = (n) => {
	const v = Number(n || 0) / 100;
	return v.toLocaleString('it-IT', { style: 'currency', currency: 'EUR', maximumFractionDigits: 2 });
};

const formatPercent = (v) => `${(Math.round((Number(v) || 0) * 10) / 10).toFixed(1).replace('.', ',')}%`;

const aovCents = computed(() => {
	if (!props.monthOrders || props.monthOrders === 0) return 0;
	return Math.round(Number(props.revenueMonth || 0) / props.monthOrders);
});

const completionRate = computed(() => {
	if (!props.statusDistribution.length) return 0;
	const total = props.statusDistribution.reduce((sum, item) => sum + (Number(item.count) || 0), 0);
	if (!total) return 0;
	const completed = props.statusDistribution
		.filter(item => ['delivered', 'completed'].includes(item.status))
		.reduce((sum, item) => sum + (Number(item.count) || 0), 0);
	return (completed / total) * 100;
});

const trendWeekVsLast = computed(() => {
	// Confronta ultima settimana vs settimana precedente (giorni 0-7 vs 7-14)
	if (!props.dailyOrders || props.dailyOrders.length < 14) return null;
	const last7 = props.dailyOrders.slice(-7).reduce((sum, d) => sum + (Number(d.count) || Number(d.value) || 0), 0);
	const prev7 = props.dailyOrders.slice(-14, -7).reduce((sum, d) => sum + (Number(d.count) || Number(d.value) || 0), 0);
	if (prev7 === 0) return last7 > 0 ? 100 : 0;
	return ((last7 - prev7) / prev7) * 100;
});

const trendIcon = computed(() => {
	if (trendWeekVsLast.value == null) return 'mdi:trending-neutral';
	if (trendWeekVsLast.value > 5) return 'mdi:trending-up';
	if (trendWeekVsLast.value < -5) return 'mdi:trending-down';
	return 'mdi:trending-neutral';
});

const trendTone = computed(() => {
	if (trendWeekVsLast.value == null) return 'text-brand-text-muted';
	if (trendWeekVsLast.value > 5) return 'text-brand-success-fg';
	if (trendWeekVsLast.value < -5) return 'text-brand-error';
	return 'text-brand-text-muted';
});

const conversionEstimate = computed(() => {
	// Stima: ordini completati / totale ordini (proxy di conversione checkout → consegnato)
	if (!props.statusDistribution.length) return 0;
	const total = props.statusDistribution.reduce((sum, item) => sum + (Number(item.count) || 0), 0);
	if (!total) return 0;
	const successful = props.statusDistribution
		.filter(item => !['cancelled', 'payment_failed', 'refunded'].includes(item.status))
		.reduce((sum, item) => sum + (Number(item.count) || 0), 0);
	return (successful / total) * 100;
});

const cards = computed(() => [
	{
		key: 'aov',
		label: 'Valore medio ordine',
		value: formatCents(aovCents.value),
		hint: 'AOV mese corrente',
		icon: 'mdi:cash-multiple',
		tone: 'text-brand-primary',
		bg: 'bg-brand-primary/10',
	},
	{
		key: 'completion',
		label: 'Tasso completamento',
		value: formatPercent(completionRate.value),
		hint: 'Ordini consegnati su totale',
		icon: 'mdi:package-variant-closed-check',
		tone: 'text-brand-success-fg',
		bg: 'bg-brand-success-bg',
	},
	{
		key: 'conversion',
		label: 'Tasso buon esito',
		value: formatPercent(conversionEstimate.value),
		hint: 'Esclusi annullati/falliti/rimborsati',
		icon: 'mdi:check-decagram',
		tone: 'text-brand-accent',
		bg: 'bg-brand-accent/10',
	},
	{
		key: 'trend',
		label: 'Trend settimana',
		value: trendWeekVsLast.value == null ? '—' : formatPercent(trendWeekVsLast.value),
		hint: 'Ultimi 7 giorni vs precedenti 7',
		icon: trendIcon.value,
		tone: trendTone.value,
		bg: 'bg-brand-bg-alt',
	},
]);
</script>

<template>
	<section class="grid grid-cols-2 desktop:grid-cols-4 gap-3" aria-label="KPI di approfondimento">
		<div
			v-for="card in cards"
			:key="card.key"
			class="rounded-card border border-brand-border bg-brand-card p-4 shadow-sf-sm">
			<div class="flex items-start gap-3">
				<div :class="['inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-control', card.bg]">
					<UIcon :name="card.icon" :class="['h-5 w-5', card.tone]" />
				</div>
				<div class="min-w-0">
					<p class="text-[0.6875rem] font-bold uppercase tracking-wider text-brand-text-muted">{{ card.label }}</p>
					<p class="mt-1 font-display text-xl font-extrabold text-brand-text leading-tight tabular-nums">{{ card.value }}</p>
					<p class="mt-1 text-xs text-brand-text-secondary">{{ card.hint }}</p>
				</div>
			</div>
		</div>
	</section>
</template>
