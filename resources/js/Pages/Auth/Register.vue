<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
const form = useForm({
	name: '', surname: '', email: '', password: '', password_confirmation: '',
	telephone_number: '', privacy_accepted: false,
});
const submit = () => form.post('/registrazione', { onFinish: () => form.reset('password', 'password_confirmation') });
</script>
<template>
	<Head title="Registrati" />
	<div class="sf-container py-12 max-w-md">
		<h1 class="text-3xl font-bold text-center mb-6">Crea account</h1>
		<form @submit.prevent="submit" class="bg-white border border-[var(--color-brand-border)] rounded-2xl p-6 space-y-4">
			<div class="grid grid-cols-2 gap-3">
				<div>
					<label class="block text-sm font-semibold mb-1">Nome</label>
					<input v-model="form.name" type="text" required autocomplete="given-name" class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2" />
					<p v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</p>
				</div>
				<div>
					<label class="block text-sm font-semibold mb-1">Cognome</label>
					<input v-model="form.surname" type="text" required autocomplete="family-name" class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2" />
				</div>
			</div>
			<div>
				<label class="block text-sm font-semibold mb-1">Email</label>
				<input v-model="form.email" type="email" required autocomplete="email" class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2" />
				<p v-if="form.errors.email" class="text-xs text-red-600 mt-1">{{ form.errors.email }}</p>
			</div>
			<div>
				<label class="block text-sm font-semibold mb-1">Telefono</label>
				<input v-model="form.telephone_number" type="tel" autocomplete="tel" class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2" />
			</div>
			<div>
				<label class="block text-sm font-semibold mb-1">Password</label>
				<input v-model="form.password" type="password" required autocomplete="new-password" minlength="8" class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2" />
				<p v-if="form.errors.password" class="text-xs text-red-600 mt-1">{{ form.errors.password }}</p>
			</div>
			<div>
				<label class="block text-sm font-semibold mb-1">Conferma password</label>
				<input v-model="form.password_confirmation" type="password" required autocomplete="new-password" minlength="8" class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2" />
			</div>
			<label class="flex items-start gap-2 text-sm">
				<input v-model="form.privacy_accepted" type="checkbox" required class="mt-1" />
				<span>Accetto la <Link href="/privacy-policy" class="text-[var(--color-brand-orange)]">privacy policy</Link></span>
			</label>
			<p v-if="form.errors.privacy_accepted" class="text-xs text-red-600">{{ form.errors.privacy_accepted }}</p>
			<button type="submit" :disabled="form.processing" class="w-full bg-[var(--color-brand-orange)] text-white py-3 rounded-full font-semibold hover:bg-[var(--color-brand-orange-light)] disabled:opacity-50">
				{{ form.processing ? 'Creazione...' : 'Registrati' }}
			</button>
		</form>
		<div class="text-center mt-4 text-sm">
			Hai gia un account? <Link href="/login" class="text-[var(--color-brand-orange)] font-semibold">Accedi</Link>
		</div>
	</div>
</template>
