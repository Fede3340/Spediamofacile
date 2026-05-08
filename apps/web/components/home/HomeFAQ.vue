<script setup>
const faqs = [
	{
		q: 'Quanto costa spedire con SpediamoFacile?',
		a: 'Le tariffe partono da 5,90 € per pacchi fino a 5 kg in Italia. Il prezzo dipende da peso, dimensioni e destinazione: calcolalo in 30 secondi senza registrazione.',
	},
	{
		q: 'Quanto tempo impiega la consegna?',
		a: 'Per le spedizioni nazionali la consegna avviene in 24/48 ore lavorative. In Europa i tempi vanno da 2 a 5 giorni lavorativi a seconda del paese.',
	},
	{
		q: 'Posso fare il ritiro a domicilio?',
		a: 'Sì. Scegli giorno e fascia oraria al momento del preventivo: il corriere BRT ritira il pacco direttamente dove preferisci, senza costi extra.',
	},
];

const openFaqIndexes = ref([]);
function isFaqOpen(i) {
	return openFaqIndexes.value.includes(i);
}
function toggleFaq(i) {
	const idx = openFaqIndexes.value.indexOf(i);
	if (idx >= 0) {
		openFaqIndexes.value.splice(idx, 1);
	} else {
		openFaqIndexes.value.push(i);
	}
}
</script>

<template>
	<section class="bg-brand-bg-alt py-16 md:py-20" aria-labelledby="home-faq-title">
		<div class="max-w-6xl mx-auto px-4 md:px-8">
			<div class="grid gap-10 lg:grid-cols-2 lg:gap-12 lg:items-center">
				<!-- LEFT: Heading + CTA box bilanciato -->
				<div class="space-y-6">
					<HomeSectionHead
						eyebrow="Domande frequenti"
						title="Tutto quello che vuoi sapere"
						title-id="home-faq-title"
						subtitle="Tre risposte rapide qui sotto. Per tutte le altre, visita il centro assistenza completo."
						align="left"
					/>
					<div class="flex flex-wrap gap-3">
						<SfButton to="/faq">Vai a tutte le FAQ</SfButton>
						<SfButton variant="secondary" to="/contatti">Contatta l'assistenza</SfButton>
					</div>
				</div>

				<!-- RIGHT: 3 FAQ accordion (bilanciato con sinistra) -->
				<ul class="space-y-3" role="list">
					<li
						v-for="(item, i) in faqs"
						:key="i"
						class="overflow-hidden rounded-[18px] border border-brand-border bg-brand-card transition-all"
						:class="isFaqOpen(i)
							? 'border-brand-primary/30 shadow-[0_8px_24px_-12px_rgba(9,88,102,0.18)]'
							: 'hover:border-brand-primary/20 hover:shadow-sf-sm'"
					>
						<button
							:id="`faq-trigger-${i}`"
							type="button"
							class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left text-base font-semibold text-brand-text transition-colors hover:text-brand-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-primary/50"
							:aria-expanded="isFaqOpen(i)"
							:aria-controls="`faq-panel-${i}`"
							@click="toggleFaq(i)"
						>
							<span>{{ item.q }}</span>
							<span
								aria-hidden="true"
								class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full transition-all"
								:class="isFaqOpen(i)
									? 'bg-brand-accent/10 text-brand-accent rotate-180'
									: 'bg-brand-primary/8 text-brand-primary'"
							>
								<svg viewBox="0 0 16 16" focusable="false" class="h-3.5 w-3.5">
									<path d="M3 6l5 5 5-5" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" />
								</svg>
							</span>
						</button>
						<div
							v-show="isFaqOpen(i)"
							:id="`faq-panel-${i}`"
							role="region"
							:aria-labelledby="`faq-trigger-${i}`"
							class="px-5 pb-5 text-sm leading-relaxed text-brand-text-secondary"
						>
							<p>{{ item.a }}</p>
						</div>
					</li>
				</ul>
			</div>
		</div>
	</section>
</template>
