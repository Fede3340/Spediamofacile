<script setup>
definePageMeta({ middleware: ["app-auth"] });

useSeoMeta({
	title: 'Le tue spedizioni',
	description: 'Consulta lo storico delle tue spedizioni, controlla lo stato degli ordini e gestisci le spedizioni configurate.',
	ogTitle: 'Le tue spedizioni',
	ogDescription: 'Consulta lo storico delle tue spedizioni, controlla lo stato degli ordini e gestisci le spedizioni configurate.',
	robots: 'noindex, nofollow',
});

const {
	filterPills, activeFilter, searchQuery, changeFilter,
	ordersStatus, filteredOrders,
	statusRaw, statusColor,
	formatPrice, getRouteLabel, getServiceLabel, getTrackingLabel, getOrderReferenceLabel,
	getSenderName, getRecipientName, getOrderSubtotalLabel, getOrderDateLabel, getOrderPackageLabel,
	isPendingPayment, getPendingReason, orderStats,
	showDetail, detailItem,
	saveError,
	// isAlreadySaved, saveToConfigured, savingToConfigured -- ARCHIVIATO 2026-04-20
} = useOrdersList();
</script>

<template>
	<section class="sf-account-shell min-h-[600px] py-[20px] tablet:py-[28px] desktop:py-[28px]">
		<div class="my-container max-w-[1280px]">
			<AccountPageHeader
				eyebrow="Storico"
				title="Le tue spedizioni"
				description="Storico, tracking e gestione degli ordini in un'unica vista pulita."
				back-to="/account"
				back-label="Torna al tuo account"
				current="Spedizioni">
				<template #meta>
					<div class="flex flex-wrap items-center gap-[8px]">
						<span class="sf-account-meta-pill"><strong class="font-[800]">{{ orderStats.total }}</strong>&nbsp;totali</span>
						<span class="sf-account-meta-pill sf-account-meta-pill--muted"><strong class="font-[700]">{{ orderStats.open }}</strong>&nbsp;aperte</span>
						<span class="sf-account-meta-pill sf-account-meta-pill--muted"><strong class="font-[700]">{{ orderStats.pending }}</strong>&nbsp;da seguire</span>
					</div>
				</template>
				<template #actions>
					<SfButton to="/preventivo">
						<svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
						Nuova spedizione
					</SfButton>
				</template>
			</AccountPageHeader>

			<div class="mb-[16px] sf-section-block sf-animate-in sf-animate-in-1">
				<div class="sf-section-block__body grid gap-[12px]">
					<div class="min-w-0">
						<p class="text-[0.72rem] font-[700] uppercase tracking-[0.08em] text-[var(--color-brand-text-muted)]">Ricerca &amp; filtri</p>
						<h2 class="mt-[2px] text-[0.95rem] font-[700] text-[var(--color-brand-text)]">Trova la spedizione giusta in un colpo d'occhio</h2>
					</div>
					<div class="relative">
						<svg
							aria-hidden="true"
							xmlns="http://www.w3.org/2000/svg"
							viewBox="0 0 24 24"
							class="absolute left-[14px] top-1/2 -translate-y-1/2 h-[18px] w-[18px] text-[var(--color-brand-text-secondary)]"
							fill="currentColor">
							<path d="M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3M9.5,5C7,5 5,7 5,9.5C5,12 7,14 9.5,14C12,14 14,12 14,9.5C14,7 12,5 9.5,5Z" />
						</svg>
						<input
							v-model="searchQuery"
							type="text"
							placeholder="Cerca riferimento, tracking, mittente o destinatario..."
							class="form-input pl-[42px]" />
					</div>
					<!-- Filtri come chip teal: stato non attivo = outline teal soft, attivo = pieno teal con badge bianco -->
					<div class="flex flex-wrap gap-[8px]" role="tablist" aria-label="Filtri spedizioni">
						<button
							v-for="filter in filterPills"
							:key="filter.id"
							@click="changeFilter(filter.id)"
							type="button"
							role="tab"
							:aria-selected="filter.id === activeFilter"
							:class="filter.id === activeFilter
								? 'bg-[var(--color-brand-primary,#095866)] text-white border-[var(--color-brand-primary,#095866)] shadow-[0_4px_12px_rgba(9,88,102,0.18)]'
								: 'bg-white text-[var(--color-brand-primary,#095866)] border-[rgba(9,88,102,0.22)] hover:border-[var(--color-brand-primary,#095866)] hover:bg-[#EEF7F8]'"
							class="inline-flex items-center gap-[8px] px-[14px] h-[36px] rounded-full border text-[13px] font-[700] cursor-pointer transition-all duration-[200ms] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-brand-primary,#095866)] focus-visible:ring-offset-2">
							<span>{{ filter.label }}</span>
							<span
								:class="filter.id === activeFilter
									? 'bg-white/20 text-white'
									: 'bg-[#EEF7F8] text-[var(--color-brand-primary,#095866)]'"
								class="rounded-full px-[7px] py-[1px] text-[11px] font-[700] tabular-nums">{{ filter.count }}</span>
						</button>
					</div>
				</div>
			</div>

			<!-- Loading: skeleton allineato alla struttura reale della card (header pill + route + 3 colonne mittente/destinatario/tracking) -->
			<div v-if="ordersStatus === 'pending'" class="space-y-[14px]" aria-busy="true">
				<div v-for="n in 3" :key="n" class="bg-white rounded-[16px] border border-[#E2E8EE] shadow-[0_3px_14px_rgba(15,23,42,0.04)] overflow-hidden">
					<div class="border-b border-[rgba(9,88,102,0.08)] bg-[linear-gradient(180deg,#FBFCFD_0%,#F8FAFB_100%)] px-[22px] py-[16px]">
						<SfSkeleton variant="text-block" />
					</div>
					<div class="px-[22px] py-[16px] grid grid-cols-1 tablet:grid-cols-3 gap-[20px]">
						<SfSkeleton variant="text-block" />
						<SfSkeleton variant="text-block" />
						<SfSkeleton variant="text-block" />
					</div>
				</div>
			</div>

			<!-- Orders list -->
			<div v-else-if="filteredOrders.length > 0" class="space-y-[16px] sf-animate-in sf-animate-in-2">
				<ShipmentOrderCard
					v-for="order in filteredOrders" :key="order.id"
					:order="order"
					:status-color="statusColor" :status-raw="statusRaw"
					:get-order-package-label="getOrderPackageLabel" :get-service-label="getServiceLabel"
					:get-tracking-label="getTrackingLabel" :get-order-reference-label="getOrderReferenceLabel"
					:get-order-date-label="getOrderDateLabel" :get-order-subtotal-label="getOrderSubtotalLabel"
					:get-route-label="getRouteLabel" :get-sender-name="getSenderName" :get-recipient-name="getRecipientName"
					:is-pending-payment="isPendingPayment" :get-pending-reason="getPendingReason"
					:save-error="saveError"
				/>
			</div>

			<!-- Empty state — pattern sf-empty-state condiviso sitewide -->
			<div v-else class="sf-empty-state" role="status">
				<div class="sf-empty-state__icon" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M21 16.5V7.5a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 7.5v9a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16.5Z"/>
						<polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
						<line x1="12" y1="22.08" x2="12" y2="12"/>
					</svg>
				</div>
				<h3 class="sf-empty-state__title">Nessuna spedizione trovata</h3>
				<p class="sf-empty-state__copy">Inizia la tua prima spedizione: ti bastano pochi passaggi per ottenere il preventivo e generare l'etichetta.</p>
				<div class="sf-empty-state__actions">
					<NuxtLink to="/preventivo" class="sf-empty-state__cta">
						<span>Crea nuova spedizione</span>
						<svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M5 12h14"/><path d="m13 5 7 7-7 7"/>
						</svg>
					</NuxtLink>
					<!-- -- ARCHIVIATO 2026-04-20: CTA "Vedi modelli salvati" -> /account/spedizioni-configurate (_archive/frontend-simplification-2026-04-20/features/spedizioni-configurate) -- -->
				</div>
			</div>
		</div>

		<!-- Detail popup -->
		<ShipmentDetailModal v-model:open="showDetail" :detail-item="detailItem" :format-price="formatPrice" />
	</section>
</template>

