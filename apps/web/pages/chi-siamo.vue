<script setup>
useSeoMeta({
	title: 'Chi siamo',
	ogTitle: 'Chi siamo',
	description:
		'Intermediari BRT autorizzati dal 2020. Aiutiamo PMI e privati italiani a spedire con prezzi trasparenti, ritiro a domicilio e supporto umano dedicato.',
	ogDescription:
		'Intermediari BRT autorizzati dal 2020 al servizio di PMI e privati italiani: prezzi chiari, ritiro a domicilio, supporto umano.',
});

useHead({
	script: [
		{
			type: 'application/ld+json',
			innerHTML: JSON.stringify({
				'@context': 'https://schema.org',
				'@type': 'AboutPage',
				name: 'Chi siamo — SpedizioneFacile',
				url: 'https://spediamofacile.it/chi-siamo',
				mainEntity: {
					'@type': 'Organization',
					name: 'SpediamoFacile',
					url: 'https://spediamofacile.it',
					foundingDate: '2020',
					description:
						'Intermediario BRT autorizzato che semplifica le spedizioni per PMI e privati italiani.',
				},
			}),
		},
	],
});

useBreadcrumbSchema([
	{ name: 'Home', url: '/' },
	{ name: 'Chi siamo' },
]);
</script>

<template>
	<div class="about-page">
		<PublicPageHeader
			eyebrow="Chi siamo"
			title="Intermediari BRT autorizzati, dal 2020"
			description="Al servizio di PMI e privati italiani. Lavoriamo ogni giorno per rendere le spedizioni una scelta consapevole: prezzo chiaro, ritiro a domicilio, tracciamento in tempo reale e una persona vera quando serve un aiuto."
			:crumbs="[{ label: 'Home', to: '/' }, { label: 'Chi siamo' }]">
			<div class="about-hero__pills" role="list">
				<span class="about-hero__pill" role="listitem">Intermediari BRT autorizzati</span>
				<span class="about-hero__pill" role="listitem">Sede a Milano</span>
				<span class="about-hero__pill" role="listitem">Team italiano</span>
			</div>
		</PublicPageHeader>

		<AboutMission />
		<AboutStats />
		<AboutTimeline />
		<AboutValues />
		<AboutTeam />
		<AboutPartnership />
		<AboutCTA />
	</div>
</template>

<style>
.about-page {
	display: block;
	background: var(--surface-page, #f8f9fb);
}

.about-page section {
	padding: 60px 0;
}

@media (max-width: 768px) {
	.about-page section {
		padding: 40px 0;
	}
}

.about-page [class$="__inner"],
.about-page .about-stats > .my-container,
.about-page .about-team > .my-container,
.about-page .about-timeline > .my-container,
.about-page .about-values > .my-container {
	max-width: 1200px;
	margin: 0 auto;
	padding-left: 24px;
	padding-right: 24px;
}

.about-page [class$="__header"] {
	display: flex;
	flex-direction: column;
	align-items: center;
	text-align: center;
	gap: 8px;
	margin-bottom: 40px;
}

.about-page [class$="__eyebrow"] {
	display: inline-block;
	font-size: 0.75rem;
	font-weight: 700;
	color: var(--color-brand-orange, #E44203);
	letter-spacing: 0.1em;
	text-transform: uppercase;
}

.about-page [class$="__title"]:not([class*="card-title"]):not([class*="title-item"]):not(.about-team__name):not(.about-partnership__title) {
	font-size: clamp(1.75rem, 3vw, 2.25rem);
	font-weight: 700;
	color: var(--color-brand-text);
	margin: 0;
	max-width: 720px;
}
</style>

<style scoped>
.about-hero__pills {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
	justify-content: center;
	margin-top: 20px;
}

.about-hero__pill {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	padding: 6px 14px;
	font-size: 0.8125rem;
	font-weight: 600;
	color: var(--color-brand-primary);
	background: rgba(9, 88, 102, 0.08);
	border-radius: 999px;
}
</style>
