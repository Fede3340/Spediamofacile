<script setup>
/**
 * AccountMobileDrawer — drawer di navigazione mobile + topbar sticky
 * dell'area account (visibile < lg). Estratto da AccountRouteShell.vue
 * senza modifiche logiche o stilistiche.
 */
import { accountCardIcons } from '~/utils/account';

const props = defineProps({
	mobileOpen: { type: Boolean, default: false },
	fullName: { type: String, required: true },
	initials: { type: String, required: true },
	roleLabel: { type: String, required: true },
	isAdmin: { type: Boolean, default: false },
	isAdminConsoleRootRoute: { type: Boolean, default: false },
	primaryCta: { type: Object, required: true },
	secondaryCta: { type: Object, default: null },
	navGroups: { type: Array, required: true },
	totalBadges: { type: Number, default: 0 },
	isItemActive: { type: Function, required: true },
});

const emit = defineEmits(['toggle', 'close', 'logout']);
</script>

<template>
	<div>
		<div
			class="account-route-shell__mobile-topbar lg:hidden fixed top-0 left-0 right-0 z-50 border-b border-[#DFE2E7]/60 bg-white/95 backdrop-blur-md"
			style="box-shadow: 0 1px 4px rgba(0,0,0,0.04);">
			<div class="mx-auto flex h-[56px] max-w-[1280px] items-center justify-between px-[20px] sm:px-[40px]">
				<div class="flex items-center gap-[10px]">
					<button
						type="button"
						class="relative flex h-[40px] w-[40px] shrink-0 items-center justify-center rounded-[10px] transition-all hover:bg-[rgba(9,88,102,0.04)]"
						aria-label="Apri menu account"
						@click="emit('toggle')">
						<svg aria-hidden="true" v-if="mobileOpen" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[20px] w-[20px] text-[var(--color-brand-text)]" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
							<path d="M6 6l12 12" />
							<path d="M18 6 6 18" />
						</svg>
						<svg aria-hidden="true" v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[20px] w-[20px] text-[var(--color-brand-text-muted)]" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
							<path d="M4 7h16" />
							<path d="M4 12h16" />
							<path d="M4 17h16" />
						</svg>
						<span
							v-if="!mobileOpen && totalBadges"
							class="absolute -right-[1px] -top-[1px] inline-flex h-[15px] min-w-[15px] items-center justify-center rounded-full bg-[var(--color-brand-primary)] text-[8px] font-[700] text-white">
							{{ totalBadges }}
						</span>
					</button>
					<span class="text-[15px] font-[700] text-[var(--color-brand-text)]">{{ fullName }}</span>
				</div>
				<div class="flex items-center gap-[8px]">
					<NuxtLink
						:to="primaryCta.to"
						:class="[
							'inline-flex h-[36px] shrink-0 items-center justify-center gap-[6px] rounded-full px-[14px] text-[12px] font-[600] text-white tracking-[-0.005em] transition-all duration-200',
							primaryCta.tone === 'admin'
								? 'account-route-shell__cta-primary--admin'
								: 'bg-[#E44203] hover:bg-[#c73600] shadow-[0_3px_10px_rgba(228,66,3,0.22)]',
						]">
						<svg aria-hidden="true" v-if="isAdmin" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[13px] w-[13px]" fill="currentColor">
							<path d="M12 2 4 5v6c0 5.55 3.84 10.74 8 11 4.16-.26 8-5.45 8-11V5l-8-3Z" />
						</svg>
						<svg aria-hidden="true" v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[14px] w-[14px]" fill="currentColor" stroke-width="2">
							<path d="M12 5L19 12L17.59 13.41L13 8.83V20H11V8.83L6.41 13.41L5 12L12 5Z" />
						</svg>
						<span class="hidden sm:inline">{{ isAdmin ? 'Console' : 'Spedisci' }}</span>
					</NuxtLink>
				</div>
			</div>
		</div>

		<transition name="fade">
			<div
				v-if="mobileOpen"
				class="fixed inset-0 z-[80] bg-black/20 backdrop-blur-[2px]"
				@click="emit('close')" />
		</transition>

		<transition name="slide-right">
			<aside
				v-if="mobileOpen"
				class="account-route-shell__mobile-drawer fixed inset-y-0 left-0 z-[81] w-[280px] max-w-[88vw] overflow-y-auto border-r border-[rgba(9,88,102,0.08)] bg-white px-[18px] py-[18px] shadow-[18px_0_40px_rgba(15,23,42,0.12)]"
				style="scrollbar-width: none; -ms-overflow-style: none;">
				<div class="flex items-start justify-between gap-[12px]">
					<div class="flex min-w-0 items-center gap-[10px]">
						<div class="flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-full bg-[linear-gradient(135deg,var(--color-brand-primary)_0%,var(--color-teal-600)_100%)] text-[12px] font-[800] text-white">
							{{ initials }}
						</div>
						<div class="min-w-0">
							<p class="truncate text-[14px] font-[700] text-[var(--color-brand-text)]">{{ fullName }}</p>
							<p class="truncate text-[11px] text-[var(--color-brand-text-muted)]">{{ roleLabel }}</p>
						</div>
					</div>

					<button
						type="button"
						class="flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-[12px] border border-[rgba(9,88,102,0.08)] bg-[#F7FAFB] text-[var(--color-brand-primary)]"
						aria-label="Chiudi menu account"
						@click="emit('close')">
						<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
							<path d="M6 6l12 12" />
							<path d="M18 6 6 18" />
						</svg>
					</button>
				</div>

				<div class="mt-[14px] space-y-[10px]">
					<NuxtLink
						:to="primaryCta.to"
						:class="[
							'flex h-[44px] w-full items-center justify-center gap-[8px] rounded-full px-[14px] text-[13px] font-[600] tracking-[-0.005em] transition-all duration-200',
							primaryCta.tone === 'admin'
								? [
									'account-route-shell__cta-primary--admin text-white',
									isAdminConsoleRootRoute ? 'account-route-shell__cta-primary--admin-active' : '',
								]
								: 'bg-[#E44203] hover:bg-[#c73600] text-white shadow-[0_3px_10px_rgba(228,66,3,0.22)]',
						]"
						@click="emit('close')">
						{{ primaryCta.label }}
					</NuxtLink>

					<NuxtLink
						v-if="secondaryCta"
						:to="secondaryCta.to"
						class="flex h-[44px] w-full items-center justify-center rounded-full border border-[rgba(9,88,102,0.12)] bg-white px-[14px] text-[13px] font-[700] text-[var(--color-brand-primary)]"
						@click="emit('close')">
						{{ secondaryCta.label }}
					</NuxtLink>
				</div>

				<nav class="mt-[16px] space-y-[14px]" aria-label="Navigazione account mobile">
					<div
						v-for="group in navGroups"
						:key="`mobile-${group.key || group.title || group.items.map((item) => item.to).join('|')}`"
						class="account-route-shell__group"
						:data-tone="group.tone || 'client'">
						<div v-if="group.title" class="mb-[5px] px-[10px]">
							<span class="account-route-shell__group-label font-[700] uppercase">{{ group.title }}</span>
						</div>

						<div class="space-y-[2px]">
							<NuxtLink
								v-for="item in group.items"
								:key="`mobile-${item.to}`"
								:to="item.to"
								:class="[
									'group flex items-center gap-[10px] rounded-[10px] px-[10px] py-[9px] text-left transition-all duration-200',
									isItemActive(item)
										? 'account-route-shell__nav-item--active bg-[rgba(9,88,102,0.08)]'
										: 'account-route-shell__nav-item--inactive hover:bg-[rgba(9,88,102,0.04)]',
								]"
								@click="emit('close')">
								<span
									class="account-route-shell__nav-icon-shell inline-flex h-[32px] w-[32px] shrink-0 items-center justify-center rounded-[10px] border"
									:class="isItemActive(item) ? 'text-[var(--color-brand-primary)]' : 'text-[var(--color-brand-text-muted)] group-hover:text-[var(--color-brand-text-muted)]'">
									<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[15px] w-[15px]" fill="currentColor" v-html="accountCardIcons[item.iconKey]" />
								</span>
								<span
									class="account-route-shell__nav-label min-w-0 flex-1 truncate text-[13px]"
									:class="isItemActive(item) ? 'font-[700] text-[var(--color-brand-primary)]' : 'font-[500] text-[var(--color-brand-text-secondary)] group-hover:text-[var(--color-brand-text)]'">
									{{ item.label }}
								</span>
								<span
									v-if="item.badge"
									class="inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-[#E44203] px-[5px] text-[9px] font-[800] text-white">
									{{ item.badge }}
								</span>
							</NuxtLink>
						</div>
					</div>
				</nav>

				<button
					type="button"
					class="mt-[14px] flex w-full items-center gap-[10px] rounded-[10px] px-[10px] py-[9px] text-left transition-all duration-200 hover:bg-[rgba(220,38,38,0.05)]"
					@click="emit('logout')">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[15px] w-[15px] shrink-0 text-[#ccc]" fill="currentColor">
						<path d="M10.08 15.59 11.5 17l5-5-5-5-1.42 1.41L12.67 11H3v2h9.67l-2.59 2.59ZM19 3H5a2 2 0 0 0-2 2v4h2V5h14v14H5v-4H3v4a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2Z" />
					</svg>
					<span class="text-[13px] font-[500] text-[#bbb]">Esci</span>
				</button>
			</aside>
		</transition>
	</div>
</template>
