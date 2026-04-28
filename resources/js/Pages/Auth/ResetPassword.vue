<script setup>
import { Head, useForm } from '@inertiajs/vue3';
const props = defineProps({ token: String, email: String });
const form = useForm({
	token: props.token,
	email: props.email,
	password: '',
	password_confirmation: '',
});
const submit = () => form.post('/aggiorna-password', { onFinish: () => form.reset('password', 'password_confirmation') });
</script>
<template>
	<Head title="Reimposta password" />
	<div class="sf-container py-12 max-w-md">
		<h1 class="text-3xl font-bold text-center mb-6">Reimposta password</h1>
		<form @submit.prevent="submit" class="bg-white border border-[var(--color-brand-border)] rounded-2xl p-6 space-y-4">
			<div>
				<label class="block text-sm font-semibold mb-1">Email</label>
				<input v-model="form.email" type="email" required readonly class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2 bg-[var(--color-brand-bg)]" />
			</div>
			<div>
				<label class="block text-sm font-semibold mb-1">Nuova password</label>
				<input v-model="form.password" type="password" required minlength="8" autocomplete="new-password" class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2" />
				<p v-if="form.errors.password" class="text-xs text-red-600 mt-1">{{ form.errors.password }}</p>
			</div>
			<div>
				<label class="block text-sm font-semibold mb-1">Conferma password</label>
				<input v-model="form.password_confirmation" type="password" required minlength="8" autocomplete="new-password" class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2" />
			</div>
			<button type="submit" :disabled="form.processing" class="w-full bg-[var(--color-brand-orange)] text-white py-3 rounded-full font-semibold hover:bg-[var(--color-brand-orange-light)] disabled:opacity-50">
				{{ form.processing ? 'Aggiornamento...' : 'Reimposta password' }}
			</button>
		</form>
	</div>
</template>
