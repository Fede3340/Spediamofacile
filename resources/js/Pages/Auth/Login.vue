<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
defineProps({ canResetPassword: { type: Boolean, default: true } });
const form = useForm({ email: '', password: '', remember: false });
const submit = () => form.post('/login', { onFinish: () => form.reset('password') });
</script>
<template>
	<Head title="Accedi" />
	<div class="sf-container py-12 max-w-md">
		<h1 class="text-3xl font-bold text-center mb-6">Accedi</h1>
		<form @submit.prevent="submit" class="bg-white border border-[var(--color-brand-border)] rounded-2xl p-6 space-y-4">
			<div>
				<label class="block text-sm font-semibold mb-1">Email</label>
				<input v-model="form.email" type="email" required autocomplete="email" class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2" />
				<p v-if="form.errors.email" class="text-xs text-red-600 mt-1">{{ form.errors.email }}</p>
			</div>
			<div>
				<label class="block text-sm font-semibold mb-1">Password</label>
				<input v-model="form.password" type="password" required autocomplete="current-password" class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2" />
				<p v-if="form.errors.password" class="text-xs text-red-600 mt-1">{{ form.errors.password }}</p>
			</div>
			<label class="flex items-center gap-2 text-sm">
				<input v-model="form.remember" type="checkbox" />
				Ricordami
			</label>
			<button type="submit" :disabled="form.processing" class="w-full bg-[var(--color-brand-orange)] text-white py-3 rounded-full font-semibold hover:bg-[var(--color-brand-orange-light)] disabled:opacity-50">
				{{ form.processing ? 'Accesso...' : 'Accedi' }}
			</button>
		</form>
		<div class="text-center mt-4 text-sm space-y-2">
			<Link v-if="canResetPassword" href="/recupera-password" class="text-[var(--color-brand-orange)]">Password dimenticata?</Link>
			<div>Non hai un account? <Link href="/registrazione" class="text-[var(--color-brand-orange)] font-semibold">Registrati</Link></div>
		</div>
	</div>
</template>
