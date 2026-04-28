<script setup>
import { Head, Link } from '@inertiajs/vue3';
defineProps({ service: { type: Object, required: true } });
</script>
<template>
	<Head :title="service.title" />
	<div class="sf-container py-12 max-w-3xl">
		<Link href="/servizi" class="text-sm text-[var(--color-brand-orange)] mb-4 inline-block">← Tutti i servizi</Link>
		<h1 class="text-4xl font-bold mb-4">{{ service.title }}</h1>
		<p class="text-lg text-[var(--color-brand-text-secondary)] mb-6">{{ service.description }}</p>
		<div v-if="service.body" class="prose max-w-none" v-html="service.body"></div>
	</div>
</template>
