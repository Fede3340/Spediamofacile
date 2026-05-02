<script setup>
import { computed, toRef } from 'vue';
import { useAddressFormField } from '~/composables/useAddressFormField';

const props = defineProps({
	type: { type: String, required: true, validator: (v) => ['origin', 'dest'].includes(v) },
	address: { type: Object, required: true },
	citySuggestions: { type: Array, default: () => [] },
	readonly: { type: Boolean, default: false },
});

const {
	handlers,
	typeKey,
	idPrefix,
	inputNamePrefix,
	sharedInputAttrs,
	readonlyClass,
	getAutocomplete,
	addressField,
	setAddressField,
	ariaInvalid,
	ariaDescribedBy,
} = useAddressFormField(toRef(props, 'type'), toRef(props, 'address'), toRef(props, 'readonly'));

const {
	fieldClass, smartBlur,
	onCapInput, onCapFocus, onCityInput, onCityFocus, onProvinciaInput, onProvinceFocus,
	selectCity, formatCitySuggestionLabel,
} = handlers;

const countryLabel = computed(() => String(props.address.country || 'Italia').trim() || 'Italia');

const forceProvinceUpper = (event) => {
	const target = event.target;
	const raw = String(target.value || '');
	const upper = raw.toUpperCase();
	if (raw !== upper) {
		const start = target.selectionStart;
		const end = target.selectionEnd;
		target.value = upper;
		try { target.setSelectionRange(start, end); } catch { /* no-op */ }
	}
	onProvinciaInput(typeKey.value, upper);
};
</script>

<template>
	<div class="address-form-field address-form-layout-grid__street">
		<label :for="`${idPrefix}address`" class="form-label">
			Via / piazza <span aria-hidden="true" class="field-required-star">*</span>
		</label>
		<div class="address-form-field__control">
			<input
				:id="`${idPrefix}address`"
				:value="addressField('address')"
				type="text"
				:name="`${inputNamePrefix}-address`"
				:autocomplete="getAutocomplete('address-line1')"
				v-bind="sharedInputAttrs"
				required
				:aria-required="true"
				:aria-invalid="ariaInvalid('address')"
				:aria-describedby="ariaDescribedBy('address')"
				:class="[fieldClass(typeKey, 'address'), readonlyClass]"
				:readonly="readonly"
				@input="setAddressField('address', $event.target.value)"
				@blur="smartBlur(typeKey, 'address')">
		</div>
		<AddressFieldFeedback :type-key="typeKey" field="address" />
	</div>

	<div class="address-form-field address-form-layout-grid__number">
		<label :for="`${idPrefix}address_number`" class="form-label">
			N. civico <span aria-hidden="true" class="field-required-star">*</span>
		</label>
		<div class="address-form-field__control">
			<input
				:id="`${idPrefix}address_number`"
				:value="addressField('address_number')"
				type="text"
				:name="`${inputNamePrefix}-address-number`"
				autocomplete="off"
				v-bind="sharedInputAttrs"
				required
				:aria-required="true"
				:aria-invalid="ariaInvalid('address_number')"
				:aria-describedby="ariaDescribedBy('address_number')"
				:class="[fieldClass(typeKey, 'address_number'), readonlyClass]"
				:readonly="readonly"
				@input="setAddressField('address_number', $event.target.value)"
				@blur="smartBlur(typeKey, 'address_number')">
		</div>
		<AddressFieldFeedback :type-key="typeKey" field="address_number" />
	</div>

	<div class="address-form-field address-form-layout-grid__intercom">
		<label :for="`${idPrefix}intercom`" class="form-label">Scala / int.</label>
		<div class="address-form-field__control">
			<input
				:id="`${idPrefix}intercom`"
				:value="addressField('intercom_code')"
				type="text"
				placeholder="Scala A, int. 4"
				:name="`${inputNamePrefix}-intercom`"
				autocomplete="off"
				v-bind="sharedInputAttrs"
				class="input-preventivo-step-2"
				@input="setAddressField('intercom_code', $event.target.value)">
		</div>
	</div>

	<div class="address-form-field address-form-layout-grid__cap">
		<label :for="`${idPrefix}postal_code`" class="form-label">
			CAP <span aria-hidden="true" class="field-required-star">*</span>
		</label>
		<div class="address-form-field__control">
			<input
				:id="`${idPrefix}postal_code`"
				:value="addressField('postal_code')"
				type="text"
				:name="`${inputNamePrefix}-postal-code`"
				:autocomplete="getAutocomplete('postal-code')"
				v-bind="sharedInputAttrs"
				required
				:aria-required="true"
				:aria-invalid="ariaInvalid('postal_code')"
				:aria-describedby="ariaDescribedBy('postal_code')"
				:class="[fieldClass(typeKey, 'postal_code'), readonlyClass]"
				:readonly="readonly"
				maxlength="5"
				@input="onCapInput(typeKey, $event.target.value)"
				@focus="onCapFocus(typeKey)"
				@blur="smartBlur(typeKey, 'postal_code')">
		</div>
		<AddressFieldFeedback :type-key="typeKey" field="postal_code" />
	</div>

	<div class="address-form-field address-form-layout-grid__city">
		<label :for="`${idPrefix}city`" class="form-label">
			Città <span aria-hidden="true" class="field-required-star">*</span>
		</label>
		<div class="address-form-field__control address-form-field__control--menu">
			<input
				:id="`${idPrefix}city`"
				:value="addressField('city')"
				type="text"
				:name="`${inputNamePrefix}-city`"
				:autocomplete="getAutocomplete('address-level2')"
				v-bind="sharedInputAttrs"
				required
				:aria-required="true"
				:aria-invalid="ariaInvalid('city')"
				:aria-describedby="ariaDescribedBy('city')"
				:class="[fieldClass(typeKey, 'city'), readonlyClass]"
				:readonly="readonly"
				@input="onCityInput(typeKey, $event.target.value)"
				@focus="onCityFocus(typeKey)"
				@blur="smartBlur(typeKey, 'city')">
			<ul v-if="!readonly && citySuggestions.length > 0" class="address-field-menu">
				<li
					v-for="location in citySuggestions"
					:key="`${location.postal_code}-${location.place_name}`"
					class="address-field-menu__item"
					@mousedown.prevent="selectCity(typeKey, location)">
					<span class="address-field-menu__label">{{ formatCitySuggestionLabel(location) }}</span>
				</li>
			</ul>
		</div>
		<AddressFieldFeedback :type-key="typeKey" field="city" />
	</div>

	<div class="address-form-field address-form-layout-grid__province">
		<label :for="`${idPrefix}province`" class="form-label">
			Prov. <span aria-hidden="true" class="field-required-star">*</span>
		</label>
		<div class="address-form-field__control">
			<input
				:id="`${idPrefix}province`"
				:value="addressField('province')"
				type="text"
				:name="`${inputNamePrefix}-province`"
				:autocomplete="getAutocomplete('address-level1')"
				v-bind="sharedInputAttrs"
				required
				:aria-required="true"
				:aria-invalid="ariaInvalid('province')"
				:aria-describedby="ariaDescribedBy('province')"
				:class="[fieldClass(typeKey, 'province'), readonlyClass, 'address-form-province-input']"
				:readonly="readonly"
				maxlength="2"
				pattern="[A-Z]{2}"
				inputmode="text"
				@input="forceProvinceUpper($event)"
				@focus="onProvinceFocus(typeKey)"
				@blur="smartBlur(typeKey, 'province')">
		</div>
		<AddressFieldFeedback :type-key="typeKey" field="province" />
	</div>

	<div class="address-form-field address-form-layout-grid__country">
		<label class="form-label">Paese</label>
		<div class="address-form-field__control">
			<span class="route-card__country-chip route-card__country-chip--static address-form-country-chip">
				{{ countryLabel }}
			</span>
		</div>
	</div>
</template>

<style scoped>
.address-form-province-input {
	text-transform: uppercase;
}
.address-form-province-input::placeholder {
	text-transform: none;
}

.address-form-country-chip {
	display: inline-flex;
	align-items: center;
	width: 100%;
	min-width: 0;
	max-width: 100%;
	height: 48px;
	justify-content: flex-start;
	padding: 0 16px;
	border-radius: 16px;
	font-size: 0.9375rem;
	font-weight: 600;
	box-sizing: border-box;
}

@media (min-width: 640px) {
	.address-form-country-chip {
		height: 50px;
	}
}
</style>
