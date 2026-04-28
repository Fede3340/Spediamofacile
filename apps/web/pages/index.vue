<script setup>
import '~/assets/css/pages/home.css';
import { ref, onMounted, onBeforeUnmount } from 'vue';
import Preventivo from '~/components/shipment/Preventivo.vue';

// ───────────────────────── SEO ─────────────────────────
useSeoMeta({
	title: 'Spedizioni BRT al miglior prezzo',
	ogTitle: 'SpediamoFacile — Spedizioni BRT al miglior prezzo',
	description:
		'Spedisci pacchi in Italia ed Europa con BRT alle migliori tariffe. Preventivo in 30 secondi, ritiro a domicilio, tracking in tempo reale.',
	ogDescription:
		'Spedisci con BRT alle migliori tariffe. Preventivo in 30 secondi, ritiro a domicilio, tracking in tempo reale.',
});

// Organization + WebSite schema sono iniettati globalmente da app.vue via useSiteSchema().
// Qui aggiungiamo solo WebApplication schema, specifico della homepage.
// (P9: ex useSchemaOrg wrapper inline — 3 righe, non serve composable.)
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

// Il preventivo rapido è il componente <Preventivo /> (hero della homepage).
// Gestisce origine/destinazione, pacchi, peso e 3 dimensioni, e continua
// direttamente nel ventaglio funnel via continueToNextStep().
// Vedi components/Preventivo.vue + composables/useQuote.ts.

// ───────────────── FAQ accordion ─────────────────
const faqs = [
	{
		q: 'Quanto costa spedire con SpediamoFacile?',
		a: 'Le tariffe partono da 5,90 € per pacchi fino a 5 kg in Italia. Il prezzo dipende da peso, dimensioni e destinazione: calcolalo in 30 secondi senza registrazione.',
	},
	{
		q: 'Quanto tempo impiega la consegna?',
		a: 'Per le spedizioni nazionali la consegna avviene in 24/48 ore lavorative. In Europa i tempi vanno da 2 a 5 giorni lavorativi a seconda del paese.',
	},
];
// Accordion multi-aperto: cliccando una FAQ non chiude le altre.
// Prima era singolo (un solo indice), ma gli utenti si lamentavano
// che le risposte precedenti "sparivano".
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

// ───────────── Fade-in on scroll (IntersectionObserver) ─────────────
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
		<!-- H1 visibile "Spedisci in tutta Italia" in ContenutoHeader.vue:74 — uno solo per pagina. -->
		<p class="sr-only">SpediamoFacile — Preventivo rapido per spedizioni BRT con ritiro a domicilio.</p>
		<!-- ═══════════════════ PREVENTIVO RAPIDO (collegato al ventaglio) ═══════════════════ -->
		<!-- ClientOnly: il form preventivo e' interattivo e richiede JS. Evita
		     hydration mismatch su SSR (fallback mantiene la stessa altezza). -->
		<ClientOnly>
			<Preventivo />
			<template #fallback>
				<div aria-hidden="true" style="min-height:460px"></div>
			</template>
		</ClientOnly>

		<!-- ═══════════════════ TRUST BAR ═══════════════════ -->
		<section class="trust" aria-label="Partner e certificazioni">
			<div class="container trust__inner">
				<p class="trust__label">In partnership con il leader italiano del corriere espresso</p>
				<ul class="trust__logos" role="list">
					<li class="trust__logo">
						<svg viewBox="0 0 120 32" aria-label="BRT Corriere Espresso" role="img">
							<rect x="0" y="0" width="120" height="32" rx="6" fill="#095866" />
							<text x="60" y="21" text-anchor="middle" font-family="Inter, sans-serif" font-weight="700" font-size="14" fill="#ffffff" letter-spacing="2">BRT</text>
						</svg>
					</li>
					<li class="trust__logo">
						<svg viewBox="0 0 120 32" aria-label="Pagamenti sicuri Stripe" role="img">
							<rect x="0" y="0" width="120" height="32" rx="6" fill="#ffffff" stroke="#e2e8df" />
							<text x="60" y="21" text-anchor="middle" font-family="Inter, sans-serif" font-weight="600" font-size="13" fill="#095866">Stripe</text>
						</svg>
					</li>
					<li class="trust__logo">
						<svg viewBox="0 0 120 32" aria-label="Conforme GDPR" role="img">
							<rect x="0" y="0" width="120" height="32" rx="6" fill="#ffffff" stroke="#e2e8df" />
							<text x="60" y="21" text-anchor="middle" font-family="Inter, sans-serif" font-weight="600" font-size="13" fill="#095866">GDPR</text>
						</svg>
					</li>
					<li class="trust__logo">
						<svg viewBox="0 0 120 32" aria-label="Connessione SSL sicura" role="img">
							<rect x="0" y="0" width="120" height="32" rx="6" fill="#ffffff" stroke="#e2e8df" />
							<text x="60" y="21" text-anchor="middle" font-family="Inter, sans-serif" font-weight="600" font-size="13" fill="#095866">SSL 256-bit</text>
						</svg>
					</li>
					<li class="trust__logo">
						<svg viewBox="0 0 120 32" aria-label="Trustpilot 4.7 su 5" role="img">
							<rect x="0" y="0" width="120" height="32" rx="6" fill="#ffffff" stroke="#e2e8df" />
							<text x="60" y="21" text-anchor="middle" font-family="Inter, sans-serif" font-weight="600" font-size="13" fill="#E44203">4,7 / 5</text>
						</svg>
					</li>
				</ul>
			</div>
		</section>

		<!-- ═══════════════════ COME FUNZIONA ═══════════════════ -->
		<section class="how" aria-labelledby="how-title">
			<div class="container">
				<header class="section-head" data-reveal>
					<p class="section-head__eyebrow">Come funziona</p>
					<h2 id="how-title" class="section-head__title">Spedire non e mai stato cosi semplice</h2>
					<p class="section-head__sub">Tre passaggi, nessuna complicazione: dal preventivo alla consegna.</p>
				</header>

				<ol class="steps" role="list">
					<li class="step" data-reveal>
						<div class="step__icon" aria-hidden="true">
							<svg viewBox="0 0 48 48" focusable="false">
								<circle cx="24" cy="24" r="22" fill="#e6f1f3" />
								<path d="M14 28h20M14 22h20M14 16h12" stroke="#095866" stroke-width="2" stroke-linecap="round" />
								<circle cx="34" cy="34" r="6" fill="#E44203" />
								<path d="M31 34l2 2 4-4" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
						</div>
						<div class="step__num">01</div>
						<h3 class="step__title">Calcola</h3>
						<p class="step__text">Inserisci CAP, peso e dimensioni. Vedi subito il prezzo trasparente, senza sorprese.</p>
					</li>
					<li class="step" data-reveal>
						<div class="step__icon" aria-hidden="true">
							<svg viewBox="0 0 48 48" focusable="false">
								<circle cx="24" cy="24" r="22" fill="#e6f1f3" />
								<rect x="14" y="14" width="20" height="22" rx="3" fill="#fff" stroke="#095866" stroke-width="2" />
								<path d="M18 20h12M18 24h12M18 28h8" stroke="#095866" stroke-width="2" stroke-linecap="round" />
								<circle cx="34" cy="14" r="6" fill="#E44203" />
								<path d="M34 11v6M31 14h6" stroke="#fff" stroke-width="2" stroke-linecap="round" />
							</svg>
						</div>
						<div class="step__num">02</div>
						<h3 class="step__title">Prenota</h3>
						<p class="step__text">Compila i dati di mittente e destinatario, paga in modo sicuro con Stripe o bonifico.</p>
					</li>
					<li class="step" data-reveal>
						<div class="step__icon" aria-hidden="true">
							<svg viewBox="0 0 48 48" focusable="false">
								<circle cx="24" cy="24" r="22" fill="#e6f1f3" />
								<path d="M8 30h20l4-8h8l-4 8v4H8z" fill="#fff" stroke="#095866" stroke-width="2" stroke-linejoin="round" />
								<circle cx="16" cy="36" r="3" fill="#095866" />
								<circle cx="34" cy="36" r="3" fill="#095866" />
								<circle cx="14" cy="14" r="6" fill="#E44203" />
								<path d="M11 14l2 2 4-4" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
						</div>
						<div class="step__num">03</div>
						<h3 class="step__title">Spediamo</h3>
						<p class="step__text">BRT ritira il pacco a casa o in azienda. Tu segui il viaggio in tempo reale dal tracking.</p>
					</li>
				</ol>
			</div>
		</section>

		<!-- ═══════════════════ SERVIZI HIGHLIGHT ═══════════════════ -->
		<section class="services" aria-labelledby="services-title">
			<div class="container">
				<header class="section-head" data-reveal>
					<p class="section-head__eyebrow">Servizi</p>
					<h2 id="services-title" class="section-head__title">Una soluzione per ogni spedizione</h2>
					<p class="section-head__sub">Italia, Europa o ritiro in PUDO: scegli la formula piu adatta a te.</p>
				</header>

				<div class="services__grid">
					<article class="service-card" data-reveal>
						<div class="service-card__head">
							<span class="service-card__tag">Italia</span>
							<svg class="service-card__icon" viewBox="0 0 32 32" aria-hidden="true">
								<path d="M16 3l11 6v8c0 7-5 11-11 13-6-2-11-6-11-13V9l11-6z" fill="#e6f1f3" stroke="#095866" stroke-width="2" />
								<path d="M11 16l4 4 7-7" stroke="#E44203" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none" />
							</svg>
						</div>
						<h3 class="service-card__title">Spedizioni nazionali</h3>
						<p class="service-card__text">
							Consegna in 24/48 h in tutta Italia. Tariffe da 5,90 €, ritiro gratuito a domicilio.
						</p>
						<NuxtLink to="/servizi" class="service-card__cta">
							Scopri
							<span aria-hidden="true">-&gt;</span>
						</NuxtLink>
					</article>

					<article class="service-card service-card--featured" data-reveal>
						<div class="service-card__head">
							<span class="service-card__tag service-card__tag--accent">Europa</span>
							<svg class="service-card__icon" viewBox="0 0 32 32" aria-hidden="true">
								<circle cx="16" cy="16" r="13" fill="#fff5f0" stroke="#E44203" stroke-width="2" />
								<path d="M3 16h26M16 3c4 4 4 22 0 26M16 3c-4 4-4 22 0 26" stroke="#095866" stroke-width="2" fill="none" />
							</svg>
						</div>
						<h3 class="service-card__title">Spedizioni in Europa</h3>
						<p class="service-card__text">
							Oltre 30 paesi europei serviti. Sdoganamento incluso, tracking unico fino a destinazione.
						</p>
						<NuxtLink to="/servizi" class="service-card__cta">
							Scopri
							<span aria-hidden="true">-&gt;</span>
						</NuxtLink>
					</article>

					<article class="service-card" data-reveal>
						<div class="service-card__head">
							<span class="service-card__tag">PUDO</span>
							<svg class="service-card__icon" viewBox="0 0 32 32" aria-hidden="true">
								<rect x="5" y="11" width="22" height="16" rx="2" fill="#e6f1f3" stroke="#095866" stroke-width="2" />
								<path d="M5 11l11-7 11 7" fill="none" stroke="#095866" stroke-width="2" stroke-linejoin="round" />
								<rect x="13" y="17" width="6" height="10" fill="#E44203" />
							</svg>
						</div>
						<h3 class="service-card__title">Ritiro in PUDO</h3>
						<p class="service-card__text">
							Risparmia fino al 25% scegliendo il ritiro o la consegna in uno dei 1.500 punti BRT.
						</p>
						<NuxtLink to="/servizi" class="service-card__cta">
							Scopri
							<span aria-hidden="true">-&gt;</span>
						</NuxtLink>
					</article>
				</div>
			</div>
		</section>

		<!-- ═══════════════════ TESTIMONIALS ═══════════════════ -->
		<section class="reviews" aria-labelledby="reviews-title">
			<div class="container">
				<header class="section-head" data-reveal>
					<p class="section-head__eyebrow">Recensioni</p>
					<h2 id="reviews-title" class="section-head__title">Migliaia di clienti soddisfatti</h2>
					<p class="section-head__sub">Privati e aziende ci scelgono ogni giorno per spedire in Italia ed Europa.</p>
				</header>

				<div class="reviews__grid">
					<figure class="review" data-reveal>
						<div class="review__stars" aria-label="5 stelle su 5">
							<span aria-hidden="true">*****</span>
						</div>
						<blockquote class="review__quote">
							"Prezzi imbattibili e ritiro puntuale. Spedisco ogni settimana per il mio
							e-commerce e non ho mai avuto problemi. Assistenza rapida e in italiano."
						</blockquote>
						<figcaption class="review__author">
							<span class="review__avatar" aria-hidden="true">MR</span>
							<span>
								<strong>Marta R.</strong>
								<span class="review__role">E-commerce, Milano</span>
							</span>
						</figcaption>
					</figure>

					<figure class="review" data-reveal>
						<div class="review__stars" aria-label="5 stelle su 5">
							<span aria-hidden="true">*****</span>
						</div>
						<blockquote class="review__quote">
							"Ho spedito un pacco fragile a Berlino, arrivato in 3 giorni e perfettamente
							integro. La piattaforma e chiarissima, anche per chi non e del settore."
						</blockquote>
						<figcaption class="review__author">
							<span class="review__avatar" aria-hidden="true">LB</span>
							<span>
								<strong>Luca B.</strong>
								<span class="review__role">Privato, Bologna</span>
							</span>
						</figcaption>
					</figure>

					<figure class="review" data-reveal>
						<div class="review__stars" aria-label="5 stelle su 5">
							<span aria-hidden="true">*****</span>
						</div>
						<blockquote class="review__quote">
							"Per la nostra azienda usavamo tre corrieri diversi: ora gestiamo tutto da
							qui. Tariffe trasparenti e fattura elettronica automatica."
						</blockquote>
						<figcaption class="review__author">
							<span class="review__avatar" aria-hidden="true">SC</span>
							<span>
								<strong>Sara C.</strong>
								<span class="review__role">PMI, Padova</span>
							</span>
						</figcaption>
					</figure>
				</div>
			</div>
		</section>

		<!-- ═══════════════════ FAQ ═══════════════════ -->
		<section class="faq" aria-labelledby="faq-title">
			<div class="container faq__inner">
				<aside class="faq__aside">
					<header class="section-head section-head--left" data-reveal>
						<p class="section-head__eyebrow">Domande frequenti</p>
						<h2 id="faq-title" class="section-head__title">Tutto quello che vuoi sapere</h2>
						<p class="section-head__sub">Le risposte alle domande più comuni dei nostri clienti.</p>
					</header>
					<div class="faq__aside-meta" data-reveal>
						<p class="faq__aside-hint">Non trovi la risposta? Scrivici o guarda le guide complete.</p>
						<div class="faq__aside-actions">
							<SfButton to="/faq">Vai a tutte le FAQ</SfButton>
							<SfButton variant="secondary" to="/contatti">Contatta l'assistenza</SfButton>
						</div>
					</div>
				</aside>

				<ul class="faq__list" role="list">
					<li
						v-for="(item, i) in faqs"
						:key="i"
						class="faq__item"
						:data-open="isFaqOpen(i) ? 'true' : 'false'"
						data-reveal
					>
						<button
							type="button"
							class="faq__q"
							:aria-expanded="isFaqOpen(i)"
							:aria-controls="`faq-panel-${i}`"
							:id="`faq-trigger-${i}`"
							@click="toggleFaq(i)"
						>
							<span>{{ item.q }}</span>
							<span class="faq__icon" aria-hidden="true">
								<svg viewBox="0 0 16 16" focusable="false">
									<path d="M3 6l5 5 5-5" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" />
								</svg>
							</span>
						</button>
						<div
							v-show="isFaqOpen(i)"
							:id="`faq-panel-${i}`"
							role="region"
							:aria-labelledby="`faq-trigger-${i}`"
							class="faq__a"
						>
							<p>{{ item.a }}</p>
						</div>
					</li>
				</ul>
			</div>
		</section>

		<!-- ═══════════════════ CTA FINALE ═══════════════════ -->
		<section class="cta-final" aria-labelledby="cta-title">
			<div class="container cta-final__inner" data-reveal>
				<div>
					<p class="cta-final__eyebrow">Preventivo istantaneo</p>
					<h2 id="cta-title" class="cta-final__title">
						Calcola il preventivo in 30 secondi.
					</h2>
					<p class="cta-final__sub">
						Tariffe trasparenti, nessun costo nascosto, nessuna registrazione obbligatoria.
					</p>
				</div>
				<SfButton to="/preventivo" size="lg">
					Inizia ora
				</SfButton>
			</div>
		</section>
	</div>
</template>
