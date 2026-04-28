<!--
  Vista sola lettura del profilo utente con card dati e pulsante logout.
  Props: user.
  Events: edit, logout.
-->
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

const getRoleBadge = (role) => {
	if (role === 'Partner Pro') return { label: 'Partner Pro', class: 'sf-account-meta-pill' };
	if (role === 'Admin') return { label: 'Admin', class: 'sf-account-meta-pill sf-account-meta-pill--admin' };
	return { label: 'Cliente', class: 'sf-account-meta-pill sf-account-meta-pill--muted' };
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
	<!-- Personal data section -->
	<section class="rounded-[16px] bg-white mb-[24px] overflow-hidden" style="box-shadow: 0 2px 8px rgba(9,88,102,0.06), 0 0 0 1px rgba(9,88,102,0.04);" aria-labelledby="sf-profilo-view-dati">
		<!-- Section header -->
		<div class="flex items-center justify-between px-[16px] pt-[16px] mb-[12px]">
			<div class="flex items-center gap-[12px]">
				<div class="w-[28px] h-[28px] rounded-[9px] bg-[rgba(9,88,102,0.08)] flex items-center justify-center shrink-0">
					<svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
				</div>
				<h2 id="sf-profilo-view-dati" class="text-[var(--color-brand-text)] text-[14px] tracking-[-0.2px] font-[700]">Dati personali</h2>
			</div>
			<button
				@click="emit('edit')"
				type="button"
				aria-label="Modifica dati personali"
				class="sf-flow-cta sf-flow-cta--secondary sf-flow-cta--compact !h-[36px] !px-[14px] !text-[12px]">
				<svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
				Modifica
			</button>
		</div>
		<!-- Fields grid -->
		<div class="px-[16px] pb-[16px]">
			<div class="grid grid-cols-1 tablet:grid-cols-2 xl:grid-cols-3 gap-[12px]">
				<div
					v-for="(field, idx) in infoFields"
					:key="idx"
					class="flex items-start gap-[10px] p-[12px] rounded-[14px] hover:bg-[rgba(9,88,102,0.03)] transition-colors">
					<div class="w-[32px] h-[32px] rounded-[10px] bg-[rgba(9,88,102,0.07)] flex items-center justify-center shrink-0">
						<svg aria-hidden="true"
							v-if="field.stroke"
							xmlns="http://www.w3.org/2000/svg"
							width="15"
							height="15"
							viewBox="0 0 24 24"
							fill="none"
							stroke="#095866"
							stroke-width="2"
							v-html="field.icon"></svg>
						<svg aria-hidden="true"
							v-else
							xmlns="http://www.w3.org/2000/svg"
							viewBox="0 0 24 24"
							class="w-[15px] h-[15px]"
							fill="#095866"
							v-html="field.icon"></svg>
					</div>
					<div class="min-w-0 flex-1">
						<p class="text-[var(--color-brand-text-muted)] text-[10px] font-[600] uppercase tracking-[0.3px]">{{ field.label }}</p>
						<p class="text-[var(--color-brand-text)] text-[13px] font-[700] leading-[1.25] truncate">{{ field.value }}</p>
						<p v-if="field.sub" class="text-[var(--color-brand-text-muted)] text-[10px]">{{ field.sub }}</p>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- Notifiche section -->
	<section class="rounded-[16px] bg-white mb-[24px] overflow-hidden" style="box-shadow: 0 2px 8px rgba(9,88,102,0.06), 0 0 0 1px rgba(9,88,102,0.04);" aria-labelledby="sf-profilo-view-notifiche">
		<div class="flex items-center gap-[12px] px-[16px] pt-[16px] mb-[12px]">
			<div class="w-[28px] h-[28px] rounded-[9px] bg-[rgba(9,88,102,0.08)] flex items-center justify-center shrink-0">
				<svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
			</div>
			<h2 id="sf-profilo-view-notifiche" class="text-[var(--color-brand-text)] text-[14px] tracking-[-0.2px] font-[700]">Notifiche</h2>
		</div>
		<div class="px-[16px] pb-[16px] space-y-[8px]">
			<!-- -- ARCHIVIATO 2026-04-20: link /account/notifiche (_archive/frontend-simplification-2026-04-20/features/notifiche-in-app) -- -->
			<div
				aria-label="Gestione email tramite assistenza"
				class="flex items-center gap-[10px] p-[12px] rounded-[12px] cursor-default group no-underline opacity-70">
				<div class="w-[32px] h-[32px] rounded-[10px] bg-[rgba(9,88,102,0.07)] flex items-center justify-center shrink-0">
					<svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
				</div>
				<span class="text-[var(--color-brand-text)] text-[13px] flex-1 font-[600]">Notifiche email</span>
				<span class="text-[var(--color-brand-text-secondary)] text-[11px] font-[600]">Via assistenza</span>
			</div>
		</div>
	</section>

	<!-- GDPR section: Privacy & Dati personali -->
	<section class="rounded-[16px] bg-white mb-[24px] overflow-hidden" style="box-shadow: 0 2px 8px rgba(9,88,102,0.06), 0 0 0 1px rgba(9,88,102,0.04);" aria-labelledby="sf-profilo-view-privacy">
		<div class="flex items-center gap-[12px] px-[16px] pt-[16px] mb-[12px]">
			<div class="w-[28px] h-[28px] rounded-[9px] bg-[rgba(9,88,102,0.08)] flex items-center justify-center shrink-0">
				<svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
			</div>
			<h2 id="sf-profilo-view-privacy" class="text-[var(--color-brand-text)] text-[14px] tracking-[-0.2px] font-[700]">Privacy e dati personali</h2>
		</div>
		<div class="px-[16px] pb-[16px] space-y-[8px]">
			<p
				v-if="gdprError"
				role="alert"
				class="text-[12px] rounded-[10px] px-[12px] py-[8px] mb-[4px]"
				style="color: var(--color-brand-error); background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2);">
				{{ gdprError }}
			</p>
			<!-- Esporta dati -->
			<button
				@click="exportData"
				:disabled="exportingData"
				class="flex items-center gap-[10px] p-[12px] rounded-[12px] hover:bg-[rgba(9,88,102,0.03)] transition-colors cursor-pointer group w-full text-left disabled:opacity-60 disabled:cursor-not-allowed">
				<div class="w-[32px] h-[32px] rounded-[10px] bg-[rgba(9,88,102,0.07)] flex items-center justify-center shrink-0">
					<svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
				</div>
				<div class="flex-1">
					<span class="text-[var(--color-brand-text)] text-[13px] block font-[600]">
						{{ exportingData ? 'Esportazione in corso...' : 'Esporta i tuoi dati' }}
					</span>
					<span class="text-[var(--color-brand-text-muted)] text-[10px]">Scarica una copia dei tuoi dati personali (Art. 20 GDPR)</span>
				</div>
				<span class="text-[var(--color-brand-primary)] text-[11px] opacity-0 group-hover:opacity-100 transition-opacity font-[600]">Scarica</span>
			</button>
			<!-- Elimina account -->
			<button
				v-if="!showDeleteConfirm"
				@click="showDeleteConfirm = true"
				class="flex items-center gap-[10px] p-[12px] rounded-[12px] transition-colors cursor-pointer group w-full text-left sf-profilo-delete-trigger">
				<div class="w-[32px] h-[32px] rounded-[10px] flex items-center justify-center shrink-0" style="background: rgba(239,68,68,0.08);">
					<svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--color-brand-error)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
				</div>
				<div class="flex-1">
					<span class="text-[13px] block font-[600]" style="color: var(--color-brand-error);">Elimina account</span>
					<span class="text-[var(--color-brand-text-muted)] text-[10px]">Cancella definitivamente account e dati personali (Art. 17 GDPR)</span>
				</div>
			</button>
			<!-- Conferma eliminazione -->
			<div
				v-if="showDeleteConfirm"
				role="alertdialog"
				aria-labelledby="sf-profilo-delete-title"
				aria-describedby="sf-profilo-delete-desc"
				class="p-[14px] rounded-[14px] space-y-[10px]"
				style="background: rgba(239,68,68,0.06); border: 1px solid rgba(239,68,68,0.24);">
				<p id="sf-profilo-delete-title" class="text-[13px] font-[700]" style="color: var(--color-brand-error);">Sei sicuro di voler eliminare il tuo account?</p>
				<p id="sf-profilo-delete-desc" class="text-[12px] leading-[1.45]" style="color: var(--color-brand-error);">Azione irreversibile. Dati personali, indirizzi e spedizioni eliminati. Ordini completati anonimizzati per obblighi fiscali.</p>
				<div class="flex gap-[12px]">
					<button
						@click="showDeleteConfirm = false"
						type="button"
						class="sf-flow-cta sf-flow-cta--secondary sf-flow-cta--compact !h-[36px] !px-[14px] !text-[12px]">
						Annulla
					</button>
					<button
						@click="confirmDeleteAccount"
						:disabled="deletingAccount"
						type="button"
						class="sf-flow-cta sf-flow-cta--compact sf-profilo-delete-confirm !h-[36px] !px-[14px] !text-[12px] disabled:opacity-60 disabled:cursor-not-allowed">
						{{ deletingAccount ? 'Eliminazione...' : 'Conferma eliminazione' }}
					</button>
				</div>
			</div>
		</div>
	</section>

	<!-- Logout button -->
	<button
		@click.prevent="emit('logout')"
		type="button"
		aria-label="Esci dall'account"
		class="w-full h-[40px] rounded-full bg-[#F0F1F4] hover:bg-[rgba(9,88,102,0.08)] text-[var(--color-brand-text-muted)] hover:text-[var(--color-brand-primary)] text-[12px] font-[600] flex items-center justify-center gap-[8px] cursor-pointer transition-all duration-[350ms]">
		<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
		Esci dall'account
	</button>
</template>

<!-- Stili estratti in assets/css/account.css (importato da main.css). -->
