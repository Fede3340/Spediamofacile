<script setup>
import { computed } from 'vue';

const props = defineProps({
	user: { type: Object, default: null },
});

defineEmits(['close']);

const fullName = computed(() => props.user
	? `${props.user.name || ''} ${props.user.surname || ''}`.trim()
	: '');

const initials = computed(() => {
	if (!props.user) return '?';
	const f = (props.user.name?.[0] || '').toUpperCase();
	const l = (props.user.surname?.[0] || '').toUpperCase();
	return `${f}${l}` || '?';
});
</script>

<template>
	<header class="admin-drawer__head">
		<div class="admin-drawer__head-main">
			<div class="admin-drawer__avatar" aria-hidden="true">{{ initials }}</div>
			<div class="admin-drawer__head-copy">
				<h2 id="drawer-title" class="admin-drawer__title">{{ fullName || 'Utente' }}</h2>
				<p class="admin-drawer__subtitle">{{ user?.email || '\u2014' }}</p>
			</div>
		</div>
		<button type="button" class="admin-drawer__close" aria-label="Chiudi dettaglio" @click="$emit('close')">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/></svg>
		</button>
	</header>
</template>

<style scoped>
.admin-drawer__head {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
	padding: 16px 20px;
	border-bottom: 1px solid var(--admin-border);
	background: var(--admin-surface-muted);
}

.admin-drawer__head-main {
	display: flex;
	align-items: center;
	gap: 12px;
	min-width: 0;
}

.admin-drawer__avatar {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 44px;
	height: 44px;
	border-radius: 999px;
	background: var(--admin-status-success-bg);
	color: var(--admin-status-success);
	font-weight: 800;
	font-size: 0.875rem;
}

.admin-drawer__head-copy {
	min-width: 0;
}

.admin-drawer__title {
	margin: 0;
	font-size: 1rem;
	font-weight: 800;
	line-height: 1.2;
	color: var(--admin-text-primary);
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	max-width: 280px;
}

.admin-drawer__subtitle {
	margin: 2px 0 0;
	font-size: 0.8125rem;
	color: var(--admin-text-secondary);
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	max-width: 280px;
}

.admin-drawer__close {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 32px;
	height: 32px;
	border-radius: var(--admin-radius-sm);
	border: 1px solid var(--admin-border);
	background: var(--admin-surface);
	color: var(--admin-text-secondary);
	cursor: pointer;
	flex-shrink: 0;
	transition: var(--admin-transition-fast);
}

.admin-drawer__close:hover {
	background: var(--admin-surface-hover);
	color: var(--admin-text-primary);
}
</style>
