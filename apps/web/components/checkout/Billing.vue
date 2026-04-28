<!--
  Billing / fiscal document section: ricevuta vs fattura (azienda / privato).
-->
<script setup>
const props = defineProps({
	fatturazioneType: { type: String, required: true },
	invoiceSubjectType: { type: String, required: true },
	fatturaData: { type: Object, required: true },
	billingShippingFullAddress: { type: String, default: '' },
	// Indirizzo di destinazione della spedizione (sorgente del toggle "stesso indirizzo").
	destinationAddress: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['update:fatturazioneType', 'update:invoiceSubjectType']);

// Toggle "Usa lo stesso indirizzo della spedizione" per i campi indirizzo/città/prov/CAP
// della fatturazione. Quando attivo copia i valori da `destinationAddress` e disabilita
// i quattro input fisici (UX coerente con i pattern di billing moderni).
const useShippingAsBilling = ref(false);

// Compone la riga indirizzo (via + civico) come fa useCheckoutBilling.applyShippingDataToBilling.
const buildAddressLine = (address) => {
	if (!address) return '';
	return [address.address, address.address_number].filter(Boolean).join(' ').trim();
};

// Snapshot dei valori precedenti per ripristino quando l'utente disattiva il toggle.
const previousBillingSnapshot = ref(null);

const applyShippingToBillingFields = () => {
	const dest = props.destinationAddress || {};
	props.fatturaData.indirizzo = buildAddressLine(dest);
	props.fatturaData.city = dest.city || '';
	props.fatturaData.province = dest.province || '';
	props.fatturaData.postal_code = dest.postal_code || '';
};

watch(useShippingAsBilling, (active) => {
	if (active) {
		// Memorizza i valori attuali prima di sovrascriverli, così possiamo ripristinarli.
		previousBillingSnapshot.value = {
			indirizzo: props.fatturaData.indirizzo || '',
			city: props.fatturaData.city || '',
			province: props.fatturaData.province || '',
			postal_code: props.fatturaData.postal_code || '',
		};
		applyShippingToBillingFields();
	} else if (previousBillingSnapshot.value) {
		// Ripristina i valori precedenti al toggle (preserva eventuale compilazione manuale).
		props.fatturaData.indirizzo = previousBillingSnapshot.value.indirizzo;
		props.fatturaData.city = previousBillingSnapshot.value.city;
		props.fatturaData.province = previousBillingSnapshot.value.province;
		props.fatturaData.postal_code = previousBillingSnapshot.value.postal_code;
		previousBillingSnapshot.value = null;
	}
});

// Se l'utente cambia i dati della destinazione mentre il toggle è attivo, li risincronizza.
watch(
	() => [
		props.destinationAddress?.address,
		props.destinationAddress?.address_number,
		props.destinationAddress?.city,
		props.destinationAddress?.province,
		props.destinationAddress?.postal_code,
	],
	() => {
		if (useShippingAsBilling.value) applyShippingToBillingFields();
	},
);
</script>

<template>
	<div class="checkout-stage-card checkout-stage-card--billing checkout-motion-card [--checkout-delay:140ms]">
		<div class="checkout-panel-head">
			<span class="checkout-panel-head__icon">
				<svg
					width="18"
					height="18"
					viewBox="0 0 24 24"
					fill="none"
					stroke="currentColor"
					stroke-width="2"
					stroke-linecap="round"
					stroke-linejoin="round">
					<rect x="4" y="3" width="16" height="18" rx="2" />
					<path d="M8 7h8" />
					<path d="M8 11h8" />
					<path d="M8 15h5" />
				</svg>
			</span>
			<div class="checkout-panel-head__copy">
				<p class="checkout-panel-head__title">Documento fiscale</p>
				<p class="checkout-panel-head__text">Ricevuta o fattura, senza uscire dal flusso.</p>
			</div>
		</div>

		<div class="checkout-mini-switch-block checkout-mini-switch-block--billing-primary">
			<div class="checkout-mini-switch checkout-mini-switch--billing checkout-mini-switch--shared" role="tablist" aria-label="Documento fiscale">
				<button
					type="button"
					@click="emit('update:fatturazioneType', 'ricevuta')"
					role="tab"
					class="checkout-mini-switch__option"
					:aria-pressed="fatturazioneType === 'ricevuta'"
					:aria-selected="fatturazioneType === 'ricevuta'"
					:class="{ 'checkout-mini-switch__option--active': fatturazioneType === 'ricevuta' }">
					Ricevuta
				</button>
				<button
					type="button"
					@click="emit('update:fatturazioneType', 'fattura')"
					role="tab"
					class="checkout-mini-switch__option"
					:aria-pressed="fatturazioneType === 'fattura'"
					:aria-selected="fatturazioneType === 'fattura'"
					:class="{ 'checkout-mini-switch__option--active': fatturazioneType === 'fattura' }">
					Fattura
				</button>
			</div>
		</div>

		<div v-if="fatturazioneType === 'fattura'" class="checkout-billing-reveal">
			<div class="checkout-billing-context-note">
				<svg
					xmlns="http://www.w3.org/2000/svg"
					viewBox="0 0 24 24"
					width="18"
					height="18"
					class="shrink-0 mt-[1px]"
					fill="var(--color-brand-primary)"
					aria-hidden="true">
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
				<div
					class="checkout-mini-switch checkout-mini-switch--billing checkout-mini-switch--subject checkout-mini-switch--tight checkout-mini-switch--shared"
					role="tablist"
					aria-label="Intestatario fattura">
					<button
						type="button"
						@click="emit('update:invoiceSubjectType', 'azienda')"
						role="tab"
						class="checkout-mini-switch__option checkout-mini-switch__option--compact"
						:aria-pressed="invoiceSubjectType === 'azienda'"
						:aria-selected="invoiceSubjectType === 'azienda'"
						:class="{ 'checkout-mini-switch__option--active': invoiceSubjectType === 'azienda' }">
						Azienda
					</button>
					<button
						type="button"
						@click="emit('update:invoiceSubjectType', 'privato')"
						role="tab"
						class="checkout-mini-switch__option checkout-mini-switch__option--compact"
						:aria-pressed="invoiceSubjectType === 'privato'"
						:aria-selected="invoiceSubjectType === 'privato'"
						:class="{ 'checkout-mini-switch__option--active': invoiceSubjectType === 'privato' }">
						Privato
					</button>
				</div>
			</div>

			<div v-if="invoiceSubjectType === 'azienda'" class="checkout-billing-fields">
				<div class="checkout-billing-grid checkout-billing-grid--company-top">
					<div>
						<label class="checkout-billing-label">Ragione Sociale *</label>
						<input
							v-model="fatturaData.ragione_sociale"
							type="text"
							placeholder="SpediamoFacile S.r.l."
								required
							class="checkout-billing-input" />
					</div>
					<div>
						<label class="checkout-billing-label">Partita IVA *</label>
						<input v-model="fatturaData.p_iva" type="text" maxlength="13" placeholder="IT 01234567890" required class="checkout-billing-input" />
					</div>
					<div>
						<label class="checkout-billing-label">Codice Fiscale</label>
						<input
							v-model="fatturaData.codice_fiscale"
							type="text"
							placeholder="01234567890"
							class="checkout-billing-input" />
					</div>
				</div>

				<div class="checkout-billing-grid checkout-billing-grid--company-mid">
					<div>
						<label class="checkout-billing-label">Codice SDI</label>
						<input
							v-model="fatturaData.codice_sdi"
							type="text"
							maxlength="7"
							placeholder="XXXXXXX"
							class="checkout-billing-input" />
					</div>
					<div>
						<label class="checkout-billing-label">PEC (alternativa)</label>
						<input
							v-model="fatturaData.pec"
							type="email"
							placeholder="fattura@pec.azienda.it (almeno una tra SDI e PEC)"
							class="checkout-billing-input" />
					</div>
				</div>

				<p class="checkout-billing-hint">
					Indica almeno uno tra Codice SDI (7 caratteri) e PEC: serve per recapitare la fattura elettronica.
				</p>

				<label class="billing-same-toggle">
					<input type="checkbox" v-model="useShippingAsBilling" class="sr-only" />
					<span class="billing-same-toggle__box" :class="{ 'billing-same-toggle__box--active': useShippingAsBilling }">
						<svg v-if="useShippingAsBilling" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
							<path d="M20 6L9 17l-5-5" />
						</svg>
					</span>
					<span>Usa lo stesso indirizzo della spedizione</span>
				</label>

				<div
					class="checkout-billing-grid checkout-billing-grid--address"
					:class="{ 'checkout-billing-grid--locked': useShippingAsBilling }">
					<div>
						<label class="checkout-billing-label">Indirizzo</label>
						<input
							v-model="fatturaData.indirizzo"
							type="text"
							placeholder="Indirizzo"
							class="checkout-billing-input"
							:disabled="useShippingAsBilling"
							:aria-disabled="useShippingAsBilling ? 'true' : 'false'" />
					</div>
					<div>
						<label class="checkout-billing-label">Città</label>
						<input
							v-model="fatturaData.city"
							type="text"
							placeholder="Città"
							class="checkout-billing-input"
							:disabled="useShippingAsBilling"
							:aria-disabled="useShippingAsBilling ? 'true' : 'false'" />
					</div>
					<div>
						<label class="checkout-billing-label">Prov.</label>
						<input
							v-model="fatturaData.province"
							type="text"
							maxlength="2"
							placeholder="Prov."
							class="checkout-billing-input"
							:disabled="useShippingAsBilling"
							:aria-disabled="useShippingAsBilling ? 'true' : 'false'" />
					</div>
					<div>
						<label class="checkout-billing-label">CAP</label>
						<input
							v-model="fatturaData.postal_code"
							type="text"
							maxlength="10"
							placeholder="CAP"
							class="checkout-billing-input"
							:disabled="useShippingAsBilling"
							:aria-disabled="useShippingAsBilling ? 'true' : 'false'" />
					</div>
				</div>
			</div>

			<div v-else class="checkout-billing-fields">
				<div class="checkout-billing-grid checkout-billing-grid--private-top">
					<div>
						<label class="checkout-billing-label">Nome completo *</label>
						<input
							v-model="fatturaData.nome_completo"
							type="text"
							placeholder="Nome e Cognome"
								required
							class="checkout-billing-input" />
					</div>
					<div>
						<label class="checkout-billing-label">Codice Fiscale *</label>
						<input
							v-model="fatturaData.codice_fiscale"
							type="text"
							placeholder="RSSMRA80A01H501U"
							maxlength="16"
							required
							class="checkout-billing-input" />
					</div>
				</div>

				<label class="billing-same-toggle">
					<input type="checkbox" v-model="useShippingAsBilling" class="sr-only" />
					<span class="billing-same-toggle__box" :class="{ 'billing-same-toggle__box--active': useShippingAsBilling }">
						<svg v-if="useShippingAsBilling" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
							<path d="M20 6L9 17l-5-5" />
						</svg>
					</span>
					<span>Usa lo stesso indirizzo della spedizione</span>
				</label>

				<div
					class="checkout-billing-grid checkout-billing-grid--address"
					:class="{ 'checkout-billing-grid--locked': useShippingAsBilling }">
					<div>
						<label class="checkout-billing-label">Indirizzo</label>
						<input
							v-model="fatturaData.indirizzo"
							type="text"
							placeholder="Indirizzo"
							class="checkout-billing-input"
							:disabled="useShippingAsBilling"
							:aria-disabled="useShippingAsBilling ? 'true' : 'false'" />
					</div>
					<div>
						<label class="checkout-billing-label">Città</label>
						<input
							v-model="fatturaData.city"
							type="text"
							placeholder="Città"
							class="checkout-billing-input"
							:disabled="useShippingAsBilling"
							:aria-disabled="useShippingAsBilling ? 'true' : 'false'" />
					</div>
					<div>
						<label class="checkout-billing-label">Prov.</label>
						<input
							v-model="fatturaData.province"
							type="text"
							maxlength="2"
							placeholder="Prov."
							class="checkout-billing-input"
							:disabled="useShippingAsBilling"
							:aria-disabled="useShippingAsBilling ? 'true' : 'false'" />
					</div>
					<div>
						<label class="checkout-billing-label">CAP</label>
						<input
							v-model="fatturaData.postal_code"
							type="text"
							maxlength="10"
							placeholder="CAP"
							class="checkout-billing-input"
							:disabled="useShippingAsBilling"
							:aria-disabled="useShippingAsBilling ? 'true' : 'false'" />
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
