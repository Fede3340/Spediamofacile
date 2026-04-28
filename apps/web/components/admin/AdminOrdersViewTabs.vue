<!-- Tabs condivisi tra le 2 viste admin della stessa tabella orders:
     - /ordini : ordini pre-label (lifecycle operativo)
     - /spedizioni : post-label (tracking BRT)
     Rende visivamente chiaro che sono sorelle della stessa entita'.
-->
<script setup>
const route = useRoute();
const isOrdini = computed(() => route.path === '/account/amministrazione/ordini');
const isSpedizioni = computed(() => route.path === '/account/amministrazione/spedizioni');
</script>

<template>
	<!-- Tabs compatte: solo icona+label, hint "Lifecycle operativo/Post-label" rimossi (P14 - decorativi). -->
	<div class="admin-orders-tabs" role="tablist" aria-label="Viste gestione ordini">
		<NuxtLink
			to="/account/amministrazione/ordini"
			role="tab"
			:aria-selected="isOrdini"
			class="admin-orders-tabs__item"
			:class="{ 'admin-orders-tabs__item--active': isOrdini }">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="admin-orders-tabs__icon" fill="currentColor">
				<path d="M17,17H7V15H17M17,13H7V11H17M14,9H7V7H14M12,3A1,1 0 0,1 13,4A1,1 0 0,1 12,5A1,1 0 0,1 11,4A1,1 0 0,1 12,3M19,3H14.82C14.4,1.84 13.3,1 12,1C10.7,1 9.6,1.84 9.18,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5A2,2 0 0,0 19,3Z" />
			</svg>
			<span>Ordini</span>
		</NuxtLink>
		<NuxtLink
			to="/account/amministrazione/spedizioni"
			role="tab"
			:aria-selected="isSpedizioni"
			class="admin-orders-tabs__item"
			:class="{ 'admin-orders-tabs__item--active': isSpedizioni }">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="admin-orders-tabs__icon" fill="currentColor">
				<path d="M3,4A2,2 0 0,0 1,6V17H3A3,3 0 0,0 6,20A3,3 0 0,0 9,17H15A3,3 0 0,0 18,20A3,3 0 0,0 21,17H23V12L20,8H17V4M17,9.5H19.5L21.47,12H17M6,15.5A1.5,1.5 0 0,1 7.5,17A1.5,1.5 0 0,1 6,18.5A1.5,1.5 0 0,1 4.5,17A1.5,1.5 0 0,1 6,15.5M18,15.5A1.5,1.5 0 0,1 19.5,17A1.5,1.5 0 0,1 18,18.5A1.5,1.5 0 0,1 16.5,17A1.5,1.5 0 0,1 18,15.5Z" />
			</svg>
			<span>Tracking BRT</span>
		</NuxtLink>
	</div>
</template>

<style scoped>
.admin-orders-tabs {
	display: flex;
	gap: 8px;
	margin-bottom: 18px;
	padding: 6px;
	background: #f5f6f9;
	border-radius: 14px;
	border: 1px solid #e6ecf0;
}

.admin-orders-tabs__item {
	flex: 1;
	display: flex;
	flex-direction: row;
	align-items: center;
	gap: 8px;
	padding: 8px 14px;
	border-radius: 10px;
	color: #5c6d7f;
	text-decoration: none;
	font-size: 0.875rem;
	font-weight: 600;
	transition: all 0.15s ease;
	position: relative;
}

.admin-orders-tabs__item:hover {
	background: #ffffff;
	color: var(--color-brand-primary, #095866);
}

.admin-orders-tabs__item--active {
	background: #ffffff;
	color: var(--color-brand-primary, #095866);
	box-shadow: 0 1px 3px rgba(9, 88, 102, 0.08);
}

.admin-orders-tabs__icon {
	width: 18px;
	height: 18px;
	display: inline-block;
	margin-right: 6px;
}

.admin-orders-tabs__item span {
	display: inline-flex;
	align-items: center;
	font-size: 0.9375rem;
}

.admin-orders-tabs__hint {
	font-size: 0.75rem;
	font-weight: 500;
	color: #8c959e;
	margin-left: 24px;
	margin-top: 2px;
}

.admin-orders-tabs__item--active .admin-orders-tabs__hint {
	color: #5c6d7f;
}

@media (max-width: 640px) {
	.admin-orders-tabs__item {
		padding: 8px 10px;
	}
	.admin-orders-tabs__hint {
		display: none;
	}
}
</style>
