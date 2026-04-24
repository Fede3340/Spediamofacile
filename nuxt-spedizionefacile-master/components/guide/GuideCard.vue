<script setup>
import '~/assets/css/components/sf-guide-card.css';

defineProps({
	guide: { type: Object, required: true },
	image: { type: String, required: true },
	category: { type: String, required: true },
	categoryColor: { type: Object, required: true },
	readTime: { type: String, required: true },
	description: { type: String, default: '' },
	featured: { type: Boolean, default: false },
	applyFallback: { type: Function, required: true },
});
</script>

<template>
	<NuxtLink
		v-if="featured"
		:to="`/guide/${guide.slug}`"
		class="guide-featured"
	>
		<div class="guide-featured__image-wrap">
			<img
				:src="image"
				:alt="guide.title"
				class="guide-featured__image"
				loading="eager"
				width="720"
				height="480"
				@error="applyFallback"
			/>
			<div class="guide-card__overlay"></div>
			<span
				class="guide-card__badge"
				:style="{ background: categoryColor.bg, color: categoryColor.text }"
			>
				{{ category }}
			</span>
		</div>
		<div class="guide-featured__body">
			<span class="guide-featured__label">In evidenza</span>
			<h2 class="guide-featured__title font-montserrat">
				{{ guide.title }}
			</h2>
			<p class="guide-featured__desc">{{ description }}</p>
			<div class="guide-featured__footer">
				<span class="guide-card__time guide-card__time--inline">
					<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
						<path d="M12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22C6.47,22 2,17.5 2,12A10,10 0 0,1 12,2M12.5,7V12.25L17,14.92L16.25,16.15L11,13V7H12.5Z" />
					</svg>
					{{ readTime }}
				</span>
				<span class="guide-card__link">
					Leggi guida
					<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<polyline points="9 18 15 12 9 6" />
					</svg>
				</span>
			</div>
		</div>
	</NuxtLink>

	<NuxtLink
		v-else
		:to="`/guide/${guide.slug}`"
		class="guide-card"
	>
		<div class="guide-card__image-wrap">
			<img
				:src="image"
				:alt="guide.title"
				class="guide-card__image"
				loading="lazy"
				width="400"
				height="240"
				@error="applyFallback"
			/>
			<div class="guide-card__overlay"></div>
			<span
				class="guide-card__badge"
				:style="{ background: categoryColor.bg, color: categoryColor.text }"
			>
				{{ category }}
			</span>
		</div>

		<div class="guide-card__body">
			<h3 class="guide-card__title font-montserrat">{{ guide.title }}</h3>
			<p class="guide-card__desc">{{ description }}</p>
			<div class="guide-card__footer">
				<span class="guide-card__time guide-card__time--inline">
					<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="currentColor">
						<path d="M12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22C6.47,22 2,17.5 2,12A10,10 0 0,1 12,2M12.5,7V12.25L17,14.92L16.25,16.15L11,13V7H12.5Z" />
					</svg>
					{{ readTime }}
				</span>
				<span class="guide-card__link">
					Leggi guida
					<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<polyline points="9 18 15 12 9 6" />
					</svg>
				</span>
			</div>
		</div>
	</NuxtLink>
</template>
