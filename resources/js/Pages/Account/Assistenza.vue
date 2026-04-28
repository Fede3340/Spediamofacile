<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>
<template>
	<Head title="Assistenza" />
	<div class="sf-container py-12 max-w-2xl">
		<h1 class="text-3xl font-bold mb-6">Assistenza</h1>
		<div class="space-y-4">
			<a href="mailto:info@spediamofacile.it" class="block bg-white border border-[var(--color-brand-border)] rounded-xl p-5 hover:shadow-md">
				<h3 class="font-bold mb-1">Email</h3>
				<p class="text-sm text-[var(--color-brand-text-secondary)]">info@spediamofacile.it · risposta in 24h</p>
			</a>
			<a href="tel:+390282954130" class="block bg-white border border-[var(--color-brand-border)] rounded-xl p-5 hover:shadow-md">
				<h3 class="font-bold mb-1">Telefono</h3>
				<p class="text-sm text-[var(--color-brand-text-secondary)]">+39 02 8295 4130 · Lun-Ven 9-18</p>
			</a>
			<Link href="/faq" class="block bg-white border border-[var(--color-brand-border)] rounded-xl p-5 hover:shadow-md no-underline text-inherit">
				<h3 class="font-bold mb-1">FAQ</h3>
				<p class="text-sm text-[var(--color-brand-text-secondary)]">Domande frequenti</p>
			</Link>
		</div>
	</div>
</template>
