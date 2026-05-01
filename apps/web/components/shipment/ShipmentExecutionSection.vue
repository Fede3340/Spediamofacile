<script setup>
const props = defineProps({
	orderData: { type: Object, required: true },
	executionData: { type: Object, default: null },
	pickupBusy: { type: Boolean, default: false },
	borderoBusy: { type: Boolean, default: false },
	documentsBusy: { type: Boolean, default: false },
	downloadBorderoBusy: { type: Boolean, default: false },
	executionError: { type: String, default: null },
	executionSuccess: { type: String, default: null },
	formatDate: { type: Function, required: true },
});

const emit = defineEmits(['request-pickup', 'create-bordero', 'send-documents', 'download-bordero', 'open-bordero']);

const statusLabels = {
	pending: 'In attesa',
	completed: 'Completato',
	requested: 'Richiesto',
	failed: 'Errore',
	manual_required: 'Manuale',
	sent: 'Inviati',
	skipped: 'Saltato',
	not_requested: 'Non richiesto',
};

const statusTone = {
	completed: 'bg-[#f0fdf4] text-[#0a8a7a] border-[#d1fae5]',
	requested: 'bg-[#eef7f8] text-[#095866] border-[#bdd5da]',
	sent: 'bg-[#f0fdf4] text-[#0a8a7a] border-[#d1fae5]',
	pending: 'bg-[#EEF0F3] text-[var(--color-brand-text-secondary)] border-[#DFE2E7]',
	not_requested: 'bg-[#EEF0F3] text-[var(--color-brand-text-secondary)] border-[#DFE2E7]',
	manual_required: 'bg-amber-50 text-amber-700 border-amber-200',
	skipped: 'bg-amber-50 text-amber-700 border-amber-200',
	failed: 'bg-red-50 text-red-700 border-red-200',
};

const hasShipmentLabel = computed(() => Boolean(props.orderData?.has_label || props.orderData?.brt_parcel_id));
const executionReady = computed(() => Boolean(props.executionData));

const labelForStatus = (value) => statusLabels[value] || 'Da verificare';
const toneForStatus = (value) => statusTone[value] || 'bg-[#EEF0F3] text-[var(--color-brand-text-secondary)] border-[#DFE2E7]';
const formatOptionalDate = (value) => (value ? props.formatDate(value) : 'Non ancora');
const formatPickupDate = (value) => {
	if (!value) return 'Non ancora';

	const normalized = String(value).trim();

	if (/^\d{4}-\d{2}-\d{2}$/.test(normalized)) {
		const [year, month, day] = normalized.split('-');
		return `${day}/${month}/${year}`;
	}

	const parsed = new Date(normalized);
	if (Number.isNaN(parsed.getTime())) {
		return normalized;
	}

	return new Intl.DateTimeFormat('it-IT', {
		day: '2-digit',
		month: '2-digit',
		year: 'numeric',
	}).format(parsed);
};
const pickupTimeSlotOptions = [
	{ label: '09:00 - 12:00', value: '09:00-12:00' },
	{ label: '09:00 - 18:00', value: '09:00-18:00' },
	{ label: '14:00 - 18:00', value: '14:00-18:00' },
];
const pickupForm = reactive({
	enabled: true,
	date: '',
	time_slot: '09:00-18:00',
	notes: '',
});
const pickupActionLabel = computed(() => {
	if (props.pickupBusy) return 'Salvataggio...';
	if (!pickupForm.enabled) return 'Segna come non richiesto';
	return props.executionData?.pickup_status === 'requested' ? 'Aggiorna ritiro' : 'Richiedi ritiro';
});
const executionActionBusy = computed(() => props.pickupBusy || props.borderoBusy || props.documentsBusy);
const borderoAvailable = computed(() =>
	Boolean(
		props.executionData?.bordero_download_available ??
		props.executionData?.bordero_document_filename ??
		props.executionData?.carrier_bordero_ref ??
		false,
	),
);
const syncPickupForm = (execution = {}) => {
	pickupForm.enabled = Boolean(execution.pickup_enabled ?? execution.pickup_date ?? true);
	pickupForm.date = String(execution.pickup_date || '').trim();
	pickupForm.time_slot = String(execution.pickup_time_slot || '09:00-18:00').trim() || '09:00-18:00';
	pickupForm.notes = String(execution.pickup_notes || '').trim();
};

watch(
	() => props.executionData,
	(value) => {
		syncPickupForm(value || {});
	},
	{ immediate: true, deep: true },
);

const isCardActionDisabled = (cardKey) => {
	if (!hasShipmentLabel.value || !executionReady.value || executionActionBusy.value) {
		return true;
	}

	if (cardKey === 'pickup') {
		return pickupForm.enabled && !pickupForm.date;
	}

	if (cardKey === 'documents') {
		return !borderoAvailable.value;
	}

	return false;
};

const executionCards = computed(() => {
	const execution = props.executionData || {};
	const pickupMetaParts = [];

	if (execution.pickup_date) {
		pickupMetaParts.push(`Data: ${formatPickupDate(execution.pickup_date)}`);
	}
	if (execution.pickup_time_slot) {
		pickupMetaParts.push(`Fascia: ${execution.pickup_time_slot}`);
	}
	if (execution.pickup_requested_at) {
		pickupMetaParts.push(`Richiesta il ${formatOptionalDate(execution.pickup_requested_at)}`);
	}
	if (execution.pickup_notes) {
		pickupMetaParts.push(`Note: ${execution.pickup_notes}`);
	}

	return [
		{
			key: 'pickup',
			eyebrow: 'Ritiro',
			status: execution.pickup_status || 'pending',
			title: execution.pickup_status === 'manual_required'
				? 'Gestione manuale richiesta'
				: execution.carrier_pickup_ref || 'Richiesta al corriere',
			meta: execution.pickup_status === 'manual_required'
				? 'Etichetta e documenti sono pronti, ma il ritiro va confermato da operatore perche l\'endpoint pickup BRT non e configurato.'
				: pickupMetaParts.length ? pickupMetaParts.join(' · ') : 'Usa i dati dell\'ordine per prenotare il passaggio del corriere.',
			actionLabel: pickupActionLabel.value,
			actionKind: 'pickup',
		},
		{
			key: 'bordero',
			eyebrow: 'Borderò',
			status: execution.bordero_status || 'pending',
			title: execution.carrier_bordero_ref || 'Documento di spedizione',
			meta: execution.carrier_bordero_ref
				? 'Borderò disponibile nel flusso ordine.'
				: 'Genera il borderò dopo l\'etichetta per completare la documentazione.',
			actionLabel: props.borderoBusy ? 'Generazione...' : execution.bordero_status === 'completed' ? 'Rigenera borderò' : 'Genera borderò',
			actionKind: 'bordero',
		},
		{
			key: 'documents',
			eyebrow: 'Documenti',
			status: execution.documents_status || 'pending',
			title: execution.documents_status === 'sent' ? 'Inviati' : 'Invio documenti',
			meta: !borderoAvailable.value
				? 'Genera prima il borderò: l\'invio documenti parte solo quando il PDF è davvero disponibile.'
				: execution.documents_sent_customer_at || execution.documents_sent_admin_at
					? `Cliente: ${formatOptionalDate(execution.documents_sent_customer_at)} · Admin: ${formatOptionalDate(execution.documents_sent_admin_at)}`
					: 'Invia etichetta e borderò ad admin e cliente con un solo passaggio.',
			actionLabel: !borderoAvailable.value
				? 'Borderò richiesto'
				: props.documentsBusy
					? 'Invio...'
					: execution.documents_status === 'sent'
						? 'Reinvia documenti'
						: 'Invia documenti',
			actionKind: 'documents',
		},
	];
});

const runCardAction = (kind) => {
	if (kind === 'pickup') {
		emit('request-pickup', {
			enabled: pickupForm.enabled,
			date: pickupForm.date,
			time_slot: pickupForm.time_slot,
			notes: pickupForm.notes,
		});
		return;
	}
	if (kind === 'bordero') {
		emit('create-bordero');
		return;
	}
	emit('send-documents');
};
</script>

<template>
	<div class="mt-4 rounded-card border border-brand-border bg-brand-card p-[18px] shadow-sf tablet:p-5">
		<div class="flex flex-col gap-[12px] border-b border-[#E9EEF2] pb-[14px] desktop:flex-row desktop:items-start desktop:justify-between">
			<div class="max-w-[760px]">
				<p class="sf-section-kicker mb-[6px]">Esecuzione spedizione</p>
				<h3 class="font-montserrat text-[1.0625rem] font-[800] leading-[1.15] text-[var(--color-brand-text)]">Ritiro, borderò e documenti</h3>
				<p class="mt-[6px] text-[0.875rem] leading-[1.5] text-[var(--color-brand-text-secondary)]">
					Il backend è già pronto: qui rendiamo visibili stato e azioni operative dell'ordine.
				</p>
			</div>
			<span
				class="inline-flex items-center gap-[6px] rounded-full border px-[12px] py-[7px] text-[0.75rem] font-semibold"
				:class="hasShipmentLabel ? 'border-[#d1fae5] bg-[#f0fdf4] text-[#0a8a7a]' : 'border-amber-200 bg-amber-50 text-amber-700'">
				{{ hasShipmentLabel ? 'Etichetta pronta' : 'Serve etichetta BRT' }}
			</span>
		</div>

		<div v-if="executionSuccess" class="ux-alert ux-alert--soft mt-[14px]">
			<div class="flex items-start gap-[10px]">
				<svg xmlns="http://www.w3.org/2000/svg" class="ux-alert__icon mt-[1px]" viewBox="0 0 24 24">
					<path fill="currentColor" d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm-1 14-4-4 1.41-1.41L11 13.17l5.59-5.58L18 9Z" />
				</svg>
				<p class="text-[0.875rem] leading-[1.5] text-[var(--color-brand-text-secondary)]">{{ executionSuccess }}</p>
			</div>
		</div>

		<div v-if="executionError" class="ux-alert ux-alert--soft mt-[14px]">
			<div class="flex items-start gap-[10px]">
				<svg xmlns="http://www.w3.org/2000/svg" class="ux-alert__icon mt-[1px]" viewBox="0 0 24 24">
					<path fill="currentColor" d="M11 15h2v2h-2zm0-8h2v6h-2z" />
					<path fill="currentColor" d="M1 21h22L12 2z" />
				</svg>
				<p class="text-[0.875rem] leading-[1.5] text-[var(--color-brand-text-secondary)]">{{ executionError }}</p>
			</div>
		</div>

		<div
			v-if="!hasShipmentLabel"
			class="mt-[14px] rounded-[16px] border border-[#F6D7AA] bg-[#FFF8EA] px-[14px] py-[12px] text-[0.8125rem] leading-[1.5] text-[#8A5E2E]">
			Le azioni operative diventano complete dopo la generazione dell'etichetta BRT. Puoi comunque consultare lo stato appena disponibile.
		</div>

		<div class="mt-4 grid gap-3.5 desktop:grid-cols-3">
			<article v-for="card in executionCards" :key="card.key" class="flex flex-col gap-2 rounded-card border border-brand-primary/[0.08] bg-brand-card p-[18px] shadow-sf-sm transition-all hover:-translate-y-0.5 hover:shadow-sf">
				<div class="flex items-start justify-between gap-[10px]">
					<div class="min-w-0">
						<p class="text-[0.75rem] font-semibold uppercase tracking-[0.08em] text-[var(--color-brand-text-muted)]">{{ card.eyebrow }}</p>
						<p class="mt-[6px] text-[1rem] font-bold leading-[1.2] text-[var(--color-brand-text)]">{{ card.title }}</p>
					</div>
					<span
						class="inline-flex shrink-0 rounded-full border px-[10px] py-[5px] text-[0.75rem] font-semibold"
						:class="toneForStatus(card.status)">
						{{ labelForStatus(card.status) }}
					</span>
				</div>

				<p class="text-[0.8125rem] leading-[1.5] text-[var(--color-brand-text-secondary)]">{{ card.meta }}</p>

				<div v-if="card.key === 'pickup'" class="grid gap-[10px]">
					<label class="flex items-start gap-[10px] rounded-[16px] border border-[#E9EEF2] bg-[#FBFCFD] px-[14px] py-[12px]">
						<input
							v-model="pickupForm.enabled"
							type="checkbox"
							class="mt-[3px] h-[16px] w-[16px] rounded border-[#C9D3DD] text-[var(--color-brand-primary)]" >
						<span class="min-w-0">
							<span class="block text-[0.875rem] font-semibold text-[var(--color-brand-text)]">Richiedi ritiro per questo ordine</span>
							<span class="mt-[2px] block text-[0.8125rem] leading-[1.5] text-[var(--color-brand-text-secondary)]">
								Disattivalo per segnare il ritiro come non richiesto e pulire data, fascia e note operative salvate.
							</span>
						</span>
					</label>

					<div v-if="pickupForm.enabled" class="grid gap-[10px]">
						<div class="grid gap-[10px] desktop:grid-cols-2">
							<div>
								<label class="form-label">Data ritiro</label>
								<input v-model="pickupForm.date" type="date" class="form-input" >
							</div>
							<div>
								<label class="form-label">Fascia</label>
								<select v-model="pickupForm.time_slot" class="form-input">
									<option v-for="slot in pickupTimeSlotOptions" :key="slot.value" :value="slot.value">{{ slot.label }}</option>
								</select>
							</div>
						</div>
						<div>
							<label class="form-label">Note ritiro</label>
							<textarea
								v-model="pickupForm.notes"
								rows="3"
								class="form-input min-h-[92px] resize-y"
								placeholder="Esempio: citofono, piano, fascia preferita o istruzioni per il corriere."/>
						</div>
					</div>

					<p
						v-else
						class="rounded-[16px] border border-[#E9EEF2] bg-white px-[14px] py-[12px] text-[0.8125rem] leading-[1.5] text-[var(--color-brand-text-secondary)]">
						Salvando, l'ordine verra' marcato come non richiesto e i riferimenti di ritiro verranno rimossi.
					</p>
				</div>

				<div class="flex flex-wrap gap-2">
					<div class="flex flex-wrap gap-[8px]">
						<SfButton
							size="sm"
							:disabled="isCardActionDisabled(card.key)"
							@click="runCardAction(card.actionKind)">
							{{ card.actionLabel }}
						</SfButton>
						<SfButton
							v-if="card.key === 'bordero' && borderoAvailable"
							variant="secondary"
							size="sm"
							:disabled="downloadBorderoBusy || borderoBusy"
							@click="emit('open-bordero')">
							Apri borderò
						</SfButton>
						<SfButton
							v-if="card.key === 'bordero' && borderoAvailable"
							variant="secondary"
							size="sm"
							:loading="downloadBorderoBusy"
							:disabled="borderoBusy"
							@click="emit('download-bordero')">
							{{ downloadBorderoBusy ? 'Download...' : 'Scarica borderò' }}
						</SfButton>
					</div>
				</div>
			</article>
		</div>

		<div v-if="executionData?.last_error" class="mt-[16px] rounded-[16px] border border-[#F6D7AA] bg-[#FFF8EA] px-[14px] py-[12px]">
			<p class="text-[0.75rem] font-semibold uppercase tracking-[0.08em] text-[#8A5E2E]">Ultimo errore operativo</p>
			<p class="mt-[6px] text-[0.8125rem] leading-[1.5] text-[#8A5E2E]">{{ executionData.last_error }}</p>
		</div>
	</div>
</template>
