<script setup>
import { provinceList } from '~/utils/location';

const props = defineProps({
	modelValue: { type: Boolean, default: false },
	mode: { type: String, default: 'create' },
	initial: { type: Object, default: null },
	submitting: { type: Boolean, default: false },
	serverError: { type: String, default: null },
});

const emit = defineEmits(['update:modelValue', 'submit', 'close']);

const emptyForm = () => ({
	type: 'destination',
	profile: 'private',
	label: '',
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

const validate = () => {
	const err = {};
	const f = form.value;

	if (!f.type) err.type = 'Seleziona un tipo';
	if (!f.label?.trim()) err.label = "Inserisci un'etichetta (es. Casa, Ufficio)";

	if (isCompany.value) {
		if (!f.company_name?.trim()) err.company_name = 'Inserisci la ragione sociale';
		if (!f.vat_number?.trim()) err.vat_number = 'Inserisci la Partita IVA';
		else if (!/^[A-Z]{0,2}\d{11}$/i.test(f.vat_number.replace(/\s/g, ''))) err.vat_number = 'Partita IVA non valida (11 cifre)';
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

	if (f.email && !/^[^\s@]{1,64}@[^\s@.]{1,253}(?:\.[^\s@.]{1,63})+$/.test(f.email)) err.email = 'Email non valida';

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

const inputClass = 'w-full rounded-xl border border-brand-border bg-brand-bg-alt px-3.5 py-2.5 text-sm text-brand-text transition focus:border-brand-primary focus:bg-white focus:shadow-[0_0_0_3px_rgba(9,88,102,0.1)] focus:outline-none aria-[invalid=true]:border-status-failed-fg';
const labelClass = 'text-[0.75rem] font-bold uppercase tracking-wide text-brand-text-secondary';
const errorClass = 'text-[0.6875rem] text-status-failed-fg';
</script>

<template>
	<SfModal :model-value="modelValue" :title="titleText" size="lg" :persistent="submitting" @update:model-value="(v) => emit('update:modelValue', v)">
		<form class="flex flex-col gap-4 font-sans" novalidate @submit.prevent="onSubmit">
			<fieldset class="m-0 grid gap-2 border-0 p-0">
				<legend :class="['mb-1 px-0', labelClass]">Tipo di indirizzo</legend>
				<div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
					<label
						:class="[
							'flex cursor-pointer items-center gap-3 rounded-card border bg-white p-3 transition',
							form.type === 'origin'
								? 'border-brand-primary bg-brand-primary/[0.06] text-brand-primary'
								: 'border-brand-border hover:border-brand-primary/30',
						]">
						<input v-model="form.type" type="radio" value="origin" class="sr-only">
						<span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-bg-alt text-current">
							<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
								<path d="M12,2A7,7 0 0,1 19,9C19,14.25 12,22 12,22C12,22 5,14.25 5,9A7,7 0 0,1 12,2M12,4A5,5 0 0,0 7,9C7,10 7,12 12,18.71C17,12 17,10 17,9A5,5 0 0,0 12,4M12,6.5A2.5,2.5 0 0,1 14.5,9A2.5,2.5 0 0,1 12,11.5A2.5,2.5 0 0,1 9.5,9A2.5,2.5 0 0,1 12,6.5Z" />
							</svg>
						</span>
						<div>
							<strong class="block text-sm font-semibold text-brand-text">Partenza</strong>
							<small class="block text-xs text-brand-text-muted">Punto di ritiro</small>
						</div>
					</label>
					<label
						:class="[
							'flex cursor-pointer items-center gap-3 rounded-card border bg-white p-3 transition',
							form.type === 'destination'
								? 'border-brand-accent bg-brand-accent/[0.06] text-brand-accent-dark'
								: 'border-brand-border hover:border-brand-primary/30',
						]">
						<input v-model="form.type" type="radio" value="destination" class="sr-only">
						<span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-bg-alt text-current">
							<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
								<path d="M12,2L4.5,20.29L5.21,21L12,18L18.79,21L19.5,20.29L12,2Z" />
							</svg>
						</span>
						<div>
							<strong class="block text-sm font-semibold text-brand-text">Destinazione</strong>
							<small class="block text-xs text-brand-text-muted">Consegna pacco</small>
						</div>
					</label>
				</div>
				<p v-if="errors.type" :class="errorClass">{{ errors.type }}</p>
			</fieldset>

			<div class="grid gap-3">
				<label class="grid gap-1">
					<span :class="labelClass">Etichetta personale *</span>
					<input
						v-model="form.label"
						type="text"
						:class="inputClass"
						placeholder="Es. Casa, Ufficio, Magazzino"
						maxlength="40"
						:aria-invalid="errors.label ? 'true' : 'false'">
					<span v-if="errors.label" :class="errorClass">{{ errors.label }}</span>
				</label>
			</div>

			<fieldset class="m-0 grid gap-2 border-0 p-0">
				<legend :class="['mb-1 px-0', labelClass]">Profilo</legend>
				<div class="grid max-w-[280px] grid-cols-2 gap-2">
					<label
						:class="[
							'flex cursor-pointer items-center justify-center gap-2 rounded-card border bg-white px-3 py-2 text-sm font-semibold transition',
							form.profile === 'private' ? 'border-brand-primary bg-brand-primary/[0.06] text-brand-primary' : 'border-brand-border text-brand-text hover:border-brand-primary/30',
						]">
						<input v-model="form.profile" type="radio" value="private" class="sr-only">
						<span>Privato</span>
					</label>
					<label
						:class="[
							'flex cursor-pointer items-center justify-center gap-2 rounded-card border bg-white px-3 py-2 text-sm font-semibold transition',
							form.profile === 'company' ? 'border-brand-primary bg-brand-primary/[0.06] text-brand-primary' : 'border-brand-border text-brand-text hover:border-brand-primary/30',
						]">
						<input v-model="form.profile" type="radio" value="company" class="sr-only">
						<span>Azienda</span>
					</label>
				</div>
			</fieldset>

			<div v-if="isCompany" class="grid grid-cols-1 gap-3 md:grid-cols-3">
				<label class="grid gap-1">
					<span :class="labelClass">Ragione sociale *</span>
					<input v-model="form.company_name" type="text" :class="inputClass" placeholder="Acme S.r.l.">
					<span v-if="errors.company_name" :class="errorClass">{{ errors.company_name }}</span>
				</label>
				<label class="grid gap-1">
					<span :class="labelClass">Partita IVA *</span>
					<input v-model="form.vat_number" type="text" :class="inputClass" placeholder="12345678901" maxlength="13">
					<span v-if="errors.vat_number" :class="errorClass">{{ errors.vat_number }}</span>
				</label>
				<label class="grid gap-1">
					<span :class="labelClass">Codice SDI</span>
					<input v-model="form.sdi_code" type="text" :class="inputClass" placeholder="XXXXXXX" maxlength="7">
				</label>
			</div>
			<div v-else class="grid grid-cols-1 gap-3 md:grid-cols-2">
				<label class="grid gap-1">
					<span :class="labelClass">Nome *</span>
					<input v-model="form.first_name" type="text" :class="inputClass" placeholder="Mario">
					<span v-if="errors.first_name" :class="errorClass">{{ errors.first_name }}</span>
				</label>
				<label class="grid gap-1">
					<span :class="labelClass">Cognome *</span>
					<input v-model="form.last_name" type="text" :class="inputClass" placeholder="Rossi">
					<span v-if="errors.last_name" :class="errorClass">{{ errors.last_name }}</span>
				</label>
			</div>

			<div class="grid grid-cols-1 gap-3 md:grid-cols-[1fr_110px]">
				<label class="grid gap-1">
					<span :class="labelClass">Indirizzo *</span>
					<input v-model="form.address" type="text" :class="inputClass" placeholder="Via Roma">
					<span v-if="errors.address" :class="errorClass">{{ errors.address }}</span>
				</label>
				<label class="grid gap-1">
					<span :class="labelClass">Civico *</span>
					<input v-model="form.address_number" type="text" :class="inputClass" placeholder="10">
					<span v-if="errors.address_number" :class="errorClass">{{ errors.address_number }}</span>
				</label>
			</div>

			<div class="grid grid-cols-1 gap-3 md:grid-cols-3">
				<label class="grid gap-1">
					<span :class="labelClass">CAP *</span>
					<input
						:value="form.postal_code"
						type="text"
						:class="inputClass"
						placeholder="00100"
						maxlength="5"
						inputmode="numeric"
						pattern="[0-9]{5}"
						@input="onPostalCodeInput">
					<span v-if="errors.postal_code" :class="errorClass">{{ errors.postal_code }}</span>
				</label>
				<label class="grid gap-1">
					<span :class="labelClass">Città *</span>
					<input v-model="form.city" type="text" :class="inputClass" placeholder="Roma">
					<span v-if="errors.city" :class="errorClass">{{ errors.city }}</span>
				</label>
				<label class="grid gap-1">
					<span :class="labelClass">Provincia *</span>
					<select v-model="form.province" :class="[inputClass, 'appearance-none pr-9 bg-[length:14px] bg-no-repeat bg-[right_12px_center]']" style="background-image: url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23095866'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E&quot;);">
						<option value="" disabled>Seleziona…</option>
						<option v-for="prov in provinceList" :key="prov" :value="prov">{{ prov }}</option>
					</select>
					<span v-if="errors.province" :class="errorClass">{{ errors.province }}</span>
				</label>
			</div>

			<div class="grid grid-cols-1 gap-3 md:grid-cols-2">
				<label class="grid gap-1">
					<span :class="labelClass">Telefono *</span>
					<input v-model="form.telephone_number" type="tel" :class="inputClass" placeholder="+39 333 0000000" inputmode="tel">
					<span v-if="errors.telephone_number" :class="errorClass">{{ errors.telephone_number }}</span>
				</label>
				<label class="grid gap-1">
					<span :class="labelClass">Email</span>
					<input v-model="form.email" type="email" :class="inputClass" placeholder="mario@esempio.it">
					<span v-if="errors.email" :class="errorClass">{{ errors.email }}</span>
				</label>
			</div>

			<label class="grid gap-1">
				<span :class="labelClass">Note aggiuntive <small class="text-brand-text-muted normal-case">(scala, piano, citofono…)</small></span>
				<input v-model="form.additional_information" type="text" :class="inputClass" placeholder="Es. Scala B, piano 3, citofono Rossi">
			</label>

			<label class="flex cursor-pointer items-center gap-2.5 text-sm text-brand-text">
				<input v-model="form.default" type="checkbox" class="h-4 w-4 cursor-pointer accent-brand-primary">
				<span>Imposta come predefinito</span>
			</label>

			<div v-if="serverError" role="alert" class="flex items-center gap-2 rounded-card border border-status-failed-fg/30 bg-status-failed-bg p-3 text-[0.8125rem] text-status-failed-fg">
				<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="shrink-0">
					<path d="M11,15H13V17H11V15M11,7H13V13H11V7M12,2C6.47,2 2,6.5 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z" />
				</svg>
				<span>{{ serverError }}</span>
			</div>
		</form>

		<template #footer>
			<SfButton variant="secondary" :disabled="submitting" @click="close">Annulla</SfButton>
			<SfButton variant="primary" :disabled="submitting" :loading="submitting" @click="onSubmit">
				{{ submitLabel }}
			</SfButton>
		</template>
	</SfModal>
</template>
