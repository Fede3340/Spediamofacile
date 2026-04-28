<!--
  PAGINA: Servizio Singolo (servizi/[slug].vue)
  Template dinamico servizi con fetch SSR, SEO reale lato server e shell pubblica coerente.
-->
<script setup>
// CSS split route-specific: servizi.css usato solo in /servizi/* + /chi-siamo.
import '~/assets/css/servizi.css';

const route = useRoute();
const slug = computed(() => String(route.params.slug || ''));

const { data: serviceResponse, pending } = await useFetch(() => `/api/public/services/${slug.value}`, {
	key: () => `public-service:${slug.value}`,
	server: true,
	lazy: false,
	default: () => null,
});

const service = computed(() => {
	const data = serviceResponse.value?.data || serviceResponse.value;
	return data?.id ? data : null;
});

if (!service.value) {
	await navigateTo('/servizi');
}

// Canonical dinamico: /servizi/<slug-reale>
useCanonical({ path: () => `/servizi/${service.value?.slug || slug.value}` });

const parseArrayPayload = (value) => {
	if (!value) return [];
	if (Array.isArray(value)) return value;
	if (typeof value !== 'string') return [];

	try {
		const parsed = JSON.parse(value);
		return Array.isArray(parsed) ? parsed : [];
	} catch {
		return [];
	}
};

const serviceSections = computed(() =>
	parseArrayPayload(service.value?.sections)
		.map((section) => ({
			heading: String(section?.heading || '').trim(),
			text: String(section?.text || '').trim(),
		}))
		.filter((section) => section.heading && section.text),
);

const serviceFaqs = computed(() =>
	parseArrayPayload(service.value?.faqs)
		.map((faq) => ({
			title: String(faq?.title || '').trim(),
			text: String(faq?.text || '').trim(),
		}))
		.filter((faq) => faq.title && faq.text),
);

const overviewCards = computed(() => serviceSections.value.slice(0, 2));
const detailSections = computed(() => serviceSections.value.slice(2));

const servicePills = computed(() => {
	const pills = ['Servizio pubblico', 'Preventivo immediato'];

	if (serviceSections.value.length) {
		pills.unshift(`${serviceSections.value.length} passaggi chiave`);
	}

	if (serviceFaqs.value.length) {
		pills.push(`${serviceFaqs.value.length} FAQ`);
	}

	return pills;
});

const serviceMetaDescription = computed(() =>
	String(service.value?.meta_description || service.value?.intro || 'Scopri il servizio SpediamoFacile e attivalo nel preventivo.').trim(),
);

const supportChecklist = computed(() => {
	const items = serviceSections.value.slice(0, 4).map((section) => section.heading);
	if (items.length) return items;

	return [
		'Verifica disponibilita e condizioni operative.',
		'Configura il servizio nel preventivo prima del pagamento.',
		'Controlla eventuali dati aggiuntivi richiesti dal corriere.',
		'Usa il riepilogo finale per confermare il flusso.',
	];
});

useSeoMeta({
	title: () => (service.value?.title ?? 'Servizio'),
	ogTitle: () => (service.value?.title ?? 'Servizio'),
	description: () => serviceMetaDescription.value,
	ogDescription: () => serviceMetaDescription.value,
	ogImage: () => service.value?.featured_image || 'https://spediamofacile.it/og/default.png',
	twitterImage: () => service.value?.featured_image || 'https://spediamofacile.it/og/default.png',
});

// Breadcrumb schema: Home › Servizi › <titolo>
useBreadcrumbSchema([
	{ name: 'Home', url: '/' },
	{ name: 'Servizi', url: '/servizi' },
	{ name: computed(() => service.value?.title || 'Servizio').value },
]);

useHead(() => {
	if (!service.value) return {};

	// JSON-LD Service completo: @id provider, areaServed, serviceType
	const scripts = [
		{
			key: 'service-schema',
			type: 'application/ld+json',
			innerHTML: JSON.stringify({
				'@context': 'https://schema.org',
				'@type': 'Service',
				name: service.value.title,
				url: `https://spediamofacile.it/servizi/${slug.value}`,
				serviceType: 'Spedizione',
				provider: {
					'@type': 'Organization',
					'@id': 'https://spediamofacile.it/#organization',
					name: 'SpediamoFacile',
					url: 'https://spediamofacile.it',
				},
				areaServed: {
					'@type': 'Country',
					name: 'IT',
				},
				description: serviceMetaDescription.value,
			}),
		},
	];

	if (serviceFaqs.value.length) {
		scripts.push({
			key: 'service-faq-schema',
			type: 'application/ld+json',
			innerHTML: JSON.stringify({
				'@context': 'https://schema.org',
				'@type': 'FAQPage',
				mainEntity: serviceFaqs.value.map((faq) => ({
					'@type': 'Question',
					name: faq.title,
					acceptedAnswer: {
						'@type': 'Answer',
						text: faq.text,
					},
				})),
			}),
		});
	}

	return { script: scripts };
});
</script>

<template>
	<section v-if="pending" class="flex min-h-[420px] items-center justify-center">
		<div class="h-[40px] w-[40px] rounded-full border-3 border-[var(--color-brand-border)] border-t-[var(--color-brand-primary)] animate-spin"></div>
	</section>

	<div v-else-if="service" class="service-detail-shell min-h-screen">
		<!-- Breadcrumb -->
		<section class="pt-[24px] desktop:pt-[28px]">
			<div class="my-container">
				<NuxtLink
					to="/servizi"
					class="inline-flex items-center gap-[8px] text-[0.875rem] font-medium text-[var(--color-brand-primary)] transition-colors hover:text-[var(--color-brand-primary-light)]">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M13 9H5" />
						<path d="M9 5l-4 4 4 4" />
					</svg>
					Tutti i servizi
				</NuxtLink>
			</div>
		</section>

		<!-- Hero -->
		<section class="py-[22px] desktop:py-[28px]">
			<div class="my-container">
				<div class="service-hero-card rounded-[16px] ring-[1px] ring-[#DFE2E7] px-[18px] py-[18px] shadow-[0_1px_4px_rgba(0,0,0,0.03)] desktop:px-[28px] desktop:py-[28px]">
					<div class="flex flex-col gap-[16px] desktop:grid desktop:grid-cols-[minmax(0,1.16fr)_minmax(300px,0.84fr)] desktop:items-center desktop:gap-[22px]">
						<div class="space-y-[10px]">
							<span class="inline-flex items-center h-[22px] px-[10px] rounded-full bg-[rgba(228,66,3,0.10)] text-[11px] font-[800] uppercase tracking-[0.08em] text-[var(--color-brand-accent)]">Servizio</span>
							<span class="block w-[24px] h-[2px] rounded-full" style="background: linear-gradient(90deg, var(--color-brand-accent) 0%, var(--color-brand-primary) 100%)" aria-hidden="true" />
							<h1 class="font-montserrat text-[1.75rem] font-[800] leading-[1.05] tracking-[-0.015em] text-[var(--color-brand-text)] desktop:text-[2rem]">
								{{ service.title }}
							</h1>
							<p class="max-w-[58ch] text-[0.9375rem] leading-[1.6] text-[var(--color-brand-text-secondary)]">
								{{ service.intro || serviceMetaDescription }}
							</p>
							<div class="flex flex-wrap gap-[8px]">
								<span
									v-for="pill in servicePills"
									:key="pill"
									class="inline-flex items-center rounded-full bg-[var(--color-brand-secondary-soft-bg)] px-[12px] py-[6px] text-[0.75rem] font-[700] text-[var(--color-brand-primary)]">
									{{ pill }}
								</span>
							</div>
						</div>

						<div class="rounded-[16px] ring-[1px] ring-[#DFE2E7] bg-white/75 p-[16px] shadow-[0_1px_4px_rgba(0,0,0,0.03)] backdrop-blur">
							<p class="text-[0.75rem] font-[800] uppercase tracking-[0.12em] text-[var(--color-brand-primary)]">Quando usarlo</p>
							<div class="mt-[10px] space-y-[10px]">
								<div
									v-for="item in supportChecklist"
									:key="item"
									class="rounded-[16px] ring-[1px] ring-[var(--color-brand-border)] bg-[var(--color-brand-secondary-soft-bg)] px-[14px] py-[10px] text-[0.8125rem] leading-[1.5] text-[var(--color-brand-text-secondary)]">
									{{ item }}
								</div>
							</div>
							<div class="mt-[14px] flex flex-wrap gap-[8px]">
								<SfButton to="/preventivo" variant="primary" size="sm">Calcola il preventivo</SfButton>
								<SfButton to="/contatti" variant="secondary" size="sm">Parla con noi</SfButton>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>

		<!-- Overview cards -->
		<section v-if="overviewCards.length" class="py-[24px] desktop:py-[30px]">
			<div class="my-container">
				<div class="grid gap-[16px] desktop:grid-cols-2">
					<article
						v-for="card in overviewCards"
						:key="card.heading"
						class="rounded-[16px] ring-[1px] ring-[#DFE2E7] bg-white px-[18px] py-[18px] shadow-[0_1px_4px_rgba(0,0,0,0.03)] desktop:px-[22px] desktop:py-[22px]">
						<h2 class="font-montserrat text-[1.125rem] font-[800] tracking-[-0.02em] text-[var(--color-brand-text)]">{{ card.heading }}</h2>
						<p class="mt-[10px] text-[0.875rem] leading-[1.65] text-[var(--color-brand-text-secondary)] desktop:text-[0.9375rem]">
							{{ card.text }}
						</p>
					</article>
				</div>
			</div>
		</section>

		<!-- Detail sections -->
		<section v-if="detailSections.length" class="py-[24px] desktop:py-[30px]">
			<div class="my-container">
				<div class="rounded-[16px] ring-[1px] ring-[#DFE2E7] bg-white px-[18px] py-[18px] shadow-[0_1px_4px_rgba(0,0,0,0.03)] desktop:px-[24px] desktop:py-[24px]">
					<div class="sf-page-intro">
						<p class="sf-section-kicker">Approfondimento</p>
						<h2 class="font-montserrat text-[1.4rem] font-[800] tracking-[-0.03em] text-[var(--color-brand-text)] desktop:text-[2rem]">Come entra nel flusso operativo</h2>
						<p class="sf-section-description max-w-[64ch]">
							Le sezioni sotto spiegano cosa cambia davvero quando attivi questo servizio e quali controlli conviene fare prima della
							conferma.
						</p>
					</div>

					<div class="mt-[18px] grid gap-[14px] desktop:grid-cols-2">
						<article
							v-for="section in detailSections"
							:key="section.heading"
							class="rounded-[16px] ring-[1px] ring-[#DFE2E7] bg-[var(--color-brand-secondary-soft-bg)] px-[16px] py-[16px]">
							<h3 class="font-montserrat text-[1rem] font-[700] text-[var(--color-brand-text)]">{{ section.heading }}</h3>
							<p class="mt-[10px] text-[0.875rem] leading-[1.65] text-[var(--color-brand-text-secondary)]">
								{{ section.text }}
							</p>
						</article>
					</div>
				</div>
			</div>
		</section>

		<!-- FAQ -->
		<section v-if="serviceFaqs.length" class="py-[24px] desktop:py-[30px]">
			<div class="my-container">
				<div class="rounded-[16px] ring-[1px] ring-[#DFE2E7] bg-white px-[18px] py-[18px] shadow-[0_1px_4px_rgba(0,0,0,0.03)] desktop:px-[24px] desktop:py-[24px]">
					<div class="sf-page-intro">
						<p class="sf-section-kicker">FAQ</p>
						<h2 class="font-montserrat text-[1.4rem] font-[800] tracking-[-0.03em] text-[var(--color-brand-text)] desktop:text-[2rem]">
							Domande frequenti su {{ service.title }}
						</h2>
					</div>

					<div class="mt-[18px] grid gap-[14px] desktop:grid-cols-2">
						<article
							v-for="faq in serviceFaqs"
							:key="faq.title"
							class="rounded-[16px] ring-[1px] ring-[#DFE2E7] bg-[var(--color-brand-secondary-soft-bg)] px-[16px] py-[16px]">
							<h3 class="font-montserrat text-[1rem] font-[700] text-[var(--color-brand-text)]">{{ faq.title }}</h3>
							<p class="mt-[10px] text-[0.875rem] leading-[1.65] text-[var(--color-brand-text-secondary)]">
								{{ faq.text }}
							</p>
						</article>
					</div>
				</div>
			</div>
		</section>

		<!-- CTA finale -->
		<section class="py-[24px] desktop:py-[30px]">
			<div class="my-container">
				<div class="services-bottom-cta">
					<div class="services-bottom-cta__copy">
						<p class="services-intro-panel__eyebrow" style="color: rgba(255,255,255,0.7)">Prossimo passo</p>
						<h2 class="services-bottom-cta__title">
							Vuoi attivare {{ service.title.toLowerCase() }}?
						</h2>
						<p class="services-bottom-cta__text">
							Parti dal preventivo per vedere subito costi, combinazioni compatibili e impatto sul riepilogo finale.
						</p>
					</div>
					<div class="services-bottom-cta__actions">
						<SfButton to="/preventivo" variant="primary" size="sm">Calcola il preventivo</SfButton>
						<SfButton to="/servizi" variant="secondary" size="sm">Esplora altri servizi</SfButton>
					</div>
				</div>
			</div>
		</section>
	</div>
</template>

<style scoped>
.service-detail-shell {
	background: linear-gradient(180deg, var(--surface-page, #F8F9FB) 0%, var(--surface-page-end, #EEF0F3) 100%);
}

.service-hero-card {
	background:
		radial-gradient(circle at top right, rgba(228, 66, 3, 0.16), transparent 30%),
		linear-gradient(180deg, rgba(9, 88, 102, 0.07) 0%, rgba(9, 88, 102, 0.02) 100%);
}
</style>
