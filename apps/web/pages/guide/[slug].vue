<!--
  PAGINA: Guida Singola (guide/[slug].vue)
  Template dinamico guide con fetch SSR, SEO server-side e shell editoriale coerente.
-->
<script setup>
import { formatDateIt } from '~/utils/date.js';

const route = useRoute();
const slug = computed(() => String(route.params.slug || ''));

const [{ data: guideResponse, pending }, { data: guideListResponse }] = await Promise.all([
	useFetch(() => `/api/public/guides/${slug.value}`, {
		key: () => `public-guide:${slug.value}`,
		server: true,
		lazy: false,
		default: () => null,
	}),
	useFetch('/api/public/guides', {
		key: 'public-guides-list',
		server: true,
		lazy: false,
		default: () => ({ data: [] }),
	}),
]);

const guide = computed(() => {
	const data = guideResponse.value?.data || guideResponse.value;
	return data?.id ? data : null;
});

if (!guide.value) {
	await navigateTo('/guide');
}

// Canonical dinamico: /guide/<slug-reale>
useCanonical({ path: () => `/guide/${guide.value?.slug || slug.value}` });

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

const guideSections = computed(() =>
	parseArrayPayload(guide.value?.sections)
		.map((section) => ({
			heading: String(section?.heading || '').trim(),
			text: String(section?.text || '').trim(),
		}))
		.filter((section) => section.heading && section.text),
);

const guides = computed(() => {
	const source = guideListResponse.value?.data || guideListResponse.value;
	return Array.isArray(source) ? source : [];
});

const guideIndex = computed(() => guides.value.findIndex((item) => item.slug === slug.value));
const prevGuide = computed(() => (guideIndex.value > 0 ? guides.value[guideIndex.value - 1] : null));
const nextGuide = computed(() =>
	guideIndex.value >= 0 && guideIndex.value < guides.value.length - 1 ? guides.value[guideIndex.value + 1] : null,
);

const guideMetaDescription = computed(() =>
	String(guide.value?.meta_description || guide.value?.intro || 'Guida pratica SpediamoFacile.').trim(),
);

const readingPills = computed(() => {
	const pills = ['Guida pratica', 'Lettura rapida'];
	if (guideSections.value.length) pills.unshift(`${guideSections.value.length} blocchi utili`);
	if (guide.value?.created_at) pills.push(formatDateIt(guide.value.created_at, '').replace(/\.$/, ''));
	return pills.filter(Boolean);
});

const firstSections = computed(() => guideSections.value.slice(0, 2));
const remainingSections = computed(() => guideSections.value.slice(2));

useSeoMeta({
	title: () => (guide.value?.title ?? 'Guida'),
	ogTitle: () => (guide.value?.title ?? 'Guida'),
	description: () => guideMetaDescription.value,
	ogDescription: () => guideMetaDescription.value,
	ogType: 'article',
	ogImage: () => guide.value?.featured_image || 'https://spediamofacile.it/og/default.png',
	twitterImage: () => guide.value?.featured_image || 'https://spediamofacile.it/og/default.png',
	articlePublishedTime: () => guide.value?.created_at || undefined,
	articleModifiedTime: () => guide.value?.updated_at || guide.value?.created_at || undefined,
	articleSection: 'Guide',
});

// Breadcrumb schema: Home › Guide › <titolo>
useBreadcrumbSchema([
	{ name: 'Home', url: '/' },
	{ name: 'Guide', url: '/guide' },
	{ name: computed(() => guide.value?.title || 'Guida').value },
]);

// JSON-LD Article completo: dateModified, publisher.logo, mainEntityOfPage object
useHead(() => {
	if (!guide.value) return {};

	return {
		script: [
			{
				key: 'guide-article-schema',
				type: 'application/ld+json',
				innerHTML: JSON.stringify({
					'@context': 'https://schema.org',
					'@type': 'Article',
					headline: guide.value.title,
					description: guideMetaDescription.value,
					inLanguage: 'it-IT',
					mainEntityOfPage: {
						'@type': 'WebPage',
						'@id': `https://spediamofacile.it/guide/${slug.value}`,
					},
					image: guide.value.featured_image || 'https://spediamofacile.it/og-image.jpg',
					datePublished: guide.value.created_at || undefined,
					dateModified: guide.value.updated_at || guide.value.created_at || undefined,
					author: {
						'@type': 'Organization',
						name: 'SpediamoFacile',
						url: 'https://spediamofacile.it',
					},
					publisher: {
						'@type': 'Organization',
						name: 'SpediamoFacile',
						url: 'https://spediamofacile.it',
						logo: {
							'@type': 'ImageObject',
							url: 'https://spediamofacile.it/img/logo-spedizionefacile.png',
						},
					},
				}),
			},
		],
	};
});
</script>

<template>
	<section v-if="pending" class="flex min-h-[420px] items-center justify-center">
		<div class="h-[40px] w-[40px] rounded-full border-3 border-[var(--color-brand-border)] border-t-[var(--color-brand-primary)] animate-spin"></div>
	</section>

	<section v-else-if="guide" class="guide-detail-shell min-h-screen py-[20px] desktop:py-[24px]">
		<div class="my-container space-y-[18px] desktop:space-y-[24px]">
			<!-- Breadcrumb -->
			<nav aria-label="Breadcrumb" class="flex items-center gap-[6px] text-[0.8125rem] text-[var(--color-brand-text-secondary)]">
				<NuxtLink to="/" class="transition-colors hover:text-[var(--color-brand-primary)]">Home</NuxtLink>
				<span aria-hidden="true">/</span>
				<NuxtLink to="/guide" class="transition-colors hover:text-[var(--color-brand-primary)]">Guide</NuxtLink>
				<span aria-hidden="true">/</span>
				<span class="font-semibold text-[var(--color-brand-primary)] truncate max-w-[28ch]">{{ guide.title }}</span>
			</nav>

			<section class="guide-hero-card rounded-[16px] ring-[1px] ring-[#DFE2E7] px-[18px] py-[18px] shadow-[0_1px_4px_rgba(0,0,0,0.03)] desktop:px-[28px] desktop:py-[28px]">
				<div class="space-y-[10px]">
					<span class="inline-flex items-center h-[22px] px-[10px] rounded-full bg-[rgba(228,66,3,0.10)] text-[11px] font-[800] uppercase tracking-[0.08em] text-[var(--color-brand-accent)]">Guida</span>
					<span class="block w-[24px] h-[2px] rounded-full" style="background: linear-gradient(90deg, var(--color-brand-accent) 0%, var(--color-brand-primary) 100%)" aria-hidden="true" />
					<h1 class="max-w-[24ch] font-montserrat text-[1.75rem] font-[800] tracking-[-0.015em] leading-[1.05] text-[var(--color-brand-text)] desktop:text-[2rem]">
						{{ guide.title }}
					</h1>
					<p class="max-w-[64ch] text-[0.9375rem] leading-[1.6] text-[var(--color-brand-text-secondary)]">
						{{ guide.intro || guideMetaDescription }}
					</p>
					<div class="flex flex-wrap gap-[8px]">
						<span
							v-for="pill in readingPills"
							:key="pill"
							class="inline-flex items-center rounded-full bg-[var(--color-brand-secondary-soft-bg)] px-[12px] py-[6px] text-[0.75rem] font-semibold text-[var(--color-brand-primary)]">
							{{ pill }}
						</span>
					</div>
				</div>
			</section>

			<section v-if="firstSections.length" class="grid gap-[14px] desktop:grid-cols-2">
				<article
					v-for="section in firstSections"
					:key="section.heading"
					class="rounded-[16px] ring-[1px] ring-[#DFE2E7] bg-white px-[18px] py-[18px] shadow-[0_1px_4px_rgba(0,0,0,0.03)] desktop:px-[22px] desktop:py-[22px]">
					<h2 class="font-montserrat text-[1.125rem] font-[800] tracking-[-0.02em] text-[var(--color-brand-text)]">{{ section.heading }}</h2>
					<p class="mt-[10px] text-[0.875rem] leading-[1.7] text-[var(--color-brand-text-secondary)] desktop:text-[0.9375rem]">
						{{ section.text }}
					</p>
				</article>
			</section>

			<section
				v-if="remainingSections.length"
				class="rounded-[16px] ring-[1px] ring-[#DFE2E7] bg-white px-[18px] py-[18px] shadow-[0_1px_4px_rgba(0,0,0,0.03)] desktop:px-[24px] desktop:py-[24px]">
				<div class="sf-page-intro">
					<p class="sf-section-kicker">Approfondimento</p>
					<h2 class="font-montserrat text-[1.4rem] font-[800] tracking-[-0.03em] text-[var(--color-brand-text)] desktop:text-[2rem]">
						I dettagli utili per applicarla davvero
					</h2>
				</div>

				<div class="mt-[18px] grid gap-[14px] desktop:grid-cols-2">
					<article
						v-for="section in remainingSections"
						:key="section.heading"
						class="rounded-[16px] ring-[1px] ring-[#DFE2E7] bg-[var(--color-brand-secondary-soft-bg)] px-[16px] py-[16px]">
						<h3 class="font-montserrat text-[1rem] font-[800] text-[var(--color-brand-text)]">{{ section.heading }}</h3>
						<p class="mt-[10px] text-[0.875rem] leading-[1.65] text-[var(--color-brand-text-secondary)]">
							{{ section.text }}
						</p>
					</article>
				</div>
			</section>

			<section
				v-if="prevGuide || nextGuide"
				class="rounded-[16px] ring-[1px] ring-[#DFE2E7] bg-white px-[18px] py-[18px] shadow-[0_1px_4px_rgba(0,0,0,0.03)] desktop:px-[24px] desktop:py-[24px]">
				<div class="sf-page-intro">
					<p class="sf-section-kicker">Continua a leggere</p>
					<h2 class="font-montserrat text-[1.35rem] font-[800] tracking-[-0.03em] text-[var(--color-brand-text)] desktop:text-[1.8rem]">
						Altre guide della stessa libreria
					</h2>
				</div>

				<div class="mt-[18px] grid gap-[14px] desktop:grid-cols-2">
					<NuxtLink
						v-if="prevGuide"
						:to="`/guide/${prevGuide.slug}`"
						class="guide-nav-card group rounded-[16px] ring-[1px] ring-[#DFE2E7] bg-[var(--color-brand-secondary-soft-bg)] px-[16px] py-[16px] transition-all duration-300 hover:ring-[var(--color-brand-secondary-soft-border)] hover:bg-white">
						<p class="text-[0.75rem] font-semibold uppercase tracking-[0.08em] text-[var(--color-brand-primary)]">Guida precedente</p>
						<h3 class="mt-[8px] font-montserrat text-[1rem] font-[800] text-[var(--color-brand-text)] transition-colors group-hover:text-[var(--color-brand-primary)]">
							{{ prevGuide.title }}
						</h3>
					</NuxtLink>
					<NuxtLink
						v-if="nextGuide"
						:to="`/guide/${nextGuide.slug}`"
						class="guide-nav-card group rounded-[16px] ring-[1px] ring-[#DFE2E7] bg-[var(--color-brand-secondary-soft-bg)] px-[16px] py-[16px] transition-all duration-300 hover:ring-[var(--color-brand-secondary-soft-border)] hover:bg-white">
						<p class="text-[0.75rem] font-semibold uppercase tracking-[0.08em] text-[var(--color-brand-primary)]">Guida successiva</p>
						<h3 class="mt-[8px] font-montserrat text-[1rem] font-[800] text-[var(--color-brand-text)] transition-colors group-hover:text-[var(--color-brand-primary)]">
							{{ nextGuide.title }}
						</h3>
					</NuxtLink>
				</div>
			</section>

			<section
				class="rounded-[16px] ring-[1px] ring-[var(--color-brand-secondary-soft-border)] bg-[linear-gradient(135deg,#0f5f6d_0%,#0c4853_100%)] px-[18px] py-[18px] text-white shadow-[0_1px_4px_rgba(0,0,0,0.03)] desktop:px-[24px] desktop:py-[24px]">
				<div class="flex flex-col gap-[14px] desktop:flex-row desktop:items-center desktop:justify-between">
					<div class="max-w-[60ch]">
						<p class="text-[0.75rem] font-semibold uppercase tracking-[0.14em] text-white/70">Passo successivo</p>
						<h2 class="mt-[8px] font-montserrat text-[1.2rem] font-[800] tracking-[-0.03em] desktop:text-[1.55rem]">
							Vuoi passare dalla teoria al preventivo?
						</h2>
						<p class="mt-[8px] text-[0.9rem] leading-[1.58] text-white/80">
							Usa la guida come checklist pratica e poi apri il preventivo per trasformare i consigli in una spedizione reale.
						</p>
					</div>

					<div class="flex flex-wrap gap-[10px]">
						<SfButton to="/preventivo">
							Calcola il preventivo
						</SfButton>
						<NuxtLink
							to="/guide"
							class="inline-flex h-[40px] items-center justify-center rounded-full border border-white/35 px-[18px] text-[0.875rem] font-[700] text-white transition-colors duration-200 hover:bg-white/10 hover:border-white/60">
							Tutte le guide
						</NuxtLink>
					</div>
				</div>
			</section>
		</div>
	</section>
</template>

<style scoped>
.guide-detail-shell {
	background: linear-gradient(180deg, var(--surface-page, #F8F9FB) 0%, var(--surface-page-end, #EEF0F3) 100%);
}

.guide-hero-card {
	background:
		radial-gradient(circle at top right, rgba(228, 66, 3, 0.16), transparent 30%),
		linear-gradient(180deg, rgba(9, 88, 102, 0.07) 0%, rgba(9, 88, 102, 0.02) 100%);
}

.guide-nav-card {
	transition:
		transform var(--sf-t2) var(--sf-ease),
		box-shadow var(--sf-t2) var(--sf-ease),
		background-color var(--sf-t2) var(--sf-ease);
}

.guide-nav-card:hover {
	transform: translateY(-2px);
	box-shadow: 0 4px 16px rgba(9, 88, 102, 0.08);
}
</style>
