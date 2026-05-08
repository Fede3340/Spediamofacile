<script setup>
import { accountCardIcons } from '~/utils/account';

defineProps({
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
<!-- eslint-disable vue/no-v-html -- icone SVG da dictionary accountCardIcons (no input utente) -->
	<div>
		<div class="fixed inset-x-0 top-0 z-50 border-b border-brand-border/60 bg-white/95 shadow-[0_1px_4px_rgba(0,0,0,0.04)] backdrop-blur-md lg:hidden">
			<div class="mx-auto flex h-14 max-w-7xl items-center justify-between px-5 sm:px-10">
				<div class="flex items-center gap-2.5">
					<button
						type="button"
						class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-control transition-colors hover:bg-brand-primary/[0.04]"
						aria-label="Apri menu account"
						@click="emit('toggle')">
						<svg v-if="mobileOpen" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-5 w-5 text-brand-text" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
							<path d="M6 6l12 12" />
							<path d="M18 6 6 18" />
						</svg>
						<svg v-else aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-5 w-5 text-brand-text-muted" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
							<path d="M4 7h16" />
							<path d="M4 12h16" />
							<path d="M4 17h16" />
						</svg>
						<span
							v-if="!mobileOpen && totalBadges"
							class="absolute -right-px -top-px inline-flex h-[15px] min-w-[15px] items-center justify-center rounded-full bg-brand-primary text-[8px] font-bold text-white">
							{{ totalBadges }}
						</span>
					</button>
					<span class="text-[15px] font-bold text-brand-text">{{ fullName }}</span>
				</div>
				<div class="flex items-center gap-2">
					<NuxtLink
						:to="primaryCta.to"
						:class="[
							'inline-flex h-9 shrink-0 items-center justify-center gap-1.5 rounded-full px-3.5 text-xs font-semibold tracking-tight text-white transition-all duration-200',
							primaryCta.tone === 'admin'
								? 'border border-brand-primary/20 bg-gradient-to-br from-brand-primary-hover to-brand-primary shadow-[0_14px_30px_rgba(9,88,102,0.18)]'
								: 'bg-brand-accent shadow-[0_3px_10px_rgba(228,66,3,0.22)] hover:bg-brand-accent-hover',
						]">
						<svg v-if="isAdmin" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[13px] w-[13px]" fill="currentColor">
							<path d="M12 2 4 5v6c0 5.55 3.84 10.74 8 11 4.16-.26 8-5.45 8-11V5l-8-3Z" />
						</svg>
						<svg v-else aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="currentColor" stroke-width="2">
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
				class="fixed inset-y-0 left-0 z-[81] w-[280px] max-w-[88vw] overflow-y-auto border-r border-brand-primary/10 bg-white px-4 py-4 shadow-[18px_0_40px_rgba(15,23,42,0.12)] [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
				<div class="flex items-start justify-between gap-3">
					<div class="flex min-w-0 items-center gap-2.5">
						<div class="flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-primary to-brand-accent text-xs font-extrabold text-white">
							{{ initials }}
						</div>
						<div class="min-w-0">
							<p class="truncate text-sm font-bold text-brand-text">{{ fullName }}</p>
							<p class="truncate text-[11px] text-brand-text-muted">{{ roleLabel }}</p>
						</div>
					</div>

					<button
						type="button"
						class="flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-xl border border-brand-primary/10 bg-brand-bg-alt text-brand-primary"
						aria-label="Chiudi menu account"
						@click="emit('close')">
						<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
							<path d="M6 6l12 12" />
							<path d="M18 6 6 18" />
						</svg>
					</button>
				</div>

				<div class="mt-3.5 space-y-2.5">
					<NuxtLink
						:to="primaryCta.to"
						:class="[
							'flex h-11 w-full items-center justify-center gap-2 rounded-full px-3.5 text-[13px] font-semibold tracking-tight transition-all duration-200',
							primaryCta.tone === 'admin'
								? [
									'border border-brand-primary/20 bg-gradient-to-br from-brand-primary-hover to-brand-primary text-white shadow-[0_14px_30px_rgba(9,88,102,0.18)]',
									isAdminConsoleRootRoute ? 'from-[#063f49] to-brand-primary-hover shadow-[0_18px_34px_rgba(9,88,102,0.24)]' : '',
								]
								: 'bg-brand-accent text-white shadow-[0_3px_10px_rgba(228,66,3,0.22)] hover:bg-brand-accent-hover',
						]"
						@click="emit('close')">
						{{ primaryCta.label }}
					</NuxtLink>

					<NuxtLink
						v-if="secondaryCta"
						:to="secondaryCta.to"
						class="flex h-11 w-full items-center justify-center rounded-full border border-brand-primary/10 bg-white px-3.5 text-[13px] font-bold text-brand-primary"
						@click="emit('close')">
						{{ secondaryCta.label }}
					</NuxtLink>
				</div>

				<nav class="mt-4 space-y-3.5" aria-label="Navigazione account mobile">
					<div
						v-for="group in navGroups"
						:key="`mobile-${group.key || group.title || group.items.map((item) => item.to).join('|')}`">
						<div v-if="group.title" class="mb-1.5 px-2.5">
							<span class="text-[10px] font-bold uppercase leading-none tracking-[0.6px] text-brand-text-muted">{{ group.title }}</span>
						</div>

						<div class="space-y-0.5">
							<NuxtLink
								v-for="item in group.items"
								:key="`mobile-${item.to}`"
								:to="item.to"
								:class="[
									'group flex items-center gap-2.5 rounded-control px-2.5 py-[9px] text-left transition-colors duration-200',
									isItemActive(item)
										? 'bg-brand-primary/[0.06]'
										: 'hover:bg-brand-primary/[0.04]',
								]"
								@click="emit('close')">
								<span
									:class="[
										'inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-control border transition-colors duration-200',
										isItemActive(item)
											? 'border-brand-primary/15 bg-brand-primary/[0.08] text-brand-primary'
											: 'border-brand-primary/10 bg-white text-brand-text-muted group-hover:border-brand-primary/15 group-hover:bg-brand-primary/[0.04] group-hover:text-brand-primary',
									]">
									<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="currentColor" v-html="accountCardIcons[item.iconKey]" />
								</span>
								<span
									:class="[
										'min-w-0 flex-1 truncate text-[13px]',
										isItemActive(item) ? 'font-bold text-brand-primary' : 'font-medium text-brand-text-secondary group-hover:text-brand-text',
									]">
									{{ item.label }}
								</span>
								<span
									v-if="item.badge"
									class="inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-brand-accent px-1.5 text-[9px] font-extrabold text-white">
									{{ item.badge }}
								</span>
							</NuxtLink>
						</div>
					</div>
				</nav>

				<button
					type="button"
					class="group mt-3.5 flex w-full items-center gap-2.5 rounded-control px-2.5 py-[9px] text-left transition-colors duration-200 hover:bg-brand-error/5"
					@click="emit('logout')">
					<span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-control border border-brand-primary/10 bg-white text-brand-text-muted transition-colors duration-200 group-hover:border-brand-error/20 group-hover:bg-brand-error/5 group-hover:text-brand-error">
						<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[15px] w-[15px]" fill="currentColor">
							<path d="M10.08 15.59 11.5 17l5-5-5-5-1.42 1.41L12.67 11H3v2h9.67l-2.59 2.59ZM19 3H5a2 2 0 0 0-2 2v4h2V5h14v14H5v-4H3v4a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2Z" />
						</svg>
					</span>
					<span class="text-[13px] font-medium text-brand-text-secondary group-hover:text-brand-error">Esci</span>
				</button>
			</aside>
		</transition>
	</div>
</template>
