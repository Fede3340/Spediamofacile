<script setup>
import { computed } from 'vue';

const props = defineProps({
	user: { type: Object, required: true },
	formatDate: { type: Function, required: true },
});

const userType = computed(() => {
	const t = (props.user?.user_type || 'privato').toLowerCase();
	return t === 'commerciante' || t === 'azienda' ? 'Azienda' : 'Privato';
});
</script>

<template>
	<section class="admin-drawer-section">
		<h3 class="admin-drawer-section__title">Dati profilo</h3>
		<dl class="admin-drawer-grid">
			<div class="admin-drawer-grid__cell">
				<dt>ID</dt>
				<dd>#{{ user.id }}</dd>
			</div>
			<div class="admin-drawer-grid__cell">
				<dt>Tipo</dt>
				<dd>{{ userType }}</dd>
			</div>
			<div class="admin-drawer-grid__cell">
				<dt>Telefono</dt>
				<dd>{{ user.telephone_number || '\u2014' }}</dd>
			</div>
			<div class="admin-drawer-grid__cell">
				<dt>Codice referral</dt>
				<dd class="admin-drawer-grid__mono">{{ user.referral_code || '\u2014' }}</dd>
			</div>
			<div class="admin-drawer-grid__cell">
				<dt>Registrato</dt>
				<dd>{{ formatDate(user.created_at) }}</dd>
			</div>
			<div class="admin-drawer-grid__cell">
				<dt>Email verificata</dt>
				<dd>{{ user.email_verified_at ? formatDate(user.email_verified_at) : 'No' }}</dd>
			</div>
		</dl>
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

.admin-drawer-grid {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: 8px;
	margin: 0;
	padding: 12px;
	background: var(--admin-surface-muted);
	border: 1px solid var(--admin-border);
	border-radius: var(--admin-radius-sm);
}

.admin-drawer-grid__cell dt {
	font-size: 0.625rem;
	font-weight: 700;
	letter-spacing: 0.06em;
	text-transform: uppercase;
	color: var(--admin-text-muted);
}

.admin-drawer-grid__cell dd {
	margin: 3px 0 0;
	font-size: 0.8125rem;
	font-weight: 600;
	color: var(--admin-text-primary);
	word-break: break-word;
}

.admin-drawer-grid__mono {
	font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
	font-size: 0.75rem;
}
</style>
