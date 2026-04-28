<!--
  Componente: ShipmentFlowAdminGateModal
  Password gate per admin che accede al funnel fuori percorso normale.
  Consolidato 2026-04-20: wrapper sottile su <SfModal> (primitive SF).
  Nessuna API esterna (state gestito via useShipmentFlowAdminGateStore).
-->
<script setup>
import { storeToRefs } from 'pinia';
import { SHIPMENT_FLOW_ROUTES } from '~/utils/shipment';

const adminGate = useShipmentFlowAdminGateStore();
const { challenge } = storeToRefs(adminGate);
const { closeGate } = adminGate;
const route = useRoute();
const sanctumClient = useSanctumClient();
const uiFeedback = useUiFeedback();

const password = ref('');
const errorMessage = ref('');
const isSubmitting = ref(false);

const isOpen = computed(() => Boolean(challenge.value));

const resetState = () => {
	password.value = '';
	errorMessage.value = '';
	isSubmitting.value = false;
};

const handleCancel = async () => {
	const fallback = challenge.value?.lastValidRoute || SHIPMENT_FLOW_ROUTES.packages;
	closeGate();
	resetState();
	if (route.fullPath !== fallback) {
		await navigateTo(fallback, { replace: true });
	}
};

const handleConfirm = async () => {
	if (isSubmitting.value) return;
	if (!String(password.value || '').trim()) {
		errorMessage.value = 'Inserisci la password amministratore per continuare.';
		return;
	}

	isSubmitting.value = true;
	errorMessage.value = '';

	try {
		await sanctumClient('/api/auth/confirm-password', {
			method: 'POST',
			body: { password: password.value },
		});
		uiFeedback.success('Conferma amministratore acquisita', 'Puoi proseguire nel flusso fuori percorso.');
		closeGate();
		resetState();
	} catch (error) {
		errorMessage.value = error?.data?.errors?.password?.[0]
			|| error?.data?.message
			|| 'Password non corretta. Riprova per continuare.';
	} finally {
		isSubmitting.value = false;
	}
};

// Difensivo: se per qualunque motivo body resta bloccato (legacy Reka/UModal),
// lo ripristiniamo esplicitamente. SfModal gestisce gia' overflow direttamente.
const restoreBodyInteractivity = () => {
	if (typeof document === 'undefined') return;
	const { body } = document;
	if (!body) return;
	body.style.pointerEvents = '';
	body.style.overflow = '';
	body.style.paddingRight = '';
	body.style.marginRight = '';
	body.style.removeProperty('--removed-body-scroll-bar-size');
};

watch(isOpen, async (open) => {
	if (open) return;
	resetState();
	await nextTick();
	restoreBodyInteractivity();
});

onBeforeUnmount(() => {
	restoreBodyInteractivity();
});

watch(() => route.fullPath, (currentPath) => {
	if (!challenge.value) return;
	if (challenge.value.targetPath === currentPath) return;
	closeGate();
	resetState();
});

// SfModal v-model: l'update a false significa tentativo di chiusura -> handleCancel.
// Con persistent=true Escape/backdrop non chiudono; resta possibile via bottoni/X.
const onUpdateModelValue = (value) => {
	if (!value) handleCancel();
};
</script>

<template>
	<SfModal
		:model-value="isOpen"
		size="md"
		persistent
		:close-on-backdrop="false"
		hide-close
		@update:model-value="onUpdateModelValue">
		<section class="sf-modal-content">
			<div class="sf-modal-header">
				<div class="sf-modal-header__main">
					<div class="sf-modal-icon sf-modal-icon--accent" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[1.2rem] h-[1.2rem]" fill="currentColor"><path d="M12,1L3,5V11C3,16.55 6.84,21.74 12,23C17.16,21.74 21,16.55 21,11V5L12,1M12,7C13.4,7 14.8,8.1 14.8,9.5V11C15.4,11 16,11.6 16,12.3V15.8C16,16.4 15.4,17 14.7,17H9.2C8.6,17 8,16.4 8,15.7V12.2C8,11.6 8.6,11 9.2,11V9.5C9.2,8.1 10.6,7 12,7M12,8.2C11.2,8.2 10.5,8.7 10.5,9.5V11H13.5V9.5C13.5,8.7 12.8,8.2 12,8.2Z"/></svg>
				</div>
					<div>
						<h2 class="sf-modal-title">Accesso admin fuori flusso</h2>
						<p class="sf-modal-description">
						Stai aprendo una sezione del funnel senza il percorso normale. Per motivi di sicurezza,
						conferma la password amministratore prima di continuare.
						</p>
					</div>
				</div>
				<button
					type="button"
					class="sf-modal-close"
					aria-label="Chiudi"
					@click="handleCancel">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[1rem] h-[1rem]" fill="currentColor"><path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/></svg>
				</button>
			</div>

			<div class="sf-modal-divider" />

			<div class="sf-modal-body">
				<div class="sf-modal-summary" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
					<div>
						<span class="admin-gate__label">Percorso richiesto</span>
						<strong>{{ challenge?.targetPath }}</strong>
					</div>
					<div>
						<span class="admin-gate__label">Fallback sicuro</span>
						<strong>{{ challenge?.lastValidRoute }}</strong>
					</div>
				</div>

				<div class="grid gap-[8px]">
					<label for="admin-flow-password" class="form-label">Password amministratore</label>
					<input
						id="admin-flow-password"
						v-model="password"
						type="password"
						class="form-input"
						autocomplete="current-password"
						placeholder="Inserisci la password per continuare"
						@keyup.enter="handleConfirm" />
					<p v-if="errorMessage" class="text-[0.8125rem] font-semibold text-[#b42318]">{{ errorMessage }}</p>
				</div>
			</div>

			<div class="sf-modal-divider" />

			<div class="sf-modal-actions">
				<SfButton variant="secondary" size="sm" @click="handleCancel">Torna al flusso corretto</SfButton>
				<SfButton variant="primary" size="sm" :loading="isSubmitting" loading-text="Verifica in corso..." @click="handleConfirm">Continua come admin</SfButton>
			</div>
		</section>
	</SfModal>
</template>

<style scoped>
.admin-gate__label {
	display: block;
	margin-bottom: 4px;
	font-size: 0.72rem;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.04em;
	color: #6b7d87;
}

@media (max-width: 640px) {
	.sf-modal-summary {
		grid-template-columns: 1fr !important;
	}
}
</style>
