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
			return 'La richiesta è già in verifica. Partner Pro sblocca inviti tracciati, commissioni e prelievi in un unico flusso ordinato.';
		case 'approved':
			return 'La richiesta è stata approvata. Ti basta aggiornare la sessione per entrare nella dashboard Partner Pro completa.';
		case 'rejected':
			return 'Puoi correggere i dati aziendali e inviare una nuova richiesta per riattivare il processo.';
		default:
			return 'Invita clienti, accumula commissioni tracciate e gestisci il saldo da una dashboard dedicata, coerente con il resto dell\'account.';
	}
});

const formHeading = computed(() => (isLocked.value ? 'Richiesta registrata' : 'Richiedi accesso'));
const formDescription = computed(() => {
	if (isLocked.value) {
		return statusType.value === 'approved'
			? 'L\'accesso è già stato approvato. Qui sotto resta il riepilogo dei dati inviati.'
			: 'Qui sotto resta il riepilogo della richiesta attuale: non serve un nuovo invio.';
	}

	return 'Inserisci i dati aziendali essenziali. Il messaggio è facoltativo ma utile se vuoi aggiungere contesto commerciale.';
});
</script>

<template>
	<div class="grid grid-cols-1 gap-[18px] desktop:grid-cols-[minmax(0,1fr)_420px] desktop:gap-5">
		<div class="h-full rounded-card border border-brand-border bg-brand-card p-[18px] shadow-sf tablet:p-5 desktop:p-[22px]">
			<div class="flex flex-col gap-4 tablet:flex-row tablet:items-start">
				<div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-primary to-brand-primary-hover text-white shadow-[0_4px_12px_rgba(9,88,102,0.2)]">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
					</svg>
				</div>
				<div class="min-w-0">
					<p class="text-xs font-semibold uppercase tracking-[0.08em] text-brand-text-secondary">Partner Pro</p>
					<h2 class="mt-1.5 font-display text-[1.25rem] font-extrabold text-brand-text tablet:text-[1.4rem] desktop:text-[1.5rem]">
						{{ heroTitle }}
					</h2>
					<p class="mt-2 max-w-[620px] text-sm leading-relaxed text-brand-text-secondary tablet:text-[0.9375rem]">
						{{ heroDescription }}
					</p>
				</div>
			</div>

			<div class="mt-[18px] grid gap-3 sm:grid-cols-3">
				<div class="rounded-card border border-brand-primary/10 bg-brand-card p-4 shadow-sf-sm">
					<p class="text-[0.6875rem] font-medium uppercase tracking-[0.8px] text-brand-text-secondary">Inviti</p>
					<p class="mt-1.5 text-sm font-semibold text-brand-text">Link e codice dedicato</p>
				</div>
				<div class="rounded-card border border-brand-primary/10 bg-brand-card p-4 shadow-sf-sm">
					<p class="text-[0.6875rem] font-medium uppercase tracking-[0.8px] text-brand-text-secondary">Commissioni</p>
					<p class="mt-1.5 text-sm font-semibold text-brand-text">5% su ogni spedizione completata</p>
				</div>
				<div class="rounded-card border border-brand-primary/10 bg-brand-card p-4 shadow-sf-sm">
					<p class="text-[0.6875rem] font-medium uppercase tracking-[0.8px] text-brand-text-secondary">Saldo</p>
					<p class="mt-1.5 text-sm font-semibold text-brand-text">Prelievi tracciati e storico ordinato</p>
				</div>
			</div>

			<div class="mt-[18px] rounded-card border border-brand-primary/10 bg-brand-bg-alt p-4 tablet:p-[18px]">
				<p class="mb-2.5 text-xs font-semibold uppercase tracking-[0.08em] text-brand-text-secondary">Come funziona</p>
				<div class="space-y-2.5">
					<div class="flex items-start gap-2.5">
						<span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-brand-primary/15 text-xs font-bold text-brand-primary">1</span>
						<p class="text-sm leading-snug text-brand-text">Ottieni un link personale e condividilo con clienti, partner o contatti del tuo network.</p>
					</div>
					<div class="flex items-start gap-2.5">
						<span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-brand-primary/15 text-xs font-bold text-brand-primary">2</span>
						<p class="text-sm leading-snug text-brand-text">Segui utilizzi, ordini e commissioni confermate senza uscire dall'area account.</p>
					</div>
					<div class="flex items-start gap-2.5">
						<span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-brand-primary/15 text-xs font-bold text-brand-primary">3</span>
						<p class="text-sm leading-snug text-brand-text">Quando il saldo è disponibile, gestisci tutto dal pannello prelievi con storico già pronto.</p>
					</div>
				</div>
			</div>

			<div
				v-if="hasRequest && statusType === 'pending'"
				class="mt-[18px] rounded-card border border-status-pending-fg/30 bg-status-pending-bg p-4 tablet:p-[18px]">
				<div class="flex items-start gap-2.5">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-px shrink-0 text-status-pending-fg">
						<circle cx="12" cy="12" r="10" />
						<polyline points="12 6 12 12 16 14" />
					</svg>
					<div>
						<p class="text-[0.9375rem] font-semibold text-status-pending-fg">Richiesta in attesa di approvazione</p>
						<p class="mt-1 text-[0.8125rem] leading-snug text-status-pending-fg">Ti avviseremo appena passa allo stato successivo.</p>
					</div>
				</div>
			</div>

			<div
				v-else-if="hasRequest && statusType === 'approved'"
				class="mt-[18px] rounded-card border border-brand-success/30 bg-brand-success-bg p-4 tablet:p-[18px]">
				<div class="flex items-start gap-2.5">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-px shrink-0 text-brand-success-fg">
						<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
						<polyline points="22 4 12 14.01 9 11.01" />
					</svg>
					<div>
						<p class="text-[0.9375rem] font-semibold text-brand-success-fg">Richiesta approvata</p>
						<p class="mt-1 text-[0.8125rem] leading-snug text-brand-success-fg">Aggiorna la sessione per aprire la dashboard Partner Pro completa.</p>
					</div>
				</div>
			</div>

			<div
				v-else-if="hasRequest && statusType === 'rejected'"
				class="mt-[18px] rounded-card border border-status-failed-fg/30 bg-status-failed-bg p-4 tablet:p-[18px]">
				<div class="flex items-start gap-2.5">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-px shrink-0 text-status-failed-fg">
						<circle cx="12" cy="12" r="10" />
						<line x1="12" y1="8" x2="12" y2="12" />
						<line x1="12" y1="16" x2="12.01" y2="16" />
					</svg>
					<div>
						<p class="text-[0.9375rem] font-semibold text-status-failed-fg">Richiesta rifiutata</p>
						<p class="mt-1 text-[0.8125rem] leading-snug text-status-failed-fg">Aggiorna i dati qui a destra e invia una nuova richiesta.</p>
					</div>
				</div>
			</div>
		</div>

		<div class="h-full rounded-card border border-brand-border bg-brand-card p-[18px] shadow-sf tablet:p-5 desktop:p-[22px]">
			<div class="mb-4">
				<p class="text-xs font-semibold uppercase tracking-[0.08em] text-brand-text-secondary">Richiesta accesso</p>
				<h3 class="mt-1.5 font-display text-lg font-extrabold text-brand-text tablet:text-[1.2rem]">
					{{ formHeading }}
				</h3>
				<p class="mt-2 text-sm leading-snug text-brand-text-secondary">
					{{ formDescription }}
				</p>
			</div>

			<div v-if="proRequestSuccess" class="rounded-card border border-brand-success/30 bg-brand-success-bg p-4 text-center tablet:p-[18px]">
				<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mx-auto mb-2 text-brand-success-fg">
					<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
					<polyline points="22 4 12 14.01 9 11.01" />
				</svg>
				<p class="text-base font-semibold text-brand-success-fg">Richiesta inviata</p>
				<p class="mt-1 text-[0.8125rem] leading-snug text-brand-success-fg">Ti aggiorneremo appena possibile.</p>
			</div>

			<div v-else class="space-y-3.5">
				<p v-if="proRequestError" class="rounded-card border border-status-failed-fg/30 bg-status-failed-bg p-2.5 text-[0.8125rem] text-status-failed-fg">
					{{ proRequestError }}
				</p>

				<p v-if="isLocked" class="rounded-card border border-brand-border bg-brand-bg-alt p-2.5 text-[0.8125rem] text-brand-text-secondary">
					{{
						statusType === 'approved'
							? "Accesso già approvato: aggiorna la pagina per entrare nell'area Partner Pro."
							: 'Richiesta già inviata: non serve un nuovo invio.'
					}}
				</p>

				<div class="space-y-2.5" :class="{ 'opacity-60': isLocked }">
					<div>
						<label class="mb-1.5 block text-[0.8125rem] font-semibold text-brand-text" for="pro_company_name">Ragione sociale</label>
						<input
							id="pro_company_name"
							:value="proRequestForm.company_name"
							:disabled="isLocked"
							type="text"
							placeholder="Nome azienda"
							class="form-input min-h-12"
							@input="updateField('company_name', $event.target.value)">
					</div>
					<div>
						<label class="mb-1.5 block text-[0.8125rem] font-semibold text-brand-text" for="pro_vat_number">Partita IVA</label>
						<input
							id="pro_vat_number"
							:value="proRequestForm.vat_number"
							:disabled="isLocked"
							type="text"
							placeholder="Partita IVA"
							class="form-input min-h-12"
							@input="updateField('vat_number', $event.target.value)">
					</div>
					<div>
						<label class="mb-1.5 block text-[0.8125rem] font-semibold text-brand-text" for="pro_message">Messaggio</label>
						<textarea
							id="pro_message"
							:value="proRequestForm.message"
							:disabled="isLocked"
							rows="4"
							placeholder="Breve nota facoltativa"
							class="form-input min-h-[120px] resize-none"
							@input="updateField('message', $event.target.value)" />
					</div>
				</div>

				<button
					:disabled="submitDisabled"
					class="btn-primary btn-compact inline-flex w-full items-center justify-center gap-2"
					@click="emit('submit')">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
					</svg>
					{{ proRequestLoading ? 'Invio in corso...' : isLocked ? 'Richiesta già registrata' : 'Invia richiesta' }}
				</button>
			</div>
		</div>
	</div>
</template>
