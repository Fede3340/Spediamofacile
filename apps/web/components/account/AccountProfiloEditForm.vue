<!--
  Form di modifica profilo: dati personali, aziendali, fatturazione, password.
  Props: modelValue, loading.
  Events: update:modelValue, submit, cancel.
-->
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
	<!--
	  Sprint UX 2026-04-18: rimosso wrapper esterno .sf-section-block (box grigio
	  ridondante che conteneva le card bianche). Il <form> e' ora il container
	  diretto: le <section class="sf-account-panel"> riempiono tutta la larghezza
	  dell'area content e mantengono spacing uniforme con space-y-[16px] (~16px gap).
	-->
	<form @submit.prevent="emit('submit')" class="sf-account-profile-edit w-full space-y-[16px]" aria-labelledby="sf-profilo-form-title">
				<h2 id="sf-profilo-form-title" class="sr-only">Modifica profilo</h2>
				<section class="sf-account-panel rounded-[16px] px-[16px] py-[16px] tablet:px-[20px] tablet:py-[18px]" aria-labelledby="sf-profilo-tipo">
					<div class="grid gap-[16px]">
						<div class="flex items-start gap-[12px] max-w-[34rem]">
							<div class="w-[36px] h-[36px] rounded-[12px] bg-[rgba(9,88,102,0.08)] flex items-center justify-center shrink-0">
							<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2">
								<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
								<circle cx="12" cy="7" r="4" />
							</svg>
						</div>
							<div class="min-w-0 flex-1 space-y-[5px]">
							<div class="space-y-[3px]">
								<h3 id="sf-profilo-tipo" class="font-montserrat text-[15px] font-[800] text-[var(--color-brand-text)]">Profilo</h3>
								<p class="text-[12px] leading-[1.45] text-[var(--color-brand-text-muted)]">
									Aggiorna i dati che usi davvero piu spesso. Il resto resta ordinato sotto.
								</p>
							</div>
							</div>
						</div>
						<div class="flex flex-wrap gap-[8px] pl-[48px]" role="radiogroup" aria-labelledby="sf-profilo-tipo">
							<label
								v-for="opt in accountTypeOptions"
								:key="opt.value"
								:class="[
									'flex min-h-[36px] min-w-[86px] items-center justify-center rounded-full border px-[14px] py-[6px] text-[11px] font-[700] transition-all cursor-pointer',
									modelValue.user_type === opt.value
										? 'border-[var(--color-brand-primary)] bg-[var(--color-brand-primary)] text-white shadow-[0_10px_22px_rgba(9,88,102,0.16)]'
										: 'border-[#D8E3E7] bg-white text-[var(--color-brand-text)] hover:border-[var(--color-brand-primary)] hover:bg-[#F8FCFD]',
								]">
								<input
									type="radio"
									:value="opt.value"
									:checked="modelValue.user_type === opt.value"
									@change="updateField('user_type', opt.value)"
									class="sr-only" />
								{{ opt.label }}
							</label>
						</div>
					</div>
				</section>

				<section class="sf-account-panel rounded-[16px] px-[16px] py-[16px] tablet:px-[20px] tablet:py-[18px]" aria-labelledby="sf-profilo-dati">
					<div class="flex items-center gap-[10px] mb-[16px]">
						<div class="w-[34px] h-[34px] rounded-[12px] bg-[rgba(9,88,102,0.08)] flex items-center justify-center shrink-0">
							<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2">
								<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
								<circle cx="12" cy="7" r="4" />
							</svg>
						</div>
						<div>
							<h3 id="sf-profilo-dati" class="font-montserrat text-[15px] font-[800] text-[var(--color-brand-text)]">Dati personali</h3>
							<p class="text-[12px] text-[var(--color-brand-text-muted)]">I riferimenti principali dell'account.</p>
						</div>
					</div>

					<div class="grid grid-cols-1 tablet:grid-cols-2 gap-[16px]">
						<div>
							<label for="sf-profilo-name" class="form-label">Nome *</label>
							<input id="sf-profilo-name" type="text" autocomplete="given-name" :value="modelValue.name" @input="updateField('name', $event.target.value)" :class="inputClass" required />
						</div>
						<div>
							<label for="sf-profilo-surname" class="form-label">Cognome</label>
							<input id="sf-profilo-surname" type="text" autocomplete="family-name" :value="modelValue.surname" @input="updateField('surname', $event.target.value)" :class="inputClass" />
						</div>
						<div>
							<label for="sf-profilo-email" class="form-label">Email *</label>
							<input id="sf-profilo-email" type="email" autocomplete="email" :value="modelValue.email" @input="updateField('email', $event.target.value)" :class="inputClass" required />
						</div>
						<div>
							<label for="sf-profilo-tel" class="form-label">Telefono</label>
							<input
								id="sf-profilo-tel"
								type="tel"
								autocomplete="tel"
								inputmode="tel"
								:value="modelValue.telephone_number"
								@input="updateField('telephone_number', $event.target.value)"
								:class="inputClass" />
						</div>
					</div>
				</section>

				<section class="sf-account-panel rounded-[16px] px-[16px] py-[16px] tablet:px-[20px] tablet:py-[18px]" aria-labelledby="sf-profilo-azienda">
					<div class="flex items-center gap-[10px] mb-[16px]">
						<div class="w-[34px] h-[34px] rounded-[12px] bg-[rgba(228,66,3,0.08)] flex items-center justify-center shrink-0">
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
							<h3 id="sf-profilo-azienda" class="font-montserrat text-[15px] font-[800] text-[var(--color-brand-text)]">Azienda</h3>
							<p class="text-[12px] text-[var(--color-brand-text-muted)]">Visibile solo per i profili aziendali.</p>
						</div>
					</div>

					<div v-if="showCompanyFields" class="space-y-[16px]">
						<div class="grid grid-cols-1 tablet:grid-cols-2 gap-[16px]">
							<div>
								<label for="sf-profilo-company" class="form-label">Ragione Sociale</label>
								<input
									id="sf-profilo-company"
									type="text"
									autocomplete="organization"
									:value="modelValue.company_name"
									@input="updateField('company_name', $event.target.value)"
									placeholder="Nome azienda"
									:class="inputClass" />
							</div>
							<div>
								<label for="sf-profilo-vat" class="form-label">Partita IVA</label>
								<input
									id="sf-profilo-vat"
									type="text"
									:value="modelValue.vat_number"
									@input="updateField('vat_number', $event.target.value)"
									placeholder="IT12345678901"
									:class="inputClass" />
							</div>
							<div>
								<label for="sf-profilo-cf" class="form-label">Codice Fiscale</label>
								<input
									id="sf-profilo-cf"
									type="text"
									:value="modelValue.fiscal_code"
									@input="updateField('fiscal_code', $event.target.value)"
									placeholder="RSSMRA80A01H501U"
									:class="inputClass" />
							</div>
							<div>
								<label for="sf-profilo-pec" class="form-label">PEC</label>
								<input
									id="sf-profilo-pec"
									type="email"
									:value="modelValue.pec"
									@input="updateField('pec', $event.target.value)"
									placeholder="azienda@pec.it"
									:class="inputClass" />
							</div>
						</div>
						<div class="max-w-[240px]">
							<label for="sf-profilo-sdi" class="form-label">Codice SDI</label>
							<input
								id="sf-profilo-sdi"
								type="text"
								:value="modelValue.sdi_code"
								@input="updateField('sdi_code', $event.target.value)"
								placeholder="0000000"
								maxlength="7"
								:class="inputClass" />
						</div>
					</div>

					<div
						v-else
						role="note"
						class="rounded-[14px] border border-dashed border-[rgba(9,88,102,0.16)] bg-[rgba(9,88,102,0.03)] px-[16px] py-[14px] text-[13px] leading-[1.55] text-[var(--color-brand-text-muted)]">
						I campi aziendali si attivano solo quando selezioni <strong class="text-[var(--color-brand-text)] font-[700]">Azienda</strong>.
					</div>
				</section>

				<section class="sf-account-panel rounded-[16px] px-[16px] py-[16px] tablet:px-[20px] tablet:py-[18px]" aria-labelledby="sf-profilo-fatt">
					<div class="flex items-center gap-[10px] mb-[16px]">
						<div class="w-[34px] h-[34px] rounded-[12px] bg-[rgba(9,88,102,0.08)] flex items-center justify-center shrink-0">
							<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2">
								<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
								<path d="M3.29 7 12 12l8.71-5" />
								<path d="M12 22V12" />
							</svg>
						</div>
						<div class="min-w-0 flex-1">
							<h3 id="sf-profilo-fatt" class="font-montserrat text-[15px] font-[800] text-[var(--color-brand-text)]">Fatturazione</h3>
							<p class="text-[12px] text-[var(--color-brand-text-muted)]">Mantieni separati solo i dati davvero diversi.</p>
						</div>
					</div>

					<label class="inline-flex items-center gap-[10px] cursor-pointer mb-[16px]">
						<input type="checkbox" v-model="billingSameAsShipping" class="w-[18px] h-[18px] accent-[var(--color-brand-primary)] cursor-pointer rounded-[4px]" />
						<span class="text-[13px] font-[600] text-[var(--color-brand-text)]">Uguale ai dati di spedizione</span>
					</label>

					<template v-if="!billingSameAsShipping">
						<div class="grid grid-cols-1 tablet:grid-cols-2 gap-[16px]">
							<div class="tablet:col-span-2">
								<label for="sf-profilo-billname" class="form-label">Intestatario fatturazione</label>
								<input
									id="sf-profilo-billname"
									type="text"
									autocomplete="billing name"
									:value="modelValue.billing_name"
									@input="updateField('billing_name', $event.target.value)"
									placeholder="Nome o Ragione Sociale"
									:class="inputClass" />
							</div>
							<div class="tablet:col-span-2">
								<label for="sf-profilo-billaddr" class="form-label">Indirizzo fatturazione</label>
								<input
									id="sf-profilo-billaddr"
									type="text"
									autocomplete="billing street-address"
									:value="modelValue.billing_address"
									@input="updateField('billing_address', $event.target.value)"
									placeholder="Via Roma 10"
									:class="inputClass" />
							</div>
							<div>
								<label for="sf-profilo-billcity" class="form-label">Citta</label>
								<input
									id="sf-profilo-billcity"
									type="text"
									autocomplete="billing address-level2"
									:value="modelValue.billing_city"
									@input="updateField('billing_city', $event.target.value)"
									placeholder="Roma"
									:class="inputClass" />
							</div>
							<div class="grid grid-cols-2 gap-[16px]">
								<div>
									<label for="sf-profilo-billcap" class="form-label">CAP</label>
									<input
										id="sf-profilo-billcap"
										type="text"
										autocomplete="billing postal-code"
										inputmode="numeric"
										:value="modelValue.billing_postal_code"
										@input="updateField('billing_postal_code', $event.target.value)"
										placeholder="00100"
										:class="inputClass" />
								</div>
								<div>
									<label for="sf-profilo-billprov" class="form-label">Provincia</label>
									<input
										id="sf-profilo-billprov"
										type="text"
										autocomplete="billing address-level1"
										maxlength="2"
										:value="modelValue.billing_province"
										@input="updateField('billing_province', $event.target.value)"
										placeholder="RM"
										:class="inputClass" />
								</div>
							</div>
						</div>
					</template>
				</section>

				<section class="sf-account-panel rounded-[16px] px-[16px] py-[16px] tablet:px-[20px] tablet:py-[18px]" aria-labelledby="sf-profilo-sicurezza">
					<div class="flex items-center gap-[10px] mb-[16px]">
						<div class="w-[34px] h-[34px] rounded-[12px] bg-[rgba(9,88,102,0.08)] flex items-center justify-center shrink-0">
							<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2">
								<rect x="3" y="11" width="18" height="10" rx="2" />
								<path d="M7 11V7a5 5 0 0 1 10 0v4" />
							</svg>
						</div>
						<div>
							<h3 id="sf-profilo-sicurezza" class="font-montserrat text-[15px] font-[800] text-[var(--color-brand-text)]">Sicurezza</h3>
							<p class="text-[12px] text-[var(--color-brand-text-muted)]">Compila la password solo se la vuoi cambiare.</p>
						</div>
					</div>

					<div class="grid grid-cols-1 tablet:grid-cols-2 gap-[16px]">
						<div>
							<label for="sf-profilo-pwd" class="form-label">Nuova password</label>
							<input
								id="sf-profilo-pwd"
								type="password"
								autocomplete="new-password"
								aria-describedby="sf-profilo-pwd-help"
								:value="modelValue.password"
								@input="updateField('password', $event.target.value)"
								placeholder="Lascia vuoto per mantenere"
								minlength="8"
								pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d).{8,}$"
								title="Minimo 8 caratteri, almeno una maiuscola, una minuscola e un numero"
								:class="inputClass" />
							<p id="sf-profilo-pwd-help" class="mt-[4px] text-[11px] text-[var(--color-brand-text-muted)]">
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
								@input="updateField('password_confirmation', $event.target.value)"
								placeholder="Conferma"
								minlength="8"
								:class="inputClass" />
						</div>
					</div>
				</section>

				<div class="flex flex-col-reverse gap-[12px] tablet:flex-row tablet:justify-end pt-[8px]">
					<button
						type="button"
						@click.prevent="emit('cancel')"
						:disabled="!!loading"
						class="sf-flow-cta sf-flow-cta--secondary sf-flow-cta--compact disabled:opacity-60 disabled:cursor-not-allowed tablet:min-w-[160px]">
						<svg aria-hidden="true"
							xmlns="http://www.w3.org/2000/svg"
							width="16"
							height="16"
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							stroke-width="2">
							<line x1="18" y1="6" x2="6" y2="18" />
							<line x1="6" y1="6" x2="18" y2="18" />
						</svg>
						Annulla
					</button>
					<button
						type="submit"
						:disabled="!!loading"
						class="sf-flow-cta sf-flow-cta--primary sf-flow-cta--compact disabled:opacity-60 disabled:cursor-not-allowed tablet:min-w-[200px]">
						<svg aria-hidden="true"
							xmlns="http://www.w3.org/2000/svg"
							width="16"
							height="16"
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							stroke-width="2">
							<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
							<polyline points="17 21 17 13 7 13 7 21" />
							<polyline points="7 3 7 8 15 8" />
						</svg>
						{{ loading ? 'Salvataggio...' : 'Salva modifiche' }}
					</button>
				</div>
			</form>
</template>
