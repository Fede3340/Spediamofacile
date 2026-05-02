<script setup>
defineProps({
	orders: { type: Array, default: () => [] },
	formatDate: { type: Function, required: true },
	formatPrice: { type: Function, required: true },
});
</script>

<template>
	<div class="mt-2">
		<div v-if="!orders.length" class="px-6 py-6 text-center text-sm text-brand-text-muted bg-brand-bg-alt border border-dashed border-brand-border rounded-control">
			Nessun ordine registrato.
		</div>
		<ul v-else class="list-none m-0 p-0 flex flex-col gap-1.5">
			<li v-for="o in orders.slice(0, 8)" :key="o.id" class="flex justify-between items-center gap-2.5 px-3 py-2.5 bg-brand-bg-alt border border-brand-border rounded-control">
				<div class="min-w-0">
					<p class="m-0 text-sm font-bold text-brand-text truncate max-w-[280px]">Ordine #{{ o.id }}</p>
					<p class="mt-0.5 text-[0.6875rem] text-brand-text-muted">{{ formatDate(o.created_at) }}</p>
				</div>
				<span class="text-sm font-bold text-brand-primary tabular-nums">{{ formatPrice(o.total_amount ?? o.amount ?? 0) }}</span>
			</li>
		</ul>
	</div>
</template>
