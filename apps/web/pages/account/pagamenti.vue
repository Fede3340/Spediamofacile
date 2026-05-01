<script setup>
definePageMeta({
	middleware: ['app-auth'],
});

useSeoMeta({
	title: 'Pagamenti',
	description: 'Portafoglio, carte e fatture in un unico spazio: gestisci i tuoi pagamenti su SpediamoFacile.',
	robots: 'noindex, nofollow',
});

const tabs = [
	{ key: 'portafoglio', label: 'Portafoglio', to: '/account/portafoglio', icon: 'wallet', desc: 'Saldo, ricariche e movimenti' },
	{ key: 'carte', label: 'Carte', to: '/account/carte', icon: 'card', desc: 'Metodi di pagamento salvati' },
	{ key: 'fatture', label: 'Fatture', to: '/account/fatture', icon: 'doc', desc: 'Storico fatture PDF scaricabili' },
];
</script>

<template>
	<section class="w-full min-h-[600px] py-6 sm:py-7 lg:py-8">
		<div class="my-container space-y-5">
			<AccountPageHeader
				eyebrow="Finanze"
				title="Pagamenti"
				description="Tutto quello che riguarda soldi e fatture in un unico spazio."
				current="Pagamenti" />

			<div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
				<NuxtLink
					v-for="tab in tabs"
					:key="tab.key"
					:to="tab.to"
					:aria-label="`Vai a ${tab.label}`"
					class="group flex flex-col gap-2 rounded-card border border-brand-border bg-white p-[18px] transition-all hover:border-brand-primary hover:shadow-sf">
					<div class="flex items-center gap-2.5">
						<div class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-primary/10 text-brand-primary">
							<svg v-if="tab.icon === 'wallet'" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4" /><path d="M3 5v14a2 2 0 0 0 2 2h16v-5" /><path d="M18 12a2 2 0 0 0 0 4h4v-4Z" /></svg>
							<svg v-else-if="tab.icon === 'card'" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2" /><line x1="2" y1="10" x2="22" y2="10" /></svg>
							<svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14 2 14 8 20 8" /></svg>
						</div>
						<h3 class="font-display text-[1.0625rem] font-extrabold text-brand-text">{{ tab.label }}</h3>
					</div>
					<p class="text-sm text-brand-text-secondary">{{ tab.desc }}</p>
					<span class="mt-auto text-[0.8125rem] font-semibold text-brand-primary group-hover:underline">Apri →</span>
				</NuxtLink>
			</div>
		</div>
	</section>
</template>
