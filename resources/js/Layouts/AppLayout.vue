<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const auth = computed(() => page.props.auth || {});
const flash = computed(() => page.props.flash || {});

const navLinks = [
	{ href: '/preventivo', label: 'Preventivo' },
	{ href: '/servizi', label: 'Servizi' },
	{ href: '/traccia', label: 'Traccia' },
	{ href: '/guide', label: 'Guide' },
	{ href: '/contatti', label: 'Contatti' },
];
</script>

<template>
	<div class="min-h-screen flex flex-col bg-white">
		<header class="border-b border-[var(--color-brand-border)] sticky top-0 bg-white z-40">
			<div class="sf-container flex items-center justify-between h-16">
				<Link href="/" class="flex items-center gap-3 no-underline">
					<span class="w-10 h-10 rounded-full bg-[var(--color-brand-teal)] text-white font-bold flex items-center justify-center">SF</span>
					<span class="font-bold text-lg text-[var(--color-brand-text)]">SpediamoFacile</span>
				</Link>
				<nav class="hidden md:flex items-center gap-6">
					<Link v-for="l in navLinks" :key="l.href" :href="l.href" class="text-sm font-medium text-[var(--color-brand-text-secondary)] hover:text-[var(--color-brand-teal)]">
						{{ l.label }}
					</Link>
				</nav>
				<div class="flex items-center gap-3">
					<Link v-if="!auth.user" href="/login" class="text-sm font-semibold text-[var(--color-brand-text)] hover:text-[var(--color-brand-teal)]">Accedi</Link>
					<Link v-else href="/account" class="text-sm font-semibold text-[var(--color-brand-text)]">{{ auth.user.name }}</Link>
					<Link href="/carrello" class="bg-[var(--color-brand-orange)] text-white px-4 py-2 rounded-full text-sm font-semibold hover:bg-[var(--color-brand-orange-light)]">Carrello</Link>
				</div>
			</div>
		</header>

		<div v-if="flash.success" class="sf-container py-2">
			<div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">{{ flash.success }}</div>
		</div>
		<div v-if="flash.error" class="sf-container py-2">
			<div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">{{ flash.error }}</div>
		</div>

		<main class="flex-1">
			<slot />
		</main>

		<footer class="border-t border-[var(--color-brand-border)] mt-16">
			<div class="sf-container py-12 grid md:grid-cols-4 gap-8 text-sm">
				<div>
					<div class="flex items-center gap-3 mb-3">
						<span class="w-10 h-10 rounded-full bg-[var(--color-brand-teal)] text-white font-bold flex items-center justify-center">SF</span>
						<span class="font-bold">SpediamoFacile</span>
					</div>
					<p class="text-[var(--color-brand-text-secondary)]">Intermediari BRT. Spedisci semplice.</p>
				</div>
				<div>
					<h4 class="font-semibold mb-3">Servizi</h4>
					<ul class="space-y-2 text-[var(--color-brand-text-secondary)]">
						<li><Link href="/servizi">Tutti i servizi</Link></li>
						<li><Link href="/preventivo">Preventivo</Link></li>
						<li><Link href="/traccia">Traccia spedizione</Link></li>
					</ul>
				</div>
				<div>
					<h4 class="font-semibold mb-3">Azienda</h4>
					<ul class="space-y-2 text-[var(--color-brand-text-secondary)]">
						<li><Link href="/chi-siamo">Chi siamo</Link></li>
						<li><Link href="/contatti">Contatti</Link></li>
					</ul>
				</div>
				<div>
					<h4 class="font-semibold mb-3">Legale</h4>
					<ul class="space-y-2 text-[var(--color-brand-text-secondary)]">
						<li><Link href="/privacy-policy">Privacy</Link></li>
						<li><Link href="/cookie-policy">Cookie</Link></li>
						<li><Link href="/termini-e-condizioni">Termini</Link></li>
					</ul>
				</div>
			</div>
			<div class="border-t border-[var(--color-brand-border)] py-4">
				<p class="sf-container text-xs text-[var(--color-brand-text-muted)]">© 2026 SpediamoFacile. Tutti i diritti riservati.</p>
			</div>
		</footer>
	</div>
</template>
