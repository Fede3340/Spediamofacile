<script setup>
defineProps({
	addresses: { type: Array, default: () => [] },
});
</script>

<template>
	<div class="admin-drawer-tabpanel">
		<div v-if="!addresses.length" class="admin-drawer-empty">Nessun indirizzo salvato.</div>
		<ul v-else class="admin-drawer-list">
			<li v-for="a in addresses" :key="a.id" class="admin-drawer-list__item">
				<div class="admin-drawer-list__main">
					<p class="admin-drawer-list__title">{{ a.label || a.name || 'Indirizzo' }}</p>
					<p class="admin-drawer-list__meta">{{ a.address }}, {{ a.postal_code }} {{ a.city }}</p>
				</div>
			</li>
		</ul>
	</div>
</template>

<style scoped>
.admin-drawer-tabpanel {
	margin-top: 8px;
}

.admin-drawer-list {
	list-style: none;
	margin: 0;
	padding: 0;
	display: flex;
	flex-direction: column;
	gap: 6px;
}

.admin-drawer-list__item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: 10px;
	padding: 10px 12px;
	background: var(--admin-surface-muted);
	border: 1px solid var(--admin-border);
	border-radius: var(--admin-radius-sm);
}

.admin-drawer-list__main {
	min-width: 0;
}

.admin-drawer-list__title {
	margin: 0;
	font-size: 0.8125rem;
	font-weight: 700;
	color: var(--admin-text-primary);
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	max-width: 280px;
}

.admin-drawer-list__meta {
	margin: 2px 0 0;
	font-size: 0.6875rem;
	color: var(--admin-text-muted);
}

.admin-drawer-empty {
	padding: 24px;
	text-align: center;
	color: var(--admin-text-muted);
	font-size: 0.8125rem;
	background: var(--admin-surface-muted);
	border: 1px dashed var(--admin-border);
	border-radius: var(--admin-radius-sm);
}
</style>
