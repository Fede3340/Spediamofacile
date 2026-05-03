<script setup>

import { useAddressFormField } from '~/composables/useAddressFormField';

const props = defineProps({
	type: { type: String, required: true, validator: (v) => ['origin', 'dest'].includes(v) },
	address: { type: Object, required: true },
	readonly: { type: Boolean, default: false },
});

const {
	handlers,
	typeKey,
	idPrefix,
	inputNamePrefix,
	sharedInputAttrs,
	getAutocomplete,
	addressField,
	setAddressField,
	ariaInvalid,
	ariaDescribedBy,
} = useAddressFormField(toRef(props, 'type'), toRef(props, 'address'), toRef(props, 'readonly'));

const { fieldClass, smartBlur, onTelefonoInput, sv } = handlers;

const contactPhone = computed({
	get: () => String(props.address.telephone_number || ''),
	set: (value) => onTelefonoInput(typeKey.value, value),
});
</script>

<template>
	<div class="address-form-field address-form-layout-grid__phone">
		<label :for="`${idPrefix}telephone`" class="form-label">
			Telefono <span aria-hidden="true" class="field-required-star">*</span>
		</label>
		<div class="address-form-field__control">
			<input
				:id="`${idPrefix}telephone`"
				v-model="contactPhone"
				type="tel"
				placeholder="+39 333 1234567"
				:name="`${inputNamePrefix}-telephone`"
				:autocomplete="getAutocomplete('tel')"
				v-bind="sharedInputAttrs"
				required
				:aria-required="true"
				:aria-invalid="ariaInvalid('telephone_number')"
				:aria-describedby="ariaDescribedBy('telephone_number')"
				:class="fieldClass(typeKey, 'telephone_number')"
				@blur="smartBlur(typeKey, 'telephone_number')">
		</div>
		<AddressFieldFeedback :type-key="typeKey" field="telephone_number" />
	</div>

	<div class="address-form-field address-form-layout-grid__email">
		<label :for="`${idPrefix}email`" class="form-label">
			Email <span aria-hidden="true" class="field-required-star">*</span>
		</label>
		<div class="address-form-field__control">
			<input
				:id="`${idPrefix}email`"
				:value="addressField('email')"
				type="email"
				placeholder="nome@email.com"
				:name="`${inputNamePrefix}-email`"
				:autocomplete="getAutocomplete('email')"
				v-bind="sharedInputAttrs"
				required
				:aria-required="true"
				:aria-invalid="ariaInvalid('email')"
				:aria-describedby="ariaDescribedBy('email')"
				:class="fieldClass(typeKey, 'email')"
				@blur="smartBlur(typeKey, 'email')"
				@input="
					setAddressField('email', $event.target.value);
					sv.onInput(`${typeKey}_email`, () => sv.validateEmail(`${typeKey}_email`, $event.target.value));
				">
		</div>
		<AddressFieldFeedback :type-key="typeKey" field="email" />
	</div>
</template>
