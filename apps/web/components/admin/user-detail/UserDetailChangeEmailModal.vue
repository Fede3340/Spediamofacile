<script setup>
const props = defineProps({
	open: { type: Boolean, default: false },
	fullName: { type: String, default: '' },
	saving: { type: Boolean, default: false },
});

const email = defineModel('email', { type: String, default: '' });

const emit = defineEmits(['update:open', 'confirm']);

const close = () => emit('update:open', false);
</script>

<template>
	<ClientOnly>
		<UModal
			v-if="open"
			:open="open"
			title="Cambia email"
			description="Inserisci la nuova email dell'utente e conferma l'aggiornamento."
			:ui="{ overlay: 'bg-[#09131c]/36 backdrop-blur-[6px]', content: '!divide-y-0 !ring-0 !p-0 sf-modal-surface w-[min(calc(100vw-1rem),26rem)]', body: '!p-0' }"
			@update:open="$emit('update:open', $event)">
			<template #body>
				<section class="admin-drawer-email-modal">
					<h3 class="admin-drawer-email-modal__title">Cambia email</h3>
					<p class="admin-drawer-email-modal__desc">Inserisci la nuova email per {{ fullName }}. L'utente verra avvisato.</p>
					<input v-model="email" type="email" class="admin-drawer-email-modal__input" placeholder="nome@dominio.it" >
					<div class="admin-drawer-email-modal__actions">
						<button type="button" class="admin-drawer-action" :disabled="saving" @click="close">Annulla</button>
						<button type="button" class="admin-drawer-action admin-drawer-action--success" :disabled="saving || !email" @click="$emit('confirm')">
							{{ saving ? 'Salvataggio...' : 'Conferma' }}
						</button>
					</div>
				</section>
			</template>
		</UModal>
	</ClientOnly>
</template>

<style scoped>
.admin-drawer-email-modal {
	padding: 20px;
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.admin-drawer-email-modal__title {
	margin: 0;
	font-size: 1rem;
	font-weight: 800;
	color: var(--admin-text-primary);
}

.admin-drawer-email-modal__desc {
	margin: 0;
	font-size: 0.8125rem;
	color: var(--admin-text-secondary);
}

.admin-drawer-email-modal__input {
	height: var(--admin-button-height);
	padding: 0 12px;
	border: 1px solid var(--admin-border);
	border-radius: var(--admin-radius-sm);
	background: var(--admin-surface);
	color: var(--admin-text-primary);
	font-size: 0.875rem;
}

.admin-drawer-email-modal__input:focus-visible {
	outline: none;
	border-color: var(--admin-border-selected);
	box-shadow: var(--admin-focus-ring);
}

.admin-drawer-email-modal__actions {
	display: flex;
	justify-content: flex-end;
	gap: 8px;
}

.admin-drawer-action {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	gap: 6px;
	min-height: var(--admin-button-height);
	padding: 0 12px;
	border-radius: var(--admin-radius-sm);
	border: 1px solid var(--admin-border);
	background: var(--admin-surface);
	color: var(--admin-text-primary);
	font-size: 0.8125rem;
	font-weight: 700;
	cursor: pointer;
	transition: var(--admin-transition-fast);
}

.admin-drawer-action:hover:not(:disabled) {
	background: var(--admin-surface-hover);
	border-color: var(--admin-border-selected);
}

.admin-drawer-action--success {
	background: var(--admin-status-success);
	color: var(--admin-text-on-brand);
	border-color: var(--admin-status-success);
}

.admin-drawer-action--success:hover:not(:disabled) {
	background: var(--color-brand-primary-hover, #074a56);
}

.admin-drawer-action:disabled {
	opacity: 0.6;
	cursor: not-allowed;
}
</style>
