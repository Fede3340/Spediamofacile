<script setup>
defineProps({
	categories: { type: Array, default: () => [] },
	activeCategory: { type: String, default: 'Tutte' },
	countByCategory: { type: Object, default: () => ({}) },
});
const emit = defineEmits(['select']);
</script>

<template>
	<section class="faq-chips" aria-label="Filtra per categoria">
		<div class="my-container">
			<div class="faq-chips__row" role="tablist">
				<button
					type="button"
					class="faq-chip"
					:class="{ 'is-active': activeCategory === 'Tutte' }"
					role="tab"
					:aria-selected="activeCategory === 'Tutte'"
					@click="emit('select', 'Tutte')">
					<span>Tutte</span>
					<span class="faq-chip__count">{{ countByCategory['Tutte'] }}</span>
				</button>
				<button
					v-for="cat in categories"
					:key="cat"
					type="button"
					class="faq-chip"
					:class="{ 'is-active': activeCategory === cat }"
					role="tab"
					:aria-selected="activeCategory === cat"
					@click="emit('select', cat)">
					<span>{{ cat }}</span>
					<span class="faq-chip__count">{{ countByCategory[cat] }}</span>
				</button>
			</div>
		</div>
	</section>
</template>

<style scoped>
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
</style>
