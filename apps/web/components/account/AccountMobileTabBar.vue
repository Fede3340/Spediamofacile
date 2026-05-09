<!--
  AccountMobileTabBar.vue — Bottom navigation persistente per mobile (lg:hidden).
  Pattern UX nativo (Stripe / Revolut / Klarna): 4 voci primarie + "Più" che apre il drawer.
  Le 4 voci primarie cambiano in base al ruolo (cliente / pro / admin).
-->
<script setup>
import { accountCardIcons } from '~/utils/account';

const props = defineProps({
	isAdmin: { type: Boolean, default: false },
	isPro: { type: Boolean, default: false },
	isItemActive: { type: Function, required: true },
	totalBadges: { type: Number, default: 0 },
});

const emit = defineEmits(['open-drawer']);

// 4 voci primarie per ruolo. Le altre stanno nel drawer "Più".
const PRIMARY_ITEMS = computed(() => {
	if (props.isAdmin) {
		return [
			{ label: 'Console', to: '/account/amministrazione', iconKey: 'shield-key', exact: true },
			{ label: 'Ordini', to: '/account/amministrazione/ordini', iconKey: 'clipboard-list' },
			{ label: 'Coda BRT', to: '/account/amministrazione/spedizioni', iconKey: 'truck-fast' },
			{ label: 'Utenti', to: '/account/amministrazione/utenti', iconKey: 'account-group' },
		];
	}
	if (props.isPro) {
		return [
			{ label: 'Account', to: '/account', iconKey: 'account', exact: true },
			{ label: 'Spedizioni', to: '/account/spedizioni', iconKey: 'truck-fast' },
			{ label: 'Indirizzi', to: '/account/indirizzi', iconKey: 'map-marker' },
			{ label: 'Wallet', to: '/account/portafoglio', iconKey: 'wallet' },
		];
	}
	return [
		{ label: 'Account', to: '/account', iconKey: 'account', exact: true },
		{ label: 'Spedizioni', to: '/account/spedizioni', iconKey: 'truck-fast' },
		{ label: 'Indirizzi', to: '/account/indirizzi', iconKey: 'map-marker' },
		{ label: 'Wallet', to: '/account/portafoglio', iconKey: 'wallet' },
	];
});
</script>

<template>
	<!-- eslint-disable vue/no-v-html -- icone SVG da dictionary accountCardIcons (no input utente) -->
	<nav
		class="fixed inset-x-0 bottom-0 z-40 border-t border-brand-border/70 bg-white/95 px-2 pt-1.5 pb-[calc(env(safe-area-inset-bottom)+6px)] shadow-[0_-2px_10px_rgba(15,23,42,0.06)] backdrop-blur-md lg:hidden"
		aria-label="Navigazione mobile account">
		<div class="mx-auto grid max-w-[440px] grid-cols-5 items-center gap-0.5">
			<NuxtLink
				v-for="item in PRIMARY_ITEMS"
				:key="`tab-${item.to}`"
				:to="item.to"
				:class="[
					'flex min-h-[52px] flex-col items-center justify-center gap-0.5 rounded-control px-1 py-1 transition-colors duration-200',
					isItemActive(item)
						? 'text-brand-primary'
						: 'text-brand-text-muted hover:text-brand-text active:bg-brand-primary/[0.04]',
				]">
				<span
					:class="[
						'inline-flex h-7 w-7 items-center justify-center rounded-full transition-colors duration-200',
						isItemActive(item) ? 'bg-brand-primary/[0.10]' : '',
					]">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="currentColor" v-html="accountCardIcons[item.iconKey]" />
				</span>
				<span :class="['text-[10px] leading-tight tracking-tight truncate w-full text-center', isItemActive(item) ? 'font-bold' : 'font-medium']">
					{{ item.label }}
				</span>
			</NuxtLink>

			<button
				type="button"
				class="relative flex min-h-[52px] flex-col items-center justify-center gap-0.5 rounded-control px-1 py-1 text-brand-text-muted transition-colors duration-200 active:bg-brand-primary/[0.04]"
				aria-label="Apri menu completo"
				@click="emit('open-drawer')">
				<span class="inline-flex h-7 w-7 items-center justify-center rounded-full">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="currentColor">
						<circle cx="5" cy="12" r="2" />
						<circle cx="12" cy="12" r="2" />
						<circle cx="19" cy="12" r="2" />
					</svg>
				</span>
				<span class="text-[10px] font-medium leading-tight">Più</span>
				<span
					v-if="totalBadges"
					class="absolute right-2 top-1.5 inline-flex h-[15px] min-w-[15px] items-center justify-center rounded-full bg-brand-accent px-1 text-[9px] font-extrabold text-white">
					{{ totalBadges }}
				</span>
			</button>
		</div>
	</nav>
</template>
