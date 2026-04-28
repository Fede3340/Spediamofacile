<script setup>
import { Head, Link } from '@inertiajs/vue3';
defineProps({ orders: { type: Object, default: () => ({ data: [] }) } });
</script>
<template>
	<Head title="Le mie spedizioni" />
	<div class="sf-container py-12">
		<div class="flex items-center justify-between mb-6">
			<h1 class="text-3xl font-bold">Le mie spedizioni</h1>
			<Link href="/preventivo" class="bg-[var(--color-brand-orange)] text-white px-5 py-2 rounded-full font-semibold">+ Nuova spedizione</Link>
		</div>
		<div v-if="orders.data && orders.data.length" class="space-y-3">
			<Link v-for="o in orders.data" :key="o.id" :href="`/account/spedizioni/${o.id}`" class="block bg-white border border-[var(--color-brand-border)] rounded-xl p-4 hover:shadow-md no-underline text-inherit">
				<div class="flex items-center justify-between">
					<div>
						<div class="font-semibold">Ordine #{{ o.id }}</div>
						<div class="text-sm text-[var(--color-brand-text-secondary)]">{{ o.route_label || '—' }}</div>
					</div>
					<div class="text-right">
						<div class="text-sm font-semibold">{{ o.payable_total }}</div>
						<div class="text-xs px-2 py-1 rounded-full inline-block mt-1" :class="o.status_class">{{ o.status_label }}</div>
					</div>
				</div>
			</Link>
		</div>
		<div v-else class="text-center py-12 text-[var(--color-brand-text-muted)]">
			Nessuna spedizione ancora. <Link href="/preventivo" class="text-[var(--color-brand-orange)] font-semibold">Crea la prima</Link>.
		</div>
	</div>
</template>
