<script setup>

import { useAddressFormField } from '~/composables/useAddressFormField';

const props = defineProps({
	type: { type: String, required: true, validator: (value) => ['origin', 'dest'].includes(value) },
	address: { type: Object, required: true },
	citySuggestions: { type: Array, default: () => [] },
	provinceSuggestions: { type: Array, default: () => [] },
	capSuggestions: { type: Array, default: () => [] },
	showBusinessFields: { type: Boolean, default: false },
	readonly: { type: Boolean, default: false },
});

const { idPrefix, sharedInputAttrs, setAddressField } = useAddressFormField(
	toRef(props, 'type'),
	toRef(props, 'address'),
	toRef(props, 'readonly'),
);

watchEffect(() => {
	if (!String(props.address.country || '').trim()) {
		setAddressField('country', 'Italia');
	}
});
</script>

<template>
	<div class="address-form-shell">
		<AddressBusinessFields
			v-if="showBusinessFields"
			:id-prefix="idPrefix"
			:address="address"
			:shared-input-attrs="sharedInputAttrs"
			@update-field="setAddressField" />
		<section class="address-form-block">
			<div class="address-form-layout-grid">
				<AddressFormNameFields
					:type="type"
					:address="address"
					:show-business-fields="showBusinessFields"
					:readonly="readonly" />
				<AddressFormLocationFields
					:type="type"
					:address="address"
					:city-suggestions="citySuggestions"
					:readonly="readonly" />
				<AddressFormContactFields
					:type="type"
					:address="address"
					:readonly="readonly" />
			</div>
			<p class="field-required-hint">
				I campi con <span aria-hidden="true">*</span> sono obbligatori.
			</p>
		</section>
	</div>
</template>

<style>
.address-form-layout-grid {
	display: grid;
	gap: 14px 16px;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	row-gap: 16px;
	align-items: start;
}

.address-form-field {
	display: grid;
	align-content: start;
	min-width: 0;
}

.address-form-field__control {
	position: relative;
	min-width: 0;
}

.address-form-field__control--menu {
	z-index: 2;
}

.address-form-field__feedback {
	display: grid;
	gap: 4px;
	padding-top: 4px;
	align-content: start;
}

.address-form-field label,
.address-form-field .form-label {
	display: block;
	margin-bottom: 6px;
	font-size: 0.875rem;
	font-weight: 600;
	color: #1d2738;
	line-height: 1.2;
}

.address-form-layout-grid__street,
.address-form-layout-grid__first-name,
.address-form-layout-grid__last-name,
.address-form-layout-grid__details,
.address-form-layout-grid__phone,
.address-form-layout-grid__email {
	grid-column: 1 / -1;
}

@media (min-width: 1024px) {
	.address-form-layout-grid {
		grid-template-columns: repeat(12, minmax(0, 1fr));
	}

	.address-form-layout-grid__first-name { grid-column: span 4; }
	.address-form-layout-grid__last-name { grid-column: span 4; }
	.address-form-layout-grid__details { grid-column: span 4; }
	.address-form-layout-grid__street { grid-column: span 7; }
	.address-form-layout-grid__number { grid-column: span 2; }
	.address-form-layout-grid__intercom { grid-column: span 3; }
	.address-form-layout-grid__cap { grid-column: span 2; }
	.address-form-layout-grid__city { grid-column: span 4; }
	.address-form-layout-grid__province { grid-column: span 2; }
	.address-form-layout-grid__country { grid-column: span 3; }
	.address-form-layout-grid__phone { grid-column: span 6; }
	.address-form-layout-grid__email { grid-column: span 6; }
}

.field-gentle-error {
	display: block;
	margin: 0;
	line-height: 1.32;
	font-size: 0.8125rem;
}

@media (max-width: 767px) {
	.address-form-layout-grid {
		grid-template-columns: minmax(0, 1fr);
		gap: 12px;
	}
}
</style>
