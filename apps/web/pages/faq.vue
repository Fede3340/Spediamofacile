<script setup>
import '~/assets/css/content.css';
import { computed, ref } from 'vue';
import { faqs, categories, highlightMatch } from '~/utils/faqs';

const faqCategories = computed(() => categories);

// ── Stato UI ─────────────────────────────────────────────────
const searchQuery = ref('');
const activeCategory = ref('Tutte');
const searchInputRef = ref(null);

// ── Computed ─────────────────────────────────────────────────
const normalizedQuery = computed(() => searchQuery.value.trim().toLowerCase());

const filteredFaqs = computed(() => {
	const q = normalizedQuery.value;
	return faqs.filter((item) => {
		const matchesCat =
			activeCategory.value === 'Tutte' || item.category === activeCategory.value;
		if (!matchesCat) return false;
		if (!q) return true;
		return (
			item.question.toLowerCase().includes(q) ||
			item.answer.toLowerCase().includes(q)
		);
	});
});

const countByCategory = computed(() => {
	const map = { Tutte: faqs.length };
	for (const cat of categories) map[cat] = 0;
	for (const item of faqs) map[item.category] = (map[item.category] || 0) + 1;
	return map;
});

const hasResults = computed(() => filteredFaqs.value.length > 0);
const totalFaqs = faqs.length;

// ── Azioni ───────────────────────────────────────────────────
function selectCategory(cat) {
	activeCategory.value = cat;
}

function resetFilters() {
	searchQuery.value = '';
	activeCategory.value = 'Tutte';
	searchInputRef.value?.focus();
}

function focusSearch() {
	searchInputRef.value?.focus();
}

// ── SEO ──────────────────────────────────────────────────────
useSeoMeta({
	title: 'FAQ — Domande frequenti',
	ogTitle: 'FAQ — Domande frequenti SpedizioneFacile',
	description:
		'Tutte le risposte su spedizioni, preventivi, pagamenti, tracking, reclami, account e profilo Pro. Cerca subito la tua domanda o contatta un operatore.',
	ogDescription:
		'Domande frequenti SpedizioneFacile: spedizione, preventivi, pagamenti, tracking, reclami, account e Pro. Cerca o parla con un operatore.',
});

// Breadcrumb: Home › FAQ
useBreadcrumbSchema([
	{ name: 'Home', url: '/' },
	{ name: 'FAQ' },
]);

// JSON-LD FAQPage con TUTTE le FAQ per rich snippet Google.
useHead({
	script: [
		{
			type: 'application/ld+json',
			innerHTML: JSON.stringify({
				'@context': 'https://schema.org',
				'@type': 'FAQPage',
				mainEntity: faqs.map((item) => ({
					'@type': 'Question',
					name: item.question,
					acceptedAnswer: {
						'@type': 'Answer',
						text: item.answer,
					},
				})),
			}),
		},
	],
});
</script>

<template>
	<div class="faq-shell">
		<!-- Skip-to-content (accessibilità) -->
		<a href="#faq-list" class="faq-skip-link">Vai alla lista delle domande</a>

		<!-- ── HERO ──────────────────────────────────────────── -->
		<PublicPageHeader
			eyebrow="Centro assistenza"
			title="Domande frequenti"
			:description="`${totalFaqs} risposte chiare su spedizioni, preventivi, pagamenti, tracking, reclami, account e profilo Pro. Cerca una parola chiave o esplora per categoria.`"
			:crumbs="[{ label: 'Home', to: '/' }, { label: 'FAQ' }]">
			<label class="faq-search" :class="{ 'is-filled': searchQuery.length > 0 }">
				<span class="sr-only">Cerca tra le domande frequenti</span>
				<svg class="faq-search__icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<circle cx="11" cy="11" r="8" />
					<path d="m21 21-4.3-4.3" />
				</svg>
				<input ref="searchInputRef" v-model="searchQuery" type="search" class="faq-search__input" placeholder="Cerca: contrassegno, ritiro, fattura, reclamo..." autocomplete="off" aria-label="Cerca tra le domande frequenti">
				<button v-if="searchQuery" type="button" class="faq-search__clear" aria-label="Cancella ricerca" @click="searchQuery = ''">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M18 6 6 18" />
						<path d="m6 6 12 12" />
					</svg>
				</button>
			</label>
		</PublicPageHeader>

		<!-- ── CHIP CATEGORIE ────────────────────────────────── -->
		<section class="faq-chips" aria-label="Filtra per categoria">
			<div class="my-container">
				<div class="faq-chips__row" role="tablist">
					<button
						type="button"
						class="faq-chip"
						:class="{ 'is-active': activeCategory === 'Tutte' }"
						role="tab"
						:aria-selected="activeCategory === 'Tutte'"
						@click="selectCategory('Tutte')"
					>
						<span>Tutte</span>
						<span class="faq-chip__count">{{ countByCategory['Tutte'] }}</span>
					</button>
					<button
						v-for="cat in faqCategories"
						:key="cat"
						type="button"
						class="faq-chip"
						:class="{ 'is-active': activeCategory === cat }"
						role="tab"
						:aria-selected="activeCategory === cat"
						@click="selectCategory(cat)"
					>
						<span>{{ cat }}</span>
						<span class="faq-chip__count">{{ countByCategory[cat] }}</span>
					</button>
				</div>
			</div>
		</section>

		<!-- ── BODY: lista + sidebar sticky ──────────────────── -->
		<section class="faq-body">
			<div class="my-container faq-body__grid">
				<!-- Colonna principale: lista accordion -->
				<div id="faq-list" class="faq-body__main">
					<header class="faq-body__head">
						<h2 class="faq-body__title">
							<template v-if="activeCategory === 'Tutte'">Tutte le domande</template>
							<template v-else>Categoria: {{ activeCategory }}</template>
						</h2>
						<p class="faq-body__count" aria-live="polite">
							{{ filteredFaqs.length }} risultat{{ filteredFaqs.length === 1 ? 'o' : 'i' }}
							<template v-if="searchQuery"> per "{{ searchQuery }}"</template>
						</p>
					</header>

					<!-- Empty state -->
					<div v-if="!hasResults" class="faq-empty" role="status">
						<div class="faq-empty__icon" aria-hidden="true">
							<svg
								xmlns="http://www.w3.org/2000/svg"
								width="38"
								height="38"
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								stroke-width="2"
								stroke-linecap="round"
								stroke-linejoin="round"
							>
								<circle cx="11" cy="11" r="8" />
								<path d="m21 21-4.3-4.3" />
								<path d="M8 11h6" />
							</svg>
						</div>
						<h3 class="faq-empty__title">Nessuna domanda trovata</h3>
						<p class="faq-empty__text">
							Non abbiamo trovato risposte per la tua ricerca. Prova a cambiare parole
							chiave o resetta i filtri.
						</p>
						<div class="faq-empty__actions">
							<SfButton variant="secondary" @click="resetFilters">
								Resetta filtri
							</SfButton>
							<SfButton to="/contatti">
								Contatta un operatore
							</SfButton>
						</div>
					</div>

					<!-- Lista accordion <details> nativi -->
					<ul v-else class="faq-list" role="list">
						<li
							v-for="(item, index) in filteredFaqs"
							:key="item.id"
							class="faq-item"
						>
							<details
								:id="`faq-${item.id}`"
								class="faq-details"
								:open="index === 0 && !!searchQuery"
							>
								<summary class="faq-details__summary">
									<span class="faq-details__cat">{{ item.category }}</span>
									<!-- eslint-disable-next-line vue/no-v-html — highlightMatch escapa il testo prima del wrap <mark> -->
									<span
										class="faq-details__q"
										v-html="highlightMatch(item.question, searchQuery)"
									/>
									<span class="faq-details__chev" aria-hidden="true">
										<svg
											xmlns="http://www.w3.org/2000/svg"
											width="18"
											height="18"
											viewBox="0 0 24 24"
											fill="none"
											stroke="currentColor"
											stroke-width="2"
											stroke-linecap="round"
											stroke-linejoin="round"
										>
											<path d="m6 9 6 6 6-6" />
										</svg>
									</span>
								</summary>
								<div class="faq-details__panel">
									<!-- eslint-disable-next-line vue/no-v-html — highlightMatch escapa il testo prima del wrap <mark> -->
									<p
										class="faq-details__a"
										v-html="highlightMatch(item.answer, searchQuery)"
									/>
									<div class="faq-details__meta">
										<a
											:href="`#faq-${item.id}`"
											class="faq-details__permalink"
											aria-label="Copia link a questa domanda"
										>
											# Link diretto
										</a>
									</div>
								</div>
							</details>
						</li>
					</ul>

					<!-- CTA "Non hai trovato risposta?" -->
					<aside class="faq-bottom-cta" aria-label="Hai bisogno di altro aiuto?">
						<div class="faq-bottom-cta__copy">
							<h3 class="faq-bottom-cta__title">Non hai trovato risposta?</h3>
							<p class="faq-bottom-cta__text">
								Scrivici o accedi al supporto: rispondiamo in giornata, in italiano,
								da persone vere.
							</p>
						</div>
						<div class="faq-bottom-cta__actions">
							<SfButton to="/contatti">
								Vai ai contatti
							</SfButton>
							<SfButton variant="secondary" to="/account/supporto">
								Apri ticket supporto
							</SfButton>
						</div>
					</aside>
				</div>

				<!-- Sidebar sticky desktop: operatore -->
				<aside class="faq-side" aria-label="Parla con un operatore">
					<div class="faq-side__card">
						<div class="faq-side__icon" aria-hidden="true">
							<svg
								xmlns="http://www.w3.org/2000/svg"
								width="22"
								height="22"
								viewBox="0 0 24 24"
								fill="currentColor"
							>
								<path
									d="M20 15.5c-1.25 0-2.45-.2-3.57-.57a1 1 0 0 0-1.02.24l-2.2 2.2a15.05 15.05 0 0 1-6.59-6.58l2.2-2.21a.96.96 0 0 0 .25-1A11.36 11.36 0 0 1 8.5 4a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1 17 17 0 0 0 17 17 1 1 0 0 0 1-1v-3.5a1 1 0 0 0-1-1Z"
								/>
							</svg>
						</div>
						<h3 class="faq-side__title">Parla con un operatore</h3>
						<p class="faq-side__text">
							Quando la FAQ non basta, un consulente SpedizioneFacile risponde via
							telefono, email o ticket.
						</p>

						<dl class="faq-side__list">
							<div class="faq-side__row">
								<dt>Numero verde</dt>
								<dd>
									<a href="tel:800123456" class="faq-side__link">800 12 34 56</a>
								</dd>
							</div>
							<div class="faq-side__row">
								<dt>Email</dt>
								<dd>
									<a
										href="mailto:supporto@spediamofacile.it"
										class="faq-side__link"
									>
										supporto@spediamofacile.it
									</a>
								</dd>
							</div>
							<div class="faq-side__row">
								<dt>Orari</dt>
								<dd>Lun-Ven 9:00-18:00<br>Sab 9:00-13:00</dd>
							</div>
						</dl>

						<div class="faq-side__actions">
							<SfButton to="/contatti" class="faq-side__cta">
								Contattaci ora
							</SfButton>
							<SfButton variant="secondary" class="faq-side__cta" @click="focusSearch">
								Cerca un'altra domanda
							</SfButton>
						</div>
					</div>
				</aside>
			</div>
		</section>
	</div>
</template>

<style scoped>
/**
 * /faq page styling.
 * Aggiunto 2026-04-28 dopo audit DOM live: 0 regole CSS .faq-* in tutto il
 * codebase, tutto renderizzato in display:block.
 *
 * Layout: shell -> [side (categorie/CTA) + body (lista FAQ con search)]
 */

.faq-shell {
	display: grid;
	grid-template-columns: 1fr;
	gap: 24px;
	max-width: 1200px;
	margin: 40px auto;
	padding: 0 24px;
}

@media (min-width: 1024px) {
	.faq-shell {
		grid-template-columns: 280px 1fr;
		gap: 32px;
	}
}

/* ===== Sidebar ===== */

.faq-side {
	display: flex;
	flex-direction: column;
	gap: 16px;
	position: relative;
}

@media (min-width: 1024px) {
	.faq-side {
		position: sticky;
		top: 90px;
		align-self: start;
	}
}

.faq-side__card {
	display: flex;
	flex-direction: column;
	gap: 12px;
	padding: 20px;
	background: #ffffff;
	border-radius: 14px;
	box-shadow: 0 2px 8px rgba(9, 88, 102, 0.05), 0 0 0 1px rgba(9, 88, 102, 0.06);
}

.faq-side__title {
	font-size: 0.875rem;
	font-weight: 700;
	color: var(--color-brand-text);
	margin: 0;
	letter-spacing: 0.04em;
	text-transform: uppercase;
}

.faq-side__list {
	display: flex;
	flex-direction: column;
	gap: 4px;
	margin: 0;
	padding: 0;
	list-style: none;
}

.faq-side__row {
	display: block;
}

.faq-side__link {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 8px;
	padding: 8px 12px;
	font-size: 0.875rem;
	font-weight: 500;
	color: var(--color-brand-text);
	background: transparent;
	border: none;
	border-radius: 8px;
	cursor: pointer;
	transition: background 150ms ease, color 150ms ease;
	width: 100%;
	text-align: left;
}

.faq-side__link:hover,
.faq-side__link.is-active {
	background: rgba(9, 88, 102, 0.08);
	color: var(--color-brand-primary);
}

.faq-side__cta {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 10px;
	padding: 20px;
	background: linear-gradient(135deg, rgba(9, 88, 102, 0.04), rgba(228, 66, 3, 0.04));
	border-radius: 14px;
	text-align: center;
}

.faq-side__icon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 40px;
	height: 40px;
	border-radius: 10px;
	background: rgba(9, 88, 102, 0.1);
	color: var(--color-brand-primary);
}

.faq-side__text {
	font-size: 0.8125rem;
	color: var(--color-brand-text-secondary, #5f6470);
	line-height: 1.4;
	margin: 0;
}

.faq-side__actions {
	display: flex;
	flex-direction: column;
	gap: 6px;
	width: 100%;
}

/* ===== Main body ===== */

.faq-body {
	display: flex;
	flex-direction: column;
	gap: 16px;
	min-width: 0;
}

.faq-body__head {
	display: flex;
	flex-direction: column;
	gap: 8px;
	margin-bottom: 8px;
}

.faq-body__title {
	font-size: clamp(1.5rem, 3vw, 2rem);
	font-weight: 700;
	color: var(--color-brand-text);
	margin: 0;
}

.faq-body__count {
	font-size: 0.875rem;
	color: var(--color-brand-text-muted, #7a8190);
	font-weight: 500;
}

.faq-body__grid {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.faq-body__main {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

/* ===== Search ===== */

.faq-search {
	display: flex;
	align-items: center;
	gap: 10px;
	padding: 10px 14px;
	background: #ffffff;
	border-radius: 10px;
	box-shadow: 0 0 0 1px rgba(9, 88, 102, 0.1);
	transition: box-shadow 150ms ease;
}

.faq-search:focus-within {
	box-shadow: 0 0 0 2px var(--color-brand-primary);
}

.faq-search__icon {
	display: inline-flex;
	color: var(--color-brand-text-muted, #7a8190);
	flex-shrink: 0;
}

.faq-search__input {
	flex: 1;
	border: none;
	outline: none;
	background: transparent;
	font-size: 0.9375rem;
	color: var(--color-brand-text);
	padding: 0;
	min-width: 0;
}

.faq-search__clear {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 24px;
	height: 24px;
	border: none;
	background: rgba(9, 88, 102, 0.08);
	color: var(--color-brand-primary);
	border-radius: 999px;
	cursor: pointer;
	flex-shrink: 0;
}

/* ===== Chips category filter ===== */

.faq-chips {
	display: flex;
	flex-direction: column;
	gap: 6px;
}

.faq-chips__row {
	display: flex;
	flex-wrap: wrap;
	gap: 6px;
}

.faq-chip {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	padding: 5px 12px;
	font-size: 0.8125rem;
	font-weight: 600;
	color: var(--color-brand-text);
	background: #ffffff;
	border: 1px solid rgba(9, 88, 102, 0.12);
	border-radius: 999px;
	cursor: pointer;
	transition: all 150ms ease;
}

.faq-chip:hover,
.faq-chip.is-active {
	border-color: var(--color-brand-primary);
	background: rgba(9, 88, 102, 0.06);
	color: var(--color-brand-primary);
}

.faq-chip__count {
	font-size: 0.6875rem;
	font-weight: 700;
	color: var(--color-brand-text-muted, #7a8190);
	padding: 0 4px;
}

/* ===== FAQ details (single accordion item) ===== */

.faq-list {
	display: flex;
	flex-direction: column;
	gap: 8px;
	margin: 0;
	padding: 0;
	list-style: none;
}

.faq-item {
	display: block;
}

.faq-details {
	display: block;
	background: #ffffff;
	border-radius: 12px;
	box-shadow: 0 2px 8px rgba(9, 88, 102, 0.04), 0 0 0 1px rgba(9, 88, 102, 0.06);
	overflow: hidden;
	transition: box-shadow 200ms ease;
}

.faq-details[open] {
	box-shadow: 0 6px 18px rgba(9, 88, 102, 0.08), 0 0 0 1px rgba(9, 88, 102, 0.12);
}

.faq-details__summary {
	display: flex;
	align-items: center;
	gap: 12px;
	padding: 16px 20px;
	font-size: 0.9375rem;
	font-weight: 600;
	color: var(--color-brand-text);
	cursor: pointer;
	list-style: none;
}

.faq-details__summary::-webkit-details-marker {
	display: none;
}

.faq-details__cat {
	display: inline-flex;
	align-items: center;
	padding: 2px 8px;
	font-size: 0.6875rem;
	font-weight: 700;
	color: var(--color-brand-orange, #E44203);
	background: rgba(228, 66, 3, 0.08);
	border-radius: 999px;
	letter-spacing: 0.04em;
	text-transform: uppercase;
	flex-shrink: 0;
}

.faq-details__chev {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 24px;
	height: 24px;
	color: var(--color-brand-text-muted, #7a8190);
	margin-left: auto;
	flex-shrink: 0;
	transition: transform 200ms ease;
}

.faq-details[open] .faq-details__chev {
	transform: rotate(180deg);
}

.faq-details__panel {
	padding: 0 20px 20px;
	font-size: 0.875rem;
	color: var(--color-brand-text-secondary, #5f6470);
	line-height: 1.6;
}

.faq-details__meta {
	display: flex;
	align-items: center;
	gap: 12px;
	margin-top: 12px;
	padding-top: 12px;
	border-top: 1px solid rgba(9, 88, 102, 0.08);
	font-size: 0.75rem;
	color: var(--color-brand-text-muted, #7a8190);
}

.faq-details__permalink {
	display: inline-flex;
	align-items: center;
	gap: 4px;
	color: var(--color-brand-primary);
	text-decoration: none;
	font-weight: 600;
}

.faq-details__permalink:hover {
	text-decoration: underline;
}

/* ===== Empty state ===== */

.faq-empty {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 16px;
	padding: 48px 24px;
	background: #ffffff;
	border-radius: 14px;
	box-shadow: 0 2px 8px rgba(9, 88, 102, 0.04);
	text-align: center;
}

.faq-empty__icon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 56px;
	height: 56px;
	border-radius: 50%;
	background: rgba(9, 88, 102, 0.08);
	color: var(--color-brand-primary);
}

.faq-empty__title {
	font-size: 1.125rem;
	font-weight: 700;
	color: var(--color-brand-text);
	margin: 0;
}

.faq-empty__text {
	font-size: 0.875rem;
	color: var(--color-brand-text-secondary, #5f6470);
	margin: 0;
	max-width: 420px;
}

.faq-empty__actions {
	display: flex;
	gap: 12px;
	flex-wrap: wrap;
	justify-content: center;
}

/* ===== Bottom CTA ===== */

.faq-bottom-cta {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 16px;
	margin-top: 24px;
	padding: 32px 24px;
	background: linear-gradient(135deg, rgba(9, 88, 102, 0.04), rgba(228, 66, 3, 0.04));
	border-radius: 16px;
	text-align: center;
}

.faq-bottom-cta__copy {
	display: flex;
	flex-direction: column;
	gap: 6px;
	align-items: center;
}

.faq-bottom-cta__title {
	font-size: 1.25rem;
	font-weight: 700;
	color: var(--color-brand-text);
	margin: 0;
}

.faq-bottom-cta__text {
	font-size: 0.9375rem;
	color: var(--color-brand-text-secondary, #5f6470);
	margin: 0;
}

.faq-bottom-cta__actions {
	display: flex;
	gap: 12px;
	flex-wrap: wrap;
	justify-content: center;
}

.faq-skip-link {
	position: absolute;
	left: -9999px;
	top: 0;
}

.faq-skip-link:focus {
	left: 12px;
	top: 12px;
	z-index: 100;
	background: #ffffff;
	padding: 8px 16px;
	border-radius: 8px;
}

</style>
