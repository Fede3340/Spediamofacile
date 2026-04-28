<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
	name: '', surname: '', email: '', telephone_number: '', message: '',
});
const submit = () => form.post('/contatti', { preserveScroll: true });
</script>
<template>
	<Head title="Contatti" />
	<div class="sf-container py-12 max-w-2xl">
		<p class="text-sm font-semibold text-[var(--color-brand-orange)] uppercase tracking-wide text-center">Assistenza</p>
		<h1 class="text-4xl font-bold text-center mt-2 mb-8">Parla con noi</h1>
		<div class="grid md:grid-cols-3 gap-4 mb-10">
			<a href="mailto:info@spediamofacile.it" class="block bg-white border border-[var(--color-brand-border)] rounded-xl p-4 hover:shadow-md transition">
				<div class="text-xs uppercase text-[var(--color-brand-text-muted)] font-semibold">Email</div>
				<div class="text-sm font-semibold mt-1">info@spediamofacile.it</div>
			</a>
			<a href="tel:+390282954130" class="block bg-white border border-[var(--color-brand-border)] rounded-xl p-4 hover:shadow-md transition">
				<div class="text-xs uppercase text-[var(--color-brand-text-muted)] font-semibold">Telefono</div>
				<div class="text-sm font-semibold mt-1">+39 02 8295 4130</div>
			</a>
			<div class="bg-white border border-[var(--color-brand-border)] rounded-xl p-4">
				<div class="text-xs uppercase text-[var(--color-brand-text-muted)] font-semibold">Orari</div>
				<div class="text-sm font-semibold mt-1">Lun-Ven 9:00-18:00</div>
			</div>
		</div>
		<form @submit.prevent="submit" class="bg-white border border-[var(--color-brand-border)] rounded-2xl p-6 space-y-4">
			<div class="grid md:grid-cols-2 gap-4">
				<div>
					<label class="block text-sm font-semibold mb-1">Nome</label>
					<input v-model="form.name" type="text" required class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2 text-sm" />
					<p v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</p>
				</div>
				<div>
					<label class="block text-sm font-semibold mb-1">Cognome</label>
					<input v-model="form.surname" type="text" required class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2 text-sm" />
				</div>
			</div>
			<div class="grid md:grid-cols-2 gap-4">
				<div>
					<label class="block text-sm font-semibold mb-1">Email</label>
					<input v-model="form.email" type="email" required class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2 text-sm" />
					<p v-if="form.errors.email" class="text-xs text-red-600 mt-1">{{ form.errors.email }}</p>
				</div>
				<div>
					<label class="block text-sm font-semibold mb-1">Telefono</label>
					<input v-model="form.telephone_number" type="tel" class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2 text-sm" />
				</div>
			</div>
			<div>
				<label class="block text-sm font-semibold mb-1">Messaggio</label>
				<textarea v-model="form.message" rows="5" required class="w-full border border-[var(--color-brand-border)] rounded-lg px-3 py-2 text-sm"></textarea>
			</div>
			<button type="submit" :disabled="form.processing" class="w-full bg-[var(--color-brand-orange)] text-white py-3 rounded-full font-semibold hover:bg-[var(--color-brand-orange-light)] disabled:opacity-50">
				{{ form.processing ? 'Invio...' : 'Invia messaggio' }}
			</button>
		</form>
	</div>
</template>
