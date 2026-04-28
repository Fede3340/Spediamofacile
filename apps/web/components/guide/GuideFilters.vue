<script setup>
import '~/assets/css/content.css';

defineProps({
	searchQuery: { type: String, default: '' },
	activeCategory: { type: String, default: 'Tutte' },
	allCategories: { type: Array, default: () => [] },
});

defineEmits(['update:searchQuery', 'update:activeCategory']);
</script>

<template>
	<section class="guide-toolbar">
		<div class="my-container">
			<div class="guide-toolbar__surface">
				<div class="guide-search-wrapper">
					<div class="guide-search-bar">
						<svg class="guide-search-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<circle cx="11" cy="11" r="8" />
							<line x1="21" y1="21" x2="16.65" y2="16.65" />
						</svg>
						<input
							:value="searchQuery"
							type="text"
							placeholder="Cerca nelle guide..."
							class="guide-search-input"
							@input="$emit('update:searchQuery', $event.target.value)"
						/>
						<button
							v-if="searchQuery"
							class="guide-search-clear"
							aria-label="Cancella ricerca"
							@click="$emit('update:searchQuery', '')"
						>
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />
							</svg>
						</button>
					</div>
				</div>

				<div class="guide-pills-wrapper">
					<div class="guide-pills">
						<button
							v-for="cat in allCategories"
							:key="cat"
							class="guide-pill"
							:class="{ 'guide-pill--active': activeCategory === cat }"
							@click="$emit('update:activeCategory', cat)"
						>
							{{ cat }}
						</button>
					</div>
				</div>
			</div>
		</div>
	</section>
</template>
