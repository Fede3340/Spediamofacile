<script setup>
import { Head, useForm } from '@inertiajs/vue3';
const form = useForm({ tracking: '' });
const submit = () => {
	if (!form.tracking) return;
	window.location.href = `/traccia/${form.tracking}`;
};
</script>
<template>
	<Head title="Traccia spedizione" />
	<div class="sf-container py-12 max-w-2xl text-center">
		<h1 class="text-4xl font-bold mb-4">Traccia spedizione</h1>
		<p class="text-[var(--color-brand-text-secondary)] mb-8">Inserisci il codice tracking BRT per vedere lo stato della tua spedizione.</p>
		<form @submit.prevent="submit" class="flex gap-3">
			<input v-model="form.tracking" type="text" placeholder="Es. 0123456789" class="flex-1 border border-[var(--color-brand-border)] rounded-full px-5 py-3" required />
			<button type="submit" class="bg-[var(--color-brand-orange)] text-white px-6 py-3 rounded-full font-semibold hover:bg-[var(--color-brand-orange-light)]">Traccia</button>
		</form>
	</div>
</template>
