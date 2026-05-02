<script setup>
defineProps({
	transactions: { type: Array, default: () => [] },
	formatDate: { type: Function, required: true },
	formatPrice: { type: Function, required: true },
});
</script>

<template>
	<div class="mt-2">
		<div v-if="!transactions.length" class="px-6 py-6 text-center text-sm text-brand-text-muted bg-brand-bg-alt border border-dashed border-brand-border rounded-control">
			Nessun movimento wallet.
		</div>
		<ul v-else class="list-none m-0 p-0 flex flex-col gap-1.5">
			<li v-for="tx in transactions.slice(0, 10)" :key="tx.id" class="flex justify-between items-center gap-2.5 px-3 py-2.5 bg-brand-bg-alt border border-brand-border rounded-control">
				<div class="min-w-0">
					<p class="m-0 text-sm font-bold text-brand-text truncate max-w-[280px]">{{ tx.description || tx.type || 'Movimento' }}</p>
					<p class="mt-0.5 text-[0.6875rem] text-brand-text-muted">{{ formatDate(tx.created_at) }}</p>
				</div>
				<span :class="['text-sm font-bold tabular-nums', Number(tx.amount) < 0 ? 'text-red-700' : 'text-brand-primary']">
					{{ formatPrice(tx.amount) }}
				</span>
			</li>
		</ul>
	</div>
</template>
