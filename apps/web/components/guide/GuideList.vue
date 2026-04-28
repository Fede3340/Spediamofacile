<script setup>
import '~/assets/css/content.css';

defineProps({
	featuredGuide: { type: Object, default: null },
	remainingGuides: { type: Array, default: () => [] },
	filteredGuidesLength: { type: Number, default: 0 },
	getImage: { type: Function, required: true },
	getCategory: { type: Function, required: true },
	getTime: { type: Function, required: true },
	getDescription: { type: Function, required: true },
	getCategoryColor: { type: Function, required: true },
	applyFallback: { type: Function, required: true },
});

defineEmits(['reset-filters']);
</script>

<template>
	<section class="guide-content">
		<div class="my-container">

			<GuideCard
				v-if="featuredGuide"
				:guide="featuredGuide"
				:image="getImage(featuredGuide)"
				:category="getCategory(featuredGuide)"
				:category-color="getCategoryColor(featuredGuide)"
				:read-time="getTime(featuredGuide)"
				:description="getDescription(featuredGuide)"
				:apply-fallback="applyFallback"
				featured
			/>

			<div v-if="remainingGuides.length" class="guide-grid">
				<GuideCard
					v-for="guide in remainingGuides"
					:key="guide.slug"
					:guide="guide"
					:image="getImage(guide)"
					:category="getCategory(guide)"
					:category-color="getCategoryColor(guide)"
					:read-time="getTime(guide)"
					:description="getDescription(guide)"
					:apply-fallback="applyFallback"
				/>
			</div>

			<div v-if="!filteredGuidesLength" class="guide-empty">
				<div class="guide-empty__icon">
					<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<circle cx="11" cy="11" r="8" /><line x1="21" y1="21" x2="16.65" y2="16.65" />
					</svg>
				</div>
				<h2 class="guide-empty__title font-montserrat">Nessun risultato</h2>
				<p class="guide-empty__desc">
					Prova a cercare con parole diverse o seleziona un'altra categoria.
				</p>
				<button class="guide-empty__reset" @click="$emit('reset-filters')">
					Mostra tutte le guide
				</button>
			</div>

		</div>
	</section>
</template>

