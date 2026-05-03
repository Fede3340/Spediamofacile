<script setup lang="ts">
/**
 * SfStatCard — metric card per dashboard (account, admin).
 *
 * Pattern:
 *   <SfStatCard label="Ordini" :value="42" trend="up" trend-label="+8% rispetto a mese scorso" icon="mdi:package-variant" />
 */

interface Props {
	label: string;
	value: string | number;
	trend?: 'up' | 'down' | 'neutral';
	trendLabel?: string;
	icon?: string;
	/** Tono colore icona di sfondo. */
	tone?: 'primary' | 'accent' | 'success' | 'warning' | 'danger';
	/** Loading state (skeleton). */
	loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
	trend: 'neutral',
	trendLabel: '',
	icon: '',
	tone: 'primary',
	loading: false,
});

const TONE_CLASS = {
	primary: 'bg-brand-primary/10 text-brand-primary',
	accent: 'bg-brand-accent-surface text-brand-accent-dark',
	success: 'bg-brand-success-bg text-brand-success-fg',
	warning: 'bg-amber-50 text-amber-700',
	danger: 'bg-red-50 text-red-700',
};

const TREND_CLASS = {
	up: 'text-brand-success-fg',
	down: 'text-brand-error',
	neutral: 'text-brand-text-muted',
};

const TREND_ICON = { up: 'mdi:trending-up', down: 'mdi:trending-down', neutral: 'mdi:minus' };

const iconClass = computed(() => TONE_CLASS[props.tone]);
</script>

<template>
	<div class="flex flex-col gap-3 p-5 bg-brand-card rounded-card border border-brand-border shadow-sf-sm transition hover:shadow-sf hover:-translate-y-0.5">
		<div class="flex items-start justify-between gap-3">
			<div class="text-xs font-semibold uppercase tracking-wide text-brand-text-muted">
				{{ label }}
			</div>
			<div v-if="icon" :class="['inline-flex h-9 w-9 items-center justify-center rounded-lg shrink-0', iconClass]">
				<UIcon :name="icon" class="h-5 w-5" />
			</div>
		</div>

		<div v-if="loading" class="h-8 w-24 bg-brand-bg-alt rounded animate-pulse" />
		<div v-else class="font-display text-2xl md:text-3xl font-bold text-brand-text leading-tight">
			{{ value }}
		</div>

		<div v-if="trendLabel" :class="['inline-flex items-center gap-1 text-xs', TREND_CLASS[trend]]">
			<UIcon :name="TREND_ICON[trend]" class="h-3.5 w-3.5" />
			{{ trendLabel }}
		</div>
	</div>
</template>
