<script setup>
defineProps({
	transactions: { type: Array, default: () => [] },
	formatDate: { type: Function, required: true },
	formatPrice: { type: Function, required: true },
});
</script>

<template>
	<div class="admin-drawer-tabpanel">
		<div v-if="!transactions.length" class="admin-drawer-empty">Nessun movimento wallet.</div>
		<ul v-else class="admin-drawer-list">
			<li v-for="tx in transactions.slice(0, 10)" :key="tx.id" class="admin-drawer-list__item">
				<div class="admin-drawer-list__main">
					<p class="admin-drawer-list__title">{{ tx.description || tx.type || 'Movimento' }}</p>
					<p class="admin-drawer-list__meta">{{ formatDate(tx.created_at) }}</p>
				</div>
				<span :class="['admin-drawer-list__value', Number(tx.amount) < 0 && 'admin-drawer-list__value--neg']">
					{{ formatPrice(tx.amount) }}
				</span>
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

.admin-drawer-list__value {
	font-size: 0.8125rem;
	font-weight: 700;
	color: var(--admin-status-success-text);
	font-variant-numeric: tabular-nums;
}

.admin-drawer-list__value--neg {
	color: var(--admin-status-danger-text, #b91c1c);
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
