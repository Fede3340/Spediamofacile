<script setup>
defineProps({
	publishStateLabel: { type: String, required: true },
	publishStateHint: { type: String, required: true },
	sectionCount: { type: Number, required: true },
	faqCount: { type: Number, required: true },
});
</script>

<template>
	<SfCard padding="md" class="mb-5">
		<div class="grid gap-4">
			<div class="grid gap-1.5 max-w-[44rem]">
				<p class="text-xs font-bold uppercase tracking-wider text-brand-text-muted">Editor servizio</p>
				<h2 class="font-display text-xl font-extrabold text-brand-text leading-tight">Struttura, tono e pubblicazione</h2>
				<p class="text-sm text-brand-text-secondary">Base chiara, sezioni ordinate e FAQ per i dubbi ricorrenti.</p>
			</div>
			<div class="grid grid-cols-1 desktop:grid-cols-3 gap-3">
				<article class="relative grid gap-1 p-4 rounded-card border border-brand-border bg-brand-card overflow-hidden">
					<span class="absolute inset-y-0 left-0 w-1 bg-brand-primary" aria-hidden="true" />
					<span class="text-xs font-bold uppercase tracking-wider text-brand-text-muted">Stato</span>
					<strong class="text-lg font-extrabold text-brand-text">{{ publishStateLabel }}</strong>
					<span class="text-sm text-brand-text-secondary">{{ publishStateHint }}</span>
				</article>
				<article class="relative grid gap-1 p-4 rounded-card border border-brand-border bg-brand-card overflow-hidden">
					<span class="absolute inset-y-0 left-0 w-1 bg-brand-primary" aria-hidden="true" />
					<span class="text-xs font-bold uppercase tracking-wider text-brand-text-muted">Sezioni</span>
					<strong class="text-lg font-extrabold text-brand-text">{{ sectionCount }}</strong>
					<span class="text-sm text-brand-text-secondary">Blocchi principali del servizio</span>
				</article>
				<article class="relative grid gap-1 p-4 rounded-card border border-brand-border bg-brand-card overflow-hidden">
					<span class="absolute inset-y-0 left-0 w-1 bg-brand-primary" aria-hidden="true" />
					<span class="text-xs font-bold uppercase tracking-wider text-brand-text-muted">FAQ</span>
					<strong class="text-lg font-extrabold text-brand-text">{{ faqCount }}</strong>
					<span class="text-sm text-brand-text-secondary">Risposte rapide finali</span>
				</article>
			</div>
		</div>
	</SfCard>
</template>
