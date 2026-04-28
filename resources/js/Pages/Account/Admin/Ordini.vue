<script setup>
import { Head, Link } from '@inertiajs/vue3';
defineProps({ orders: { type: Object, default: () => ({ data: [] }) } });
</script>
<template>
	<Head title="Ordini admin" />
	<div class="sf-container py-12">
		<h1 class="text-3xl font-bold mb-6">Ordini</h1>
		<div class="bg-white border rounded-2xl overflow-hidden">
			<table class="w-full">
				<thead class="bg-[var(--color-brand-bg)] text-left text-xs uppercase font-semibold">
					<tr><th class="p-3">#</th><th class="p-3">Cliente</th><th class="p-3">Tratta</th><th class="p-3">Totale</th><th class="p-3">Stato</th><th class="p-3"></th></tr>
				</thead>
				<tbody>
					<tr v-for="o in orders.data" :key="o.id" class="border-t">
						<td class="p-3 font-semibold">#{{ o.id }}</td>
						<td class="p-3">{{ o.user_name }}</td>
						<td class="p-3 text-sm">{{ o.route }}</td>
						<td class="p-3">{{ o.total }}</td>
						<td class="p-3"><span class="text-xs px-2 py-1 rounded-full" :class="o.status_class">{{ o.status }}</span></td>
						<td class="p-3"><Link :href="`/account/amministrazione/ordini/${o.id}`" class="text-[var(--color-brand-orange)] text-sm font-semibold">Apri</Link></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</template>
