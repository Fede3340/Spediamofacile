<script setup>
import { Head } from '@inertiajs/vue3';
defineProps({ invoices: { type: Array, default: () => [] } });
</script>
<template>
	<Head title="Fatture" />
	<div class="sf-container py-12">
		<h1 class="text-3xl font-bold mb-6">Le tue fatture</h1>
		<div v-if="invoices.length" class="bg-white border border-[var(--color-brand-border)] rounded-2xl overflow-hidden">
			<table class="w-full">
				<thead class="bg-[var(--color-brand-bg)] text-left text-xs uppercase font-semibold">
					<tr><th class="p-3">Numero</th><th class="p-3">Data</th><th class="p-3">Importo</th><th class="p-3"></th></tr>
				</thead>
				<tbody>
					<tr v-for="inv in invoices" :key="inv.id" class="border-t border-[var(--color-brand-border)]">
						<td class="p-3 font-semibold">{{ inv.number }}</td>
						<td class="p-3">{{ inv.date }}</td>
						<td class="p-3">{{ inv.amount }}</td>
						<td class="p-3"><a :href="`/account/fatture/${inv.id}/pdf`" class="text-[var(--color-brand-orange)]">PDF</a></td>
					</tr>
				</tbody>
			</table>
		</div>
		<div v-else class="text-center py-12 text-[var(--color-brand-text-muted)]">Nessuna fattura ancora.</div>
	</div>
</template>
