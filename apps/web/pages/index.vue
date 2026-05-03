<script setup>

import Preventivo from '~/components/shipment/Preventivo.vue';

useSeoMeta({
	title: 'Spedizioni BRT al miglior prezzo',
	ogTitle: 'SpediamoFacile — Spedizioni BRT al miglior prezzo',
	description:
		'Spedisci pacchi in Italia ed Europa con BRT alle migliori tariffe. Preventivo in 30 secondi, ritiro a domicilio, tracking in tempo reale.',
	ogDescription:
		'Spedisci con BRT alle migliori tariffe. Preventivo in 30 secondi, ritiro a domicilio, tracking in tempo reale.',
});

useHead({
	script: [{
		key: 'home-webapp-schema',
		type: 'application/ld+json',
		innerHTML: JSON.stringify({
			'@context': 'https://schema.org',
			'@type': 'WebApplication',
			'@id': 'https://spediamofacile.it/#webapp',
			name: 'SpediamoFacile',
			url: 'https://spediamofacile.it',
			applicationCategory: 'BusinessApplication',
			applicationSubCategory: 'Shipping',
			operatingSystem: 'Any (web-based)',
			browserRequirements: 'Requires JavaScript. Richiede un browser moderno.',
			inLanguage: 'it-IT',
			offers: {
				'@type': 'Offer',
				price: '0',
				priceCurrency: 'EUR',
				description: 'Registrazione e preventivo gratuiti. Paghi solo le spedizioni effettive.',
			},
			publisher: { '@id': 'https://spediamofacile.it/#organization' },
		}),
	}],
});

let observer = null;
onMounted(() => {
	if (!('IntersectionObserver' in window)) return;
	observer = new IntersectionObserver(
		(entries) => {
			for (const entry of entries) {
				if (entry.isIntersecting) {
					entry.target.classList.add('is-visible');
					observer.unobserve(entry.target);
				}
			}
		},
		{ threshold: 0.12, rootMargin: '0px 0px -40px 0px' },
	);
	document.querySelectorAll('[data-reveal]').forEach((el) => observer.observe(el));
});
onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
	<div class="home">
		<p class="sr-only">SpediamoFacile — Preventivo rapido per spedizioni BRT con ritiro a domicilio.</p>
		<ClientOnly>
			<Preventivo />
			<template #fallback>
				<div aria-hidden="true" style="min-height:460px"/>
			</template>
		</ClientOnly>

		<HomeTrustBar />
		<HomeHowItWorks />
		<HomeServicesHighlight />
		<HomeReviews />
		<HomeFAQ />
		<HomeCTAFinal />
	</div>
</template>

<style>
[data-reveal] {
	opacity: 0;
	transform: translateY(16px);
	transition: opacity var(--sf-t2) var(--sf-ease), transform var(--sf-t2) var(--sf-ease);
}
[data-reveal].is-visible {
	opacity: 1;
	transform: none;
}
@media (prefers-reduced-motion: reduce) {
	[data-reveal] { opacity: 1; transform: none; transition: none; }
}
</style>

<style scoped>
.home {
	color: #1a2a2e;
	background: #ffffff;
}
</style>
