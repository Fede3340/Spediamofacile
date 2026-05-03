<script setup lang="ts">
/**
 * SfAddressChip — chip semantico per indirizzi (Origine, Destinazione, Predefinito).
 */

type Variant = 'origin' | 'destination' | 'default' | 'neutral';

interface Props {
	variant: Variant;
	/** Override label (altrimenti deriva da variant). */
	label?: string;
}

const props = withDefaults(defineProps<Props>(), { label: '' });

const VARIANT_CONFIG = {
	origin: { tone: 'bg-brand-primary/10 text-brand-primary border-brand-primary/20', icon: 'mdi:upload', label: 'Origine' },
	destination: { tone: 'bg-brand-accent-surface text-brand-accent-dark border-brand-accent/20', icon: 'mdi:download', label: 'Destinazione' },
	default: { tone: 'bg-brand-success-bg text-brand-success-fg border-brand-success/20', icon: 'mdi:star', label: 'Predefinito' },
	neutral: { tone: 'bg-brand-bg-alt text-brand-text-secondary border-brand-border', icon: 'mdi:map-marker', label: 'Indirizzo' },
};

const config = computed(() => VARIANT_CONFIG[props.variant]);
const displayLabel = computed(() => props.label || config.value.label);
</script>

<template>
	<span
		:class="[
			'inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide rounded-full border',
			config.tone,
		]"
	>
		<UIcon :name="config.icon" class="h-3 w-3" aria-hidden="true" />
		{{ displayLabel }}
	</span>
</template>
