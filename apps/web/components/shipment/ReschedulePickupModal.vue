<!--
  Componente: ReschedulePickupModal
  Modale per riprogrammare la data di ritiro di un ordine (F04 audit BRT).
  L'admin/utente puo' scegliere una nuova data nel range +1..+10 giorni lavorativi
  e opzionalmente una fascia oraria.
  Chiama PATCH /api/orders/{id}/pickup.
-->
<script setup>
const props = defineProps({
	show: { type: Boolean, required: true },
	orderId: { type: [String, Number], required: true },
	currentPickupDate: { type: String, default: null },
	currentTimeSlot: { type: String, default: null },
	currentNotes: { type: String, default: null },
});

const emit = defineEmits(['update:show', 'updated']);

const sanctum = useSanctumClient();

const dialogRef = ref(null);
const previousFocus = ref(null);

const form = reactive({
	pickup_date: '',
	pickup_time_slot: '09:00-18:00',
	pickup_notes: '',
});

const saving = ref(false);
const errorMsg = ref('');
const successMsg = ref('');

/* Calcola il range di date valido (+1..+10 giorni lavorativi) */
const isWeekday = (d) => {
	const day = d.getDay();
	return day !== 0 && day !== 6; // escludi domenica (0) e sabato (6)
};

const addWeekdays = (from, count) => {
	const d = new Date(from);
	let added = 0;
	while (added < count) {
		d.setDate(d.getDate() + 1);
		if (isWeekday(d)) added++;
	}
	return d;
};

const toIsoDate = (d) => {
	const y = d.getFullYear();
	const m = String(d.getMonth() + 1).padStart(2, '0');
	const day = String(d.getDate()).padStart(2, '0');
	return `${y}-${m}-${day}`;
};

const minDate = computed(() => toIsoDate(addWeekdays(new Date(), 1)));
const maxDate = computed(() => toIsoDate(addWeekdays(new Date(), 10)));

const trapFocus = (e) => {
	if (!dialogRef.value) return;
	const focusable = dialogRef.value.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
	if (!focusable.length) return;
	const first = focusable[0];
	const last = focusable[focusable.length - 1];
	if (e.key === 'Tab') {
		if (e.shiftKey && document.activeElement === first) {
			e.preventDefault();
			last.focus();
		} else if (!e.shiftKey && document.activeElement === last) {
			e.preventDefault();
			first.focus();
		}
	}
	if (e.key === 'Escape') emit('update:show', false);
};

watch(
	() => props.show,
	(open) => {
		if (open) {
			previousFocus.value = document.activeElement;
			errorMsg.value = '';
			successMsg.value = '';
			// Pre-popola con valore esistente se valido
			form.pickup_date = props.currentPickupDate && props.currentPickupDate >= minDate.value ? props.currentPickupDate : minDate.value;
			form.pickup_time_slot = props.currentTimeSlot || '09:00-18:00';
			form.pickup_notes = props.currentNotes || '';
			nextTick(() => {
				dialogRef.value?.querySelector('input, select, button')?.focus();
				document.addEventListener('keydown', trapFocus);
			});
		} else {
			document.removeEventListener('keydown', trapFocus);
			nextTick(() => previousFocus.value?.focus?.());
		}
	},
);

onUnmounted(() => {
	document.removeEventListener('keydown', trapFocus);
});

const validate = () => {
	if (!form.pickup_date) {
		errorMsg.value = 'Seleziona una data di ritiro.';
		return false;
	}
	if (form.pickup_date < minDate.value || form.pickup_date > maxDate.value) {
		errorMsg.value = 'La data deve essere compresa tra ' + minDate.value + ' e ' + maxDate.value + ' (giorni lavorativi).';
		return false;
	}
	const slot = form.pickup_time_slot;
	if (slot && !['09:00-12:00', '09:00-18:00', '14:00-18:00'].includes(slot)) {
		errorMsg.value = 'Fascia oraria non valida.';
		return false;
	}
	return true;
};

const submit = async () => {
	errorMsg.value = '';
	successMsg.value = '';
	if (!validate()) return;
	saving.value = true;
	try {
		const res = await sanctum(`/api/orders/${props.orderId}/pickup`, {
			method: 'PATCH',
			body: {
				pickup_date: form.pickup_date,
				pickup_time_slot: form.pickup_time_slot,
				pickup_notes: form.pickup_notes || null,
			},
		});
		successMsg.value = res?.message || 'Data di ritiro aggiornata con successo.';
		emit('updated', res?.data || res);
		setTimeout(() => emit('update:show', false), 1200);
	} catch (error) {
		errorMsg.value = error?.response?._data?.message || error?.data?.message || 'Impossibile aggiornare la data di ritiro.';
	} finally {
		saving.value = false;
	}
};
</script>

<template>
	<Teleport to="body">
		<div v-if="show" class="fixed inset-0 z-[9999] flex items-center justify-center">
			<div class="absolute inset-0 bg-black/50" @click="emit('update:show', false)"/>
			<div
				ref="dialogRef"
				role="dialog"
				aria-modal="true"
				aria-labelledby="reschedule-modal-title"
				class="relative bg-white rounded-card shadow-lg max-w-[520px] w-full mx-[16px] p-[20px] z-[1]">
				<!-- Header -->
				<div class="flex items-center gap-[12px] mb-[20px]">
					<div class="w-[44px] h-[44px] rounded-full bg-[#EEF6F8] flex items-center justify-center shrink-0" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
					</div>
					<div>
						<h2 id="reschedule-modal-title" class="font-montserrat text-[1.125rem] font-[800] text-[var(--color-brand-text)]">Cambia data di ritiro</h2>
						<p class="text-[0.8125rem] text-[var(--color-brand-text-secondary)]">Scegli un nuovo giorno lavorativo tra domani e i prossimi 10 giorni.</p>
					</div>
				</div>

				<!-- Body -->
				<div class="space-y-[14px]">
					<div>
						<label for="pickup-date-input" class="block text-[0.75rem] text-[var(--color-brand-text-secondary)] uppercase font-medium mb-[4px]">
							Nuova data di ritiro
						</label>
						<input
							id="pickup-date-input"
							v-model="form.pickup_date"
							type="date"
							:min="minDate"
							:max="maxDate"
							class="w-full bg-[#F8F9FB] border border-brand-border rounded-control px-[12px] py-[10px] text-[0.875rem] focus:border-[var(--color-brand-primary)] focus:outline-none" >
						<p class="mt-[4px] text-[0.6875rem] text-[var(--color-brand-text-muted)]">
							Range consentito: {{ minDate }} → {{ maxDate }} (solo giorni feriali).
						</p>
					</div>

					<div>
						<label for="pickup-slot-select" class="block text-[0.75rem] text-[var(--color-brand-text-secondary)] uppercase font-medium mb-[4px]">
							Fascia oraria
						</label>
						<select
							id="pickup-slot-select"
							v-model="form.pickup_time_slot"
							class="w-full bg-[#F8F9FB] border border-brand-border rounded-control px-[12px] py-[10px] text-[0.875rem] focus:border-[var(--color-brand-primary)] focus:outline-none">
							<option value="09:00-18:00">09:00 — 18:00 (tutto il giorno)</option>
							<option value="09:00-12:00">09:00 — 12:00 (mattina)</option>
							<option value="14:00-18:00">14:00 — 18:00 (pomeriggio)</option>
						</select>
					</div>

					<div>
						<label for="pickup-notes-input" class="block text-[0.75rem] text-[var(--color-brand-text-secondary)] uppercase font-medium mb-[4px]">
							Note per il corriere (opzionale)
						</label>
						<textarea
							id="pickup-notes-input"
							v-model="form.pickup_notes"
							placeholder="Es. citofono interno, orari preferiti..."
							maxlength="500"
							rows="2"
							class="w-full bg-[#F8F9FB] border border-brand-border rounded-control px-[12px] py-[10px] text-[0.875rem] resize-none focus:border-[var(--color-brand-primary)] focus:outline-none"/>
					</div>

					<div v-if="errorMsg" class="bg-red-50 border border-red-200 rounded-control px-[14px] py-[10px] text-red-600 text-[0.8125rem]" role="alert">
						{{ errorMsg }}
					</div>
					<div v-if="successMsg" class="bg-[#E9F7EC] border border-[rgba(31,122,58,0.3)] rounded-control px-[14px] py-[10px] text-[#1F7A3A] text-[0.8125rem]" role="status">
						{{ successMsg }}
					</div>
				</div>

				<!-- Actions -->
				<div class="mt-[20px] flex flex-col gap-[10px] tablet:flex-row tablet:justify-end">
					<SfButton variant="secondary" size="sm" :disabled="saving" @click="emit('update:show', false)">Annulla</SfButton>
					<SfButton variant="primary" size="sm" :loading="saving" loading-text="Salvataggio..." @click="submit">Conferma nuova data</SfButton>
				</div>

				<p class="mt-[10px] text-[0.6875rem] text-[var(--color-brand-text-muted)]">
					Se il ritiro è già stato processato da BRT, il sistema notificherà il supporto per contattarti con una conferma diretta.
				</p>
			</div>
		</div>
	</Teleport>
</template>
