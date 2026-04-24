<script setup>
definePageMeta({
	layout: false,
});

useSeoMeta({
	title: 'Preview Home Hero | SpediamoFacile',
	robots: 'noindex, nofollow',
});

const viewport = computed(() => (useRoute().query.viewport === 'mobile' ? 'mobile' : 'desktop'));
</script>

<template>
	<div class="preview-hero-shell" :class="viewport === 'mobile' ? 'is-mobile' : 'is-desktop'">
		<Header />
		<Preventivo />
	</div>
</template>

<style scoped>
.preview-hero-shell {
	background: #e3e3e3;
	width: 100%;
	height: 100%;
}

.preview-hero-shell.is-desktop {
	width: 1440px;
	max-width: 1440px;
	margin: 0 auto;
}

.preview-hero-shell.is-mobile {
	width: 390px;
	max-width: 390px;
	margin: 0 auto;
}
</style>
