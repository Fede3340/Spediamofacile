<!-- FILE: pages/account/amministrazione/spedizioni.vue -->
<script setup>
import '~/assets/css/components/sf-admin-spedizioni-page.css';

definePageMeta({
	middleware: ['app-auth', 'admin'],
});

useSeoMeta({
	title: 'Spedizioni admin | SpediamoFacile',
	ogTitle: 'Spedizioni admin | SpediamoFacile',
	description: 'Controlla tracking, etichette e stati delle spedizioni dal pannello admin SpediamoFacile.',
	ogDescription: 'Lista spedizioni BRT con tracking, etichette e gestione stati nel pannello admin SpediamoFacile.',
	robots: 'noindex, nofollow',
});

useHead({
	title: 'Spedizioni admin | SpediamoFacile',
});

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
	<section class="sf-account-shell min-h-[600px] py-[24px] tablet:py-[28px] desktop:py-[28px]">
		<div class="my-container">
			<AccountPageHeader
				eyebrow="Area amministrazione"
				title="Spedizioni"
				description="Tracking, etichette e stati in una vista allineata agli ordini."
				back-to="/account/amministrazione"
				back-label="Torna al pannello admin"
				:crumbs="[
					{ label: 'Account', to: '/account' },
					{ label: 'Amministrazione', to: '/account/amministrazione' },
					{ label: 'Spedizioni' },
				]" />

			<AdminOrdersViewTabs />

			<AdminActionBanner :message="actionMessage?.text || ''" :tone="actionMessage?.type || ''" />

			<!-- Toolbar: header + azioni + ricerca + pill filter -->
			<div class="admin-spedizioni-toolbar rounded-[16px] p-[16px] tablet:p-[18px] desktop:p-[20px] mt-[18px]">
				<div class="admin-spedizioni-toolbar__top">
					<div class="admin-spedizioni-toolbar__copy">
						<h2 class="admin-spedizioni-toolbar__title">Coda spedizioni BRT</h2>
						<p class="admin-spedizioni-toolbar__text">Filtra tracking, etichette e stati usando la stessa grammatica degli ordini.</p>
					</div>
					<div class="admin-spedizioni-toolbar__actions">
						<button
							type="button"
							@click="shipmentsPage = 1; fetchShipments();"
							class="btn-secondary btn-compact inline-flex items-center justify-center gap-[6px]">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[16px] w-[16px]" fill="currentColor">
								<path d="M12,6V9L16,5L12,1V4C7.58,4 4,7.58 4,12C4,13.57 4.46,15.03 5.24,16.26L6.7,14.8C6.25,13.96 6,13 6,12A6,6 0 0,1 12,6M18.76,7.74L17.3,9.2C17.75,10.04 18,11 18,12A6,6 0 0,1 12,18V15L8,19L12,23V20C16.42,20 20,16.42 20,12C20,10.43 19.54,8.97 18.76,7.74Z" />
							</svg>
							Aggiorna
						</button>
						<button
							type="button"
							@click="resetFilters"
							:disabled="!hasActiveFilters"
							class="btn-secondary btn-compact inline-flex items-center justify-center gap-[6px] disabled:cursor-not-allowed disabled:opacity-45">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[16px] w-[16px]" fill="currentColor">
								<path d="M13.39,8.23L15.5,10.34L20.43,5.41L19,4L15.5,7.5L14.8,6.8C13.76,5.76 12.33,5.11 10.76,5.02C7.06,4.8 4,7.84 4,11.54C4,13.37 4.73,15.03 5.91,16.22L4.5,17.63C2.95,16.08 2,13.93 2,11.54C2,6.56 6.11,2.5 11.09,2.54C13.42,2.56 15.53,3.5 17.06,5L20.41,1.65L21.82,3.06L16.89,7.99L19,10.1H13.39V8.23M10.61,15.77L8.5,13.66L3.57,18.59L5,20L8.5,16.5L9.2,17.2C10.24,18.24 11.67,18.89 13.24,18.98C16.94,19.2 20,16.16 20,12.46C20,10.63 19.27,8.97 18.09,7.78L19.5,6.37C21.05,7.92 22,10.07 22,12.46C22,17.44 17.89,21.5 12.91,21.46C10.58,21.44 8.47,20.5 6.94,19L3.59,22.35L2.18,20.94L7.11,16.01L5,13.9H10.61V15.77Z" />
							</svg>
							Reset
						</button>
					</div>
				</div>

				<div class="admin-spedizioni-toolbar__summary">
					<span class="admin-spedizioni-toolbar__pill">{{ visibleShipmentsCount }} visibili</span>
					<span class="admin-spedizioni-toolbar__pill admin-spedizioni-toolbar__pill--muted">{{ shipmentsData.total || visibleShipmentsCount }} totali</span>
				</div>

				<div class="admin-spedizioni-toolbar__filters">
					<label class="admin-spedizioni-toolbar__field">
						<span class="admin-spedizioni-toolbar__field-label">Ricerca</span>
						<div class="admin-spedizioni-toolbar__input-shell">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="admin-spedizioni-toolbar__field-icon" fill="currentColor">
								<path d="M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3M9.5,5C7,5 5,7 5,9.5C5,12 7,14 9.5,14C12,14 14,12 14,9.5C14,7 12,5 9.5,5Z" />
							</svg>
							<input
								v-model="shipmentsSearch"
								@input="onShipmentsSearch"
								type="text"
								placeholder="Cerca per utente, Parcel ID, tratta..."
								class="admin-spedizioni-toolbar__input" />
						</div>
					</label>
					<div class="admin-spedizioni-toolbar__field admin-spedizioni-toolbar__field--filters">
						<span class="admin-spedizioni-toolbar__field-label">Filtro rapido</span>
						<AdminFilterBar
							:filters="statusFilters"
							:active-filter="activeFilter"
							size="sm"
							@change="setActiveFilter" />
					</div>
				</div>
			</div>

			<!-- Tabella spedizioni -->
			<div class="admin-spedizioni-table-card mt-[18px] desktop:mt-[22px]">
				<div class="mb-[12px] flex flex-col gap-[6px] border-b border-[#EEF1F3] pb-[12px] tablet:flex-row tablet:items-end tablet:justify-between">
					<div>
						<h2 class="text-[16px] font-bold text-[#1d2738] font-['Montserrat',sans-serif]">Lista spedizioni BRT</h2>
						<p class="mt-[4px] text-[13px] text-[#5A6474] leading-[1.4]">Tracking, consegna e azioni con layout speculare agli ordini.</p>
					</div>
				</div>

				<div v-if="tabLoading" class="py-[24px] flex justify-center">
					<div class="w-[32px] h-[32px] border-3 border-[var(--color-brand-border)] border-t-[var(--color-brand-primary)] rounded-full animate-spin"></div>
				</div>

				<div v-else-if="fetchError" class="text-center py-[28px]">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[40px] h-[40px] text-red-300 mx-auto mb-[12px]" fill="currentColor"><path d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z"/></svg>
					<p class="text-[var(--color-brand-text-secondary)] mb-[12px]">Errore nel caricamento delle spedizioni.</p>
					<button @click="fetchShipments" class="px-[14px] py-[8px] bg-[var(--color-brand-primary)] text-white rounded-[50px] text-[13px] font-medium cursor-pointer hover:bg-[var(--color-brand-primary-hover)] transition-colors">Riprova</button>
				</div>

				<div v-else-if="!visibleShipments?.length" class="text-center py-[28px]">
					<div class="w-[64px] h-[64px] mx-auto mb-[16px] bg-[#F5F6F9] rounded-full flex items-center justify-center">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[28px] h-[28px]" fill="#C8CCD0">
							<path d="M3,4A2,2 0 0,0 1,6V17H3A3,3 0 0,0 6,20A3,3 0 0,0 9,17H15A3,3 0 0,0 18,20A3,3 0 0,0 21,17H23V12L20,8H17V4M17,9.5H19.5L21.47,12H17M6,15.5A1.5,1.5 0 0,1 7.5,17A1.5,1.5 0 0,1 6,18.5A1.5,1.5 0 0,1 4.5,17A1.5,1.5 0 0,1 6,15.5M18,15.5A1.5,1.5 0 0,1 19.5,17A1.5,1.5 0 0,1 18,18.5A1.5,1.5 0 0,1 16.5,17A1.5,1.5 0 0,1 18,15.5Z" />
						</svg>
					</div>
					<h2 class="text-[16px] font-bold text-[#1d2738] font-['Montserrat',sans-serif] mb-[8px]">Nessuna spedizione trovata</h2>
					<p class="text-[#5A6474] text-[14px]">Nessuna spedizione corrisponde ai filtri selezionati.</p>
				</div>

				<!-- Lista spedizioni: vista Flat con AdminTableLayout + AdminStatusBadge -->
				<AdminSpedizioniFlatView
					v-else
					:shipments="visibleShipments"
					:format-date="formatDate"
					:download-label="downloadLabel"
					:get-available-statuses="getAvailableStatuses"
					@change-status="changeOrderStatus" />

				<!-- Paginazione server-side (una sola pagina API per volta) -->
				<div v-if="shipmentsData.last_page > 1" class="mt-[16px] flex flex-col gap-[8px] rounded-[14px] border border-[#E6ECF0] bg-white px-[14px] py-[12px] shadow-[0_2px_8px_rgba(20,37,48,0.05)] tablet:flex-row tablet:items-center tablet:justify-between">
					<p class="text-[0.8125rem] font-medium text-[#5c6d7f]">{{ paginationLabel }}</p>
					<div class="flex items-center justify-between gap-[8px] tablet:justify-end">
						<button
							@click="shipmentsPage = Math.max(1, shipmentsPage - 1); fetchShipments();"
							:disabled="shipmentsPage <= 1"
							class="btn-tertiary px-[12px] py-[8px] text-[0.8125rem] disabled:opacity-40">
							Precedente
						</button>
						<button
							@click="shipmentsPage = Math.min(shipmentsData.last_page, shipmentsPage + 1); fetchShipments();"
							:disabled="shipmentsPage >= shipmentsData.last_page"
							class="btn-tertiary px-[12px] py-[8px] text-[0.8125rem] disabled:opacity-40">
							Successivo
						</button>
					</div>
				</div>
			</div>
		</div>
	</section>
</template>

