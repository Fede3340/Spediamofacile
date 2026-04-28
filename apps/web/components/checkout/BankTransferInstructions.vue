<!--
  Componente: BankTransferInstructions
  Istruzioni per completare il pagamento tramite bonifico bancario (F05 audit BRT).
  Mostra IBAN, causale obbligatoria (ORD-{id}), importo e tempistiche di conferma.
  Usato in Success.vue dopo checkout con paymentMethod === 'bonifico'.
-->
<script setup>
import '~/assets/css/shipment-flow.css';

const props = defineProps({
	orderId: { type: [String, Number], required: true },
	amount: { type: String, default: '' }, // formattato es. "24,50 EUR"
	iban: { type: String, default: 'IT60 X054 2811 1010 0000 0123 456' },
	bankName: { type: String, default: 'SpediamoFacile SRL' },
	bic: { type: String, default: 'BPMOIT22XXX' },
});

const orderIds = computed(() => String(props.orderId || '').split(',').map((s) => s.trim()).filter(Boolean));
const hasMulti = computed(() => orderIds.value.length > 1);
const causale = computed(() => {
	if (hasMulti.value) return orderIds.value.map((id) => `ORD-${id}`).join(' + ');
	return `ORD-${props.orderId}`;
});

const copied = ref('');

const copy = async (value, key) => {
	try {
		await navigator.clipboard.writeText(value);
		copied.value = key;
		setTimeout(() => { if (copied.value === key) copied.value = ''; }, 1800);
	} catch (_) {
		// fallback — nessun-op, l'utente puo' selezionare manualmente
	}
};
</script>

<template>
	<div class="bank-transfer-instructions">
		<div class="bank-transfer-instructions__header">
			<div class="bank-transfer-instructions__icon" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M3 21h18"/><path d="M3 10h18"/><path d="M5 6l7-3 7 3"/><path d="M4 10v11"/><path d="M20 10v11"/><path d="M8 14v3"/><path d="M12 14v3"/><path d="M16 14v3"/>
				</svg>
			</div>
			<div>
				<h3 class="bank-transfer-instructions__title">Completa il pagamento con bonifico</h3>
				<p class="bank-transfer-instructions__subtitle">
					Effettua il bonifico con i dati qui sotto. Appena lo riceviamo (1-2 giorni lavorativi) la spedizione parte in automatico.
				</p>
			</div>
		</div>

		<div class="bank-transfer-instructions__grid">
			<div class="bti-field">
				<div class="bti-field__label">IBAN</div>
				<div class="bti-field__row">
					<span class="bti-field__value bti-field__value--mono">{{ iban }}</span>
					<button type="button" class="bti-copy" @click="copy(iban, 'iban')" :aria-label="`Copia IBAN`">
						{{ copied === 'iban' ? 'Copiato' : 'Copia' }}
					</button>
				</div>
			</div>

			<div class="bti-field bti-field--highlight">
				<div class="bti-field__label">Causale (obbligatoria)</div>
				<div class="bti-field__row">
					<span class="bti-field__value bti-field__value--mono">{{ causale }}</span>
					<button type="button" class="bti-copy bti-copy--primary" @click="copy(causale, 'causale')" :aria-label="`Copia causale`">
						{{ copied === 'causale' ? 'Copiato' : 'Copia' }}
					</button>
				</div>
				<p class="bti-field__hint">Senza questa causale il bonifico non può essere abbinato al tuo ordine.</p>
			</div>

			<div class="bti-field">
				<div class="bti-field__label">Intestatario</div>
				<div class="bti-field__value">{{ bankName }}</div>
			</div>

			<div class="bti-field">
				<div class="bti-field__label">BIC / SWIFT</div>
				<div class="bti-field__row">
					<span class="bti-field__value bti-field__value--mono">{{ bic }}</span>
					<button type="button" class="bti-copy" @click="copy(bic, 'bic')" :aria-label="`Copia BIC`">
						{{ copied === 'bic' ? 'Copiato' : 'Copia' }}
					</button>
				</div>
			</div>

			<div v-if="amount" class="bti-field">
				<div class="bti-field__label">Importo</div>
				<div class="bti-field__value bti-field__value--strong">{{ amount }}</div>
			</div>
		</div>

		<div class="bank-transfer-instructions__footer">
			<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
			<p>
				Il bonifico arriva in genere entro 1-2 giorni lavorativi. Ti mandiamo una email di conferma appena lo registriamo e la spedizione parte in automatico.
			</p>
		</div>
	</div>
</template>

