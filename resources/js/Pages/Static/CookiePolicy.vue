<script setup>
import { Head } from '@inertiajs/vue3';
defineProps({ content: { type: String, default: '' } });
</script>
<template>
	<Head title="Cookie Policy" />
	<div class="sf-container py-12 max-w-3xl">
		<h1 class="text-4xl font-bold mb-6">Cookie Policy</h1>
		<div class="prose max-w-none text-[var(--color-brand-text-secondary)]" v-html="content || '<p>Usiamo cookie tecnici e funzionali. Per analytics e marketing chiediamo consenso esplicito.</p>'"></div>
	</div>
</template>
