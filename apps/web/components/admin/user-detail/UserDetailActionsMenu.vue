<script setup>
defineProps({
	isBanned: { type: Boolean, default: false },
	canMaster: { type: Boolean, default: false },
});

defineEmits(['reset-password', 'toggle-ban', 'change-email', 'impersonate']);
</script>

<template>
	<section class="admin-drawer-section admin-drawer-section--danger">
		<h3 class="admin-drawer-section__title">Azioni rapide</h3>
		<div class="admin-drawer-actions">
			<button type="button" class="admin-drawer-action" @click="$emit('reset-password')">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12.63,2C18.16,2 22.64,6.5 22.64,12C22.64,17.5 18.16,22 12.63,22C9.12,22 6.05,20.18 4.26,17.43L5.84,16.18C7.25,18.47 9.76,20 12.64,20A8,8 0 0,0 20.64,12A8,8 0 0,0 12.64,4C8.56,4 5.2,7.06 4.71,11H7.47L3.73,14.73L0,11H2.69C3.19,5.95 7.45,2 12.63,2M15.59,10.24C16.09,10.25 16.5,10.65 16.5,11.16V15.77C16.5,16.27 16.09,16.69 15.58,16.69H10.05C9.54,16.69 9.13,16.27 9.13,15.77V11.16C9.13,10.65 9.54,10.25 10.04,10.24V9.23C10.04,7.7 11.29,6.46 12.81,6.46C14.34,6.46 15.59,7.7 15.59,9.23V10.24M12.81,7.86C12.06,7.86 11.44,8.47 11.44,9.23V10.24H14.19V9.23C14.19,8.47 13.57,7.86 12.81,7.86Z"/></svg>
				Reset password
			</button>
			<button type="button" :class="['admin-drawer-action', isBanned ? 'admin-drawer-action--success' : 'admin-drawer-action--danger']" @click="$emit('toggle-ban')">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12,2C17.5,2 22,6.5 22,12C22,17.5 17.5,22 12,22C6.5,22 2,17.5 2,12C2,6.5 6.5,2 12,2M12,4C10.1,4 8.4,4.6 7.1,5.7L18.3,16.9C19.3,15.5 20,13.8 20,12C20,7.6 16.4,4 12,4M16.9,18.3L5.7,7.1C4.6,8.4 4,10.1 4,12C4,16.4 7.6,20 12,20C13.9,20 15.6,19.4 16.9,18.3Z"/></svg>
				{{ isBanned ? 'Rimuovi ban' : 'Banna utente' }}
			</button>
			<button v-if="canMaster" type="button" class="admin-drawer-action" @click="$emit('change-email')">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M20,8L12,13L4,8V6L12,11L20,6M20,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6C22,4.89 21.1,4 20,4Z"/></svg>
				Cambia email
			</button>
			<button v-if="canMaster" type="button" class="admin-drawer-action admin-drawer-action--accent" @click="$emit('impersonate')">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M10,17V14H3V10H10V7L15,12L10,17M10,2H19A2,2 0 0,1 21,4V20A2,2 0 0,1 19,22H10A2,2 0 0,1 8,20V18H10V20H19V4H10V6H8V4A2,2 0 0,1 10,2Z"/></svg>
				Impersona
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

.admin-drawer-section--danger {
	padding-top: 14px;
	border-top: 1px solid var(--admin-border);
}

.admin-drawer-section__title {
	margin: 0;
	font-size: 0.6875rem;
	font-weight: 800;
	letter-spacing: 0.1em;
	text-transform: uppercase;
	color: var(--admin-text-muted);
}

.admin-drawer-actions {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
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

.admin-drawer-action--danger {
	color: var(--admin-status-danger-text, #b91c1c);
	border-color: rgba(220, 38, 38, 0.32);
}

.admin-drawer-action--danger:hover:not(:disabled) {
	background: var(--admin-status-danger-bg, rgba(220, 38, 38, 0.08));
	border-color: var(--admin-status-danger, #dc2626);
}

.admin-drawer-action--success {
	background: var(--admin-status-success);
	color: var(--admin-text-on-brand);
	border-color: var(--admin-status-success);
}

.admin-drawer-action--success:hover:not(:disabled) {
	background: var(--color-brand-primary-hover, #074a56);
}

.admin-drawer-action--accent {
	background: var(--color-brand-accent, #E44203);
	color: #fff;
	border-color: var(--color-brand-accent, #E44203);
}

.admin-drawer-action--accent:hover:not(:disabled) {
	filter: brightness(0.95);
}

.admin-drawer-action:disabled {
	opacity: 0.6;
	cursor: not-allowed;
}

@media (max-width: 540px) {
	.admin-drawer-actions {
		grid-template-columns: 1fr;
	}
}
</style>
