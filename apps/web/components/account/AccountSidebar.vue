<script setup>
import { accountCardIcons } from '~/utils/account';

defineProps({
	fullName: { type: String, required: true },
	initials: { type: String, required: true },
	roleLabel: { type: String, required: true },
	isAdmin: { type: Boolean, default: false },
	isAdminConsoleRootRoute: { type: Boolean, default: false },
	primaryCta: { type: Object, required: true },
	secondaryCta: { type: Object, default: null },
	navGroups: { type: Array, required: true },
	canCollapseGroup: { type: Function, required: true },
	isGroupOpen: { type: Function, required: true },
	toggleGroup: { type: Function, required: true },
	isItemActive: { type: Function, required: true },
});

const emit = defineEmits(['logout']);
</script>

<template>
<!-- eslint-disable vue/no-v-html -- icone SVG da dictionary accountCardIcons (no input utente) -->
	<aside class="hidden w-[204px] shrink-0 self-start lg:sticky lg:top-7 lg:mt-5 lg:block">
		<div class="flex flex-col">
			<div class="mb-3 flex items-center gap-2.5 px-1">
				<div class="inline-flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-primary to-brand-accent text-sm font-bold leading-none text-white shadow-[0_6px_16px_rgba(9,88,102,0.20),0_2px_6px_rgba(228,66,3,0.12)]">
					{{ initials }}
				</div>
				<div class="min-w-0">
					<p class="truncate text-[15px] font-bold leading-tight text-brand-primary">{{ fullName }}</p>
					<p class="truncate text-xs font-medium leading-snug text-brand-text-muted">{{ roleLabel }}</p>
				</div>
			</div>

			<div class="mb-3.5 grid gap-[7px]">
				<NuxtLink
					:to="primaryCta.to"
					:class="[
						'flex h-[38px] w-full items-center justify-center gap-2 rounded-full px-3.5 text-[13px] font-bold text-white transition-all duration-200 hover:-translate-y-px',
						primaryCta.tone === 'admin'
							? [
								'border border-brand-primary/20 bg-gradient-to-br from-brand-primary-hover to-brand-primary shadow-[0_14px_30px_rgba(9,88,102,0.18)] hover:saturate-[1.03] hover:shadow-[0_18px_34px_rgba(9,88,102,0.22)]',
								isAdminConsoleRootRoute ? 'from-[#063f49] to-brand-primary-hover shadow-[0_18px_34px_rgba(9,88,102,0.24)]' : '',
							]
							: 'bg-brand-accent shadow-[0_12px_26px_rgba(228,66,3,0.18)]',
					]">
					<span class="inline-flex h-[22px] w-[22px] items-center justify-center rounded-full border border-white/20 bg-white/10 text-white" aria-hidden="true">
						<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-3 w-3" fill="currentColor">
							<path v-if="isAdmin" d="M12 2 4 5v6c0 5.55 3.84 10.74 8 11 4.16-.26 8-5.45 8-11V5l-8-3Zm0 3.2 5 1.88V11c0 3.95-2.53 7.6-5 8.77C9.53 18.6 7 14.95 7 11V7.08l5-1.88Z" />
							<path v-else d="M12 5L19 12L17.59 13.41L13 8.83V20H11V8.83L6.41 13.41L5 12L12 5Z" />
						</svg>
					</span>
					{{ primaryCta.label }}
				</NuxtLink>

				<NuxtLink
					v-if="secondaryCta"
					:to="secondaryCta.to"
					class="flex h-[42px] w-full items-center justify-center rounded-full border border-brand-primary/10 bg-brand-card px-3.5 text-[13px] font-bold text-brand-primary transition-all duration-200 hover:-translate-y-px hover:border-brand-primary/20 hover:shadow-[0_10px_22px_rgba(20,37,48,0.06)]">
					{{ secondaryCta.label }}
				</NuxtLink>
			</div>

			<nav aria-label="Navigazione account">
				<div class="flex flex-col gap-2.5 pb-1">
					<div
						v-for="group in navGroups"
						:key="group.key || group.title || group.items.map((item) => item.to).join('|')">
						<div
							v-if="group.title"
							:class="[
								'mb-1 px-2',
								canCollapseGroup(group) ? 'cursor-pointer select-none' : '',
							]"
							@click="toggleGroup(group)">
							<div class="flex items-center justify-between gap-2">
								<span class="text-[10px] font-bold uppercase leading-none tracking-[0.6px] text-brand-text-muted">{{ group.title }}</span>
								<button
									v-if="canCollapseGroup(group)"
									type="button"
									class="inline-flex h-[18px] w-[18px] items-center justify-center rounded-full text-brand-text-muted transition-colors duration-200 hover:text-brand-primary"
									:aria-label="`${isGroupOpen(group) ? 'Chiudi' : 'Apri'} gruppo ${group.title}`"
									@click.stop="toggleGroup(group)">
									<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-3 w-3 transition-transform duration-200" :class="isGroupOpen(group) ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
										<path d="m6 9 6 6 6-6" />
									</svg>
								</button>
							</div>
						</div>

						<div v-if="isGroupOpen(group)" class="grid gap-[3px]">
							<NuxtLink
								v-for="item in group.items"
								:key="item.to"
								:to="item.to"
								:class="[
									'group flex min-h-[40px] items-center gap-[9px] rounded-control px-3.5 py-2.5 text-left transition-colors duration-200',
									isItemActive(item)
										? 'bg-brand-primary/[0.06]'
										: 'hover:bg-brand-primary/[0.04]',
								]">
								<span
									:class="[
										'inline-flex h-[30px] w-[30px] shrink-0 items-center justify-center rounded-control border transition-colors duration-200',
										isItemActive(item)
											? 'border-brand-primary/15 bg-brand-primary/[0.08] text-brand-primary'
											: 'border-brand-primary/10 bg-white text-brand-text-muted group-hover:border-brand-primary/15 group-hover:bg-brand-primary/[0.04] group-hover:text-brand-primary',
									]">
									<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="currentColor" v-html="accountCardIcons[item.iconKey]" />
								</span>
								<span
									:class="[
										'min-w-0 flex-1 truncate text-[13px] leading-tight',
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
				</div>
			</nav>

			<button
				type="button"
				class="group mt-2.5 flex min-h-[40px] w-full shrink-0 items-center gap-[9px] rounded-control px-3.5 py-2.5 text-left transition-colors duration-200 hover:bg-brand-error/5"
				@click="emit('logout')">
				<span class="inline-flex h-[30px] w-[30px] shrink-0 items-center justify-center rounded-control border border-brand-primary/10 bg-white text-brand-text-muted transition-colors duration-200 group-hover:border-brand-error/20 group-hover:bg-brand-error/5 group-hover:text-brand-error">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="currentColor">
						<path d="M10.08 15.59 11.5 17l5-5-5-5-1.42 1.41L12.67 11H3v2h9.67l-2.59 2.59ZM19 3H5a2 2 0 0 0-2 2v4h2V5h14v14H5v-4H3v4a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2Z" />
					</svg>
				</span>
				<span class="min-w-0 flex-1 truncate text-[13px] font-medium leading-tight text-brand-text-secondary group-hover:text-brand-error">Esci</span>
			</button>
		</div>
	</aside>
</template>
