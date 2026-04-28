<script setup>
import { Head } from '@inertiajs/vue3';
defineProps({ content: { type: String, default: '' } });
</script>
<template>
	<Head title="Termini e Condizioni" />
	<div class="sf-container py-12 max-w-3xl">
		<h1 class="text-4xl font-bold mb-6">Termini e Condizioni di Servizio</h1>
		<div class="prose max-w-none text-[var(--color-brand-text-secondary)]" v-html="content || '<p>Termini di servizio SpediamoFacile. Versione completa disponibile su richiesta.</p>'"></div>
	</div>
</template>
