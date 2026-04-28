<script setup>
import { Head, useForm } from '@inertiajs/vue3';
defineProps({ status: String });
const form = useForm({});
const resend = () => form.post('/email/verification-notification');
</script>
<template>
	<Head title="Verifica email" />
	<div class="sf-container py-12 max-w-md text-center">
		<h1 class="text-3xl font-bold mb-3">Verifica la tua email</h1>
		<p class="text-[var(--color-brand-text-secondary)] mb-6">Ti abbiamo inviato un link di verifica. Controlla la posta.</p>
		<div v-if="status === 'verification-link-sent'" class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm mb-4">
			Nuovo link inviato.
		</div>
		<button @click="resend" :disabled="form.processing" class="text-[var(--color-brand-orange)] font-semibold">
			Reinvia link verifica
		</button>
	</div>
</template>
