<script setup>
const model = defineModel({
	type: Object,
	required: true,
});

defineProps({
	saving: { type: Boolean, default: false },
});

defineEmits(['save']);
</script>

<template>
	<section class="admin-drawer-section">
		<h3 class="admin-drawer-section__title">Permessi e stato</h3>
		<div class="admin-drawer-form">
			<label class="admin-drawer-field">
				<span class="admin-drawer-field__label">Ruolo</span>
				<select v-model="model.role" class="admin-drawer-field__control" :disabled="saving">
					<option value="User">Cliente (Privato)</option>
					<option value="Partner">Partner</option>
					<option value="Partner Pro">Partner Pro</option>
					<option value="Admin">Admin</option>
				</select>
			</label>
			<label class="admin-drawer-field">
				<span class="admin-drawer-field__label">Stato account</span>
				<select v-model="model.status" class="admin-drawer-field__control" :disabled="saving">
					<option value="active">Attivo</option>
					<option value="pending-verification">In verifica email</option>
					<option value="banned">Bannato</option>
				</select>
			</label>
			<label class="admin-drawer-toggle">
				<input v-model="model.is_pro" type="checkbox" :disabled="saving" >
				<span class="admin-drawer-toggle__track"><span class="admin-drawer-toggle__thumb" /></span>
				<span class="admin-drawer-toggle__label">Utente Pro (visibile come Partner Pro)</span>
			</label>
			<button type="button" class="admin-drawer-save" :disabled="saving" @click="$emit('save')">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M21,7L9,19L3.5,13.5L4.91,12.09L9,16.17L19.59,5.59L21,7Z"/></svg>
				{{ saving ? 'Salvataggio...' : 'Salva modifiche' }}
			</button>
		</div>
	</section>
</template>

<style scoped>
.admin-drawer-section {
	display: flex;
	flex-direction: column;
	gap: 10px;
}

.admin-drawer-section__title {
	margin: 0;
	font-size: 0.6875rem;
	font-weight: 800;
	letter-spacing: 0.1em;
	text-transform: uppercase;
	color: var(--admin-text-muted);
}

.admin-drawer-form {
	display: flex;
	flex-direction: column;
	gap: 10px;
	padding: 12px;
	background: var(--admin-surface-muted);
	border: 1px solid var(--admin-border);
	border-radius: var(--admin-radius-sm);
}

.admin-drawer-field {
	display: flex;
	flex-direction: column;
	gap: 4px;
}

.admin-drawer-field__label {
	font-size: 0.6875rem;
	font-weight: 700;
	letter-spacing: 0.04em;
	text-transform: uppercase;
	color: var(--admin-text-muted);
}

.admin-drawer-field__control {
	height: var(--admin-button-height);
	padding: 0 12px;
	border: 1px solid var(--admin-border);
	border-radius: var(--admin-radius-sm);
	background: var(--admin-surface);
	color: var(--admin-text-primary);
	font-size: 0.875rem;
	font-weight: 600;
	cursor: pointer;
	transition: var(--admin-transition-fast);
}

.admin-drawer-field__control:focus-visible {
	outline: none;
	border-color: var(--admin-border-selected);
	box-shadow: var(--admin-focus-ring);
}

.admin-drawer-toggle {
	display: flex;
	align-items: center;
	gap: 10px;
	padding: 8px 4px;
	cursor: pointer;
}

.admin-drawer-toggle input {
	position: absolute;
	width: 1px;
	height: 1px;
	opacity: 0;
	pointer-events: none;
}

.admin-drawer-toggle__track {
	position: relative;
	width: 36px;
	height: 20px;
	border-radius: 999px;
	background: var(--admin-border-hover);
	flex-shrink: 0;
	transition: background var(--sf-t1) var(--sf-ease);
}

.admin-drawer-toggle__thumb {
	position: absolute;
	top: 2px;
	left: 2px;
	width: 16px;
	height: 16px;
	border-radius: 999px;
	background: #fff;
	box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
	transition: transform var(--sf-t1) var(--sf-ease);
}

.admin-drawer-toggle input:checked + .admin-drawer-toggle__track {
	background: var(--admin-status-success);
}

.admin-drawer-toggle input:checked + .admin-drawer-toggle__track .admin-drawer-toggle__thumb {
	transform: translateX(16px);
}

.admin-drawer-toggle__label {
	font-size: 0.8125rem;
	font-weight: 600;
	color: var(--admin-text-primary);
}

.admin-drawer-save {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	gap: 6px;
	min-height: var(--admin-button-height);
	padding: 0 14px;
	border-radius: var(--admin-radius-sm);
	border: 1px solid var(--admin-status-success);
	background: var(--admin-status-success);
	color: var(--admin-text-on-brand);
	font-size: 0.8125rem;
	font-weight: 700;
	cursor: pointer;
	transition: var(--admin-transition-fast);
}

.admin-drawer-save:hover:not(:disabled) {
	background: var(--color-brand-primary-hover, #074a56);
	border-color: var(--color-brand-primary-hover, #074a56);
}

.admin-drawer-save:disabled {
	opacity: 0.6;
	cursor: not-allowed;
}
</style>
