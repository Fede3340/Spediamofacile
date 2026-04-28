<script setup>
import { Head, useForm } from '@inertiajs/vue3';
const props = defineProps({ user: { type: Object, required: true } });
const form = useForm({
	name: props.user.name,
	surname: props.user.surname || '',
	email: props.user.email,
	telephone_number: props.user.telephone_number || '',
});
const submit = () => form.put('/account/profilo', { preserveScroll: true });
</script>
<template>
	<Head title="Profilo" />
	<div class="sf-container py-12 max-w-2xl">
		<h1 class="text-3xl font-bold mb-6">Il mio profilo</h1>
		<form @submit.prevent="submit" class="bg-white border border-[var(--color-brand-border)] rounded-2xl p-6 space-y-4">
			<div class="grid md:grid-cols-2 gap-4">
				<div>
					<label class="block text-sm font-semibold mb-1">Nome</label>
					<input v-model="form.name" type="text" required class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2" />
				</div>
				<div>
					<label class="block text-sm font-semibold mb-1">Cognome</label>
					<input v-model="form.surname" type="text" class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2" />
				</div>
			</div>
			<div>
				<label class="block text-sm font-semibold mb-1">Email</label>
				<input v-model="form.email" type="email" required class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2" />
			</div>
			<div>
				<label class="block text-sm font-semibold mb-1">Telefono</label>
				<input v-model="form.telephone_number" type="tel" class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2" />
			</div>
			<button type="submit" :disabled="form.processing" class="bg-[var(--color-brand-orange)] text-white px-6 py-2 rounded-full font-semibold disabled:opacity-50">Salva</button>
		</form>
	</div>
</template>
