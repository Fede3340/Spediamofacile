<script setup>
import '~/assets/css/content.css';

const props = defineProps({
	items: { type: Array, default: () => [] },
});

const emit = defineEmits(['toggle']);
const toggle = (idx) => emit('toggle', idx);
</script>

<template>
	<section class="sv-faq">
		<div class="my-container">
			<header class="sv-faq__head">
				<span class="sv-faq__kicker">FAQ</span>
				<span class="sv-faq__accent-bar" aria-hidden="true" />
				<h2 class="sv-faq__heading">Domande frequenti sui servizi</h2>
			</header>
			<div class="sv-faq__list">
				<div
					v-for="(item, idx) in items"
					:key="'faq'+idx"
					class="sv-faq__item"
					:class="{ 'sv-faq__item--open': item.open }"
				>
					<button
						class="sv-faq__q"
						:aria-expanded="item.open"
						@click="toggle(idx)"
					>
						<span>{{ item.question }}</span>
						<span class="sv-faq__icon-shell" aria-hidden="true">
							<svg class="sv-faq__icon sv-faq__icon--plus" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
							<svg class="sv-faq__icon sv-faq__icon--minus" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/></svg>
						</span>
					</button>
					<div v-show="item.open" class="sv-faq__a">
						<p>{{ item.answer }}</p>
					</div>
				</div>
			</div>
		</div>
	</section>
</template>
