<script setup lang="ts">
/**
 * SfBadge — pill testuale per metadata, conteggi, stati semplici.
 *
 * Per stato ordini usare SfStatusPill (semantica ordine).
 */

type Tone = 'neutral' | 'primary' | 'accent' | 'success' | 'warning' | 'danger' | 'info';

interface Props {
	tone?: Tone;
	size?: 'xs' | 'sm' | 'md';
	/** Variante shape (default rounded-full). */
	variant?: 'pill' | 'square';
	/** Icona leading. */
	icon?: string;
}

const props = withDefaults(defineProps<Props>(), {
	tone: 'neutral',
	size: 'sm',
	variant: 'pill',
	icon: '',
});

const TONE_CLASS = {
	neutral: 'bg-brand-bg-alt text-brand-text-secondary border-brand-border',
	primary: 'bg-brand-primary/10 text-brand-primary border-brand-primary/20',
	accent: 'bg-brand-accent-surface text-brand-accent-dark border-brand-accent/20',
	success: 'bg-brand-success-bg text-brand-success-fg border-brand-success/20',
	warning: 'bg-amber-50 text-amber-700 border-amber-200',
	danger: 'bg-red-50 text-red-700 border-red-200',
	info: 'bg-brand-soft-bg text-brand-soft-text border-brand-soft-border',
};

const SIZE_CLASS = {
	xs: 'text-[10px] px-1.5 py-0.5 gap-1',
	sm: 'text-xs px-2 py-0.5 gap-1.5',
	md: 'text-sm px-2.5 py-1 gap-1.5',
};

const classes = computed(() => [
	'inline-flex items-center font-medium border',
	TONE_CLASS[props.tone],
	SIZE_CLASS[props.size],
	props.variant === 'pill' ? 'rounded-full' : 'rounded',
]);
</script>

<template>
	<span :class="classes">
		<UIcon v-if="icon" :name="icon" class="shrink-0" />
		<slot />
	</span>
</template>
