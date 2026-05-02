<script setup>
defineProps({
	addresses: { type: Array, default: () => [] },
});
</script>

<template>
	<div class="mt-2">
		<div v-if="!addresses.length" class="px-6 py-6 text-center text-sm text-brand-text-muted bg-brand-bg-alt border border-dashed border-brand-border rounded-control">
			Nessun indirizzo salvato.
		</div>
		<ul v-else class="list-none m-0 p-0 flex flex-col gap-1.5">
			<li v-for="a in addresses" :key="a.id" class="flex justify-between items-center gap-2.5 px-3 py-2.5 bg-brand-bg-alt border border-brand-border rounded-control">
				<div class="min-w-0">
					<p class="m-0 text-sm font-bold text-brand-text truncate max-w-[280px]">{{ a.label || a.name || 'Indirizzo' }}</p>
					<p class="mt-0.5 text-[0.6875rem] text-brand-text-muted">{{ a.address }}, {{ a.postal_code }} {{ a.city }}</p>
				</div>
			</li>
		</ul>
	</div>
</template>
