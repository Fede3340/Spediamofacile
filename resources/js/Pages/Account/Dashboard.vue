<script setup>
import { Head, Link } from '@inertiajs/vue3';
defineProps({
	stats: { type: Object, default: () => ({}) },
	recentOrders: { type: Array, default: () => [] },
});
</script>
<template>
	<Head title="Account" />
	<div class="sf-container py-12">
		<h1 class="text-3xl font-bold mb-6">Il mio account</h1>
		<div class="grid md:grid-cols-4 gap-4 mb-8">
			<div class="bg-white border border-[var(--color-brand-border)] rounded-xl p-4">
				<div class="text-xs uppercase text-[var(--color-brand-text-muted)] font-semibold">Spedizioni</div>
				<div class="text-2xl font-bold mt-1">{{ stats.shipments || 0 }}</div>
			</div>
			<div class="bg-white border border-[var(--color-brand-border)] rounded-xl p-4">
				<div class="text-xs uppercase text-[var(--color-brand-text-muted)] font-semibold">In transito</div>
				<div class="text-2xl font-bold mt-1">{{ stats.in_transit || 0 }}</div>
			</div>
			<div class="bg-white border border-[var(--color-brand-border)] rounded-xl p-4">
				<div class="text-xs uppercase text-[var(--color-brand-text-muted)] font-semibold">Wallet</div>
				<div class="text-2xl font-bold mt-1">{{ stats.wallet_balance || '0,00 €' }}</div>
			</div>
			<div class="bg-white border border-[var(--color-brand-border)] rounded-xl p-4">
				<div class="text-xs uppercase text-[var(--color-brand-text-muted)] font-semibold">Fatture</div>
				<div class="text-2xl font-bold mt-1">{{ stats.invoices || 0 }}</div>
			</div>
		</div>
		<div class="grid md:grid-cols-3 gap-4 mb-8">
			<Link href="/account/spedizioni" class="block bg-white border border-[var(--color-brand-border)] rounded-xl p-5 hover:shadow-md no-underline text-inherit">
				<h3 class="font-bold mb-1">Le mie spedizioni</h3>
				<p class="text-sm text-[var(--color-brand-text-secondary)]">Lista, tracking, etichette</p>
			</Link>
			<Link href="/account/indirizzi" class="block bg-white border border-[var(--color-brand-border)] rounded-xl p-5 hover:shadow-md no-underline text-inherit">
				<h3 class="font-bold mb-1">Indirizzi</h3>
				<p class="text-sm text-[var(--color-brand-text-secondary)]">Mittenti e destinatari salvati</p>
			</Link>
			<Link href="/account/portafoglio" class="block bg-white border border-[var(--color-brand-border)] rounded-xl p-5 hover:shadow-md no-underline text-inherit">
				<h3 class="font-bold mb-1">Portafoglio</h3>
				<p class="text-sm text-[var(--color-brand-text-secondary)]">Ricarica, movimenti, prelievi</p>
			</Link>
			<Link href="/account/fatture" class="block bg-white border border-[var(--color-brand-border)] rounded-xl p-5 hover:shadow-md no-underline text-inherit">
				<h3 class="font-bold mb-1">Fatture</h3>
				<p class="text-sm text-[var(--color-brand-text-secondary)]">Storico fatturazione</p>
			</Link>
			<Link href="/account/profilo" class="block bg-white border border-[var(--color-brand-border)] rounded-xl p-5 hover:shadow-md no-underline text-inherit">
				<h3 class="font-bold mb-1">Profilo</h3>
				<p class="text-sm text-[var(--color-brand-text-secondary)]">Dati anagrafici e password</p>
			</Link>
			<Link href="/account/assistenza" class="block bg-white border border-[var(--color-brand-border)] rounded-xl p-5 hover:shadow-md no-underline text-inherit">
				<h3 class="font-bold mb-1">Assistenza</h3>
				<p class="text-sm text-[var(--color-brand-text-secondary)]">Apri ticket o consulta FAQ</p>
			</Link>
		</div>
		<div v-if="recentOrders.length" class="bg-white border border-[var(--color-brand-border)] rounded-2xl p-6">
			<h2 class="text-xl font-bold mb-4">Ultime spedizioni</h2>
			<div class="space-y-2">
				<Link v-for="o in recentOrders" :key="o.id" :href="`/account/spedizioni/${o.id}`" class="flex items-center justify-between py-2 border-b border-[var(--color-brand-border)] hover:bg-[var(--color-brand-bg)] no-underline text-inherit">
					<div>
						<div class="font-semibold">#{{ o.id }} {{ o.route_label || '—' }}</div>
						<div class="text-xs text-[var(--color-brand-text-muted)]">{{ o.created_at }}</div>
					</div>
					<div class="text-sm font-semibold">{{ o.total }}</div>
				</Link>
			</div>
		</div>
	</div>
</template>
