<script setup>
import { Head, useForm } from '@inertiajs/vue3';
const props = defineProps({ settings: { type: Object, default: () => ({}) } });
const form = useForm({ ...props.settings });
const submit = () => form.put('/account/amministrazione/impostazioni', { preserveScroll: true });
</script>
<template>
	<Head title="Impostazioni admin" />
	<div class="sf-container py-12 max-w-2xl">
		<h1 class="text-3xl font-bold mb-6">Impostazioni</h1>
		<form @submit.prevent="submit" class="bg-white border rounded-2xl p-6 space-y-4">
			<div v-for="(value, key) in form.data()" :key="key">
				<label class="block text-sm font-semibold mb-1">{{ key }}</label>
				<input v-model="form[key]" type="text" class="w-full border rounded-lg px-3 py-2" />
			</div>
			<button type="submit" :disabled="form.processing" class="bg-[var(--color-brand-orange)] text-white px-6 py-2 rounded-full font-semibold disabled:opacity-50">Salva</button>
		</form>
	</div>
</template>
