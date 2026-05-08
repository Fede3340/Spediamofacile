<!-- FILE: pages/account/amministrazione/spedizioni.vue -->
<script setup>
definePageMeta({
	middleware: ['app-auth', 'admin'],
});

useSeoMeta({
	title: 'Spedizioni admin',
	ogTitle: 'Spedizioni admin',
	description: 'Controlla tracking, etichette e stati delle spedizioni dal pannello admin SpediamoFacile.',
	ogDescription: 'Lista spedizioni BRT con tracking, etichette e gestione stati nel pannello admin SpediamoFacile.',
	robots: 'noindex, nofollow',
});

useHead({ title: 'Spedizioni admin' });

const {
	shipmentsData, shipmentsPage, shipmentsSearch, activeFilter,
	tabLoading, fetchError,
	visibleShipments, visibleShipmentsCount, statusFilters,
	paginationLabel, hasActiveFilters,
	fetchShipments, onShipmentsSearch, changeOrderStatus,
	setActiveFilter, resetFilters, getAvailableStatuses,
	actionMessage, formatDate, downloadLabel,
} = useAdminSpedizioni();

onMounted(() => { fetchShipments(); });
</script>

<template>
	<AccountPageSection spacing="space-y-6 md:space-y-8">
		<AccountPageHeader
				eyebrow="Area amministrazione"
				title="Coda BRT"
				description="Tracking, etichette e stati delle spedizioni in lavorazione."
				back-to="/account/amministrazione"
				back-label="Torna al pannello admin"
				:crumbs="[
					{ label: 'Account', to: '/account' },
					{ label: 'Amministrazione', to: '/account/amministrazione' },
					{ label: 'Coda BRT' },
				]" />

			<AdminOrdersViewTabs />

			<SfActionBanner :message="actionMessage" />

			<SfCard padding="md">
				<div class="flex flex-col tablet:flex-row tablet:items-start tablet:justify-between gap-4 mb-4">
					<div class="space-y-1 min-w-0">
						<h2 class="text-lg font-bold text-brand-text">Coda spedizioni BRT</h2>
						<p class="text-sm text-brand-text-secondary">Filtra tracking, etichette e stati usando la stessa grammatica degli ordini.</p>
					</div>
					<div class="flex items-center gap-2 shrink-0">
						<SfButton variant="secondary" size="sm" @click="shipmentsPage = 1; fetchShipments();">
							<template #leading><UIcon name="mdi:refresh" class="w-4 h-4" /></template>
							Aggiorna
						</SfButton>
						<SfButton variant="secondary" size="sm" :disabled="!hasActiveFilters" @click="resetFilters">
							<template #leading><UIcon name="mdi:close" class="w-4 h-4" /></template>
							Reset
						</SfButton>
					</div>
				</div>

				<div class="flex flex-wrap items-center gap-2 mb-4">
					<span class="inline-flex items-center px-3 py-1 rounded-pill bg-brand-soft-bg text-brand-primary text-xs font-semibold border border-brand-soft-border">{{ visibleShipmentsCount }} visibili</span>
					<span class="inline-flex items-center px-3 py-1 rounded-pill bg-brand-bg-alt text-brand-text-secondary text-xs font-semibold border border-brand-border">{{ shipmentsData.total || visibleShipmentsCount }} totali</span>
				</div>

				<div class="flex flex-col tablet:flex-row gap-3">
					<SfFormGroup label="Ricerca" class="flex-1 min-w-0">
						<SfInput
							v-model="shipmentsSearch"
							type="search"
							placeholder="Cerca per utente, Parcel ID, tratta..."
							leading-icon="mdi:magnify"
							@update:model-value="onShipmentsSearch" />
					</SfFormGroup>
					<SfFormGroup label="Filtro rapido" class="flex-1 min-w-0">
						<AdminFilterBar
							:filters="statusFilters"
							:active-filter="activeFilter"
							size="sm"
							@change="setActiveFilter" />
					</SfFormGroup>
				</div>
			</SfCard>

			<div>
				<div v-if="tabLoading" class="py-6 flex justify-center">
					<UIcon name="mdi:loading" class="w-8 h-8 text-brand-primary animate-spin" />
				</div>

				<div v-else-if="fetchError" class="text-center py-7">
					<UIcon name="mdi:alert-circle" class="w-10 h-10 text-red-300 mx-auto mb-3" />
					<p class="text-brand-text-secondary mb-3">Errore nel caricamento delle spedizioni.</p>
					<SfButton size="sm" @click="fetchShipments">Riprova</SfButton>
				</div>

				<SfEmptyState
					v-else-if="!visibleShipments?.length"
					icon="mdi:truck-outline"
					title="Nessuna spedizione trovata"
					description="Nessuna spedizione corrisponde ai filtri selezionati." />

				<AdminSpedizioniFlatView
					v-else
					:shipments="visibleShipments"
					:format-date="formatDate"
					:download-label="downloadLabel"
					:get-available-statuses="getAvailableStatuses"
					@change-status="changeOrderStatus" />

				<div v-if="shipmentsData.last_page > 1" class="mt-4 flex flex-col gap-2 rounded-card border border-brand-border bg-brand-card px-3.5 py-3 shadow-sf-sm tablet:flex-row tablet:items-center tablet:justify-between">
					<p class="text-sm font-medium text-brand-text-secondary">{{ paginationLabel }}</p>
					<div class="flex items-center justify-between gap-2 tablet:justify-end">
						<SfButton
							variant="secondary"
							size="sm"
							:disabled="shipmentsPage <= 1"
							@click="shipmentsPage = Math.max(1, shipmentsPage - 1); fetchShipments();">
							Precedente
						</SfButton>
						<SfButton
							variant="secondary"
							size="sm"
							:disabled="shipmentsPage >= shipmentsData.last_page"
							@click="shipmentsPage = Math.min(shipmentsData.last_page, shipmentsPage + 1); fetchShipments();">
							Successivo
						</SfButton>
					</div>
				</div>
			</div>
	</AccountPageSection>
</template>
