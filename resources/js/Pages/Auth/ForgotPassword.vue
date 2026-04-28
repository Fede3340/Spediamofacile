<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
const form = useForm({ email: '' });
const submit = () => form.post('/recupera-password');
</script>
<template>
	<Head title="Recupera password" />
	<div class="sf-container py-12 max-w-md">
		<h1 class="text-3xl font-bold text-center mb-3">Recupera password</h1>
		<p class="text-center text-[var(--color-brand-text-secondary)] mb-6 text-sm">Inserisci la tua email: ti invieremo un link per reimpostarla.</p>
		<form @submit.prevent="submit" class="bg-white border border-[var(--color-brand-border)] rounded-2xl p-6 space-y-4">
			<div>
				<label class="block text-sm font-semibold mb-1">Email</label>
				<input v-model="form.email" type="email" required autocomplete="email" class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2" />
				<p v-if="form.errors.email" class="text-xs text-red-600 mt-1">{{ form.errors.email }}</p>
			</div>
			<button type="submit" :disabled="form.processing" class="w-full bg-[var(--color-brand-orange)] text-white py-3 rounded-full font-semibold hover:bg-[var(--color-brand-orange-light)] disabled:opacity-50">
				{{ form.processing ? 'Invio...' : 'Invia link' }}
			</button>
		</form>
		<div class="text-center mt-4 text-sm">
			<Link href="/login" class="text-[var(--color-brand-orange)]">← Torna al login</Link>
		</div>
	</div>
</template>
