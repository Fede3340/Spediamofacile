<script setup lang="ts">
/**
 * SfStatusPill — badge stato ordine semantico (pending/paid/shipped/delivered/...).
 *
 * Mapping centralizzato: lo stato passato come prop diventa colore + icona + label
 * tradotta. Usare ovunque appaia lo stato di un Order/Payment/Shipment.
 */

type OrderStatus =
	| 'pending'
	| 'paid'
	| 'failed'
	| 'refunded'
	| 'cancelled'
	| 'shipped'
	| 'delivered'
	| 'in_transit'
	| 'returned'
	| 'processing';

interface Props {
	status: OrderStatus | string;
	/** Override label (altrimenti deriva dallo status). */
	label?: string;
	size?: 'xs' | 'sm' | 'md';
}

const props = withDefaults(defineProps<Props>(), {
	label: '',
	size: 'sm',
});

const STATUS_MAP: Record<string, { tone: string; icon: string; label: string }> = {
	pending: { tone: 'bg-status-pending-bg text-status-pending-fg', icon: 'mdi:clock-outline', label: 'In attesa' },
	processing: { tone: 'bg-status-pending-bg text-status-pending-fg', icon: 'mdi:progress-clock', label: 'In elaborazione' },
	paid: { tone: 'bg-status-paid-bg text-status-paid-fg', icon: 'mdi:check-circle', label: 'Pagato' },
	failed: { tone: 'bg-status-failed-bg text-status-failed-fg', icon: 'mdi:alert-circle', label: 'Fallito' },
	refunded: { tone: 'bg-status-refunded-bg text-status-refunded-fg', icon: 'mdi:cash-refund', label: 'Rimborsato' },
	cancelled: { tone: 'bg-status-neutral-bg text-status-neutral-fg', icon: 'mdi:close-circle', label: 'Annullato' },
	shipped: { tone: 'bg-status-info-bg text-status-info-fg', icon: 'mdi:truck-fast', label: 'Spedito' },
	in_transit: { tone: 'bg-status-info-bg text-status-info-fg', icon: 'mdi:truck-delivery', label: 'In transito' },
	delivered: { tone: 'bg-status-paid-bg text-status-paid-fg', icon: 'mdi:package-variant-closed-check', label: 'Consegnato' },
	returned: { tone: 'bg-status-refunded-bg text-status-refunded-fg', icon: 'mdi:undo-variant', label: 'Reso' },
};

const SIZE_CLASS = {
	xs: 'text-[10px] px-1.5 py-0.5 gap-1',
	sm: 'text-xs px-2.5 py-1 gap-1.5',
	md: 'text-sm px-3 py-1.5 gap-2',
};

const ICON_SIZE = { xs: 'h-3 w-3', sm: 'h-3.5 w-3.5', md: 'h-4 w-4' };

const config = computed(() => STATUS_MAP[props.status] || {
	tone: 'bg-status-neutral-bg text-status-neutral-fg',
	icon: 'mdi:help-circle-outline',
	label: props.status,
});

const displayLabel = computed(() => props.label || config.value.label);
</script>

<template>
	<span
		:class="[
			'inline-flex items-center font-semibold rounded-full',
			config.tone,
			SIZE_CLASS[size],
		]"
	>
		<UIcon :name="config.icon" :class="ICON_SIZE[size]" aria-hidden="true" />
		{{ displayLabel }}
	</span>
</template>
