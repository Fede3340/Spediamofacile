<script setup>
import { computed, toRef } from 'vue';
import { useAddressFormField } from '~/composables/useAddressFormField';

const props = defineProps({
	type: { type: String, required: true, validator: (v) => ['origin', 'dest'].includes(v) },
	address: { type: Object, required: true },
	showBusinessFields: { type: Boolean, default: false },
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

const { fieldClass, smartBlur, onNameInput } = handlers;

const sanitizeContactValue = (value) => (
	String(value || '')
		.replace(/\d/g, '')
		.replace(/[^\p{L}'`.\-\s]/gu, ' ')
		.replace(/\s+/g, ' ')
		.trimStart()
);

const normalizeNameChunk = (value) => sanitizeContactValue(value).trim();

const splitContactName = (value) => {
	const normalized = normalizeNameChunk(value);
	if (!normalized) return { firstName: '', lastName: '' };
	const parts = normalized.split(' ').filter(Boolean);
	if (parts.length === 1) return { firstName: parts[0], lastName: '' };
	return {
		firstName: parts.slice(0, -1).join(' '),
		lastName: parts[parts.length - 1] || '',
	};
};

const commitContactName = (firstName, lastName) => {
	const combinedName = [normalizeNameChunk(firstName), normalizeNameChunk(lastName)]
		.filter(Boolean)
		.join(' ')
		.trim();
	onNameInput(typeKey.value, combinedName);
};

const contactFirstName = computed({
	get: () => splitContactName(props.address.full_name).firstName,
	set: (value) => {
		const current = splitContactName(props.address.full_name);
		commitContactName(value, current.lastName);
	},
});

const contactLastName = computed({
	get: () => splitContactName(props.address.full_name).lastName,
	set: (value) => {
		const current = splitContactName(props.address.full_name);
		commitContactName(current.firstName, value);
	},
});

const detailsLabel = computed(() => (props.showBusinessFields ? 'Azienda / dettagli' : 'Presso / info aggiuntive'));
const detailsPlaceholder = computed(() => (props.showBusinessFields ? 'Ragione sociale, c/o, piano...' : 'Presso, piano, interno...'));
</script>

<template>
	<div class="address-form-field address-form-layout-grid__first-name">
		<label :for="`${idPrefix}first_name`" class="form-label">
			Nome <span aria-hidden="true" class="field-required-star">*</span>
		</label>
		<div class="address-form-field__control">
			<input
				:id="`${idPrefix}first_name`"
				v-model="contactFirstName"
				type="text"
				placeholder="Nome"
				:name="`${inputNamePrefix}-first-name`"
				:autocomplete="getAutocomplete('given-name')"
				v-bind="sharedInputAttrs"
				required
				:aria-required="true"
				:aria-invalid="ariaInvalid('full_name')"
				:aria-describedby="ariaDescribedBy('full_name')"
				:class="fieldClass(typeKey, 'full_name')"
				@blur="smartBlur(typeKey, 'full_name')">
		</div>
		<AddressFieldFeedback :type-key="typeKey" field="full_name" />
	</div>

	<div class="address-form-field address-form-layout-grid__last-name">
		<label :for="`${idPrefix}last_name`" class="form-label">
			Cognome <span aria-hidden="true" class="field-required-star">*</span>
		</label>
		<div class="address-form-field__control">
			<input
				:id="`${idPrefix}last_name`"
				v-model="contactLastName"
				type="text"
				placeholder="Cognome"
				:name="`${inputNamePrefix}-last-name`"
				:autocomplete="getAutocomplete('family-name')"
				v-bind="sharedInputAttrs"
				required
				:aria-required="true"
				:aria-invalid="ariaInvalid('full_name')"
				:aria-describedby="ariaDescribedBy('full_name')"
				:class="fieldClass(typeKey, 'full_name')"
				@blur="smartBlur(typeKey, 'full_name')">
		</div>
	</div>

	<div class="address-form-field address-form-layout-grid__details">
		<label :for="`${idPrefix}additional_info`" class="form-label">{{ detailsLabel }}</label>
		<div class="address-form-field__control">
			<input
				:id="`${idPrefix}additional_info`"
				:value="addressField('additional_information')"
				type="text"
				:placeholder="detailsPlaceholder"
				:name="`${inputNamePrefix}-additional-info`"
				:autocomplete="getAutocomplete('address-line2')"
				v-bind="sharedInputAttrs"
				class="input-preventivo-step-2"
				@input="setAddressField('additional_information', $event.target.value)">
		</div>
	</div>
</template>
