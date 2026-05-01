<script setup>
/**
 * AddressBusinessFields — sotto-fieldset "Dati azienda" del form indirizzi.
 *
 * Estratto da AddressFormFields.vue (Ondata 6b split markup-only).
 * Mostrato solo quando showBusinessFields=true (utenti business B2B).
 * Markup identico al pixel: 4 input (ragione sociale, P.IVA, SDI, PEC).
 */
const props = defineProps({
	idPrefix: { type: String, required: true },
	address: { type: Object, required: true },
	sharedInputAttrs: { type: Object, required: true },
});

const emit = defineEmits(['update-field']);

const addressField = (field) => props.address?.[field] || '';
const setAddressField = (field, value) => emit('update-field', field, value);
</script>

<template>
	<fieldset class="address-form-fields__business">
		<legend class="address-form-fields__legend">Dati azienda</legend>
		<div class="address-form-fields__row">
			<div class="address-form-fields__field">
				<label :for="`${idPrefix}company_name`">
					Ragione sociale <span aria-hidden="true" class="field-required-star">*</span>
				</label>
				<input
					:id="`${idPrefix}company_name`"
					:value="addressField('company_name')"
					type="text"
					required
					:aria-required="true"
					v-bind="sharedInputAttrs"
					@input="setAddressField('company_name', $event.target.value)" >
			</div>
			<div class="address-form-fields__field">
				<label :for="`${idPrefix}vat_number`">
					Partita IVA <span aria-hidden="true" class="field-required-star">*</span>
				</label>
				<input
					:id="`${idPrefix}vat_number`"
					:value="addressField('vat_number')"
					type="text"
					pattern="\d{11}"
					maxlength="11"
					required
					:aria-required="true"
					v-bind="sharedInputAttrs"
					@input="setAddressField('vat_number', $event.target.value)" >
			</div>
		</div>
		<div class="address-form-fields__row">
			<div class="address-form-fields__field">
				<label :for="`${idPrefix}sdi_code`">Codice destinatario SDI</label>
				<input
					:id="`${idPrefix}sdi_code`"
					:value="addressField('sdi_code')"
					type="text"
					maxlength="7"
					placeholder="Es. M5UXCR1"
					v-bind="sharedInputAttrs"
					@input="setAddressField('sdi_code', $event.target.value)" >
			</div>
			<div class="address-form-fields__field">
				<label :for="`${idPrefix}pec_email`">PEC</label>
				<input
					:id="`${idPrefix}pec_email`"
					:value="addressField('pec_email')"
					type="email"
					placeholder="esempio@pec.it"
					v-bind="sharedInputAttrs"
					@input="setAddressField('pec_email', $event.target.value)" >
			</div>
		</div>
		<p class="address-form-fields__business-hint">
			Uno fra SDI o PEC è obbligatorio per la fatturazione elettronica.
		</p>
	</fieldset>
</template>
