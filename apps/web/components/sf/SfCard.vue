<script setup>
/**
 * SfCard — primitive card unificata sitewide.
 * Sostituisce 5+ varianti (.sf-card, .sf-shell-card, .sf-account-panel, inline rounded-[16px]).
 *
 * Pattern uso:
 *   <SfCard>contenuto</SfCard>                   // default
 *   <SfCard variant="panel">grosso panel</SfCard>
 *   <SfCard variant="metric">KPI dashboard</SfCard>
 *   <SfCard variant="empty"><p>Nessun dato</p></SfCard>
 *   <SfCard padding="lg">extra padding</SfCard>
 *
 * Tokens design (var --color-brand-*, --radius-*, --shadow-*) da assets/css/main.css.
 */
const props = defineProps({
	variant: {
		type: String,
		default: 'default',
		validator: (v) => ['default', 'panel', 'metric', 'empty'].includes(v),
	},
	padding: {
		type: String,
		default: 'md',
		validator: (v) => ['none', 'sm', 'md', 'lg'].includes(v),
	},
	/** Se true: rende come <article> per lista semantica */
	tag: { type: String, default: 'div' },
});

const VARIANT_CLASS = {
	default: 'sf-card-default',
	panel: 'sf-card-panel',
	metric: 'sf-card-metric',
	empty: 'sf-card-empty',
};

const PADDING_CLASS = {
	none: 'sf-card-pad-none',
	sm: 'sf-card-pad-sm',
	md: 'sf-card-pad-md',
	lg: 'sf-card-pad-lg',
};

const cardClasses = computed(() => [VARIANT_CLASS[props.variant], PADDING_CLASS[props.padding]].join(' '));
</script>

<template>
	<component :is="tag" :class="cardClasses">
		<slot />
	</component>
</template>

<style scoped>
/* Token unificati: tutte le card seguono stessa grammatica visiva. */

.sf-card-default {
	background: #fff;
	border: 1px solid rgba(9, 88, 102, 0.12);
	border-radius: 16px;
	box-shadow: 0 2px 8px rgba(9, 88, 102, 0.04);
}

.sf-card-panel {
	background: linear-gradient(180deg, rgba(9, 88, 102, 0.04) 0%, transparent 100%);
	border: 1px solid rgba(9, 88, 102, 0.16);
	border-radius: 20px;
	box-shadow: 0 4px 16px rgba(9, 88, 102, 0.06);
}

.sf-card-metric {
	background: #fff;
	border: 1px solid rgba(9, 88, 102, 0.10);
	border-radius: 12px;
	box-shadow: 0 1px 3px rgba(9, 88, 102, 0.04);
}

.sf-card-empty {
	background: var(--color-brand-bg-soft, #f5f6f9);
	border: 1px dashed rgba(9, 88, 102, 0.18);
	border-radius: 16px;
	text-align: center;
}

.sf-card-pad-none { padding: 0; }
.sf-card-pad-sm { padding: 12px; }
.sf-card-pad-md { padding: 20px; }
.sf-card-pad-lg { padding: 28px; }
</style>
