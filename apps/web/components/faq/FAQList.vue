<script setup>
defineProps({
	items: { type: Array, default: () => [] },
	searchQuery: { type: String, default: '' },
	activeCategory: { type: String, default: 'Tutte' },
});
const emit = defineEmits(['reset']);
</script>

<template>
	<div id="faq-list" class="faq-body__main">
		<header class="faq-body__head">
			<h2 class="faq-body__title">
				<template v-if="activeCategory === 'Tutte'">Tutte le domande</template>
				<template v-else>Categoria: {{ activeCategory }}</template>
			</h2>
			<p class="faq-body__count" aria-live="polite">
				{{ items.length }} risultat{{ items.length === 1 ? 'o' : 'i' }}
				<template v-if="searchQuery"> per "{{ searchQuery }}"</template>
			</p>
		</header>

		<FAQEmpty v-if="items.length === 0" @reset="emit('reset')" />

		<ul v-else class="faq-list" role="list">
			<li v-for="(item, index) in items" :key="item.id" class="faq-item">
				<FAQItem
					:item="item"
					:search-query="searchQuery"
					:open="index === 0 && !!searchQuery" />
			</li>
		</ul>

		<aside class="faq-bottom-cta" aria-label="Hai bisogno di altro aiuto?">
			<div class="faq-bottom-cta__copy">
				<h3 class="faq-bottom-cta__title">Non hai trovato risposta?</h3>
				<p class="faq-bottom-cta__text">
					Scrivici o accedi al supporto: rispondiamo in giornata, in italiano,
					da persone vere.
				</p>
			</div>
			<div class="faq-bottom-cta__actions">
				<SfButton to="/contatti">Vai ai contatti</SfButton>
				<SfButton variant="secondary" to="/account/supporto">Apri ticket supporto</SfButton>
			</div>
		</aside>
	</div>
</template>

<style scoped>
.faq-body__main {
	display: flex;
	flex-direction: column;
	gap: 8px;
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

.faq-bottom-cta {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 16px;
	margin-top: 24px;
	padding: 32px 24px;
	background: linear-gradient(135deg, rgba(9, 88, 102, 0.04), rgba(228, 66, 3, 0.04));
	border-radius: 18px;
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
</style>
