<script setup>
import '~/assets/css/layout.css';

const props = defineProps({
	isNavbar: Boolean,
});

const isNavbarLogo = computed(() => Boolean(props.isNavbar));
const { isAuthMinimalShellRoute } = useShellRouteState();
const showNavbarDivider = computed(() => isNavbarLogo.value && !isAuthMinimalShellRoute.value);
const showDivider = computed(() => showNavbarDivider.value || !isNavbarLogo.value);
</script>

<template>
	<div
		class="logo-mark"
		:class="[
			isNavbarLogo ? 'logo-mark--navbar' : 'logo-mark--footer',
			showDivider ? (isNavbarLogo ? 'logo-mark--with-divider' : 'logo-mark--with-divider-light') : ''
		]">
		<span class="logo-mark__glyph">SF</span>
	</div>
	<template v-if="isNavbarLogo">
		<span
			:class="[
				showNavbarDivider ? 'desktop:ml-[31px] ml-[10px] tablet:ml-[26px]' : 'desktop:ml-[14px] ml-[8px] tablet:ml-[12px]',
				'text-[var(--color-brand-text)] font-semibold tracking-[-0.03em]',
			]"
		>
			<!-- Brand intero su tutti i breakpoint — mobile usa font-size più piccolo invece di troncare. -->
			<span class="text-[0.75rem] tablet:text-[1rem] desktop:text-[1.125rem] desktop-xl:text-[1.2rem]">SpediamoFacile</span>
		</span>
	</template>
	<span
		v-else
		class="ml-[18px] sm:ml-[26px] lg:ml-[31px] text-[0.8125rem] sm:text-[1rem] lg:text-[1.125rem] font-semibold tracking-[-0.03em] !text-white"
	>
		SpediamoFacile
	</span>
</template>

