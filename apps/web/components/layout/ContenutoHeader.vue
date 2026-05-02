<script setup>
import { formatEuro } from '~/utils/price.js';

const route = useRoute();

defineProps({
	title: String,
	description: String,
	button: String,
	image: String,
	path: String,
});

// Hero logic (immagine, viewport, preview, lifecycle)
const { isHomepageHeroRoute, heroImageUrl, heroImageStyle, prefetchHero } = useContenutoHeader();

// Fasce prezzo per badge hero
const { loadPriceBands, getMinPrice, promoSettings } = usePriceBands();

// Caricamento NON bloccante: il hero si renderizza subito con fallback,
// poi si aggiorna quando i dati arrivano
onMounted(() => {
	if (isHomepageHeroRoute.value) {
		prefetchHero();
		loadPriceBands();
	}
});

const minPriceInfo = computed(() => getMinPrice());
const minPriceFormatted = computed(() => {
	const p = minPriceInfo.value?.effectivePrice;
	if (!p) return '8,90';
	return formatEuro(p);
});
const minBasePriceFormatted = computed(() => {
	const p = minPriceInfo.value?.basePrice;
	if (!p) return null;
	return formatEuro(p);
});
const showMinPriceDiscount = computed(() => {
	return minPriceInfo.value?.hasDiscount && minPriceInfo.value?.showDiscount && promoSettings.value?.show_badges;
});

function scrollToPreventivo() {
	document.getElementById('preventivo')?.scrollIntoView({ behavior: 'smooth' });
}

// Entrance animations

</script>

<template>
	<!-- ============================================================
	     HOMEPAGE HERO — Prototype-aligned layout
	     Two-column: text left + image right (desktop)
	     Stacked: text top + image bottom (mobile)
	     ============================================================ -->
	<section
		v-if="isHomepageHeroRoute"
		class="hero-homepage relative z-[2]">

		<div class="flex flex-col lg:flex-row items-center gap-[16px] lg:gap-[24px]">

			<!-- ── Colonna sinistra: testo ── -->
			<div class="relative z-[5] flex flex-col flex-1 min-w-0 lg:max-w-[480px]">

				<!-- Gradient accent strip — brand signature -->
				<div
					class="h-[6px] w-[72px] rounded-full mb-[20px]"
					style="background: linear-gradient(90deg, var(--color-brand-accent), var(--color-brand-primary))" />

				<!-- Titolo Montserrat -->
				<h1 class="hero-title">
					<span>Spedisci in </span>
					<span class="hero-title-highlight">tutta Italia</span>
				</h1>

				<!-- Sottotitolo -->
				<p class="hero-subtitle">
					Ritiro a domicilio, consegna veloce, prezzo fisso.
				</p>

				<!-- Promo badges (se attivi) -->
				<div v-if="showMinPriceDiscount" class="mt-[12px] flex items-center gap-[8px] flex-wrap">
					<span class="inline-flex items-center gap-[4px] px-[10px] py-[4px] rounded-full bg-[#095866] text-white text-[0.8125rem] font-bold">
						-{{ minPriceInfo.discountPercent }}%
					</span>
					<span v-if="minBasePriceFormatted" class="text-[0.875rem] font-medium text-[var(--color-brand-text-muted)] line-through">
						{{ minBasePriceFormatted }}&euro;
					</span>
				</div>
				<div v-if="promoSettings?.active && promoSettings?.label_text" class="mt-[8px]">
					<span
						:style="{ backgroundColor: promoSettings.label_color || 'var(--color-brand-accent)' }"
						class="inline-flex items-center gap-[6px] px-[10px] py-[4px] rounded-full text-white text-[0.75rem] font-bold tracking-wide shadow-sm">
						<img v-if="promoSettings.label_image" :src="promoSettings.label_image" alt="" decoding="async" width="40" height="16" class="h-[16px] w-auto shrink-0" >
						{{ promoSettings.label_text }}
					</span>
				</div>
				<p v-if="promoSettings?.active && promoSettings?.description" class="text-[0.8125rem] text-[var(--color-brand-text-secondary)] font-medium mt-[6px]">
					{{ promoSettings.description }}
				</p>

				<!-- Price + CTA unified box (prototype design) -->
				<div class="hero-price-cta-box">
					<!-- Price info -->
					<div class="flex flex-col gap-[2px] min-w-0">
						<div class="flex items-baseline gap-[8px]">
							<span class="text-white/70 text-[14px] font-semibold">Da</span>
							<span class="text-white text-[18px] sm:text-[20px] tracking-[-0.5px] leading-[1] font-[800]">
								{{ minPriceFormatted }}<span class="text-[16px] text-white/70">&euro;</span>
							</span>
						</div>
						<span class="text-white/70 text-[12px] sm:text-[13px] font-medium">IVA e ritiro incluso</span>
					</div>

					<!-- CTA -->
					<SfButton
						size="lg"
						class="w-full sm:w-auto"
						@click="scrollToPreventivo">
						Calcola preventivo
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0">
							<path d="M12 5v14M19 12l-7 7-7-7"/>
						</svg>
					</SfButton>
				</div>

				<!-- Trust signals row -->
				<div class="hero-trust-row">
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

			<!-- ── Colonna destra: immagine con gradient border ── -->
			<div class="hero-image-wrapper">
				<div class="hero-image-gradient-border">
					<div class="hero-image-frame">
						<!--
							width/height intrinsec per prevenire CLS:
							aspect ratio reale ~1600×900 landscape della hero-truck.
							CSS override la dimensione display (100% × 240px).
						-->
						<img
							:src="heroImageUrl"
							alt="Spedizioni veloci in tutta Italia"
							class="hero-image"
							:style="heroImageStyle"
							width="1600"
							height="900"
							loading="eager"
							fetchpriority="high"
							decoding="async" >
					</div>
				</div>
			</div>
		</div>
	</section>

	<!--
		Hero secondari (solo 2 righe: H1 title + description).
		Eyebrow kicker rimosso, semantica corretta: <h1> SOLO sul titolo reale.
	-->

	<!-- Servizi -->
	<div v-if="route.path === '/servizi'" class="relative z-2 flex flex-col items-center justify-center desktop:h-[calc(100%-48px)] tablet:h-[calc(100%-42px)] h-[calc(100%-30px)]">
		<h1 class="content-header-title text-center">
			Soluzioni pratiche per spedire meglio
		</h1>
		<a href="#servizi" class="content-header-scroll-link mx-auto block mt-[18px]">
			<span class="after:bg-[url('/img/arrow-down.svg')] after:bg-no-repeat after:inline-block after:size-[16px] after:ml-[11px] after:rotate-90 after:align-[-1px]">Scendi</span>
		</a>
	</div>

	<!-- Servizi - pagamento alla consegna -->
	<div
		v-if="route.path.includes('pagamento-alla-consegna')"
		class="relative z-2 flex flex-col items-start justify-center h-[calc(100%-30px)] desktop:h-[calc(100%-48px)] tablet:h-[calc(100%-42px)]">
		<div class="w-full">
			<h1 class="font-montserrat text-[1.5rem] desktop:text-[3rem] desktop-xl:text-[5.5rem] leading-[110%] tracking-[-0.576px] desktop:tracking-[-2.2112px] font-[800] text-[var(--color-brand-text)] text-left tablet:max-w-[360px] desktop-xl:max-w-[1056px] max-w-[200px] desktop:max-w-full">
				Pagamento alla consegna
			</h1>
			<a href="#pagamento-alla-consegna" class="content-header-scroll-link mt-[16px] desktop-xl:mt-[30px]">
				<span class="after:bg-[url('/img/arrow-down.svg')] after:bg-no-repeat after:inline-block after:size-[16px] after:ml-[11px] after:rotate-90 after:align-[-1px]">Scendi</span>
			</a>
		</div>
	</div>

	<!-- Contatti -->
	<div
		v-if="route.path === '/contatti'"
		class="relative z-2 flex flex-col items-start justify-center h-[calc(100%-30px)] desktop:h-[calc(100%-48px)] tablet:h-[calc(100%-42px)]">
		<div class="w-full max-w-[640px]">
			<h1 class="font-montserrat text-[var(--color-brand-text)] font-[800] leading-[105%] tracking-[-0.8px] text-[1.75rem] tablet:text-[2.25rem] desktop:text-[2.75rem]">
				Contatti
			</h1>
			<p class="mt-[10px] max-w-[560px] text-[0.9375rem] tablet:text-[1rem] desktop:text-[1.0625rem] leading-[1.55] text-[var(--color-brand-text-secondary)]">
				Ti aiutiamo con spedizioni, assistenza e richieste commerciali senza farti perdere tempo.
			</p>
		</div>
	</div>

	<!-- Chi siamo -->
	<div
		v-if="route.path === '/chi-siamo'"
		class="relative z-2 flex flex-col items-center justify-center h-[calc(100%-30px)] desktop:h-[calc(100%-48px)] tablet:h-[calc(100%-42px)]">
		<div class="w-full max-w-[760px]">
			<h1 class="content-header-title text-center tablet:max-w-[360px] desktop-xl:max-w-[1056px] max-w-[320px] desktop:max-w-[620px] mx-auto">
				Spedizioni chiare, veloci e senza stress
			</h1>
			<a href="#chi-siamo" class="content-header-scroll-link mx-auto mt-[18px]">
				<span class="after:bg-[url('/img/arrow-down.svg')] after:bg-no-repeat after:inline-block after:size-[16px] after:ml-[11px] after:rotate-90 after:align-[-1px]">Scendi</span>
			</a>
		</div>
	</div>

	<!-- Guide -->
	<div
		v-if="route.path.startsWith('/guide')"
		class="relative z-2 flex flex-col items-center justify-between h-[calc(100%-30px)] desktop:h-[calc(100%-48px)] tablet:h-[calc(100%-42px)]">
		<div class="mt-[34px] mid-desktop:mt-[18px] desktop:mt-[50px]">
			<h1 class="content-header-title text-center tablet:max-w-[360px] desktop-xl:max-w-[1056px] max-w-[320px] desktop:max-w-[620px] mx-auto">
				Guide pratiche per spedire meglio
			</h1>
			<a href="#guide" class="content-header-scroll-link mx-auto mt-[24px]">
				<span class="after:bg-[url('/img/arrow-down.svg')] after:bg-no-repeat after:inline-block after:size-[16px] after:ml-[11px] after:rotate-90 after:align-[-1px]">Scendi</span>
			</a>
		</div>
	</div>

	<!-- FAQ -->
	<div v-if="route.path === '/faq'" class="relative z-2 flex flex-col items-center justify-between h-[calc(100%-38px)] desktop:h-[calc(100%-65px)] tablet:h-[calc(100%-50px)]">
		<div class="mt-[34px] mid-desktop:mt-[18px] desktop:mt-[50px]">
			<h1 class="content-header-title text-center tablet:max-w-[360px] desktop-xl:max-w-[1056px] max-w-[320px] desktop:max-w-[620px] mx-auto">
				Risposte rapide alle domande comuni
			</h1>
		</div>
	</div>

</template>

<style scoped>
.hero-homepage {
	padding: 16px 0 4px;
}

.hero-title {
	font-family: var(--font-montserrat);
	font-weight: 800;
	color: var(--color-brand-text);
	font-size: clamp(2.4rem, 4.5vw, 3.4rem);
	line-height: 1.05;
	letter-spacing: -1.5px;
}

.hero-title-highlight {
	position: relative;
	display: inline-block;
}
.hero-title-highlight::after {
	content: '';
	position: absolute;
	left: -4px;
	right: -4px;
	bottom: 2px;
	height: 10px;
	border-radius: 3px;
	z-index: -1;
	opacity: 0.20;
	background: linear-gradient(90deg, var(--color-brand-accent), var(--color-brand-primary));
}

.hero-subtitle {
	margin-top: 12px;
	color: #777;
	font-weight: 450;
	font-size: 15px;
	line-height: 1.55;
	max-width: 380px;
}

.hero-price-cta-box {
	margin-top: 22px;
	border-radius: 16px;
	padding: 14px 20px;
	display: flex;
	flex-direction: column;
	align-items: flex-start;
	gap: 12px;
	background: var(--color-brand-primary);
}

.hero-image-wrapper {
	position: relative;
	z-index: 2;
	flex: 1 1 0%;
	width: 100%;
}
.hero-image-gradient-border {
	padding: 3px;
	border-radius: 22px;
	background: linear-gradient(135deg, var(--color-brand-accent) 0%, rgba(228, 66, 3, 0.3) 35%, rgba(9, 88, 102, 0.3) 65%, var(--color-brand-primary) 100%);
}
.hero-image-frame {
	position: relative;
	width: 100%;
	aspect-ratio: 16 / 9;
	height: 240px;
	overflow: hidden;
	border-radius: 19px;
	background: #fff;
}
.hero-image {
	position: absolute;
	inset: 0;
	width: 100%;
	height: 100%;
	object-fit: cover;
	pointer-events: none;
	user-select: none;
}

.hero-trust-row {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: 14px;
	margin-top: 16px;
}
.hero-trust-item {
	display: inline-flex;
	align-items: center;
	gap: 5px;
	color: var(--color-brand-text-secondary, #777);
	font-size: 12px;
	font-weight: 600;
	letter-spacing: 0.01em;
}
.hero-trust-item svg {
	color: var(--color-brand-primary, #095866);
	flex-shrink: 0;
}

@media (min-width: 40rem) {
	.hero-homepage {
		padding: 24px 0 4px;
	}
	.hero-subtitle {
		font-size: 16px;
	}
	.hero-price-cta-box {
		flex-direction: row;
		align-items: center;
		gap: 16px;
		padding: 16px 24px;
	}
	.hero-trust-row {
		gap: 18px;
		margin-top: 18px;
	}
	.hero-trust-item {
		font-size: 13px;
	}
	.hero-image,
	.hero-image-frame {
		height: 280px;
	}
}

@media (min-width: 64rem) {
	.hero-image-wrapper {
		width: auto;
	}
	.hero-image,
	.hero-image-frame {
		height: 320px;
	}
}

@media (max-width: 23.4375rem) {
	.hero-homepage {
		padding: 12px 0 4px;
	}
	.hero-title {
		font-size: clamp(1.75rem, 7vw, 2.4rem);
		letter-spacing: -1px;
	}
	.hero-subtitle {
		font-size: 13px;
		margin-top: 8px;
		max-width: 100%;
	}
	.hero-price-cta-box {
		margin-top: 16px;
		padding: 12px 14px;
		border-radius: 14px;
		gap: 10px;
	}
	.hero-trust-row {
		gap: 8px 12px;
		margin-top: 12px;
	}
	.hero-trust-item {
		font-size: 11px;
		gap: 4px;
	}
	.hero-trust-item svg {
		width: 12px;
		height: 12px;
	}
	.hero-image-frame {
		height: 180px;
		border-radius: 13px;
	}
	.hero-image {
		height: 180px;
	}
	.hero-image-gradient-border {
		border-radius: 16px;
	}
}

@media (max-width: 39.99rem) {
	.hero-image-frame,
	.hero-image {
		height: 200px;
	}
	.hero-price-cta-box {
		flex-direction: column;
		align-items: stretch;
	}
}
</style>
