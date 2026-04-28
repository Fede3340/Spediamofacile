<script setup>
const { isAccountRoute, isAuthMinimalShellRoute, isAuthPageRoute, isHomepageLikeRoute, isPreventivoRoute, isStandaloneMarketingHeroRoute } =
	useShellRouteState();

const headerSticky = ref(false);

// Debounce scroll via rAF per evitare layout thrash e CLS sul toggle sticky.
let scrollScheduled = false;
const onScroll = () => {
	if (scrollScheduled) return;
	scrollScheduled = true;
	requestAnimationFrame(() => {
		const newSticky = window.scrollY > 60;
		if (newSticky !== headerSticky.value) headerSticky.value = newSticky;
		scrollScheduled = false;
	});
};

onMounted(() => {
	onScroll();
	window.addEventListener('scroll', onScroll, { passive: true });
});

onUnmounted(() => {
	window.removeEventListener('scroll', onScroll);
});
</script>

<template>
	<header
		class="sticky top-0 z-40 transition-all duration-[400ms]"
		:class="headerSticky
			? 'header--sticky'
			: 'bg-[#F8F9FB]'"
	>
		<!-- Top accent line -->
		<div
			v-if="!isAuthMinimalShellRoute"
			class="header-accent-line"
		></div>

		<div class="max-w-[1280px] mx-auto px-[14px] sm:px-[40px]">
			<Navbar />
		</div>

	</header>

	<!-- ContenutoHeader below navbar when on homepage (OUTSIDE sticky header) -->
	<div v-if="isHomepageLikeRoute && !isAccountRoute && !isAuthPageRoute" class="w-full bg-[#F8F9FB]">
		<div class="max-w-[1280px] mx-auto px-[14px] sm:px-[40px]">
			<ContenutoHeader />
		</div>
	</div>
</template>

<style scoped>
.header--sticky {
	background: rgba(255, 255, 255, 0.9);
	backdrop-filter: blur(12px);
	-webkit-backdrop-filter: blur(12px);
	box-shadow: 0 1px 8px rgba(0, 0, 0, 0.05);
}

.header-accent-line {
	height: 3px;
	width: 100%;
	background: linear-gradient(90deg, var(--color-brand-accent) 0%, #ff6a33 50%, var(--color-brand-accent) 100%);
}
</style>
