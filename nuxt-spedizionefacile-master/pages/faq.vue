<script setup lang="ts">
import '~/assets/css/faq.css';
import { computed, ref } from 'vue';
import { useFaqs, type FaqCategory } from '~/composables/useFaqs';

const { faqs, categories, highlightMatch, escapeHtml } = useFaqs();
const faqCategories = computed(() => categories as FaqCategory[]);

// ── Stato UI ─────────────────────────────────────────────────
const searchQuery = ref('');
const activeCategory = ref<FaqCategory | 'Tutte'>('Tutte');
const searchInputRef = ref<HTMLInputElement | null>(null);

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
	const map: Record<string, number> = { Tutte: faqs.length };
	for (const cat of categories) map[cat] = 0;
	for (const item of faqs) map[item.category] = (map[item.category] || 0) + 1;
	return map;
});

const hasResults = computed(() => filteredFaqs.value.length > 0);
const totalFaqs = faqs.length;

// ── Azioni ───────────────────────────────────────────────────
function selectCategory(cat: FaqCategory | 'Tutte') {
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
	title: 'FAQ — Domande frequenti SpedizioneFacile',
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
							<button type="button" class="btn btn-secondary" @click="resetFilters">
								Resetta filtri
							</button>
							<NuxtLink to="/contatti" class="btn btn-primary">
								Contatta un operatore
							</NuxtLink>
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
							<NuxtLink to="/contatti" class="btn btn-primary">
								Vai ai contatti
							</NuxtLink>
							<NuxtLink to="/account/supporto" class="btn btn-secondary">
								Apri ticket supporto
							</NuxtLink>
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
							<NuxtLink to="/contatti" class="btn btn-primary faq-side__cta">
								Contattaci ora
							</NuxtLink>
							<button
								type="button"
								class="btn btn-secondary faq-side__cta"
								@click="focusSearch"
							>
								Cerca un'altra domanda
							</button>
						</div>
					</div>
				</aside>
			</div>
		</section>
	</div>
</template>
