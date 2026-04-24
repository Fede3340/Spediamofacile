<!--
	error.vue — Root error handler Nuxt 3.

	Renderizzato automaticamente da Nuxt per ogni errore (404, 500, 503, ecc.)
	al posto del layout normale. Riceve `error` via prop standard Nuxt.

	A11y: h1 unico, contrasto AA, CTA grandi tappabili, focus-visible.
	SEO: title dinamico per status, robots: noindex (no SEO bleed delle pagine errore).
	Design: palette teal + arancione, illustrazione SVG inline (zero asset extra),
	radius 14px, shadow soft. Mai blu.
-->
<script setup lang="ts">
import { computed } from 'vue'

interface NuxtErrorShape {
	statusCode?: number
	statusMessage?: string
	message?: string
	url?: string
}

const props = defineProps<{ error: NuxtErrorShape }>()

const statusCode = computed<number>(() => Number(props.error?.statusCode) || 500)
const is404 = computed(() => statusCode.value === 404)
const is503 = computed(() => statusCode.value === 503)
const is5xx = computed(() => statusCode.value >= 500 && statusCode.value !== 503)

const heading = computed(() => {
	if (is404.value) return 'Pagina non trovata'
	if (is503.value) return 'Manutenzione in corso'
	if (is5xx.value) return 'Errore di sistema'
	return 'Si è verificato un problema'
})

const description = computed(() => {
	if (is404.value) return 'La pagina che cerchi non esiste o è stata spostata.'
	if (is503.value) return 'Stiamo aggiornando il servizio per migliorarlo. Torneremo online a breve.'
	if (is5xx.value) return 'Si è verificato un problema. I tecnici sono stati avvisati.'
	return 'Si è verificato un errore imprevisto. Riprova fra qualche istante.'
})

// ETA placeholder per la modalità manutenzione: in produzione potrà essere
// popolato dal backend (es. header `Retry-After`) o da una env var.
const maintenanceEta = computed(() => 'Stimato: pochi minuti')

const pageTitle = computed(() => `${statusCode.value} · ${heading.value} — SpediamoFacile`)

useSeoMeta({
	title: pageTitle,
	robots: 'noindex, nofollow',
	description: description,
})

const handleHome = () => {
	clearError({ redirect: '/' })
}
</script>

<template>
	<div class="error-shell">
		<main class="error-shell__main" role="main">
			<article class="error-card" :data-status="statusCode">
				<div class="error-card__logo">
					<NuxtLink to="/" aria-label="Vai alla home di SpediamoFacile" class="error-card__logo-link">
						<Logo :is-navbar="true" />
					</NuxtLink>
				</div>

				<div class="error-card__illustration" aria-hidden="true">
					<!-- 404: pacco smarrito con punto interrogativo -->
					<svg
						v-if="is404"
						viewBox="0 0 240 180"
						width="240"
						height="180"
						xmlns="http://www.w3.org/2000/svg"
					>
						<defs>
							<linearGradient id="errorBox404" x1="0" y1="0" x2="0" y2="1">
								<stop offset="0%" stop-color="#0d6e80" />
								<stop offset="100%" stop-color="#095866" />
							</linearGradient>
						</defs>
						<!-- ombra -->
						<ellipse cx="120" cy="160" rx="78" ry="8" fill="#095866" opacity="0.12" />
						<!-- pacco -->
						<g transform="translate(60 40)">
							<path d="M0 30 L60 0 L120 30 L60 60 Z" fill="#f4a17a" />
							<path d="M0 30 L60 60 L60 120 L0 90 Z" fill="url(#errorBox404)" />
							<path d="M120 30 L60 60 L60 120 L120 90 Z" fill="#0a7489" />
							<path d="M0 30 L60 60 L120 30" fill="none" stroke="#ffffff" stroke-width="2" opacity="0.4" />
							<!-- nastro arancione -->
							<rect x="58" y="0" width="4" height="120" fill="#e44203" opacity="0.85" />
							<rect x="-2" y="44" width="124" height="4" fill="#e44203" opacity="0.85" transform="skewY(-26.5)" />
						</g>
						<!-- punto interrogativo arancione -->
						<g transform="translate(170 18)" fill="#e44203">
							<circle cx="18" cy="18" r="18" opacity="0.15" />
							<path
								d="M18 8a7 7 0 0 1 7 7c0 3-2 4.5-3.5 5.5-1 .7-1.5 1.3-1.5 2.5v1h-4v-1.5c0-2.5 1.3-3.7 2.6-4.6.9-.6 1.4-1.1 1.4-2.4a2 2 0 0 0-4 0h-4a6 6 0 0 1 6-6Z"
							/>
							<circle cx="18" cy="28" r="2.2" />
						</g>
					</svg>

					<!-- 503: chiave inglese / strumenti manutenzione -->
					<svg
						v-else-if="is503"
						viewBox="0 0 240 180"
						width="240"
						height="180"
						xmlns="http://www.w3.org/2000/svg"
					>
						<ellipse cx="120" cy="160" rx="70" ry="8" fill="#095866" opacity="0.12" />
						<g transform="translate(56 30)" fill="none" stroke="#095866" stroke-width="6" stroke-linecap="round" stroke-linejoin="round">
							<path d="M14 96 L70 40 a18 18 0 0 1 26 0 a18 18 0 0 1 0 26 L40 122 a10 10 0 0 1-14 0 l-12 -12 a10 10 0 0 1 0 -14Z" fill="#ffffff" />
							<path d="M76 30 a14 14 0 1 0 24 24" stroke="#e44203" />
						</g>
						<g transform="translate(140 24)" fill="#e44203">
							<circle cx="14" cy="14" r="14" opacity="0.18" />
							<path d="M14 5 v10 M14 19 v.01" stroke="#e44203" stroke-width="3" stroke-linecap="round" />
						</g>
					</svg>

					<!-- 500 e altri: triangolo allerta + pacco -->
					<svg
						v-else
						viewBox="0 0 240 180"
						width="240"
						height="180"
						xmlns="http://www.w3.org/2000/svg"
					>
						<ellipse cx="120" cy="160" rx="74" ry="8" fill="#095866" opacity="0.12" />
						<g transform="translate(50 56)">
							<path d="M0 26 L52 0 L104 26 L52 52 Z" fill="#0a7489" />
							<path d="M0 26 L52 52 L52 100 L0 74 Z" fill="#095866" />
							<path d="M104 26 L52 52 L52 100 L104 74 Z" fill="#0d6e80" />
						</g>
						<g transform="translate(150 18)" fill="#e44203">
							<path d="M30 4 L58 52 H2 Z" />
							<path d="M30 22 v16 M30 44 v.01" stroke="#ffffff" stroke-width="4" stroke-linecap="round" fill="none" />
						</g>
					</svg>
				</div>

				<p class="error-card__status" aria-hidden="true">{{ statusCode }}</p>
				<h1 class="error-card__heading">{{ heading }}</h1>
				<p class="error-card__desc">{{ description }}</p>

				<p v-if="is503" class="error-card__eta" role="status">
					{{ maintenanceEta }}
				</p>

				<div class="error-card__actions">
					<button
						type="button"
						class="btn btn-cta error-card__cta"
						@click="handleHome"
					>
						Torna alla home
					</button>

					<NuxtLink
						v-if="is404"
						to="/traccia"
						class="btn btn-secondary error-card__cta"
					>
						Traccia spedizione
					</NuxtLink>

					<NuxtLink
						v-else-if="!is503"
						to="/contatti"
						class="btn btn-secondary error-card__cta"
					>
						Contatta assistenza
					</NuxtLink>
				</div>
			</article>
		</main>
	</div>
</template>

<style scoped>
.error-shell {
	min-height: 100vh;
	display: flex;
	flex-direction: column;
	background:
		radial-gradient(circle at 12% 0%, rgba(9, 88, 102, 0.1), transparent 42%),
		radial-gradient(circle at 88% 100%, rgba(228, 66, 3, 0.08), transparent 42%),
		#f8f9fb;
}

.error-shell__main {
	flex: 1;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 2rem 1rem;
}

.error-card {
	background: #ffffff;
	border: 1px solid rgba(9, 88, 102, 0.12);
	border-radius: 14px;
	padding: 2rem 1.5rem;
	max-width: 520px;
	width: 100%;
	text-align: center;
	box-shadow:
		0 18px 48px rgba(9, 88, 102, 0.12),
		0 4px 12px rgba(15, 23, 42, 0.05);
}

.error-card__logo {
	display: flex;
	justify-content: center;
	margin-bottom: 1.25rem;
}

.error-card__logo img {
	height: 40px;
	width: auto;
}

.error-card__illustration {
	display: flex;
	justify-content: center;
	margin-bottom: 0.5rem;
}

.error-card__illustration svg {
	max-width: 100%;
	height: auto;
}

.error-card__status {
	font-family: var(--font-montserrat, 'Montserrat', sans-serif);
	font-size: 2.75rem;
	font-weight: 800;
	color: var(--color-brand-primary, #095866);
	margin: 0;
	line-height: 1;
	letter-spacing: -0.04em;
}

.error-card__heading {
	font-family: var(--font-montserrat, 'Montserrat', sans-serif);
	font-size: 1.35rem;
	font-weight: 800;
	color: var(--color-brand-text, #1d2738);
	margin: 0.35rem 0 0.5rem;
	letter-spacing: -0.01em;
}

.error-card__desc {
	font-size: 0.95rem;
	line-height: 1.55;
	color: var(--color-brand-text-secondary, #525252);
	margin: 0 0 1.25rem;
}

.error-card__eta {
	display: inline-block;
	margin: 0 0 1.25rem;
	padding: 0.45rem 0.9rem;
	border-radius: 999px;
	font-size: 0.8125rem;
	font-weight: 700;
	letter-spacing: 0.02em;
	color: var(--color-brand-secondary, #e44203);
	background: rgba(228, 66, 3, 0.1);
	border: 1px solid rgba(228, 66, 3, 0.2);
}

.error-card__actions {
	display: flex;
	flex-direction: column;
	gap: 0.6rem;
	align-items: stretch;
}

@media (min-width: 480px) {
	.error-card__actions {
		flex-direction: row;
		justify-content: center;
		flex-wrap: wrap;
	}
}

.error-card__cta {
	min-height: 46px;
	padding: 0 1.25rem;
	border-radius: 12px;
	font-weight: 700;
	letter-spacing: -0.01em;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	text-decoration: none;
}

.error-card__cta:focus-visible {
	outline: 2px solid var(--color-brand-secondary, #e44203);
	outline-offset: 2px;
}
</style>
