<script setup>
import { Head, Link } from '@inertiajs/vue3';
defineProps({ stats: { type: Object, default: () => ({}) } });
</script>
<template>
	<Head title="Console amministrazione" />
	<div class="sf-container py-12">
		<h1 class="text-3xl font-bold mb-6">Console amministrazione</h1>
		<div class="grid md:grid-cols-4 gap-4 mb-8">
			<div class="bg-white border rounded-xl p-4"><div class="text-xs uppercase font-semibold text-[var(--color-brand-text-muted)]">Ordini oggi</div><div class="text-2xl font-bold mt-1">{{ stats.orders_today || 0 }}</div></div>
			<div class="bg-white border rounded-xl p-4"><div class="text-xs uppercase font-semibold text-[var(--color-brand-text-muted)]">Ricavi mese</div><div class="text-2xl font-bold mt-1">{{ stats.revenue_month || '0,00 €' }}</div></div>
			<div class="bg-white border rounded-xl p-4"><div class="text-xs uppercase font-semibold text-[var(--color-brand-text-muted)]">Utenti totali</div><div class="text-2xl font-bold mt-1">{{ stats.users_total || 0 }}</div></div>
			<div class="bg-white border rounded-xl p-4"><div class="text-xs uppercase font-semibold text-[var(--color-brand-text-muted)]">Bonifici pending</div><div class="text-2xl font-bold mt-1">{{ stats.bank_transfers_pending || 0 }}</div></div>
		</div>
		<div class="grid md:grid-cols-3 gap-4">
			<Link href="/account/amministrazione/ordini" class="block bg-white border rounded-xl p-5 hover:shadow-md no-underline text-inherit"><h3 class="font-bold">Ordini</h3><p class="text-sm text-[var(--color-brand-text-secondary)]">Lista, stato, etichette BRT</p></Link>
			<Link href="/account/amministrazione/utenti" class="block bg-white border rounded-xl p-5 hover:shadow-md no-underline text-inherit"><h3 class="font-bold">Utenti</h3><p class="text-sm text-[var(--color-brand-text-secondary)]">Ruoli, stati, dettaglio</p></Link>
			<Link href="/account/amministrazione/spedizioni" class="block bg-white border rounded-xl p-5 hover:shadow-md no-underline text-inherit"><h3 class="font-bold">Coda BRT</h3><p class="text-sm text-[var(--color-brand-text-secondary)]">Tracking + stati</p></Link>
			<Link href="/account/amministrazione/bonifici" class="block bg-white border rounded-xl p-5 hover:shadow-md no-underline text-inherit"><h3 class="font-bold">Bonifici</h3><p class="text-sm text-[var(--color-brand-text-secondary)]">In attesa di conferma</p></Link>
			<Link href="/account/amministrazione/prezzi" class="block bg-white border rounded-xl p-5 hover:shadow-md no-underline text-inherit"><h3 class="font-bold">Prezzi</h3><p class="text-sm text-[var(--color-brand-text-secondary)]">Bands, supplementi</p></Link>
			<Link href="/account/amministrazione/impostazioni" class="block bg-white border rounded-xl p-5 hover:shadow-md no-underline text-inherit"><h3 class="font-bold">Impostazioni</h3><p class="text-sm text-[var(--color-brand-text-secondary)]">Config sistema</p></Link>
		</div>
	</div>
</template>
