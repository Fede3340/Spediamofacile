<script setup>
import { accountCardIcons } from '~/utils/account';
import '~/assets/css/components/sf-account-dashboard-client.css';

defineProps({
	customerOrdersLoading: { type: Boolean, default: false },
	highlightedCustomerOrders: { type: Array, default: () => [] },
	recentCompletedCustomerOrders: { type: Array, default: () => [] },
	personalHighlights: { type: Array, default: () => [] },
	// -- ARCHIVIATO 2026-04-20: bonusPage (_archive/frontend-simplification-2026-04-20/features/bonus-fedelta) --
	// bonusPage: { type: Object, default: null },
	isLoggingOut: { type: Boolean, default: false },
});

const emit = defineEmits(['logout']);
const handleLogout = () => emit('logout');

// resolveAccountPageUrl era usato dal bonus-cta (archiviato). Lasciato come utility
// eslint-disable-next-line @typescript-eslint/no-unused-vars
const resolveAccountPageUrl = (url = '') => {
	if (!url) return '/account';
	if (url.startsWith('/account')) return url;
	return `/account${url.startsWith('/') ? url : `/${url}`}`;
};
</script>

<template>
	<div class="sf-account-admin-stack">
		<AccountPageHeader
			class="sf-account-shell-hero--compact sf-account-root__header"
			:crumbs="[]"
			title="Il tuo account"
			description="Spedizioni attive, storico recente e nuova spedizione in una sola vista piu chiara.">

			<template #actions>
				<div class="flex flex-wrap justify-end gap-[10px]">
					<NuxtLink
						to="/preventivo"
						class="btn-primary btn-compact inline-flex min-w-[180px] items-center justify-center gap-[8px]">
						<svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<line x1="12" y1="5" x2="12" y2="19" />
							<line x1="5" y1="12" x2="19" y2="12" />
						</svg>
						Nuova spedizione
					</NuxtLink>

					<button
						type="button"
						@click="handleLogout"
						:disabled="isLoggingOut"
						class="btn-secondary btn-compact inline-flex min-w-[118px] items-center justify-center gap-[8px]">
						<svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
							<polyline points="16 17 21 12 16 7" />
							<line x1="21" y1="12" x2="9" y2="12" />
						</svg>
						{{ isLoggingOut ? 'Uscita...' : 'Esci' }}
					</button>
				</div>
			</template>
		</AccountPageHeader>

		<div class="sf-shell-card sf-account-root__orders-card sf-animate-in sf-animate-in-1">
			<div class="sf-account-root__orders-header">
				<div class="min-w-0">
					<p class="sf-section-kicker mb-[6px]">Spedizioni</p>
					<h2 class="sf-section-title">Spedizioni attive</h2>
					<p class="sf-section-description mt-[6px]">
						Tracking e riferimenti principali restano subito sopra il fold, come primo punto davvero utile della dashboard.
					</p>
				</div>

				<NuxtLink
					to="/account/spedizioni"
					class="sf-account-root__orders-link">
					Tutte le spedizioni
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M9 18l6-6-6-6" />
					</svg>
				</NuxtLink>
			</div>

			<div v-if="customerOrdersLoading" class="sf-account-root__orders-empty">
				<p class="text-[0.9rem] text-[var(--color-brand-text-secondary)]">
					Sto caricando le spedizioni del tuo account.
				</p>
			</div>

			<div v-else-if="highlightedCustomerOrders.length" class="sf-account-root__orders-list">
				<NuxtLink
					v-for="order in highlightedCustomerOrders"
					:key="order.id"
					:to="order.url"
					class="sf-account-root__order-item group">
					<div
						class="sf-account-root__order-icon"
						:style="{ background: order.tone.bg, color: order.tone.color }">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="h-[16px] w-[16px]" v-html="accountCardIcons['truck-fast']" />
					</div>

					<div class="min-w-0 flex-1">
						<p class="sf-account-root__order-title">{{ order.title }}</p>
						<p class="sf-account-root__order-meta">{{ order.meta }}</p>
					</div>

					<span
						class="sf-account-root__order-status"
						:style="{ background: order.tone.bg, color: order.tone.color }">
						{{ order.statusLabel }}
					</span>
				</NuxtLink>
			</div>

			<div v-else class="sf-account-root__orders-empty sf-account-root__orders-empty--hero">
				<div class="sf-account-root__orders-empty-icon">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="h-[18px] w-[18px]" v-html="accountCardIcons.package" />
				</div>
				<div class="min-w-0 flex-1">
					<p class="sf-account-root__orders-empty-meta">Nessuna spedizione recente. Quando ne crei una nuova, ritrovi qui tracking, stato e riferimenti principali.</p>
				</div>
			</div>
		</div>

		<div class="sf-account-summary-strip sf-animate-in sf-animate-in-2">
			<div
				v-for="item in personalHighlights"
				:key="item.label"
				class="sf-account-summary-item rounded-[16px] border border-[rgba(9,88,102,0.08)] bg-white"
				:style="{
					'--sf-hub-icon-bg': item.iconBg,
					'--sf-hub-icon-color': item.iconColor,
					boxShadow: 'var(--sf-shell-shadow)',
				}">
				<div class="sf-account-summary-item__icon" :style="{ background: item.iconBg, color: item.iconColor }">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="h-[16px] w-[16px]" v-html="accountCardIcons[item.iconKey]" />
				</div>
				<div class="sf-account-summary-item__body">
					<span class="sf-account-summary-item__label">{{ item.label }}</span>
					<span class="sf-account-summary-item__value">{{ item.value }}</span>
					<span class="sf-account-summary-item__meta">{{ item.meta }}</span>
				</div>
			</div>
		</div>

		<div class="sf-shell-card sf-account-root__history-card sf-animate-in sf-animate-in-3">
			<div class="sf-account-root__orders-header">
				<div class="min-w-0">
					<p class="sf-section-kicker mb-[6px]">Storico</p>
					<h2 class="sf-section-title">Ultime spedizioni</h2>
					<p class="sf-section-description mt-[6px]">
						Le ultime consegne restano compatte e facili da riaprire senza trasformare la root in un hub di navigazione.
					</p>
				</div>

				<NuxtLink
					to="/account/spedizioni"
					class="sf-account-root__orders-link">
					Vedi storico completo
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M9 18l6-6-6-6" />
					</svg>
				</NuxtLink>
			</div>

			<div v-if="recentCompletedCustomerOrders.length" class="sf-account-root__orders-list">
				<NuxtLink
					v-for="order in recentCompletedCustomerOrders"
					:key="order.id"
					:to="order.url"
					class="sf-account-root__order-item group">
					<div
						class="sf-account-root__order-icon"
						style="background:rgba(5,150,105,0.1); color:#047857;">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="h-[16px] w-[16px]" v-html="accountCardIcons['check-circle']" />
					</div>

					<div class="min-w-0 flex-1">
						<p class="sf-account-root__order-title">{{ order.title }}</p>
						<p class="sf-account-root__order-meta">{{ order.meta }}</p>
					</div>

					<span
						class="sf-account-root__order-status"
						style="background:rgba(5,150,105,0.1); color:#047857;">
						Consegnata
					</span>
				</NuxtLink>
			</div>

			<div v-else class="sf-account-root__orders-empty">
				<div
					class="sf-account-root__orders-empty-icon"
					style="background:rgba(5,150,105,0.1); color:#047857;">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="h-[18px] w-[18px]" v-html="accountCardIcons['history']" />
				</div>
				<div>
					<p class="sf-account-root__orders-empty-title">Nessuna consegna archiviata</p>
					<p class="sf-account-root__orders-empty-meta">Appena una spedizione si chiude, la ritrovi qui con riferimenti e data di creazione.</p>
				</div>
			</div>
		</div>

		<!-- -- ARCHIVIATO 2026-04-20: Bonus CTA (_archive/frontend-simplification-2026-04-20/features/bonus-fedelta) -- -->
	</div>
</template>

