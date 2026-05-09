<script setup>
import { formatEuro } from '~/utils/price.js';

const route = useRoute();

defineProps({
	title: String, description: String, button: String, image: String, path: String,
});

const { isHomepageHeroRoute, heroImageUrl, heroImageStyle, prefetchHero } = useContenutoHeader();
const { loadPriceBands, getMinPrice, promoSettings } = usePriceBands();

onMounted(() => {
	if (isHomepageHeroRoute.value) { prefetchHero(); loadPriceBands(); }
});

const minPriceInfo = computed(() => getMinPrice());
const minPriceFormatted = computed(() => minPriceInfo.value?.effectivePrice ? formatEuro(minPriceInfo.value.effectivePrice) : '8,90');
const minBasePriceFormatted = computed(() => minPriceInfo.value?.basePrice ? formatEuro(minPriceInfo.value.basePrice) : null);
const showMinPriceDiscount = computed(() => minPriceInfo.value?.hasDiscount && minPriceInfo.value?.showDiscount && promoSettings.value?.show_badges);

function scrollToPreventivo() {
	document.getElementById('preventivo')?.scrollIntoView({ behavior: 'smooth' });
}

// Hero secondari: config-driven (titolo + ancora scroll opzionale)
const heroBaseClass = 'relative z-2 flex flex-col h-[calc(100%-30px)] desktop:h-[calc(100%-48px)] tablet:h-[calc(100%-42px)]';
const secondaryHeros = [
	{ match: (p) => p === '/servizi', layout: 'center', title: 'Soluzioni pratiche per spedire meglio', anchor: '#servizi', anchorMt: 'mt-[18px]' },
	{ match: (p) => p === '/chi-siamo', layout: 'center', title: 'Spedizioni chiare, veloci e senza stress', anchor: '#chi-siamo', anchorMt: 'mt-[18px]' },
	{ match: (p) => p.startsWith('/guide'), layout: 'guide', title: 'Guide pratiche per spedire meglio', anchor: '#guide', anchorMt: 'mt-[24px]' },
	{ match: (p) => p === '/faq', layout: 'faq', title: 'Risposte rapide alle domande comuni' },
];
const activeHero = computed(() => secondaryHeros.find((h) => h.match(route.path)));

</script>

<template>
	<section v-if="isHomepageHeroRoute" class="hero-homepage relative z-[2]">
		<div class="flex flex-col lg:flex-row items-center gap-[16px] lg:gap-[24px]">
			<div class="relative z-[5] flex flex-col flex-1 min-w-0 lg:max-w-[480px]">
				<!-- Accent bar — solo desktop (mobile compatto) -->
				<div class="hidden tablet:block h-[6px] w-[72px] rounded-full mb-[20px]" style="background: linear-gradient(90deg, var(--color-brand-accent), var(--color-brand-primary))" />
				<h1 class="hero-title">
					<span>Spedisci in </span><span class="hero-title-highlight">tutta Italia</span>
				</h1>
				<p class="hero-subtitle">Ritiro a domicilio, consegna veloce, prezzo fisso.</p>

				<!-- Mobile (< 640px): banner immagine curato (compatto, ~110px) + chip prezzo inline -->
				<div class="hero-mobile-banner tablet:hidden" aria-hidden="true">
					<img :src="heroImageUrl" alt="" class="hero-mobile-banner__image" :style="heroImageStyle" width="1600" height="900" loading="eager" fetchpriority="high" decoding="async">
					<div class="hero-mobile-banner__overlay">
						<span class="hero-mobile-banner__chip">
							<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
							Corriere BRT · Tracking in tempo reale
						</span>
					</div>
				</div>

				<!-- Mobile (< 640px): chip prezzo inline compatto sotto il banner. -->
				<div class="hero-price-chip tablet:hidden">
					<span class="hero-price-chip__from">Da</span>
					<span class="hero-price-chip__amount">{{ minPriceFormatted }}<span class="hero-price-chip__currency">&euro;</span></span>
					<span class="hero-price-chip__sep" aria-hidden="true">·</span>
					<span class="hero-price-chip__detail">IVA e ritiro incluso</span>
				</div>

				<div v-if="showMinPriceDiscount" class="hidden tablet:flex mt-[12px] items-center gap-[8px] flex-wrap">
					<span class="inline-flex items-center gap-[4px] px-[10px] py-[4px] rounded-full bg-[var(--color-brand-primary)] text-white text-[0.8125rem] font-bold">-{{ minPriceInfo.discountPercent }}%</span>
					<span v-if="minBasePriceFormatted" class="text-[0.875rem] font-medium text-[var(--color-brand-text-muted)] line-through">{{ minBasePriceFormatted }}&euro;</span>
				</div>
				<div v-if="promoSettings?.active && promoSettings?.label_text" class="hidden tablet:block mt-[8px]">
					<span :style="{ backgroundColor: promoSettings.label_color || 'var(--color-brand-accent)' }" class="inline-flex items-center gap-[6px] px-[10px] py-[4px] rounded-full text-white text-[0.75rem] font-bold tracking-wide shadow-sm">
						<img v-if="promoSettings.label_image" :src="promoSettings.label_image" alt="" decoding="async" width="40" height="16" class="h-[16px] w-auto shrink-0">
						{{ promoSettings.label_text }}
					</span>
				</div>
				<p v-if="promoSettings?.active && promoSettings?.description" class="hidden tablet:block text-[0.8125rem] text-[var(--color-brand-text-secondary)] font-medium mt-[6px]">{{ promoSettings.description }}</p>

				<!-- Box prezzo + CTA: solo da tablet in su. Su mobile il form è direttamente sotto, no CTA ridondante. -->
				<div class="hero-price-cta-box hidden tablet:flex">
					<div class="flex flex-col gap-[2px] min-w-0">
						<div class="flex items-baseline gap-[8px]">
							<span class="text-white/70 text-sm font-semibold">Da</span>
							<span class="text-white text-[18px] sm:text-[20px] tracking-[-0.5px] leading-[1] font-[800]">{{ minPriceFormatted }}<span class="text-[16px] text-white/70">&euro;</span></span>
						</div>
						<span class="text-white/70 text-xs sm:text-[13px] font-medium">IVA e ritiro incluso</span>
					</div>
					<SfButton size="lg" class="w-full sm:w-auto" @click="scrollToPreventivo">
						Calcola preventivo
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="M12 5v14M19 12l-7 7-7-7"/></svg>
					</SfButton>
				</div>

				<!-- Trust row: solo da tablet (mobile li ha già il Preventivo subito sotto + bar trust nel form) -->
				<div class="hero-trust-row hidden tablet:flex">
					<span class="hero-trust-item">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>
						Pagamento sicuro
					</span>
					<span class="hero-trust-item">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
						Corriere BRT
					</span>
					<span class="hero-trust-item">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
						Ritiro 24h
					</span>
				</div>
			</div>

			<!-- Hero image: nascosta su mobile per dare spazio al Preventivo above the fold. -->
			<div class="hero-image-wrapper hidden tablet:block">
				<div class="hero-image-gradient-border">
					<div class="hero-image-frame">
						<img :src="heroImageUrl" alt="Spedizioni veloci in tutta Italia" class="hero-image" :style="heroImageStyle" width="1600" height="900" loading="eager" fetchpriority="high" decoding="async">
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- Hero pagamento alla consegna (layout dedicato left-aligned) -->
	<div v-else-if="route.path.includes('pagamento-alla-consegna')" :class="`${heroBaseClass} items-start justify-center`">
		<div class="w-full">
			<h1 class="font-montserrat text-[1.5rem] desktop:text-[3rem] desktop-xl:text-[5.5rem] leading-[110%] tracking-[-0.576px] desktop:tracking-[-2.2112px] font-[800] text-[var(--color-brand-text)] text-left tablet:max-w-[360px] desktop-xl:max-w-[1056px] max-w-[200px] desktop:max-w-full">Pagamento alla consegna</h1>
			<a href="#pagamento-alla-consegna" class="content-header-scroll-link mt-[16px] desktop-xl:mt-[30px]">
				<span class="after:bg-[url('/img/arrow-down.svg')] after:bg-no-repeat after:inline-block after:size-[16px] after:ml-[11px] after:rotate-90 after:align-[-1px]">Scendi</span>
			</a>
		</div>
	</div>

	<!-- Hero contatti -->
	<div v-else-if="route.path === '/contatti'" :class="`${heroBaseClass} items-start justify-center`">
		<div class="w-full max-w-[640px]">
			<h1 class="font-montserrat text-[var(--color-brand-text)] font-[800] leading-[105%] tracking-[-0.8px] text-[1.75rem] tablet:text-[2.25rem] desktop:text-[2.75rem]">Contatti</h1>
			<p class="mt-[10px] max-w-[560px] text-[0.9375rem] tablet:text-[1rem] desktop:text-[1.0625rem] leading-[1.55] text-[var(--color-brand-text-secondary)]">Ti aiutiamo con spedizioni, assistenza e richieste commerciali senza farti perdere tempo.</p>
		</div>
	</div>

	<!-- Hero secondari standard (servizi, chi-siamo, guide, faq) -->
	<div v-else-if="activeHero" :class="[heroBaseClass, activeHero.layout === 'center' ? 'items-center justify-center' : 'items-center justify-between', activeHero.layout === 'faq' ? '!h-[calc(100%-38px)] desktop:!h-[calc(100%-65px)] tablet:!h-[calc(100%-50px)]' : '']">
		<div :class="activeHero.layout === 'center' ? 'w-full max-w-[760px]' : 'mt-[34px] mid-desktop:mt-[18px] desktop:mt-[50px]'">
			<h1 class="content-header-title text-center tablet:max-w-[360px] desktop-xl:max-w-[1056px] max-w-[320px] desktop:max-w-[620px] mx-auto">{{ activeHero.title }}</h1>
			<a v-if="activeHero.anchor" :href="activeHero.anchor" :class="`content-header-scroll-link mx-auto ${activeHero.anchorMt}`">
				<span class="after:bg-[url('/img/arrow-down.svg')] after:bg-no-repeat after:inline-block after:size-[16px] after:ml-[11px] after:rotate-90 after:align-[-1px]">Scendi</span>
			</a>
		</div>
	</div>
</template>

<style scoped>
.hero-homepage { padding: 16px 0 4px; }
.hero-title { font-family: var(--font-montserrat); font-weight: 800; color: var(--color-brand-text); font-size: clamp(2.4rem, 4.5vw, 3.4rem); line-height: 1.05; letter-spacing: -1.5px; }
.hero-title-highlight { position: relative; display: inline-block; }
.hero-title-highlight::after {
	content: ''; position: absolute; left: -4px; right: -4px; bottom: 2px;
	height: 10px; border-radius: 3px; z-index: -1; opacity: 0.20;
	background: linear-gradient(90deg, var(--color-brand-accent), var(--color-brand-primary));
}
.hero-subtitle { margin-top: 12px; color: #777; font-weight: 450; font-size: 15px; line-height: 1.55; max-width: 380px; }

/* Mobile-first: banner immagine curato compatto (110px max). Visibile solo su mobile. */
.hero-mobile-banner {
	position: relative;
	margin-top: 12px;
	height: 110px;
	border-radius: 14px;
	overflow: hidden;
	background: linear-gradient(135deg, var(--color-brand-accent) 0%, rgba(228, 66, 3, 0.3) 35%, rgba(9, 88, 102, 0.3) 65%, var(--color-brand-primary) 100%);
	padding: 2px;
}
/* Tablet+ (≥720px): nasconde il banner mobile e il price chip — vincono il box CTA + image desktop. */
@media (min-width: 45rem) {
	.hero-mobile-banner,
	.hero-price-chip {
		display: none !important;
	}
}
.hero-mobile-banner__image {
	display: block;
	width: 100%;
	height: 100%;
	object-fit: cover;
	border-radius: 12px;
	pointer-events: none;
	user-select: none;
}
.hero-mobile-banner__overlay {
	position: absolute;
	inset: 0;
	display: flex;
	align-items: flex-end;
	padding: 8px 10px;
	border-radius: 12px;
	background: linear-gradient(180deg, transparent 35%, rgba(9, 30, 36, 0.55) 100%);
	pointer-events: none;
}
.hero-mobile-banner__chip {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	padding: 4px 10px;
	border-radius: 999px;
	background: rgba(255, 255, 255, 0.96);
	color: var(--color-brand-primary);
	font-size: 11px;
	font-weight: 700;
	letter-spacing: 0.01em;
	box-shadow: 0 4px 10px rgba(9, 30, 36, 0.18);
}
.hero-mobile-banner__chip svg { color: var(--color-brand-accent); flex-shrink: 0; }

/* Mobile-first: chip prezzo compatto sostituisce price-cta-box. Save ~80px. */
.hero-price-chip {
	display: inline-flex;
	align-items: baseline;
	gap: 6px;
	margin-top: 10px;
	padding: 4px 10px;
	border-radius: 999px;
	background: var(--color-brand-primary);
	color: #fff;
	font-size: 12px;
	font-weight: 700;
	width: fit-content;
}
.hero-price-chip__from { font-weight: 600; opacity: 0.85; font-size: 11px; }
.hero-price-chip__amount { font-size: 14px; letter-spacing: -0.3px; }
.hero-price-chip__currency { font-size: 12px; opacity: 0.85; }
.hero-price-chip__sep { opacity: 0.5; }
.hero-price-chip__detail { font-weight: 500; opacity: 0.85; font-size: 11px; }
.hero-price-cta-box {
	margin-top: 22px; border-radius: 16px; padding: 14px 20px;
	display: none; flex-direction: column; align-items: flex-start; gap: 12px;
	background: var(--color-brand-primary);
}
@media (min-width: 40rem) {
	.hero-price-cta-box { display: flex; }
}
.hero-image-wrapper { position: relative; z-index: 2; flex: 1 1 0%; width: 100%; }
.hero-image-gradient-border {
	padding: 3px; border-radius: 22px;
	background: linear-gradient(135deg, var(--color-brand-accent) 0%, rgba(228, 66, 3, 0.3) 35%, rgba(9, 88, 102, 0.3) 65%, var(--color-brand-primary) 100%);
}
.hero-image-frame { position: relative; width: 100%; aspect-ratio: 16 / 9; height: 240px; overflow: hidden; border-radius: 19px; background: #fff; }
.hero-image { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; pointer-events: none; user-select: none; }
.hero-trust-row { display: none; flex-wrap: wrap; align-items: center; gap: 14px; margin-top: 16px; }
@media (min-width: 40rem) {
	.hero-trust-row { display: flex; }
}
.hero-trust-item { display: inline-flex; align-items: center; gap: 5px; color: var(--color-brand-text-secondary, #777); font-size: 12px; font-weight: 600; letter-spacing: 0.01em; }
.hero-trust-item svg { color: var(--color-brand-primary, #095866); flex-shrink: 0; }

@media (min-width: 40rem) {
	.hero-homepage { padding: 24px 0 4px; }
	.hero-subtitle { font-size: 16px; }
	.hero-price-cta-box { flex-direction: row; align-items: center; gap: 16px; padding: 16px 24px; }
	.hero-trust-row { gap: 18px; margin-top: 18px; }
	.hero-trust-item { font-size: 13px; }
	.hero-image, .hero-image-frame { height: 280px; }
}
@media (min-width: 64rem) {
	.hero-image-wrapper { width: auto; }
	.hero-image, .hero-image-frame { height: 320px; }
}
@media (max-width: 23.4375rem) {
	.hero-homepage { padding: 8px 0 0; }
	.hero-title { font-size: clamp(1.5rem, 6.5vw, 2rem); letter-spacing: -0.8px; }
	.hero-subtitle { font-size: 13px; margin-top: 6px; max-width: 100%; line-height: 1.4; }
	.hero-mobile-banner { height: 96px; margin-top: 10px; }
	.hero-mobile-banner__chip { font-size: 10px; padding: 3px 8px; }
	.hero-price-chip { margin-top: 8px; padding: 3px 9px; }
}
@media (max-width: 39.99rem) {
	.hero-homepage { padding: 10px 0 0; }
	.hero-title { font-size: clamp(1.6rem, 6vw, 2.2rem); line-height: 1.1; letter-spacing: -0.8px; }
	.hero-subtitle { font-size: 14px; margin-top: 8px; line-height: 1.45; }
}
</style>
