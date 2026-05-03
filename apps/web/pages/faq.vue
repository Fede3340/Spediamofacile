<script setup>

import { faqs, categories } from '~/utils/faqs';

const searchQuery = ref('');
const activeCategory = ref('Tutte');
const searchRef = ref(null);

const normalizedQuery = computed(() => searchQuery.value.trim().toLowerCase());

const filteredFaqs = computed(() => {
	const q = normalizedQuery.value;
	return faqs.filter((item) => {
		const matchesCat = activeCategory.value === 'Tutte' || item.category === activeCategory.value;
		if (!matchesCat) return false;
		if (!q) return true;
		return item.question.toLowerCase().includes(q) || item.answer.toLowerCase().includes(q);
	});
});

const countByCategory = computed(() => {
	const map = { Tutte: faqs.length };
	for (const cat of categories) map[cat] = 0;
	for (const item of faqs) map[item.category] = (map[item.category] || 0) + 1;
	return map;
});

const totalFaqs = faqs.length;

function selectCategory(cat) {
	activeCategory.value = cat;
}

function resetFilters() {
	searchQuery.value = '';
	activeCategory.value = 'Tutte';
	searchRef.value?.focus();
}

function focusSearch() {
	searchRef.value?.focus();
}

useSeoMeta({
	title: 'FAQ — Domande frequenti',
	ogTitle: 'FAQ — Domande frequenti SpedizioneFacile',
	description:
		'Tutte le risposte su spedizioni, preventivi, pagamenti, tracking, reclami, account e profilo Pro. Cerca subito la tua domanda o contatta un operatore.',
	ogDescription:
		'Domande frequenti SpedizioneFacile: spedizione, preventivi, pagamenti, tracking, reclami, account e Pro. Cerca o parla con un operatore.',
});

useBreadcrumbSchema([
	{ name: 'Home', url: '/' },
	{ name: 'FAQ' },
]);

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
					acceptedAnswer: { '@type': 'Answer', text: item.answer },
				})),
			}),
		},
	],
});
</script>

<template>
	<div class="faq-shell">
		<a href="#faq-list" class="faq-skip-link">Vai alla lista delle domande</a>

		<PublicPageHeader
			eyebrow="Centro assistenza"
			title="Domande frequenti"
			:description="`${totalFaqs} risposte chiare su spedizioni, preventivi, pagamenti, tracking, reclami, account e profilo Pro. Cerca una parola chiave o esplora per categoria.`"
			:crumbs="[{ label: 'Home', to: '/' }, { label: 'FAQ' }]">
			<FAQSearch ref="searchRef" v-model="searchQuery" />
		</PublicPageHeader>

		<FAQCategoryChips
			:categories="categories"
			:active-category="activeCategory"
			:count-by-category="countByCategory"
			@select="selectCategory" />

		<section class="faq-body">
			<div class="my-container faq-body__grid">
				<FAQList
					:items="filteredFaqs"
					:search-query="searchQuery"
					:active-category="activeCategory"
					@reset="resetFilters" />
				<FAQSidebar @focus-search="focusSearch" />
			</div>
		</section>
	</div>
</template>

<style scoped>
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

.faq-body {
	display: flex;
	flex-direction: column;
	gap: 16px;
	min-width: 0;
}

.faq-body__grid {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

@media (min-width: 1024px) {
	.faq-body__grid {
		display: grid;
		grid-template-columns: 1fr 280px;
		gap: 24px;
	}
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
