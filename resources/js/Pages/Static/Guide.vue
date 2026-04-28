<script setup>
import { Head, Link } from '@inertiajs/vue3';
defineProps({ guides: { type: Array, default: () => [] } });
</script>
<template>
	<Head title="Guide alle spedizioni" />
	<div class="sf-container py-12">
		<h1 class="text-4xl font-bold text-center mb-8">Guide e risorse</h1>
		<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
			<Link v-for="g in guides" :key="g.slug" :href="`/guide/${g.slug}`" class="block bg-white border border-[var(--color-brand-border)] rounded-2xl p-6 hover:shadow-lg transition no-underline text-inherit">
				<h3 class="font-bold mb-2">{{ g.title }}</h3>
				<p class="text-sm text-[var(--color-brand-text-secondary)] line-clamp-3">{{ g.excerpt }}</p>
			</Link>
		</div>
	</div>
</template>
