<script setup>
useHead({
	bodyAttrs: {
		class: 'auth-layout-body',
	},
});
</script>

<template>
	<div class="min-h-screen bg-[#F7F8FA]">
		<a
			href="#main-content"
			class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-[100] focus:bg-white focus:px-4 focus:py-2 focus:rounded-[18px] focus:shadow-lg focus:text-[var(--color-brand-primary)] focus:font-semibold">
			Vai al contenuto
		</a>
		<header class="border-b border-[var(--color-brand-border)] bg-white">
			<div class="my-container flex items-center py-[14px] tablet:py-[18px]">
				<NuxtLink to="/" class="inline-flex items-center">
					<Logo :is-navbar="true" />
				</NuxtLink>
			</div>
		</header>

		<main id="main-content" class="min-h-[calc(100vh-88px)]">
			<slot />
		</main>
	</div>
</template>
