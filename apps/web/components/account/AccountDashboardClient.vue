<script setup>
import { accountCardIcons } from '~/utils/account';

defineProps({
	customerOrdersLoading: { type: Boolean, default: false },
	highlightedCustomerOrders: { type: Array, default: () => [] },
	recentCompletedCustomerOrders: { type: Array, default: () => [] },
	personalHighlights: { type: Array, default: () => [] },
	isLoggingOut: { type: Boolean, default: false },
});

const emit = defineEmits(['logout']);
const handleLogout = () => emit('logout');
</script>

<template>
<!-- eslint-disable vue/no-v-html -- icone SVG da dictionary accountCardIcons (no input utente) -->
	<div class="flex flex-col gap-6">
		<AccountPageHeader
			:crumbs="[]"
			title="Il tuo account"
			description="Spedizioni attive, storico recente e nuova spedizione in una sola vista più chiara.">

			<template #actions>
				<div class="flex flex-wrap justify-end gap-2.5">
					<NuxtLink
						to="/preventivo"
						class="btn-primary btn-compact inline-flex min-w-[180px] items-center justify-center gap-2">
						<svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<line x1="12" y1="5" x2="12" y2="19" />
							<line x1="5" y1="12" x2="19" y2="12" />
						</svg>
						Nuova spedizione
					</NuxtLink>

					<SfButton variant="secondary" size="sm" :loading="isLoggingOut" loading-text="Uscita..." @click="handleLogout">
						<template #leading>
							<svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
								<polyline points="16 17 21 12 16 7" />
								<line x1="21" y1="12" x2="9" y2="12" />
							</svg>
						</template>
						Esci
					</SfButton>
				</div>
			</template>
		</AccountPageHeader>

		<div class="sf-animate-in sf-animate-in-1 rounded-card border border-brand-border bg-brand-card p-5 shadow-sf md:p-[22px]">
			<div class="mb-4 flex flex-wrap items-start justify-between gap-4">
				<div class="min-w-0">
					<p class="sf-section-kicker mb-1.5">Spedizioni</p>
					<h2 class="sf-section-title">Spedizioni attive</h2>
					<p class="sf-section-description mt-1.5">
						Tracking e riferimenti principali restano subito sopra il fold, come primo punto davvero utile della dashboard.
					</p>
				</div>

				<NuxtLink
					to="/account/spedizioni"
					class="inline-flex items-center gap-1.5 text-[0.84rem] font-bold text-brand-primary transition-all hover:gap-2">
					Tutte le spedizioni
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M9 18l6-6-6-6" />
					</svg>
				</NuxtLink>
			</div>

			<div v-if="customerOrdersLoading" class="flex items-center gap-3.5 rounded-card border border-dashed border-brand-primary/15 bg-brand-bg-alt/90 p-4.5">
				<p class="text-[0.9rem] text-brand-text-secondary">
					Sto caricando le spedizioni del tuo account.
				</p>
			</div>

			<div v-else-if="highlightedCustomerOrders.length" class="grid gap-2.5">
				<NuxtLink
					v-for="order in highlightedCustomerOrders"
					:key="order.id"
					:to="order.url"
					class="group flex items-center gap-3.5 rounded-card border border-brand-primary/10 bg-brand-bg-alt/90 px-[18px] py-4 transition-all hover:-translate-y-px hover:border-brand-primary/15 hover:shadow-sf-sm">
					<div
						class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-control"
						:style="{ background: order.tone.bg, color: order.tone.color }">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="h-4 w-4" v-html="accountCardIcons['truck-fast']" />
					</div>

					<div class="min-w-0 flex-1">
						<p class="text-[0.95rem] font-extrabold leading-tight text-brand-text">{{ order.title }}</p>
						<p class="mt-1 text-[0.83rem] leading-relaxed text-brand-text-secondary">{{ order.meta }}</p>
					</div>

					<span
						class="inline-flex shrink-0 items-center justify-center rounded-full px-2.5 py-1.5 text-[0.72rem] font-bold leading-none"
						:style="{ background: order.tone.bg, color: order.tone.color }">
						{{ order.statusLabel }}
					</span>
				</NuxtLink>
			</div>

			<div v-else class="flex items-center justify-between gap-3.5 rounded-card border border-dashed border-brand-primary/15 bg-brand-bg-alt/90 p-4.5">
				<div class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-control bg-brand-primary/[0.08] text-brand-primary">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="h-[18px] w-[18px]" v-html="accountCardIcons.package" />
				</div>
				<div class="min-w-0 flex-1">
					<p class="mt-1 text-[0.83rem] leading-relaxed text-brand-text-secondary">Nessuna spedizione recente. Quando ne crei una nuova, ritrovi qui tracking, stato e riferimenti principali.</p>
				</div>
			</div>
		</div>

		<div class="sf-animate-in sf-animate-in-2 grid gap-3 sm:grid-cols-[repeat(auto-fit,minmax(180px,1fr))]">
			<div
				v-for="item in personalHighlights"
				:key="item.label"
				class="grid grid-cols-[auto_minmax(0,1fr)] items-center gap-2.5 rounded-control border border-brand-primary/12 bg-gradient-to-b from-white to-brand-bg-alt/95 px-4 py-3 shadow-sf-sm">
				<div class="inline-flex h-[30px] w-[30px] items-center justify-center rounded-full" :style="{ background: item.iconBg, color: item.iconColor }">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="h-4 w-4" v-html="accountCardIcons[item.iconKey]" />
				</div>
				<div class="flex min-w-0 flex-col gap-[3px]">
					<span class="text-sm font-extrabold leading-snug text-brand-text">{{ item.label }}</span>
					<span class="text-[1.08rem] font-extrabold leading-tight text-brand-text">{{ item.value }}</span>
					<span class="text-xs leading-relaxed text-brand-text-secondary">{{ item.meta }}</span>
				</div>
			</div>
		</div>

		<div class="sf-animate-in sf-animate-in-3 rounded-card border border-brand-border bg-brand-card p-5 shadow-sf md:p-[22px]">
			<div class="mb-4 flex flex-wrap items-start justify-between gap-4">
				<div class="min-w-0">
					<p class="sf-section-kicker mb-1.5">Storico</p>
					<h2 class="sf-section-title">Ultime spedizioni</h2>
					<p class="sf-section-description mt-1.5">
						Le ultime consegne restano compatte e facili da riaprire senza trasformare la root in un hub di navigazione.
					</p>
				</div>

				<NuxtLink
					to="/account/spedizioni"
					class="inline-flex items-center gap-1.5 text-[0.84rem] font-bold text-brand-primary transition-all hover:gap-2">
					Vedi storico completo
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M9 18l6-6-6-6" />
					</svg>
				</NuxtLink>
			</div>

			<div v-if="recentCompletedCustomerOrders.length" class="grid gap-2.5">
				<NuxtLink
					v-for="order in recentCompletedCustomerOrders"
					:key="order.id"
					:to="order.url"
					class="group flex items-center gap-3.5 rounded-card border border-brand-primary/10 bg-brand-bg-alt/90 px-[18px] py-4 transition-all hover:-translate-y-px hover:border-brand-primary/15 hover:shadow-sf-sm">
					<div class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-control bg-brand-success-bg text-brand-success-fg">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="h-4 w-4" v-html="accountCardIcons['check-circle']" />
					</div>

					<div class="min-w-0 flex-1">
						<p class="text-[0.95rem] font-extrabold leading-tight text-brand-text">{{ order.title }}</p>
						<p class="mt-1 text-[0.83rem] leading-relaxed text-brand-text-secondary">{{ order.meta }}</p>
					</div>

					<span class="inline-flex shrink-0 items-center justify-center rounded-full bg-brand-success-bg px-2.5 py-1.5 text-[0.72rem] font-bold leading-none text-brand-success-fg">
						Consegnata
					</span>
				</NuxtLink>
			</div>

			<div v-else class="flex items-center gap-3.5 rounded-card border border-dashed border-brand-primary/15 bg-brand-bg-alt/90 p-4.5">
				<div class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-control bg-brand-success-bg text-brand-success-fg">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="h-[18px] w-[18px]" v-html="accountCardIcons['history']" />
				</div>
				<div>
					<p class="text-[0.95rem] font-extrabold leading-tight text-brand-text">Nessuna consegna archiviata</p>
					<p class="mt-1 text-[0.83rem] leading-relaxed text-brand-text-secondary">Appena una spedizione si chiude, la ritrovi qui con riferimenti e data di creazione.</p>
				</div>
			</div>
		</div>

	</div>
</template>
