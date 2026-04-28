<!--
  Form richiesta Partner Pro per utenti non ancora Pro.
  Props: proRequestStatus, proRequestForm, proRequestError, proRequestSuccess, proRequestLoading.
  Events: update:proRequestForm, submit.
-->
<script setup>
const props = defineProps({
	proRequestStatus: { type: Object, default: null },
	proRequestForm: { type: Object, required: true },
	proRequestError: { type: String, default: null },
	proRequestSuccess: { type: Boolean, default: false },
	proRequestLoading: { type: Boolean, default: false },
	proRequestStatusLoading: { type: Boolean, default: false },
	canSubmit: { type: Boolean, default: true },
});

const emit = defineEmits(['update:proRequestForm', 'submit']);

const updateField = (key, value) => {
	emit('update:proRequestForm', { ...props.proRequestForm, [key]: value });
};

const statusType = computed(() => props.proRequestStatus?.status || null);
const hasRequest = computed(() => props.proRequestStatus?.has_request);
const isLocked = computed(() => hasRequest.value && ['pending', 'approved'].includes(String(statusType.value || '')));
const submitDisabled = computed(() => props.proRequestLoading || !props.canSubmit || props.proRequestStatusLoading);

const heroTitle = computed(() => {
	switch (String(statusType.value || '')) {
		case 'pending':
			return 'Richiesta in revisione';
		case 'approved':
			return 'Accesso approvato';
		case 'rejected':
			return 'Aggiorna e reinvia';
		default:
			return 'Attiva Partner Pro';
	}
});

const heroDescription = computed(() => {
	switch (String(statusType.value || '')) {
		case 'pending':
			return 'La richiesta e gia in verifica. Partner Pro sblocca inviti tracciati, commissioni e prelievi in un unico flusso ordinato.';
		case 'approved':
			return 'La richiesta e stata approvata. Ti basta aggiornare la sessione per entrare nella dashboard Partner Pro completa.';
		case 'rejected':
			return 'Puoi correggere i dati aziendali e inviare una nuova richiesta per riattivare il processo.';
		default:
			return 'Invita clienti, accumula commissioni tracciate e gestisci il saldo da una dashboard dedicata, coerente con il resto dell account.';
	}
});

const formHeading = computed(() => (isLocked.value ? 'Richiesta registrata' : 'Richiedi accesso'));
const formDescription = computed(() => {
	if (isLocked.value) {
		return statusType.value === 'approved'
			? 'L accesso e gia stato approvato. Qui sotto resta il riepilogo dei dati inviati.'
			: 'Qui sotto resta il riepilogo della richiesta attuale: non serve un nuovo invio.';
	}

	return 'Inserisci i dati aziendali essenziali. Il messaggio e facoltativo ma utile se vuoi aggiungere contesto commerciale.';
});
</script>

<template>
	<div class="grid grid-cols-1 desktop:grid-cols-[minmax(0,1fr)_420px] gap-[18px] desktop:gap-[20px]">
		<div class="sf-account-panel rounded-[16px] p-[18px] tablet:p-[20px] desktop:p-[22px] h-full">
			<div class="flex flex-col tablet:flex-row tablet:items-start gap-[16px]">
				<div class="sf-account-value-card__icon w-[64px] h-[64px] shrink-0 rounded-[16px]">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--color-brand-primary)" stroke-width="2">
						<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
					</svg>
				</div>
				<div class="min-w-0">
					<p class="text-[0.75rem] uppercase tracking-[0.08em] text-[var(--color-brand-text-secondary)] font-semibold">Partner Pro</p>
					<h2 class="font-montserrat text-[1.25rem] tablet:text-[1.4rem] desktop:text-[1.5rem] font-[800] text-[var(--color-brand-text)] mt-[6px]">
						{{ heroTitle }}
					</h2>
					<p class="text-[var(--color-brand-text-secondary)] text-[0.875rem] tablet:text-[0.9375rem] max-w-[620px] leading-[1.6] mt-[8px]">
						{{ heroDescription }}
					</p>
				</div>
			</div>

			<div class="sf-account-stat-grid mt-[18px]">
				<div class="sf-account-stat-card">
					<p class="text-[0.6875rem] uppercase tracking-[0.8px] text-[var(--color-brand-text-secondary)] font-medium">Inviti</p>
					<p class="text-[0.875rem] font-semibold text-[var(--color-brand-text)] mt-[6px]">Link e codice dedicato</p>
				</div>
				<div class="sf-account-stat-card">
					<p class="text-[0.6875rem] uppercase tracking-[0.8px] text-[var(--color-brand-text-secondary)] font-medium">Commissioni</p>
					<p class="text-[0.875rem] font-semibold text-[var(--color-brand-text)] mt-[6px]">5% su ogni spedizione completata</p>
				</div>
				<div class="sf-account-stat-card">
					<p class="text-[0.6875rem] uppercase tracking-[0.8px] text-[var(--color-brand-text-secondary)] font-medium">Saldo</p>
					<p class="text-[0.875rem] font-semibold text-[var(--color-brand-text)] mt-[6px]">Prelievi tracciati e storico ordinato</p>
				</div>
			</div>

			<div class="mt-[18px] rounded-[16px] bg-[#F8FAFB] border border-[rgba(9,88,102,0.08)] p-[16px] tablet:p-[18px]">
				<p class="text-[0.75rem] uppercase tracking-[0.08em] text-[var(--color-brand-text-secondary)] font-semibold mb-[10px]">Come funziona</p>
				<div class="space-y-[10px]">
					<div class="flex items-start gap-[10px]">
						<span class="inline-flex h-[24px] w-[24px] items-center justify-center rounded-full bg-[rgba(9,88,102,0.12)] text-[0.75rem] font-bold text-[var(--color-brand-primary)]">1</span>
						<p class="text-[0.875rem] leading-[1.55] text-[var(--color-brand-text)]">Ottieni un link personale e condividilo con clienti, partner o contatti del tuo network.</p>
					</div>
					<div class="flex items-start gap-[10px]">
						<span class="inline-flex h-[24px] w-[24px] items-center justify-center rounded-full bg-[rgba(9,88,102,0.12)] text-[0.75rem] font-bold text-[var(--color-brand-primary)]">2</span>
						<p class="text-[0.875rem] leading-[1.55] text-[var(--color-brand-text)]">Segui utilizzi, ordini e commissioni confermate senza uscire dall area account.</p>
					</div>
					<div class="flex items-start gap-[10px]">
						<span class="inline-flex h-[24px] w-[24px] items-center justify-center rounded-full bg-[rgba(9,88,102,0.12)] text-[0.75rem] font-bold text-[var(--color-brand-primary)]">3</span>
						<p class="text-[0.875rem] leading-[1.55] text-[var(--color-brand-text)]">Quando il saldo e disponibile, gestisci tutto dal pannello prelievi con storico gia pronto.</p>
					</div>
				</div>
			</div>

			<div
				v-if="hasRequest && statusType === 'pending'"
				class="mt-[18px] bg-amber-50 border border-transparent rounded-[16px] p-[16px] tablet:p-[18px] shadow-[0_1px_3px_rgba(0,0,0,0.05)]">
				<div class="flex items-start gap-[10px]">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2" class="shrink-0 mt-[1px]">
						<circle cx="12" cy="12" r="10" />
						<polyline points="12 6 12 12 16 14" />
					</svg>
					<div>
						<p class="text-[0.9375rem] font-semibold text-amber-800">Richiesta in attesa di approvazione</p>
						<p class="text-[0.8125rem] text-amber-700 mt-[4px] leading-[1.5]">Ti avviseremo appena passa allo stato successivo.</p>
					</div>
				</div>
			</div>

			<div
				v-else-if="hasRequest && statusType === 'approved'"
				class="mt-[18px] bg-[#f0fdf4] border border-transparent rounded-[16px] shadow-[0_1px_3px_rgba(0,0,0,0.05)] p-[16px] tablet:p-[18px]">
				<div class="flex items-start gap-[10px]">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2" class="shrink-0 mt-[1px]">
						<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
						<polyline points="22 4 12 14.01 9 11.01" />
					</svg>
					<div>
						<p class="text-[0.9375rem] font-semibold text-[#0a8a7a]">Richiesta approvata</p>
						<p class="text-[0.8125rem] text-[#0a8a7a] mt-[4px] leading-[1.5]">Aggiorna la sessione per aprire la dashboard Partner Pro completa.</p>
					</div>
				</div>
			</div>

			<div
				v-else-if="hasRequest && statusType === 'rejected'"
				class="mt-[18px] bg-red-50 border border-transparent rounded-[16px] p-[16px] tablet:p-[18px] shadow-[0_1px_3px_rgba(0,0,0,0.05)]">
				<div class="flex items-start gap-[10px]">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2" class="shrink-0 mt-[1px]">
						<circle cx="12" cy="12" r="10" />
						<line x1="12" y1="8" x2="12" y2="12" />
						<line x1="12" y1="16" x2="12.01" y2="16" />
					</svg>
					<div>
						<p class="text-[0.9375rem] font-semibold text-red-800">Richiesta rifiutata</p>
						<p class="text-[0.8125rem] text-red-700 mt-[4px] leading-[1.5]">Aggiorna i dati qui a destra e invia una nuova richiesta.</p>
					</div>
				</div>
			</div>
		</div>

		<div class="sf-account-panel rounded-[16px] p-[18px] tablet:p-[20px] desktop:p-[22px] h-full">
			<div class="mb-[16px]">
				<p class="text-[0.75rem] uppercase tracking-[0.08em] text-[var(--color-brand-text-secondary)] font-semibold">Richiesta accesso</p>
				<h3 class="font-montserrat text-[1.125rem] tablet:text-[1.2rem] font-[800] text-[var(--color-brand-text)] mt-[6px]">
					{{ formHeading }}
				</h3>
				<p class="text-[var(--color-brand-text-secondary)] text-[0.875rem] leading-[1.55] mt-[8px]">
					{{ formDescription }}
				</p>
			</div>

			<div v-if="proRequestSuccess" class="bg-[#f0fdf4] border border-transparent rounded-[16px] shadow-[0_1px_3px_rgba(0,0,0,0.05)] p-[16px] tablet:p-[18px] text-center">
				<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2" class="mx-auto mb-[8px]">
					<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
					<polyline points="22 4 12 14.01 9 11.01" />
				</svg>
				<p class="text-[1rem] font-semibold text-[#0a8a7a]">Richiesta inviata</p>
				<p class="text-[0.8125rem] text-[#0a8a7a] mt-[4px] leading-[1.5]">Ti aggiorneremo appena possibile.</p>
			</div>

			<div v-else class="space-y-[14px]">
				<p v-if="proRequestError" class="text-red-600 text-[0.8125rem] bg-red-50 p-[10px] rounded-[16px] border border-transparent">
					{{ proRequestError }}
				</p>

				<p v-if="isLocked" class="text-[var(--color-brand-text-secondary)] text-[0.8125rem] bg-[#F5F6F9] p-[10px] rounded-[16px] border border-transparent">
					{{
						statusType === 'approved'
							? 'Accesso gia approvato: aggiorna la pagina per entrare nell area Partner Pro.'
							: 'Richiesta gia inviata: non serve un nuovo invio.'
					}}
				</p>

				<div class="space-y-[10px]" :class="{ 'opacity-60': isLocked }">
					<div>
						<label class="block text-[0.8125rem] font-semibold text-[var(--color-brand-text)] mb-[6px]" for="pro_company_name">Ragione sociale</label>
						<input
							id="pro_company_name"
							:value="proRequestForm.company_name"
							:disabled="isLocked"
							@input="updateField('company_name', $event.target.value)"
							type="text"
							placeholder="Nome azienda"
							class="form-input min-h-[48px]" />
					</div>
					<div>
						<label class="block text-[0.8125rem] font-semibold text-[var(--color-brand-text)] mb-[6px]" for="pro_vat_number">Partita IVA</label>
						<input
							id="pro_vat_number"
							:value="proRequestForm.vat_number"
							:disabled="isLocked"
							@input="updateField('vat_number', $event.target.value)"
							type="text"
							placeholder="Partita IVA"
							class="form-input min-h-[48px]" />
					</div>
					<div>
						<label class="block text-[0.8125rem] font-semibold text-[var(--color-brand-text)] mb-[6px]" for="pro_message">Messaggio</label>
						<textarea
							id="pro_message"
							:value="proRequestForm.message"
							:disabled="isLocked"
							@input="updateField('message', $event.target.value)"
							rows="4"
							placeholder="Breve nota facoltativa"
							class="form-input min-h-[120px] resize-none"></textarea>
					</div>
				</div>

				<button
					@click="emit('submit')"
					:disabled="submitDisabled"
					class="btn-primary btn-compact w-full inline-flex items-center justify-center gap-[8px]">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
					</svg>
					{{ proRequestLoading ? 'Invio in corso...' : isLocked ? 'Richiesta gia registrata' : 'Invia richiesta' }}
				</button>
			</div>
		</div>
	</div>
</template>

