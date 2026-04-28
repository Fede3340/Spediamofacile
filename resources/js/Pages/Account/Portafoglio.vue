<script setup>
import { Head } from '@inertiajs/vue3';
defineProps({
	balance: { type: String, default: '0,00 €' },
	movements: { type: Array, default: () => [] },
});
</script>
<template>
	<Head title="Portafoglio" />
	<div class="sf-container py-12 max-w-3xl">
		<h1 class="text-3xl font-bold mb-6">Portafoglio</h1>
		<div class="bg-gradient-to-br from-[var(--color-brand-teal)] to-[var(--color-brand-teal-dark)] text-white rounded-2xl p-8 mb-6">
			<div class="text-sm opacity-80">Saldo disponibile</div>
			<div class="text-5xl font-bold mt-2">{{ balance }}</div>
		</div>
		<div class="bg-white border border-[var(--color-brand-border)] rounded-2xl p-6">
			<h2 class="font-bold mb-4">Movimenti recenti</h2>
			<div v-if="movements.length" class="space-y-2">
				<div v-for="m in movements" :key="m.id" class="flex items-center justify-between py-2 border-b border-[var(--color-brand-border)]">
					<div>
						<div class="font-semibold text-sm">{{ m.description }}</div>
						<div class="text-xs text-[var(--color-brand-text-muted)]">{{ m.date }}</div>
					</div>
					<div class="font-bold" :class="m.amount.startsWith('-') ? 'text-red-600' : 'text-green-700'">{{ m.amount }}</div>
				</div>
			</div>
			<div v-else class="text-center py-8 text-[var(--color-brand-text-muted)] text-sm">Nessun movimento.</div>
		</div>
	</div>
</template>
