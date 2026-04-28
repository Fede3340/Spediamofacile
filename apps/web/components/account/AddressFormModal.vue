<!-- COMPONENTE: AddressFormModal -->
<script setup>
import '~/assets/css/account.css';
import { provinceList } from '~/utils/provinceList';

const props = defineProps({
	modelValue: { type: Boolean, default: false },
	mode: { type: String, default: 'create' },
	initial: { type: Object, default: null },
	submitting: { type: Boolean, default: false },
	serverError: { type: String, default: null },
});

const emit = defineEmits(['update:modelValue', 'submit', 'close']);

const emptyForm = () => ({
	type: 'destination',         // 'origin' | 'destination'
	profile: 'private',          // 'private' | 'company'
	label: '',                   // Etichetta personale (Casa, Ufficio…)
	first_name: '',
	last_name: '',
	company_name: '',
	vat_number: '',
	sdi_code: '',
	address: '',
	address_number: '',
	postal_code: '',
	city: '',
	province: '',
	country: 'IT',
	telephone_number: '',
	email: '',
	additional_information: '',
	default: false,
});

const form = ref(emptyForm());
const errors = ref({});

const isEdit = computed(() => props.mode === 'edit');
const isCompany = computed(() => form.value.profile === 'company');

// Inizializza form quando si apre la modale
watch(
	() => props.modelValue,
	(open) => {
		if (open) {
			errors.value = {};
			if (props.initial) {
				const src = props.initial;
				const parts = String(src.name || '').trim().split(/\s+/);
				form.value = {
					...emptyForm(),
					...src,
					type: src.type === 'origin' ? 'origin' : 'destination',
					profile: src.profile === 'company' || src.company_name ? 'company' : 'private',
					first_name: src.first_name || parts[0] || '',
					last_name: src.last_name || parts.slice(1).join(' ') || '',
					label: src.label || '',
					default: !!src.default,
				};
			} else {
				form.value = emptyForm();
			}
		}
	},
	{ immediate: true },
);

// Validazione
const validate = () => {
	const err = {};
	const f = form.value;

	if (!f.type) err.type = 'Seleziona un tipo';
	if (!f.label?.trim()) err.label = "Inserisci un'etichetta (es. Casa, Ufficio)";

	if (isCompany.value) {
		if (!f.company_name?.trim()) err.company_name = 'Inserisci la ragione sociale';
		if (!f.vat_number?.trim()) err.vat_number = 'Inserisci la Partita IVA';
		else if (!/^[A-Z]{0,2}[0-9]{11}$/i.test(f.vat_number.replace(/\s/g, ''))) err.vat_number = 'Partita IVA non valida (11 cifre)';
	} else {
		if (!f.first_name?.trim()) err.first_name = 'Inserisci il nome';
		if (!f.last_name?.trim()) err.last_name = 'Inserisci il cognome';
	}

	if (!f.address?.trim()) err.address = "Inserisci l'indirizzo";
	if (!f.address_number?.trim()) err.address_number = 'Inserisci il numero civico';

	if (!f.postal_code?.trim()) err.postal_code = 'Inserisci il CAP';
	else if (!/^\d{5}$/.test(f.postal_code)) err.postal_code = 'Il CAP deve avere 5 cifre';

	if (!f.city?.trim()) err.city = 'Inserisci la città';
	if (!f.province?.trim()) err.province = 'Seleziona una provincia';

	if (!f.telephone_number?.trim()) err.telephone_number = 'Inserisci un numero di telefono';
	else if (!/^[+\d\s().-]{6,}$/.test(f.telephone_number)) err.telephone_number = 'Numero di telefono non valido';

	if (f.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.email)) err.email = 'Email non valida';

	errors.value = err;
	return Object.keys(err).length === 0;
};

const onPostalCodeInput = (e) => {
	form.value.postal_code = String(e.target.value || '').replace(/\D/g, '').slice(0, 5);
};

const onSubmit = () => {
	if (!validate()) return;

	const f = form.value;
	const fullName = isCompany.value
		? f.company_name.trim()
		: [f.first_name.trim(), f.last_name.trim()].filter(Boolean).join(' ');

	// Payload mappato sui campi backend (vedi UserAddressStoreRequest).
	// Profilo + dati azienda + label sono extra: per ora vanno in
	// `additional_information` come prefisso strutturato (gap backend).
	const extras = [];
	if (f.label) extras.push(`Etichetta: ${f.label}`);
	if (isCompany.value) {
		if (f.vat_number) extras.push(`P.IVA: ${f.vat_number}`);
		if (f.sdi_code) extras.push(`SDI: ${f.sdi_code}`);
	}
	const additional = [extras.join(' | '), f.additional_information].filter(Boolean).join(' — ');

	const payload = {
		type: f.type,
		name: fullName,
		address: f.address.trim(),
		address_number: f.address_number.trim(),
		number_type: 'civico',
		country: f.country || 'IT',
		city: f.city.trim(),
		postal_code: f.postal_code.trim(),
		province: f.province,
		telephone_number: f.telephone_number.trim(),
		email: f.email?.trim() || '',
		additional_information: additional,
		default: f.default ? 1 : 0,
		// Campi extra mantenuti per uso frontend (ignorati dal backend attuale)
		_meta: {
			profile: f.profile,
			label: f.label,
			first_name: f.first_name,
			last_name: f.last_name,
			company_name: f.company_name,
			vat_number: f.vat_number,
			sdi_code: f.sdi_code,
		},
	};

	emit('submit', payload);
};

const close = () => {
	emit('update:modelValue', false);
	emit('close');
};

const titleText = computed(() => (isEdit.value ? 'Modifica indirizzo' : 'Nuovo indirizzo'));
const submitLabel = computed(() => {
	if (props.submitting) return isEdit.value ? 'Salvataggio…' : 'Salvataggio…';
	return isEdit.value ? 'Salva modifiche' : 'Aggiungi indirizzo';
});
</script>

<template>
	<SfModal :model-value="modelValue" @update:model-value="(v) => emit('update:modelValue', v)" :title="titleText" size="lg" :persistent="submitting">
		<form class="sf-addr-form" @submit.prevent="onSubmit" novalidate>
			<!-- TIPO INDIRIZZO -->
			<fieldset class="sf-addr-fieldset">
				<legend class="sf-addr-legend">Tipo di indirizzo</legend>
				<div class="sf-addr-radio-group">
					<label :class="['sf-addr-radio', form.type === 'origin' ? 'sf-addr-radio--active sf-addr-radio--origin' : '']">
						<input v-model="form.type" type="radio" value="origin" />
						<span class="sf-addr-radio__icon">
							<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
								<path d="M12,2A7,7 0 0,1 19,9C19,14.25 12,22 12,22C12,22 5,14.25 5,9A7,7 0 0,1 12,2M12,4A5,5 0 0,0 7,9C7,10 7,12 12,18.71C17,12 17,10 17,9A5,5 0 0,0 12,4M12,6.5A2.5,2.5 0 0,1 14.5,9A2.5,2.5 0 0,1 12,11.5A2.5,2.5 0 0,1 9.5,9A2.5,2.5 0 0,1 12,6.5Z" />
							</svg>
						</span>
						<div>
							<strong>Partenza</strong>
							<small>Punto di ritiro</small>
						</div>
					</label>
					<label :class="['sf-addr-radio', form.type === 'destination' ? 'sf-addr-radio--active sf-addr-radio--dest' : '']">
						<input v-model="form.type" type="radio" value="destination" />
						<span class="sf-addr-radio__icon">
							<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
								<path d="M12,2L4.5,20.29L5.21,21L12,18L18.79,21L19.5,20.29L12,2Z" />
							</svg>
						</span>
						<div>
							<strong>Destinazione</strong>
							<small>Consegna pacco</small>
						</div>
					</label>
				</div>
				<p v-if="errors.type" class="sf-addr-error">{{ errors.type }}</p>
			</fieldset>

			<!-- ETICHETTA PERSONALE -->
			<div class="sf-addr-row">
				<label class="sf-addr-field">
					<span class="sf-addr-field__label">Etichetta personale *</span>
					<input
						v-model="form.label"
						type="text"
						class="sf-addr-input"
						placeholder="Es. Casa, Ufficio, Magazzino"
						maxlength="40"
						:aria-invalid="errors.label ? 'true' : 'false'"
					/>
					<span v-if="errors.label" class="sf-addr-error">{{ errors.label }}</span>
				</label>
			</div>

			<!-- PROFILO -->
			<fieldset class="sf-addr-fieldset">
				<legend class="sf-addr-legend">Profilo</legend>
				<div class="sf-addr-radio-group sf-addr-radio-group--compact">
					<label :class="['sf-addr-radio sf-addr-radio--mini', form.profile === 'private' ? 'sf-addr-radio--active' : '']">
						<input v-model="form.profile" type="radio" value="private" />
						<span>Privato</span>
					</label>
					<label :class="['sf-addr-radio sf-addr-radio--mini', form.profile === 'company' ? 'sf-addr-radio--active' : '']">
						<input v-model="form.profile" type="radio" value="company" />
						<span>Azienda</span>
					</label>
				</div>
			</fieldset>

			<!-- DATI AZIENDA / NOME COGNOME -->
			<div v-if="isCompany" class="sf-addr-row sf-addr-row--triple">
				<label class="sf-addr-field">
					<span class="sf-addr-field__label">Ragione sociale *</span>
					<input v-model="form.company_name" type="text" class="sf-addr-input" placeholder="Acme S.r.l." />
					<span v-if="errors.company_name" class="sf-addr-error">{{ errors.company_name }}</span>
				</label>
				<label class="sf-addr-field">
					<span class="sf-addr-field__label">Partita IVA *</span>
					<input v-model="form.vat_number" type="text" class="sf-addr-input" placeholder="12345678901" maxlength="13" />
					<span v-if="errors.vat_number" class="sf-addr-error">{{ errors.vat_number }}</span>
				</label>
				<label class="sf-addr-field">
					<span class="sf-addr-field__label">Codice SDI</span>
					<input v-model="form.sdi_code" type="text" class="sf-addr-input" placeholder="XXXXXXX" maxlength="7" />
				</label>
			</div>
			<div v-else class="sf-addr-row sf-addr-row--double">
				<label class="sf-addr-field">
					<span class="sf-addr-field__label">Nome *</span>
					<input v-model="form.first_name" type="text" class="sf-addr-input" placeholder="Mario" />
					<span v-if="errors.first_name" class="sf-addr-error">{{ errors.first_name }}</span>
				</label>
				<label class="sf-addr-field">
					<span class="sf-addr-field__label">Cognome *</span>
					<input v-model="form.last_name" type="text" class="sf-addr-input" placeholder="Rossi" />
					<span v-if="errors.last_name" class="sf-addr-error">{{ errors.last_name }}</span>
				</label>
			</div>

			<!-- INDIRIZZO + CIVICO -->
			<div class="sf-addr-row sf-addr-row--street">
				<label class="sf-addr-field">
					<span class="sf-addr-field__label">Indirizzo *</span>
					<input v-model="form.address" type="text" class="sf-addr-input" placeholder="Via Roma" />
					<span v-if="errors.address" class="sf-addr-error">{{ errors.address }}</span>
				</label>
				<label class="sf-addr-field">
					<span class="sf-addr-field__label">Civico *</span>
					<input v-model="form.address_number" type="text" class="sf-addr-input" placeholder="10" />
					<span v-if="errors.address_number" class="sf-addr-error">{{ errors.address_number }}</span>
				</label>
			</div>

			<!-- CAP / CITTA / PROVINCIA -->
			<div class="sf-addr-row sf-addr-row--triple">
				<label class="sf-addr-field">
					<span class="sf-addr-field__label">CAP *</span>
					<input
						:value="form.postal_code"
						@input="onPostalCodeInput"
						type="text"
						class="sf-addr-input"
						placeholder="00100"
						maxlength="5"
						inputmode="numeric"
						pattern="[0-9]{5}"
					/>
					<span v-if="errors.postal_code" class="sf-addr-error">{{ errors.postal_code }}</span>
				</label>
				<label class="sf-addr-field">
					<span class="sf-addr-field__label">Città *</span>
					<input v-model="form.city" type="text" class="sf-addr-input" placeholder="Roma" />
					<span v-if="errors.city" class="sf-addr-error">{{ errors.city }}</span>
				</label>
				<label class="sf-addr-field">
					<span class="sf-addr-field__label">Provincia *</span>
					<select v-model="form.province" class="sf-addr-input sf-addr-input--select">
						<option value="" disabled>Seleziona…</option>
						<option v-for="prov in provinceList" :key="prov" :value="prov">{{ prov }}</option>
					</select>
					<span v-if="errors.province" class="sf-addr-error">{{ errors.province }}</span>
				</label>
			</div>

			<!-- TELEFONO + EMAIL -->
			<div class="sf-addr-row sf-addr-row--double">
				<label class="sf-addr-field">
					<span class="sf-addr-field__label">Telefono *</span>
					<input v-model="form.telephone_number" type="tel" class="sf-addr-input" placeholder="+39 333 0000000" inputmode="tel" />
					<span v-if="errors.telephone_number" class="sf-addr-error">{{ errors.telephone_number }}</span>
				</label>
				<label class="sf-addr-field">
					<span class="sf-addr-field__label">Email</span>
					<input v-model="form.email" type="email" class="sf-addr-input" placeholder="mario@esempio.it" />
					<span v-if="errors.email" class="sf-addr-error">{{ errors.email }}</span>
				</label>
			</div>

			<!-- INFO AGGIUNTIVE -->
			<label class="sf-addr-field">
				<span class="sf-addr-field__label">Note aggiuntive <small>(scala, piano, citofono…)</small></span>
				<input v-model="form.additional_information" type="text" class="sf-addr-input" placeholder="Es. Scala B, piano 3, citofono Rossi" />
			</label>

			<!-- DEFAULT -->
			<label class="sf-addr-checkbox">
				<input v-model="form.default" type="checkbox" />
				<span>Imposta come predefinito</span>
			</label>

			<!-- ERRORE SERVER -->
			<div v-if="serverError" role="alert" class="sf-addr-server-error">
				<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
					<path d="M11,15H13V17H11V15M11,7H13V13H11V7M12,2C6.47,2 2,6.5 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z" />
				</svg>
				<span>{{ serverError }}</span>
			</div>
		</form>

		<template #footer>
			<button type="button" class="sf-addr-btn sf-addr-btn--ghost" :disabled="submitting" @click="close">Annulla</button>
			<button type="button" class="sf-addr-btn sf-addr-btn--primary" :disabled="submitting" @click="onSubmit">
				<svg v-if="submitting" class="sf-addr-spin" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
					<path d="M12,4V2A10,10 0 0,0 2,12H4A8,8 0 0,1 12,4Z" />
				</svg>
				{{ submitLabel }}
			</button>
		</template>
	</SfModal>
</template>
