<script setup>
import { Head } from '@inertiajs/vue3';
defineProps({ addresses: { type: Array, default: () => [] } });
</script>
<template>
	<Head title="Indirizzi" />
	<div class="sf-container py-12">
		<h1 class="text-3xl font-bold mb-6">I tuoi indirizzi</h1>
		<div v-if="addresses.length" class="grid md:grid-cols-2 gap-4">
			<div v-for="a in addresses" :key="a.id" class="bg-white border border-[var(--color-brand-border)] rounded-xl p-5">
				<div class="font-semibold">{{ a.name }}</div>
				<div class="text-sm text-[var(--color-brand-text-secondary)]">{{ a.address }}, {{ a.address_number }}</div>
				<div class="text-sm text-[var(--color-brand-text-secondary)]">{{ a.postal_code }} {{ a.city }} ({{ a.province }})</div>
				<div class="text-sm text-[var(--color-brand-text-secondary)]">{{ a.telephone_number }}</div>
			</div>
		</div>
		<div v-else class="text-center py-12 text-[var(--color-brand-text-muted)]">Nessun indirizzo salvato.</div>
	</div>
</template>
