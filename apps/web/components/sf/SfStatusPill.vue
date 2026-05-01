<script setup>
/**
 * SfStatusPill — badge stato unificato sitewide.
 *
 * Pill colorata con label + style derivato da useStatusBadge (mappa
 * status → palette color/bg). Sostituisce le varianti hex hardcoded sparse
 * in account/admin/orders. Pattern uso:
 *
 *   <SfStatusPill status="paid" label="Pagato" />
 *   <SfStatusPill status="In consegna" />          // label deriva da status
 *   <SfStatusPill status="pending" size="sm" />
 */

const props = defineProps({
	/** Stato raw (slug) o italiano (label). Es. "paid" / "In transito". */
	status: { type: String, required: true },
	/** Label override; se assente usa props.status come testo. */
	label: { type: String, default: '' },
	size: {
		type: String,
		default: 'md',
		validator: (v) => ['sm', 'md'].includes(v),
	},
});

const { getStyle } = useStatusBadge();

const pillStyle = computed(() => {
	const palette = getStyle(props.status);
	return {
		color: palette.color,
		background: palette.background,
	};
});

const displayLabel = computed(() => props.label || props.status || '');

const pillClass = computed(() => [
	'sf-status-pill',
	{ 'sf-status-pill--sm': props.size === 'sm' },
]);
</script>

<template>
	<span :class="pillClass" :style="pillStyle">{{ displayLabel }}</span>
</template>

<style scoped>
.sf-status-pill {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	gap: 4px;
	padding: 4px 10px;
	border-radius: var(--sf-radius-pill, 999px);
	font-size: 0.75rem;
	font-weight: 700;
	letter-spacing: 0.02em;
	line-height: 1.2;
	white-space: nowrap;
}
.sf-status-pill--sm {
	padding: 2px 8px;
	font-size: 0.6875rem;
}
</style>
