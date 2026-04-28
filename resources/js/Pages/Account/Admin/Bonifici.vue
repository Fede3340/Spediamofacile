<script setup>
import { Head } from '@inertiajs/vue3';
defineProps({ orders: { type: Array, default: () => [] } });
</script>
<template>
	<Head title="Bonifici admin" />
	<div class="sf-container py-12">
		<h1 class="text-3xl font-bold mb-6">Bonifici in attesa</h1>
		<div v-if="orders.length" class="space-y-3">
			<div v-for="o in orders" :key="o.id" class="bg-white border rounded-xl p-4 flex items-center justify-between">
				<div>
					<div class="font-semibold">Ordine #{{ o.id }} · {{ o.user_name }}</div>
					<div class="text-sm text-[var(--color-brand-text-secondary)]">Atteso: {{ o.total }} · {{ o.created_at }}</div>
				</div>
				<button class="bg-green-600 text-white px-4 py-2 rounded-full text-sm font-semibold">Conferma</button>
			</div>
		</div>
		<div v-else class="text-center py-12 text-[var(--color-brand-text-muted)]">Nessun bonifico in attesa.</div>
	</div>
</template>
