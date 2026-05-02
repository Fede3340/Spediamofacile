<!-- Tabs condivisi tra le 2 viste admin della stessa tabella orders:
     - /ordini : ordini pre-label (lifecycle operativo)
     - /spedizioni : post-label (tracking BRT)
-->
<script setup>
const route = useRoute();
const isOrdini = computed(() => route.path === '/account/amministrazione/ordini');
const isSpedizioni = computed(() => route.path === '/account/amministrazione/spedizioni');
</script>

<template>
	<div
		class="flex gap-2 mb-4 p-1.5 bg-brand-bg-alt rounded-card border border-brand-border"
		role="tablist"
		aria-label="Viste gestione ordini">
		<NuxtLink
			to="/account/amministrazione/ordini"
			role="tab"
			:aria-selected="isOrdini"
			:class="[
				'flex-1 inline-flex items-center justify-center gap-2 px-3.5 py-2 rounded-control text-sm font-semibold transition no-underline',
				isOrdini
					? 'bg-brand-card text-brand-primary shadow-sf-sm'
					: 'text-brand-text-secondary hover:bg-brand-card hover:text-brand-primary',
			]">
			<UIcon name="mdi:clipboard-text" class="w-[18px] h-[18px]" />
			<span>Ordini</span>
		</NuxtLink>
		<NuxtLink
			to="/account/amministrazione/spedizioni"
			role="tab"
			:aria-selected="isSpedizioni"
			:class="[
				'flex-1 inline-flex items-center justify-center gap-2 px-3.5 py-2 rounded-control text-sm font-semibold transition no-underline',
				isSpedizioni
					? 'bg-brand-card text-brand-primary shadow-sf-sm'
					: 'text-brand-text-secondary hover:bg-brand-card hover:text-brand-primary',
			]">
			<UIcon name="mdi:truck-fast" class="w-[18px] h-[18px]" />
			<span>Tracking BRT</span>
		</NuxtLink>
	</div>
</template>
