<!-- COMPONENTE: OrderStatusBadge -->
<script setup>
const props = defineProps({
	status: { type: String, required: true },
	size: { type: String, default: 'md', validator: (v) => ['sm', 'md', 'lg'].includes(v) },
	label: { type: String, default: '' }, // override etichetta
});

// Mappa stato -> stile + label italiano
const STATUS_MAP = {
	pending: { label: 'In attesa', tone: 'neutral' },
	awaiting_bank_transfer: { label: 'Attesa bonifico', tone: 'neutral' },
	payment_failed: { label: 'Pagamento fallito', tone: 'danger' },
	failed: { label: 'Fallito', tone: 'danger' },
	paid: { label: 'Pagato', tone: 'teal' },
	processing: { label: 'In lavorazione', tone: 'teal' },
	shipped: { label: 'Spedito', tone: 'tealAccent' },
	in_transit: { label: 'In transito', tone: 'tealAccent' },
	out_for_delivery: { label: 'In consegna', tone: 'tealAccent' },
	delivered: { label: 'Consegnato', tone: 'success' },
	completed: { label: 'Completato', tone: 'success' },
	canceled: { label: 'Annullato', tone: 'danger' },
	cancelled: { label: 'Annullato', tone: 'danger' },
	refunded: { label: 'Rimborsato', tone: 'orange' },
	in_giacenza: { label: 'In giacenza', tone: 'orange' },
	returned: { label: 'Reso', tone: 'orange' },
	refused: { label: 'Rifiutato', tone: 'danger' },
};

// Palette tone -> colori (zero blu, solo teal/arancione/neutri/verde/rosso)
const TONE_STYLES = {
	neutral: { background: '#FFFBEB', color: '#B45309', border: 'rgba(180,83,9,0.18)', dot: '#D97706' },
	teal: { background: '#EEF7F8', color: '#095866', border: 'rgba(9,88,102,0.22)', dot: '#095866' },
	tealAccent: {
		background: 'linear-gradient(135deg,#EEF7F8 0%,#FFE9DD 100%)',
		color: '#095866',
		border: 'rgba(228,66,3,0.28)',
		dot: '#E44203',
	},
	success: { background: '#ECFDF3', color: '#047857', border: 'rgba(4,120,87,0.22)', dot: '#10B981' },
	danger: { background: '#FEF2F2', color: '#B91C1C', border: 'rgba(185,28,28,0.22)', dot: '#DC2626' },
	orange: { background: '#FFF7ED', color: '#C2410C', border: 'rgba(228,66,3,0.28)', dot: '#E44203' },
};

const SIZE_STYLES = {
	sm: { padding: '3px 8px', fontSize: '0.6875rem', dot: '5px', gap: '5px', radius: '999px' },
	md: { padding: '5px 11px', fontSize: '0.75rem', dot: '7px', gap: '6px', radius: '999px' },
	lg: { padding: '8px 16px', fontSize: '0.875rem', dot: '9px', gap: '8px', radius: '999px' },
};

const meta = computed(() => STATUS_MAP[props.status] || { label: props.status || 'Stato sconosciuto', tone: 'neutral' });
const tone = computed(() => TONE_STYLES[meta.value.tone] || TONE_STYLES.neutral);
const sizing = computed(() => SIZE_STYLES[props.size] || SIZE_STYLES.md);
const text = computed(() => props.label || meta.value.label);

const badgeStyle = computed(() => ({
	background: tone.value.background,
	color: tone.value.color,
	border: `1px solid ${tone.value.border}`,
	padding: sizing.value.padding,
	fontSize: sizing.value.fontSize,
	gap: sizing.value.gap,
	borderRadius: sizing.value.radius,
}));

const dotStyle = computed(() => ({
	width: sizing.value.dot,
	height: sizing.value.dot,
	background: tone.value.dot,
	borderRadius: '999px',
	flex: '0 0 auto',
}));
</script>

<template>
	<span class="order-status-badge" :style="badgeStyle" role="status" :aria-label="`Stato ordine: ${text}`">
		<span class="order-status-badge__dot" :style="dotStyle" aria-hidden="true"></span>
		<span class="order-status-badge__label">{{ text }}</span>
	</span>
</template>

<style scoped>
.order-status-badge {
	display: inline-flex;
	align-items: center;
	font-weight: 600;
	letter-spacing: 0.01em;
	line-height: 1.1;
	white-space: nowrap;
	transition: transform var(--sf-t1) var(--sf-ease), box-shadow var(--sf-t1) var(--sf-ease);
}

.order-status-badge__label {
	display: inline-block;
}
</style>
