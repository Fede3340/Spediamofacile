<script setup>
import { Head } from '@inertiajs/vue3';
defineProps({ content: { type: String, default: '' } });
</script>
<template>
	<Head title="Privacy Policy" />
	<div class="sf-container py-12 max-w-3xl">
		<h1 class="text-4xl font-bold mb-6">Informativa sulla privacy</h1>
		<div class="prose max-w-none text-[var(--color-brand-text-secondary)]" v-html="content || '<p>Documento privacy completo disponibile su richiesta a privacy@spediamofacile.it.</p>'"></div>
	</div>
</template>
