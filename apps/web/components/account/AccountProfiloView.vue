<script setup>
const props = defineProps({
	user: { type: Object, default: null },
});

const emit = defineEmits(['edit', 'logout']);

const sanctum = useSanctumClient();
const { clearSnapshot } = useAuthUiSnapshotPersistence();
const exportingData = ref(false);
const deletingAccount = ref(false);
const showDeleteConfirm = ref(false);
const gdprError = ref(null);

const exportData = async () => {
	exportingData.value = true;
	gdprError.value = null;
	try {
		const data = await sanctum('/api/user/data-export', { method: 'GET' });
		const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
		const url = window.URL.createObjectURL(blob);
		const link = document.createElement('a');
		link.href = url;
		link.download = `spediamofacile-dati-personali-${new Date().toISOString().slice(0, 10)}.json`;
		document.body.appendChild(link);
		link.click();
		window.URL.revokeObjectURL(url);
		link.remove();
	} catch {
		gdprError.value = 'Errore durante l\'esportazione dei dati. Riprova.';
	} finally {
		exportingData.value = false;
	}
};

const confirmDeleteAccount = async () => {
	deletingAccount.value = true;
	gdprError.value = null;
	try {
		await sanctum('/api/user/account', { method: 'DELETE' });
		clearSnapshot();
		showDeleteConfirm.value = false;
		try {
			await refreshNuxtData();
		} catch {
			// Se il refresh fallisce, il redirect alla home riallinea comunque la UI.
		}
		await navigateTo('/', { replace: true });
	} catch {
		gdprError.value = 'Errore durante l\'eliminazione dell\'account. Riprova.';
	} finally {
		deletingAccount.value = false;
	}
};

const getTelephoneNumber = (tel) => {
	if (!tel || tel === '0') return 'Non ancora aggiunto';
	return tel;
};

const userTypeLabel = computed(() => (props.user?.user_type || 'privato') === 'commerciante' ? 'Azienda' : 'Privato');

const infoFields = computed(() => {
	const fields = [
		{
			label: 'Email',
			value: props.user?.email,
			icon: '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>',
			stroke: true,
		},
		{
			label: 'Numero di telefono',
			value: getTelephoneNumber(props.user?.telephone_number),
			icon: '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>',
			stroke: true,
		},
		{
			label: 'Tipo account',
			value: userTypeLabel.value,
			icon: '<path d="M12,5.5A3.5,3.5 0 0,1 15.5,9A3.5,3.5 0 0,1 12,12.5A3.5,3.5 0 0,1 8.5,9A3.5,3.5 0 0,1 12,5.5M5,8C5.56,8 6.08,8.15 6.53,8.42C6.38,9.85 6.8,11.27 7.66,12.38C7.16,13.34 6.16,14 5,14A3,3 0 0,1 2,11A3,3 0 0,1 5,8M19,8A3,3 0 0,1 22,11A3,3 0 0,1 19,14C17.84,14 16.84,13.34 16.34,12.38C17.2,11.27 17.62,9.85 17.47,8.42C17.92,8.15 18.44,8 19,8M5.5,18.25C5.5,16.18 8.41,14.5 12,14.5C15.59,14.5 18.5,16.18 18.5,18.25V20H5.5V18.25Z"/>',
			stroke: false,
		},
	];

	if (props.user?.company_name) {
		fields.splice(3, 0, {
			label: 'Azienda',
			value: props.user.company_name,
			sub: props.user.vat_number ? `P.IVA: ${props.user.vat_number}` : '',
			icon: '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
			stroke: true,
		});
	}

	return fields;
});
</script>

<template>
<!-- eslint-disable vue/no-v-html -- icone SVG da dictionary accountCardIcons (no input utente) -->
	<section class="mb-6 overflow-hidden rounded-card bg-white shadow-[0_2px_8px_rgba(9,88,102,0.06),0_0_0_1px_rgba(9,88,102,0.04)]" aria-labelledby="sf-profilo-view-dati">
		<div class="mb-3 flex items-center justify-between px-4 pt-4">
			<div class="flex items-center gap-3">
				<div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-[9px] bg-brand-primary/[0.08]">
					<svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" /><circle cx="12" cy="7" r="4" /></svg>
				</div>
				<h2 id="sf-profilo-view-dati" class="text-sm font-bold tracking-tight text-brand-text">Dati personali</h2>
			</div>
			<SfButton variant="secondary" size="sm" aria-label="Modifica dati personali" @click="emit('edit')">
				<template #leading>
					<svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" /><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" /></svg>
				</template>
				Modifica
			</SfButton>
		</div>
		<div class="px-4 pb-4">
			<div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
				<div
					v-for="(field, idx) in infoFields"
					:key="idx"
					class="flex items-start gap-2.5 rounded-[14px] p-3 transition-colors hover:bg-brand-primary/[0.03]">
					<div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-[10px] bg-brand-primary/[0.07]">
						<svg
							v-if="field.stroke"
							aria-hidden="true"
							xmlns="http://www.w3.org/2000/svg"
							width="15"
							height="15"
							viewBox="0 0 24 24"
							fill="none"
							stroke="#095866"
							stroke-width="2"
							v-html="field.icon" />
						<svg
							v-else
							aria-hidden="true"
							xmlns="http://www.w3.org/2000/svg"
							viewBox="0 0 24 24"
							class="h-[15px] w-[15px]"
							fill="#095866"
							v-html="field.icon" />
					</div>
					<div class="min-w-0 flex-1">
						<p class="text-[10px] font-semibold uppercase tracking-[0.3px] text-brand-text-muted">{{ field.label }}</p>
						<p class="truncate text-[13px] font-bold leading-tight text-brand-text">{{ field.value }}</p>
						<p v-if="field.sub" class="text-[10px] text-brand-text-muted">{{ field.sub }}</p>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section class="mb-6 overflow-hidden rounded-card bg-white shadow-[0_2px_8px_rgba(9,88,102,0.06),0_0_0_1px_rgba(9,88,102,0.04)]" aria-labelledby="sf-profilo-view-notifiche">
		<div class="mb-3 flex items-center gap-3 px-4 pt-4">
			<div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-[9px] bg-brand-primary/[0.08]">
				<svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" /></svg>
			</div>
			<h2 id="sf-profilo-view-notifiche" class="text-sm font-bold tracking-tight text-brand-text">Notifiche</h2>
		</div>
		<div class="space-y-2 px-4 pb-4">
			<div
				aria-label="Gestione email tramite assistenza"
				class="group flex cursor-default items-center gap-2.5 rounded-[12px] p-3 no-underline opacity-70">
				<div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-[10px] bg-brand-primary/[0.07]">
					<svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" /></svg>
				</div>
				<span class="flex-1 text-[13px] font-semibold text-brand-text">Notifiche email</span>
				<span class="text-[11px] font-semibold text-brand-text-secondary">Via assistenza</span>
			</div>
		</div>
	</section>

	<section class="mb-6 overflow-hidden rounded-card bg-white shadow-[0_2px_8px_rgba(9,88,102,0.06),0_0_0_1px_rgba(9,88,102,0.04)]" aria-labelledby="sf-profilo-view-privacy">
		<div class="mb-3 flex items-center gap-3 px-4 pt-4">
			<div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-[9px] bg-brand-primary/[0.08]">
				<svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" /></svg>
			</div>
			<h2 id="sf-profilo-view-privacy" class="text-sm font-bold tracking-tight text-brand-text">Privacy e dati personali</h2>
		</div>
		<div class="space-y-2 px-4 pb-4">
			<p
				v-if="gdprError"
				role="alert"
				class="mb-1 rounded-[10px] border border-brand-error/20 bg-brand-error/[0.08] px-3 py-2 text-xs text-brand-error">
				{{ gdprError }}
			</p>
			<button
				:disabled="exportingData"
				class="group flex w-full cursor-pointer items-center gap-2.5 rounded-[12px] p-3 text-left transition-colors hover:bg-brand-primary/[0.03] disabled:cursor-not-allowed disabled:opacity-60"
				@click="exportData">
				<div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-[10px] bg-brand-primary/[0.07]">
					<svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" /><polyline points="7 10 12 15 17 10" /><line x1="12" y1="15" x2="12" y2="3" /></svg>
				</div>
				<div class="flex-1">
					<span class="block text-[13px] font-semibold text-brand-text">
						{{ exportingData ? 'Esportazione in corso...' : 'Esporta i tuoi dati' }}
					</span>
					<span class="text-[10px] text-brand-text-muted">Scarica una copia dei tuoi dati personali (Art. 20 GDPR)</span>
				</div>
				<span class="text-[11px] font-semibold text-brand-primary opacity-0 transition-opacity group-hover:opacity-100">Scarica</span>
			</button>
			<button
				v-if="!showDeleteConfirm"
				class="group flex w-full cursor-pointer items-center gap-2.5 rounded-[12px] p-3 text-left transition-colors hover:bg-brand-error/[0.06]"
				@click="showDeleteConfirm = true">
				<div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-[10px] bg-brand-error/[0.08]">
					<svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--color-brand-error)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" /></svg>
				</div>
				<div class="flex-1">
					<span class="block text-[13px] font-semibold text-brand-error">Elimina account</span>
					<span class="text-[10px] text-brand-text-muted">Cancella definitivamente account e dati personali (Art. 17 GDPR)</span>
				</div>
			</button>
			<div
				v-if="showDeleteConfirm"
				role="alertdialog"
				aria-labelledby="sf-profilo-delete-title"
				aria-describedby="sf-profilo-delete-desc"
				class="space-y-2.5 rounded-[14px] border border-brand-error/[0.24] bg-brand-error/[0.06] p-3.5">
				<p id="sf-profilo-delete-title" class="text-[13px] font-bold text-brand-error">Sei sicuro di voler eliminare il tuo account?</p>
				<p id="sf-profilo-delete-desc" class="text-xs leading-snug text-brand-error">Azione irreversibile. Dati personali, indirizzi e spedizioni eliminati. Ordini completati anonimizzati per obblighi fiscali.</p>
				<div class="flex gap-3">
					<SfButton variant="secondary" size="sm" @click="showDeleteConfirm = false">
						Annulla
					</SfButton>
					<button
						:disabled="deletingAccount"
						type="button"
						class="inline-flex h-9 items-center justify-center rounded-full bg-brand-error px-3.5 text-xs font-bold text-white shadow-[0_6px_18px_rgba(239,68,68,0.24)] transition hover:bg-[#dc2626] hover:shadow-[0_8px_22px_rgba(239,68,68,0.32)] focus-visible:outline focus-visible:outline-[3px] focus-visible:outline-offset-2 focus-visible:outline-brand-error/45 disabled:cursor-not-allowed disabled:opacity-60"
						@click="confirmDeleteAccount">
						{{ deletingAccount ? 'Eliminazione...' : 'Conferma eliminazione' }}
					</button>
				</div>
			</div>
		</div>
	</section>

	<button
		type="button"
		aria-label="Esci dall'account"
		class="flex h-10 w-full cursor-pointer items-center justify-center gap-2 rounded-full bg-brand-bg-alt text-xs font-semibold text-brand-text-muted transition-all duration-300 hover:bg-brand-primary/[0.08] hover:text-brand-primary"
		@click.prevent="emit('logout')">
		<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" /><polyline points="16 17 21 12 16 7" /><line x1="21" y1="12" x2="9" y2="12" /></svg>
		Esci dall'account
	</button>
</template>
