<script setup>
const props = defineProps({
	modelValue: { type: Object, required: true },
	loading: { type: String, default: null },
});

const emit = defineEmits(['update:modelValue', 'submit', 'cancel']);

const updateField = (key, value) => {
	emit('update:modelValue', { ...props.modelValue, [key]: value });
};

const billingSameAsShipping = ref(false);
const showCompanyFields = computed(() => props.modelValue.user_type === 'commerciante');

const inputClass = 'form-input';

const accountTypeOptions = [
	{ value: 'privato', label: 'Privato' },
	{ value: 'commerciante', label: 'Azienda' },
];
</script>

<template>
	<form class="w-full space-y-4" aria-labelledby="sf-profilo-form-title" @submit.prevent="emit('submit')">
		<h2 id="sf-profilo-form-title" class="sr-only">Modifica profilo</h2>

		<section class="rounded-card border border-brand-border bg-brand-card px-4 py-4 shadow-sf tablet:px-5 tablet:py-[18px]" aria-labelledby="sf-profilo-tipo">
			<div class="grid gap-4">
				<div class="flex max-w-[34rem] items-start gap-3">
					<div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-primary/[0.08]">
						<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2">
							<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
							<circle cx="12" cy="7" r="4" />
						</svg>
					</div>
					<div class="min-w-0 flex-1 space-y-1.5">
						<div class="space-y-1">
							<h3 id="sf-profilo-tipo" class="font-display text-[15px] font-extrabold text-brand-text">Profilo</h3>
							<p class="text-xs leading-snug text-brand-text-muted">
								Aggiorna i dati che usi davvero più spesso. Il resto resta ordinato sotto.
							</p>
						</div>
					</div>
				</div>
				<div class="flex flex-wrap gap-2 pl-12" role="radiogroup" aria-labelledby="sf-profilo-tipo">
					<label
						v-for="opt in accountTypeOptions"
						:key="opt.value"
						:class="[
							'flex min-h-9 min-w-[86px] cursor-pointer items-center justify-center rounded-full border px-3.5 py-1.5 text-[11px] font-bold transition-all',
							modelValue.user_type === opt.value
								? 'border-brand-primary bg-brand-primary text-white shadow-[0_10px_22px_rgba(9,88,102,0.16)]'
								: 'border-brand-border bg-white text-brand-text hover:border-brand-primary hover:bg-brand-bg-alt',
						]">
						<input
							type="radio"
							:value="opt.value"
							:checked="modelValue.user_type === opt.value"
							class="sr-only"
							@change="updateField('user_type', opt.value)">
						{{ opt.label }}
					</label>
				</div>
			</div>
		</section>

		<section class="rounded-card border border-brand-border bg-brand-card px-4 py-4 shadow-sf tablet:px-5 tablet:py-[18px]" aria-labelledby="sf-profilo-dati">
			<div class="mb-4 flex items-center gap-2.5">
				<div class="flex h-[34px] w-[34px] shrink-0 items-center justify-center rounded-xl bg-brand-primary/[0.08]">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2">
						<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
						<circle cx="12" cy="7" r="4" />
					</svg>
				</div>
				<div>
					<h3 id="sf-profilo-dati" class="font-display text-[15px] font-extrabold text-brand-text">Dati personali</h3>
					<p class="text-xs text-brand-text-muted">I riferimenti principali dell'account.</p>
				</div>
			</div>

			<div class="grid grid-cols-1 gap-4 tablet:grid-cols-2">
				<div>
					<label for="sf-profilo-name" class="form-label">Nome *</label>
					<input id="sf-profilo-name" type="text" autocomplete="given-name" :value="modelValue.name" :class="inputClass" required @input="updateField('name', $event.target.value)">
				</div>
				<div>
					<label for="sf-profilo-surname" class="form-label">Cognome</label>
					<input id="sf-profilo-surname" type="text" autocomplete="family-name" :value="modelValue.surname" :class="inputClass" @input="updateField('surname', $event.target.value)">
				</div>
				<div>
					<label for="sf-profilo-email" class="form-label">Email *</label>
					<input id="sf-profilo-email" type="email" autocomplete="email" :value="modelValue.email" :class="inputClass" required @input="updateField('email', $event.target.value)">
				</div>
				<div>
					<label for="sf-profilo-tel" class="form-label">Telefono</label>
					<input
						id="sf-profilo-tel"
						type="tel"
						autocomplete="tel"
						inputmode="tel"
						:value="modelValue.telephone_number"
						:class="inputClass"
						@input="updateField('telephone_number', $event.target.value)">
				</div>
			</div>
		</section>

		<section class="rounded-card border border-brand-border bg-brand-card px-4 py-4 shadow-sf tablet:px-5 tablet:py-[18px]" aria-labelledby="sf-profilo-azienda">
			<div class="mb-4 flex items-center gap-2.5">
				<div class="flex h-[34px] w-[34px] shrink-0 items-center justify-center rounded-xl bg-brand-accent/[0.08]">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#E44203" stroke-width="2">
						<path d="M3 21h18" />
						<path d="M5 21V7l8-4 6 4v14" />
						<path d="M9 9h.01" />
						<path d="M9 13h.01" />
						<path d="M9 17h.01" />
						<path d="M15 13h.01" />
						<path d="M15 17h.01" />
					</svg>
				</div>
				<div>
					<h3 id="sf-profilo-azienda" class="font-display text-[15px] font-extrabold text-brand-text">Azienda</h3>
					<p class="text-xs text-brand-text-muted">Visibile solo per i profili aziendali.</p>
				</div>
			</div>

			<div v-if="showCompanyFields" class="space-y-4">
				<div class="grid grid-cols-1 gap-4 tablet:grid-cols-2">
					<div>
						<label for="sf-profilo-company" class="form-label">Ragione Sociale</label>
						<input
							id="sf-profilo-company"
							type="text"
							autocomplete="organization"
							:value="modelValue.company_name"
							placeholder="Nome azienda"
							:class="inputClass"
							@input="updateField('company_name', $event.target.value)">
					</div>
					<div>
						<label for="sf-profilo-vat" class="form-label">Partita IVA</label>
						<input
							id="sf-profilo-vat"
							type="text"
							:value="modelValue.vat_number"
							placeholder="IT12345678901"
							:class="inputClass"
							@input="updateField('vat_number', $event.target.value)">
					</div>
					<div>
						<label for="sf-profilo-cf" class="form-label">Codice Fiscale</label>
						<input
							id="sf-profilo-cf"
							type="text"
							:value="modelValue.fiscal_code"
							placeholder="RSSMRA80A01H501U"
							:class="inputClass"
							@input="updateField('fiscal_code', $event.target.value)">
					</div>
					<div>
						<label for="sf-profilo-pec" class="form-label">PEC</label>
						<input
							id="sf-profilo-pec"
							type="email"
							:value="modelValue.pec"
							placeholder="azienda@pec.it"
							:class="inputClass"
							@input="updateField('pec', $event.target.value)">
					</div>
				</div>
				<div class="max-w-[240px]">
					<label for="sf-profilo-sdi" class="form-label">Codice SDI</label>
					<input
						id="sf-profilo-sdi"
						type="text"
						:value="modelValue.sdi_code"
						placeholder="0000000"
						maxlength="7"
						:class="inputClass"
						@input="updateField('sdi_code', $event.target.value)">
				</div>
			</div>

			<div
				v-else
				role="note"
				class="rounded-control border border-dashed border-brand-primary/15 bg-brand-primary/[0.03] px-4 py-3.5 text-[13px] leading-snug text-brand-text-muted">
				I campi aziendali si attivano solo quando selezioni <strong class="font-bold text-brand-text">Azienda</strong>.
			</div>
		</section>

		<section class="rounded-card border border-brand-border bg-brand-card px-4 py-4 shadow-sf tablet:px-5 tablet:py-[18px]" aria-labelledby="sf-profilo-fatt">
			<div class="mb-4 flex items-center gap-2.5">
				<div class="flex h-[34px] w-[34px] shrink-0 items-center justify-center rounded-xl bg-brand-primary/[0.08]">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2">
						<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
						<path d="M3.29 7 12 12l8.71-5" />
						<path d="M12 22V12" />
					</svg>
				</div>
				<div class="min-w-0 flex-1">
					<h3 id="sf-profilo-fatt" class="font-display text-[15px] font-extrabold text-brand-text">Fatturazione</h3>
					<p class="text-xs text-brand-text-muted">Mantieni separati solo i dati davvero diversi.</p>
				</div>
			</div>

			<label class="mb-4 inline-flex cursor-pointer items-center gap-2.5">
				<input v-model="billingSameAsShipping" type="checkbox" class="h-[18px] w-[18px] cursor-pointer rounded accent-brand-primary">
				<span class="text-[13px] font-semibold text-brand-text">Uguale ai dati di spedizione</span>
			</label>

			<template v-if="!billingSameAsShipping">
				<div class="grid grid-cols-1 gap-4 tablet:grid-cols-2">
					<div class="tablet:col-span-2">
						<label for="sf-profilo-billname" class="form-label">Intestatario fatturazione</label>
						<input
							id="sf-profilo-billname"
							type="text"
							autocomplete="billing name"
							:value="modelValue.billing_name"
							placeholder="Nome o Ragione Sociale"
							:class="inputClass"
							@input="updateField('billing_name', $event.target.value)">
					</div>
					<div class="tablet:col-span-2">
						<label for="sf-profilo-billaddr" class="form-label">Indirizzo fatturazione</label>
						<input
							id="sf-profilo-billaddr"
							type="text"
							autocomplete="billing street-address"
							:value="modelValue.billing_address"
							placeholder="Via Roma 10"
							:class="inputClass"
							@input="updateField('billing_address', $event.target.value)">
					</div>
					<div>
						<label for="sf-profilo-billcity" class="form-label">Città</label>
						<input
							id="sf-profilo-billcity"
							type="text"
							autocomplete="billing address-level2"
							:value="modelValue.billing_city"
							placeholder="Roma"
							:class="inputClass"
							@input="updateField('billing_city', $event.target.value)">
					</div>
					<div class="grid grid-cols-2 gap-4">
						<div>
							<label for="sf-profilo-billcap" class="form-label">CAP</label>
							<input
								id="sf-profilo-billcap"
								type="text"
								autocomplete="billing postal-code"
								inputmode="numeric"
								:value="modelValue.billing_postal_code"
								placeholder="00100"
								:class="inputClass"
								@input="updateField('billing_postal_code', $event.target.value)">
						</div>
						<div>
							<label for="sf-profilo-billprov" class="form-label">Provincia</label>
							<input
								id="sf-profilo-billprov"
								type="text"
								autocomplete="billing address-level1"
								maxlength="2"
								:value="modelValue.billing_province"
								placeholder="RM"
								:class="inputClass"
								@input="updateField('billing_province', $event.target.value)">
						</div>
					</div>
				</div>
			</template>
		</section>

		<section class="rounded-card border border-brand-border bg-brand-card px-4 py-4 shadow-sf tablet:px-5 tablet:py-[18px]" aria-labelledby="sf-profilo-sicurezza">
			<div class="mb-4 flex items-center gap-2.5">
				<div class="flex h-[34px] w-[34px] shrink-0 items-center justify-center rounded-xl bg-brand-primary/[0.08]">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2">
						<rect x="3" y="11" width="18" height="10" rx="2" />
						<path d="M7 11V7a5 5 0 0 1 10 0v4" />
					</svg>
				</div>
				<div>
					<h3 id="sf-profilo-sicurezza" class="font-display text-[15px] font-extrabold text-brand-text">Sicurezza</h3>
					<p class="text-xs text-brand-text-muted">Compila la password solo se la vuoi cambiare.</p>
				</div>
			</div>

			<div class="grid grid-cols-1 gap-4 tablet:grid-cols-2">
				<div>
					<label for="sf-profilo-pwd" class="form-label">Nuova password</label>
					<input
						id="sf-profilo-pwd"
						type="password"
						autocomplete="new-password"
						aria-describedby="sf-profilo-pwd-help"
						:value="modelValue.password"
						placeholder="Lascia vuoto per mantenere"
						minlength="8"
						pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d).{8,}$"
						title="Minimo 8 caratteri, almeno una maiuscola, una minuscola e un numero"
						:class="inputClass"
						@input="updateField('password', $event.target.value)">
					<p id="sf-profilo-pwd-help" class="mt-1 text-[11px] text-brand-text-muted">
						Minimo 8 caratteri, con almeno una maiuscola, una minuscola e un numero.
					</p>
				</div>
				<div>
					<label for="sf-profilo-pwd2" class="form-label">Conferma password</label>
					<input
						id="sf-profilo-pwd2"
						type="password"
						autocomplete="new-password"
						:value="modelValue.password_confirmation"
						placeholder="Conferma"
						minlength="8"
						:class="inputClass"
						@input="updateField('password_confirmation', $event.target.value)">
				</div>
			</div>
		</section>

		<div class="flex flex-col-reverse gap-3 pt-2 tablet:flex-row tablet:justify-end">
			<SfButton variant="secondary" :disabled="!!loading" class="tablet:min-w-[160px]" @click.prevent="emit('cancel')">
				<template #leading>
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<line x1="18" y1="6" x2="6" y2="18" />
						<line x1="6" y1="6" x2="18" y2="18" />
					</svg>
				</template>
				Annulla
			</SfButton>
			<SfButton type="submit" variant="primary" :disabled="!!loading" :loading="!!loading" class="tablet:min-w-[200px]">
				<template #leading>
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
						<polyline points="17 21 17 13 7 13 7 21" />
						<polyline points="7 3 7 8 15 8" />
					</svg>
				</template>
				{{ loading ? 'Salvataggio...' : 'Salva modifiche' }}
			</SfButton>
		</div>
	</form>
</template>
