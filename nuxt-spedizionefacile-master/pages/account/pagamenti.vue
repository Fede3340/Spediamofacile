<script setup>
/**
 * Pagamenti — hub unificato per Portafoglio + Carte + Fatture.
 *
 * Pattern Stripe/Shopify: una sola voce sidebar "Pagamenti" con 3 tab
 * che linkano alle pagine esistenti. Riduce cognitive load (P6 plan).
 *
 * Le 3 pagine /account/portafoglio /account/carte /account/fatture
 * restano funzionanti per link diretti e SEO; il vantaggio è la voce
 * unica nella sidebar e l'header coerente.
 */
import '~/assets/css/account-shell.css';

definePageMeta({
	middleware: ['app-auth'],
});

useSeoMeta({
	title: 'Pagamenti | SpediamoFacile',
	description: 'Portafoglio, carte e fatture in un unico spazio: gestisci i tuoi pagamenti su SpediamoFacile.',
	robots: 'noindex, nofollow',
});

const route = useRoute();
const initialTab = computed(() => route.query.tab || 'portafoglio');

const tabs = [
	{ key: 'portafoglio', label: 'Portafoglio', to: '/account/portafoglio', icon: 'wallet', desc: 'Saldo, ricariche e movimenti' },
	{ key: 'carte', label: 'Carte', to: '/account/carte', icon: 'card', desc: 'Metodi di pagamento salvati' },
	{ key: 'fatture', label: 'Fatture', to: '/account/fatture', icon: 'doc', desc: 'Storico fatture PDF scaricabili' },
];
</script>

<template>
	<section class="sf-account-shell min-h-[600px] py-[24px] sm:py-[28px] lg:py-[32px]">
		<div class="my-container space-y-[20px]">
			<AccountPageHeader
				eyebrow="Finanze"
				title="Pagamenti"
				description="Tutto quello che riguarda soldi e fatture in un unico spazio."
				current="Pagamenti" />

			<div class="grid grid-cols-1 sm:grid-cols-3 gap-[12px]">
				<NuxtLink
					v-for="tab in tabs"
					:key="tab.key"
					:to="tab.to"
					class="group flex flex-col gap-[8px] rounded-[16px] bg-white border border-[var(--color-brand-border)] p-[18px] hover:border-[var(--color-brand-primary)] hover:shadow-md transition-all"
					:aria-label="`Vai a ${tab.label}`">
					<div class="flex items-center gap-[10px]">
						<div class="w-[36px] h-[36px] rounded-full bg-[var(--surface-teal-pale,#F0F8F9)] flex items-center justify-center text-[var(--color-brand-primary)]">
							<svg v-if="tab.icon === 'wallet'" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg>
							<svg v-else-if="tab.icon === 'card'" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
							<svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
						</div>
						<h3 class="font-montserrat text-[1.0625rem] font-[800] text-[var(--color-brand-text)]">{{ tab.label }}</h3>
					</div>
					<p class="text-[0.875rem] text-[var(--color-brand-text-secondary)]">{{ tab.desc }}</p>
					<span class="text-[0.8125rem] font-semibold text-[var(--color-brand-primary)] group-hover:underline mt-auto">Apri →</span>
				</NuxtLink>
			</div>
		</div>
	</section>
</template>
