<!--
  Billing / fiscal document section: ricevuta vs fattura (azienda / privato).
-->
<script setup>
const props = defineProps({
	fatturazioneType: { type: String, required: true },
	invoiceSubjectType: { type: String, required: true },
	fatturaData: { type: Object, required: true },
	billingShippingFullAddress: { type: String, default: '' },
	destinationAddress: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['update:fatturazioneType', 'update:invoiceSubjectType', 'update:fatturaData']);

// Toggle "stesso indirizzo della spedizione": copia da destinationAddress e disabilita gli input.
const useShippingAsBilling = ref(false);
const previousBillingSnapshot = ref(null);

const buildAddressLine = (a) => (a ? [a.address, a.address_number].filter(Boolean).join(' ').trim() : '');
const fatturaField = (field) => props.fatturaData?.[field] || '';
const updateFatturaData = (patch) => emit('update:fatturaData', { ...props.fatturaData, ...patch });
const setFatturaField = (field, value) => updateFatturaData({ [field]: value });

const addressFromShipping = () => {
	const d = props.destinationAddress || {};
	return { indirizzo: buildAddressLine(d), city: d.city || '', province: d.province || '', postal_code: d.postal_code || '' };
};
const snapshotAddress = () => ({
	indirizzo: fatturaField('indirizzo'), city: fatturaField('city'),
	province: fatturaField('province'), postal_code: fatturaField('postal_code'),
});

watch(useShippingAsBilling, (active) => {
	if (active) { previousBillingSnapshot.value = snapshotAddress(); updateFatturaData(addressFromShipping()); }
	else if (previousBillingSnapshot.value) { updateFatturaData(previousBillingSnapshot.value); previousBillingSnapshot.value = null; }
});

watch(
	() => {
		const d = props.destinationAddress || {};
		return [d.address, d.address_number, d.city, d.province, d.postal_code];
	},
	() => { if (useShippingAsBilling.value) updateFatturaData(addressFromShipping()); },
);

// Config dei campi: una sola fonte di verità per gli input (label, name, attrs).
const ADDRESS_FIELDS = [
	{ name: 'indirizzo', label: 'Indirizzo', placeholder: 'Indirizzo' },
	{ name: 'city', label: 'Città', placeholder: 'Città' },
	{ name: 'province', label: 'Prov.', placeholder: 'Prov.', maxlength: 2 },
	{ name: 'postal_code', label: 'CAP', placeholder: 'CAP', maxlength: 10 },
];
const COMPANY_TOP_FIELDS = [
	{ name: 'ragione_sociale', label: 'Ragione Sociale *', placeholder: 'SpediamoFacile S.r.l.', required: true },
	{ name: 'p_iva', label: 'Partita IVA *', placeholder: 'IT 01234567890', required: true, maxlength: 13 },
	{ name: 'codice_fiscale', label: 'Codice Fiscale', placeholder: '01234567890' },
];
const COMPANY_MID_FIELDS = [
	{ name: 'codice_sdi', label: 'Codice SDI', placeholder: 'XXXXXXX', maxlength: 7 },
	{ name: 'pec', label: 'PEC (alternativa)', type: 'email', placeholder: 'fattura@pec.azienda.it (almeno una tra SDI e PEC)' },
];
const PRIVATE_TOP_FIELDS = [
	{ name: 'nome_completo', label: 'Nome completo *', placeholder: 'Nome e Cognome', required: true },
	{ name: 'codice_fiscale', label: 'Codice Fiscale *', placeholder: 'RSSMRA80A01H501U', required: true, maxlength: 16 },
];

const SWITCH_PRIMARY = [
	{ value: 'ricevuta', label: 'Ricevuta' },
	{ value: 'fattura', label: 'Fattura' },
];
const SWITCH_SUBJECT = [
	{ value: 'azienda', label: 'Azienda' },
	{ value: 'privato', label: 'Privato' },
];
</script>

<template>
	<div class="checkout-stage-card checkout-stage-card--billing checkout-motion-card [--checkout-delay:140ms]">
		<div class="checkout-panel-head">
			<span class="checkout-panel-head__icon">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<rect x="4" y="3" width="16" height="18" rx="2" />
					<path d="M8 7h8" /><path d="M8 11h8" /><path d="M8 15h5" />
				</svg>
			</span>
			<div class="checkout-panel-head__copy">
				<p class="checkout-panel-head__eyebrow">DOCUMENTO FISCALE</p>
				<p class="checkout-panel-head__title">Ricevuta o fattura</p>
			</div>
		</div>

		<div class="checkout-mini-switch-block checkout-mini-switch-block--billing-primary">
			<div class="checkout-mini-switch checkout-mini-switch--billing checkout-mini-switch--shared" role="tablist" aria-label="Documento fiscale">
				<button
v-for="opt in SWITCH_PRIMARY" :key="opt.value" type="button" role="tab"
					class="checkout-mini-switch__option"
					:aria-pressed="fatturazioneType === opt.value" :aria-selected="fatturazioneType === opt.value"
					:class="{ 'checkout-mini-switch__option--active': fatturazioneType === opt.value }"
					@click="emit('update:fatturazioneType', opt.value)">
					{{ opt.label }}
				</button>
			</div>
		</div>

		<div v-if="fatturazioneType === 'fattura'" class="checkout-billing-reveal">
			<div class="checkout-billing-context-note">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" class="shrink-0 mt-[1px]" fill="var(--color-brand-primary)" aria-hidden="true">
					<path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M13,17H11V11H13M13,9H11V7H13" />
				</svg>
				<div>
					<p class="checkout-billing-context-note__title">Intestazione</p>
					<p class="checkout-billing-context-note__text">Modifica solo se serve.</p>
					<p v-if="billingShippingFullAddress" class="checkout-billing-context-note__prefill">
						Base attuale: {{ billingShippingFullAddress }}
					</p>
				</div>
			</div>

			<div class="checkout-mini-switch-block checkout-mini-switch-block--sub checkout-mini-switch-block--billing-secondary">
				<div class="checkout-mini-switch checkout-mini-switch--billing checkout-mini-switch--subject checkout-mini-switch--tight checkout-mini-switch--shared" role="tablist" aria-label="Intestatario fattura">
					<button
v-for="opt in SWITCH_SUBJECT" :key="opt.value" type="button" role="tab"
						class="checkout-mini-switch__option checkout-mini-switch__option--compact"
						:aria-pressed="invoiceSubjectType === opt.value" :aria-selected="invoiceSubjectType === opt.value"
						:class="{ 'checkout-mini-switch__option--active': invoiceSubjectType === opt.value }"
						@click="emit('update:invoiceSubjectType', opt.value)">
						{{ opt.label }}
					</button>
				</div>
			</div>

			<div v-if="invoiceSubjectType === 'azienda'" class="checkout-billing-fields">
				<div class="checkout-billing-grid checkout-billing-grid--company-top">
					<div v-for="f in COMPANY_TOP_FIELDS" :key="f.name">
						<label class="checkout-billing-label">{{ f.label }}</label>
						<input
:value="fatturaField(f.name)" :type="f.type || 'text'" :placeholder="f.placeholder"
							:required="f.required" :maxlength="f.maxlength" class="checkout-billing-input"
							@input="setFatturaField(f.name, $event.target.value)" >
					</div>
				</div>

				<div class="checkout-billing-grid checkout-billing-grid--company-mid">
					<div v-for="f in COMPANY_MID_FIELDS" :key="f.name">
						<label class="checkout-billing-label">{{ f.label }}</label>
						<input
:value="fatturaField(f.name)" :type="f.type || 'text'" :placeholder="f.placeholder"
							:maxlength="f.maxlength" class="checkout-billing-input"
							@input="setFatturaField(f.name, $event.target.value)" >
					</div>
				</div>

				<p class="checkout-billing-hint">
					Indica almeno uno tra Codice SDI (7 caratteri) e PEC: serve per recapitare la fattura elettronica.
				</p>

				<label class="billing-same-toggle">
					<input v-model="useShippingAsBilling" type="checkbox" class="sr-only" >
					<span class="billing-same-toggle__box" :class="{ 'billing-same-toggle__box--active': useShippingAsBilling }">
						<svg v-if="useShippingAsBilling" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
							<path d="M20 6L9 17l-5-5" />
						</svg>
					</span>
					<span>Usa lo stesso indirizzo della spedizione</span>
				</label>

				<div class="checkout-billing-grid checkout-billing-grid--address" :class="{ 'checkout-billing-grid--locked': useShippingAsBilling }">
					<div v-for="f in ADDRESS_FIELDS" :key="f.name">
						<label class="checkout-billing-label">{{ f.label }}</label>
						<input
:value="fatturaField(f.name)" type="text" :placeholder="f.placeholder" :maxlength="f.maxlength"
							class="checkout-billing-input" :disabled="useShippingAsBilling"
							:aria-disabled="useShippingAsBilling ? 'true' : 'false'"
							@input="setFatturaField(f.name, $event.target.value)" >
					</div>
				</div>
			</div>

			<div v-else class="checkout-billing-fields">
				<div class="checkout-billing-grid checkout-billing-grid--private-top">
					<div v-for="f in PRIVATE_TOP_FIELDS" :key="f.name">
						<label class="checkout-billing-label">{{ f.label }}</label>
						<input
:value="fatturaField(f.name)" type="text" :placeholder="f.placeholder"
							:required="f.required" :maxlength="f.maxlength" class="checkout-billing-input"
							@input="setFatturaField(f.name, $event.target.value)" >
					</div>
				</div>

				<label class="billing-same-toggle">
					<input v-model="useShippingAsBilling" type="checkbox" class="sr-only" >
					<span class="billing-same-toggle__box" :class="{ 'billing-same-toggle__box--active': useShippingAsBilling }">
						<svg v-if="useShippingAsBilling" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
							<path d="M20 6L9 17l-5-5" />
						</svg>
					</span>
					<span>Usa lo stesso indirizzo della spedizione</span>
				</label>

				<div class="checkout-billing-grid checkout-billing-grid--address" :class="{ 'checkout-billing-grid--locked': useShippingAsBilling }">
					<div v-for="f in ADDRESS_FIELDS" :key="f.name">
						<label class="checkout-billing-label">{{ f.label }}</label>
						<input
:value="fatturaField(f.name)" type="text" :placeholder="f.placeholder" :maxlength="f.maxlength"
							class="checkout-billing-input" :disabled="useShippingAsBilling"
							:aria-disabled="useShippingAsBilling ? 'true' : 'false'"
							@input="setFatturaField(f.name, $event.target.value)" >
					</div>
				</div>
			</div>
		</div>

		<div v-else class="checkout-billing-receipt-note">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" class="shrink-0 mt-[1px]" fill="#999" aria-hidden="true">
				<path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M13,17H11V11H13M13,9H11V7H13" />
			</svg>
			<p>Usiamo i dati del checkout per la ricevuta, senza altri passaggi.</p>
		</div>
	</div>
</template>
