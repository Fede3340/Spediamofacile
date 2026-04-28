<script setup>
defineProps({
	services: { type: Array, default: () => [] },
	getMeta: { type: Function, required: true },
	getHighlights: { type: Function, required: true },
	getVisualTone: { type: Function, required: true },
	getDescription: { type: Function, required: true },
});
</script>

<template>
	<section class="sv-cards">
		<div class="my-container">
			<div class="sv-cards__grid">
				<ServizioCard
					v-for="service in services"
					:key="service.slug"
					:service="service"
					:meta="getMeta(service)"
					:highlights="getHighlights(service)"
					:visual-tone="getVisualTone(service)"
					:description="getDescription(service)"
				/>
			</div>
		</div>
	</section>
</template>

<style scoped>
.sv-cards {
	padding: 22px 0 32px;
}

.sv-cards__grid {
	display: grid;
	grid-template-columns: 1fr;
	gap: 16px;
}

@media (max-width: 29.99rem) {
	.sv-cards { padding: 24px 0; }
}

@media (min-width: 30rem) {
	.sv-cards__grid { grid-template-columns: repeat(2, 1fr); }
}

@media (min-width: 64rem) {
	.sv-cards__grid { grid-template-columns: repeat(3, 1fr); gap: 24px; }
	.sv-cards { padding: 40px 0 44px; }
}
</style>
