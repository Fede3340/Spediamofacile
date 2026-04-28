<!-- AdminStatusBadge.vue — Badge stato admin unificato. -->
<script setup>
import { computed } from 'vue';

const props = defineProps({
	status: { type: String, required: true },
	type: {
		type: String,
		default: 'generic',
		validator: (v) => ['order', 'role', 'generic'].includes(v),
	},
	label: { type: String, default: '' },
});

/* Mappa stato ordine → tone + label italiano */
const ORDER_STATUS_MAP = {
	pending: { tone: 'warning', label: 'In attesa' },
	processing: { tone: 'info', label: 'In lavorazione' },
	label_generated: { tone: 'info', label: 'Etichetta generata' },
	completed: { tone: 'success', label: 'Completato' },
	paid: { tone: 'success', label: 'Pagato' },
	payment_failed: { tone: 'danger', label: 'Pagamento fallito' },
	cancelled: { tone: 'neutral', label: 'Annullato' },
	refunded: { tone: 'warning', label: 'Rimborsato' },
	in_transit: { tone: 'info', label: 'In transito' },
	out_for_delivery: { tone: 'info', label: 'In consegna' },
	delivered: { tone: 'success', label: 'Consegnato' },
	in_giacenza: { tone: 'warning', label: 'In giacenza' },
	returned: { tone: 'warning', label: 'Reso' },
	refused: { tone: 'danger', label: 'Rifiutato' },
};

/* Mappa ruolo utente → tone + label italiano */
const ROLE_MAP = {
	Admin: { tone: 'warning', label: 'Admin' },
	admin: { tone: 'warning', label: 'Admin' },
	'Partner Pro': { tone: 'success', label: 'Partner Pro' },
	partner_pro: { tone: 'success', label: 'Partner Pro' },
	User: { tone: 'neutral', label: 'Cliente' },
	user: { tone: 'neutral', label: 'Cliente' },
};

const entry = computed(() => {
	if (props.type === 'order') return ORDER_STATUS_MAP[props.status] || { tone: 'neutral', label: props.status };
	if (props.type === 'role') return ROLE_MAP[props.status] || { tone: 'neutral', label: props.status };
	return { tone: 'neutral', label: props.status };
});

const tone = computed(() => entry.value.tone);
const label = computed(() => props.label || entry.value.label);
</script>

<template>
	<span :class="['admin-status-badge', `admin-status-badge--${tone}`]">
		{{ label }}
	</span>
</template>

<!-- Stili estratti in assets/css/admin.css (importato da main.css). -->
