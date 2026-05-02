<script setup>
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

<style scoped>
.logo-mark {
	position: relative;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	flex: 0 0 auto;
	border-radius: 999px;
	background: var(--color-brand-accent);
	color: #ffffff;
}

.logo-mark--navbar,
.logo-mark--footer {
	width: 38px;
	height: 38px;
}

.logo-mark__glyph {
	font-size: 14px;
	line-height: 1;
	font-weight: 800;
	letter-spacing: -0.03em;
}

.logo-mark--with-divider::after,
.logo-mark--with-divider-light::after {
	content: "";
	position: absolute;
	right: -12px;
	top: 0;
	width: 2px;
	height: 100%;
}
.logo-mark--with-divider::after {
	background: #333333;
}
.logo-mark--with-divider-light::after {
	background: #ffffff;
}

@media (min-width: 640px) {
	.logo-mark--navbar,
	.logo-mark--footer {
		width: 50px;
		height: 50px;
	}
	.logo-mark__glyph { font-size: 18px; }
	.logo-mark--with-divider::after,
	.logo-mark--with-divider-light::after { right: -15px; }
}

@media (min-width: 1024px) {
	.logo-mark--navbar,
	.logo-mark--footer {
		width: 62px;
		height: 62px;
	}
	.logo-mark__glyph { font-size: 22px; }
	.logo-mark--with-divider::after,
	.logo-mark--with-divider-light::after {
		right: -19px;
		width: 4px;
	}
}
</style>

