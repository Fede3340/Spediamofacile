<script setup>
// CRITICAL: vedi CLAUDE.md "Eccezioni documentate" — non splittare senza E2E gating Stripe.
// File 737 LOC: form multi-zona (pickup/delivery/common) con validazione cross-field.
const props = defineProps({
	type: { type: String, required: true, validator: (value) => ['origin', 'dest'].includes(value) },
	address: { type: Object, required: true },
	citySuggestions: { type: Array, default: () => [] },
	provinceSuggestions: { type: Array, default: () => [] },
	capSuggestions: { type: Array, default: () => [] },
	showBusinessFields: { type: Boolean, default: false },
	readonly: { type: Boolean, default: false },
});

// InjectionKey tipato (Ondata 6). Vue InjectionKey<T> garantisce che inject()
// ritorni il tipo corretto a TypeScript senza cast manuali.
const handlers = inject(shipmentFormHandlersKey);
if (!handlers) throw new Error('AddressFormFields: shipmentFormHandlersKey non iniettata');
const {
	fieldClass, getFieldError, smartBlur,
	onNameInput, onCityInput, onCityFocus, onProvinciaInput, onProvinceFocus,
	onCapInput, onCapFocus, onTelefonoInput,
	updateAddressField,
	selectCity, sv,
	formatCitySuggestionLabel,
} = handlers;

const typeKey = props.type;
const idPrefix = typeKey === 'origin' ? '' : 'dest_';
const inputNamePrefix = computed(() => (typeKey === 'origin' ? 'shipment-origin' : 'shipment-dest'));
const countryLabel = computed(() => String(props.address.country || 'Italia').trim() || 'Italia');
const autocompleteSection = computed(() => (typeKey === 'origin' ? 'section-origin' : 'section-destination'));
const getAutocomplete = (purpose) => `${autocompleteSection.value} shipping ${purpose}`;
const addressField = (field) => props.address?.[field] || '';
const setAddressField = (field, value) => updateAddressField(typeKey, field, value);
const sharedInputAttrs = {
	autocapitalize: 'off',
	autocorrect: 'off',
	spellcheck: 'false',
	'data-lpignore': 'true',
	'data-1p-ignore': 'true',
	'data-form-type': 'other',
};

const readonlyClass = computed(() => (
	props.readonly
		? '!bg-white !border-[#CBD5DF] !text-[var(--color-brand-text-secondary)] cursor-not-allowed'
		: ''
));

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

	if (!normalized) {
		return { firstName: '', lastName: '' };
	}

	const parts = normalized.split(' ').filter(Boolean);

	if (parts.length === 1) {
		return { firstName: parts[0], lastName: '' };
	}

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

	onNameInput(typeKey, combinedName);
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

const contactPhone = computed({
	get: () => String(props.address.telephone_number || ''),
	set: (value) => {
		onTelefonoInput(typeKey, value);
	},
});

const firstNamePlaceholder = computed(() => 'Nome');
const lastNamePlaceholder = computed(() => 'Cognome');
const detailsLabel = computed(() => (props.showBusinessFields ? 'Azienda / dettagli' : 'Presso / info aggiuntive'));
const detailsPlaceholder = computed(() => (props.showBusinessFields ? 'Ragione sociale, c/o, piano...' : 'Presso, piano, interno...'));
// A11y helpers: errore ID coerente con aria-describedby
const errorId = (field) => `${idPrefix}${field}_error`;
const ariaInvalid = (field) => Boolean(getFieldError(typeKey, field));
const ariaDescribedBy = (field) => (getFieldError(typeKey, field) ? errorId(field) : undefined);

// Forza la sigla provincia in MAIUSCOLO durante l'input (es. "mi" → "MI"),
// preservando la posizione del cursore e propagando il valore al composable autocomplete.
const forceProvinceUpper = (event) => {
	const target = event.target;
	const raw = String(target.value || '');
	const upper = raw.toUpperCase();
	if (raw !== upper) {
		const start = target.selectionStart;
		const end = target.selectionEnd;
		target.value = upper;
		try { target.setSelectionRange(start, end); } catch { /* no-op su input non testuali */ }
	}
	onProvinciaInput(typeKey, upper);
};

watchEffect(() => {
	if (!String(props.address.country || '').trim()) {
		setAddressField('country', 'Italia');
	}
});
</script>

<template>
	<div class="address-form-shell">
		<fieldset v-if="showBusinessFields" class="address-form-fields__business">
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
		<section class="address-form-block">
			<div class="address-form-layout-grid">
				<div class="address-form-field address-form-layout-grid__first-name">
					<label :for="`${idPrefix}first_name`" class="form-label">
						Nome <span aria-hidden="true" class="field-required-star">*</span>
					</label>
					<div class="address-form-field__control">
						<input
							:id="`${idPrefix}first_name`"
							v-model="contactFirstName"
							type="text"
							:placeholder="firstNamePlaceholder"
							:name="`${inputNamePrefix}-first-name`"
							:autocomplete="getAutocomplete('given-name')"
							v-bind="sharedInputAttrs"
							required
							:aria-required="true"
							:aria-invalid="ariaInvalid('full_name')"
							:aria-describedby="ariaDescribedBy('full_name')"
							:class="fieldClass(typeKey, 'full_name')"
							@blur="smartBlur(typeKey, 'full_name')" >
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
							:placeholder="lastNamePlaceholder"
							:name="`${inputNamePrefix}-last-name`"
							:autocomplete="getAutocomplete('family-name')"
							v-bind="sharedInputAttrs"
							required
							:aria-required="true"
							:aria-invalid="ariaInvalid('full_name')"
							:aria-describedby="ariaDescribedBy('full_name')"
							:class="fieldClass(typeKey, 'full_name')"
							@blur="smartBlur(typeKey, 'full_name')" >
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
							@input="setAddressField('additional_information', $event.target.value)" >
					</div>
				</div>

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
							@blur="smartBlur(typeKey, 'address')" >
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
							@blur="smartBlur(typeKey, 'address_number')" >
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
							@input="setAddressField('intercom_code', $event.target.value)" >
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
							@blur="smartBlur(typeKey, 'postal_code')" >
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
							@blur="smartBlur(typeKey, 'city')" >
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
							@blur="smartBlur(typeKey, 'province')" >
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
							@blur="smartBlur(typeKey, 'telephone_number')" >
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
							" >
					</div>
					<AddressFieldFeedback :type-key="typeKey" field="email" />
				</div>
			</div>
			<p class="field-required-hint">
				I campi con <span aria-hidden="true">*</span> sono obbligatori.
			</p>
		</section>
	</div>
</template>

<style scoped>
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

/* Fix 8 — Labels: no overlap con input */
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

	.address-form-layout-grid__first-name {
		grid-column: span 4;
	}

	.address-form-layout-grid__last-name {
		grid-column: span 4;
	}

	.address-form-layout-grid__details {
		grid-column: span 4;
	}

	.address-form-layout-grid__street {
		grid-column: span 7;
	}

	.address-form-layout-grid__number {
		grid-column: span 2;
	}

	.address-form-layout-grid__intercom {
		grid-column: span 3;
	}

	.address-form-layout-grid__cap {
		grid-column: span 2;
	}

	.address-form-layout-grid__city {
		grid-column: span 4;
	}

	.address-form-layout-grid__province {
		grid-column: span 2;
	}

	.address-form-layout-grid__country {
		grid-column: span 3;
	}

	.address-form-layout-grid__phone {
		grid-column: span 6;
	}

	.address-form-layout-grid__email {
		grid-column: span 6;
	}
}

.field-gentle-error {
	display: block;
	margin: 0;
	line-height: 1.32;
	font-size: 0.8125rem;
}

/* Sigla provincia: sempre maiuscola (es. "MI", "RM"). Il browser mostra l'uppercase
   ma `forceProvinceUpper` normalizza anche il valore reale per coerenza con backend. */
.address-form-province-input {
	text-transform: uppercase;
}
.address-form-province-input::placeholder {
	text-transform: none;
}

@media (max-width: 767px) {
	.address-form-layout-grid {
		grid-template-columns: minmax(0, 1fr);
		gap: 12px;
	}
}
</style>
