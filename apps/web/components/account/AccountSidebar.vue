<script setup>
/**
 * AccountSidebar — sidebar desktop dell'area account (visibile da lg).
 * Estratto da AccountRouteShell.vue senza modifiche logiche.
 * Gli stili .account-route-shell__* sono definiti nel parent (global, non
 * scoped) così che sidebar e drawer mobile condividano lo stesso look.
 */
import { accountCardIcons } from '~/utils/account';

const props = defineProps({
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
	<aside class="account-route-shell__sidebar hidden lg:block shrink-0 self-start">
		<div class="account-route-shell__sidebar-sticky flex flex-col">
			<div class="mb-[12px] flex items-center gap-[10px] px-[4px]">
				<div class="sf-sidebar-avatar shrink-0">
					{{ initials }}
				</div>
				<div class="min-w-0">
					<p class="account-route-shell__identity-name truncate text-[var(--color-brand-text)]">{{ fullName }}</p>
					<p class="account-route-shell__identity-role truncate text-[var(--color-brand-text-muted)]">{{ roleLabel }}</p>
				</div>
			</div>

			<div class="account-route-shell__sidebar-cta mb-[14px]">
				<NuxtLink
					:to="primaryCta.to"
					:class="[
						'flex h-[38px] w-full items-center justify-center gap-[8px] rounded-full px-[14px] text-[13px] font-[700] transition-all duration-200 hover:-translate-y-[1px]',
						primaryCta.tone === 'admin'
							? [
								'account-route-shell__cta-primary--admin text-white',
								isAdminConsoleRootRoute ? 'account-route-shell__cta-primary--admin-active' : '',
							]
							: 'bg-[var(--color-brand-accent)] text-white shadow-[0_12px_26px_rgba(228,66,3,0.18)]',
					]">
					<span
						:class="[
							'inline-flex h-[22px] w-[22px] items-center justify-center rounded-full',
							primaryCta.tone === 'admin'
								? 'border border-white/18 bg-white/12 text-white'
								: 'border border-white/20 bg-white/12 text-white',
						]"
						aria-hidden="true">
						<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[12px] w-[12px]" fill="currentColor">
							<path v-if="isAdmin" d="M12 2 4 5v6c0 5.55 3.84 10.74 8 11 4.16-.26 8-5.45 8-11V5l-8-3Zm0 3.2 5 1.88V11c0 3.95-2.53 7.6-5 8.77C9.53 18.6 7 14.95 7 11V7.08l5-1.88Z" />
							<path v-else d="M12 5L19 12L17.59 13.41L13 8.83V20H11V8.83L6.41 13.41L5 12L12 5Z" />
						</svg>
					</span>
					{{ primaryCta.label }}
				</NuxtLink>

				<NuxtLink
					v-if="secondaryCta"
					:to="secondaryCta.to"
					class="flex h-[42px] w-full items-center justify-center rounded-full border border-[rgba(9,88,102,0.12)] bg-white px-[14px] text-[13px] font-[700] text-[var(--color-brand-primary)] transition-all duration-200 hover:-translate-y-[1px] hover:border-[rgba(9,88,102,0.18)] hover:shadow-[0_10px_22px_rgba(20,37,48,0.06)]">
					{{ secondaryCta.label }}
				</NuxtLink>
			</div>

			<nav aria-label="Navigazione account">
				<div class="account-route-shell__nav-groups flex flex-col pb-[4px]">
					<div
						v-for="group in navGroups"
						:key="group.key || group.title || group.items.map((item) => item.to).join('|')"
						class="account-route-shell__group"
						:data-tone="group.tone || 'client'">
						<div
							v-if="group.title"
							:class="[
								'account-route-shell__group-toggle mb-[4px] px-[8px]',
								canCollapseGroup(group) ? 'cursor-pointer select-none' : '',
							]"
							@click="toggleGroup(group)">
							<div class="flex items-center justify-between gap-[8px]">
								<span class="account-route-shell__group-label font-[700] uppercase">{{ group.title }}</span>
								<button
									v-if="canCollapseGroup(group)"
									type="button"
									class="inline-flex h-[18px] w-[18px] items-center justify-center rounded-full text-[var(--color-brand-text-muted)] transition-colors duration-200 hover:text-[var(--color-brand-primary)]"
									:aria-label="`${isGroupOpen(group) ? 'Chiudi' : 'Apri'} gruppo ${group.title}`"
									@click.stop="toggleGroup(group)">
									<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[12px] w-[12px] transition-transform duration-200" :class="isGroupOpen(group) ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
										<path d="m6 9 6 6 6-6" />
									</svg>
								</button>
							</div>
						</div>

						<div v-if="isGroupOpen(group)" class="account-route-shell__group-list">
							<NuxtLink
								v-for="item in group.items"
								:key="item.to"
								:to="item.to"
								:class="[
									'account-route-shell__nav-item group flex min-h-[36px] items-center gap-[9px] rounded-[10px] px-[8px] py-[6px] text-left transition-all duration-200',
									isItemActive(item)
										? 'account-route-shell__nav-item--active bg-[rgba(9,88,102,0.08)]'
										: 'account-route-shell__nav-item--inactive hover:bg-[rgba(9,88,102,0.04)]',
								]">
								<span
									:class="[
										'account-route-shell__nav-icon-shell inline-flex h-[28px] w-[28px] shrink-0 items-center justify-center rounded-[9px] border transition-colors duration-200',
										isItemActive(item)
											? 'border-[rgba(9,88,102,0.14)] bg-[rgba(9,88,102,0.08)] text-[var(--color-brand-primary)]'
											: 'border-[rgba(9,88,102,0.10)] bg-white text-[var(--color-brand-text-muted)] group-hover:border-[rgba(9,88,102,0.14)] group-hover:bg-[rgba(9,88,102,0.04)] group-hover:text-[var(--color-brand-text-muted)]',
									]">
									<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[14px] w-[14px]" fill="currentColor" v-html="accountCardIcons[item.iconKey]" />
								</span>
								<span
									class="account-route-shell__nav-label min-w-0 flex-1 truncate leading-[1.3]"
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
				</div>
			</nav>

			<button
				type="button"
				class="account-route-shell__logout group mt-[10px] flex min-h-[36px] w-full shrink-0 items-center gap-[9px] rounded-[10px] px-[8px] py-[6px] text-left hover:bg-[rgba(220,38,38,0.05)]"
				@click="emit('logout')">
				<span class="account-route-shell__nav-icon-shell inline-flex h-[28px] w-[28px] shrink-0 items-center justify-center rounded-[9px] border border-[rgba(9,88,102,0.10)] bg-white text-[var(--color-brand-text-muted)] group-hover:border-[rgba(220,38,38,0.20)] group-hover:text-[#C75A29]">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-[14px] w-[14px]" fill="currentColor">
						<path d="M10.08 15.59 11.5 17l5-5-5-5-1.42 1.41L12.67 11H3v2h9.67l-2.59 2.59ZM19 3H5a2 2 0 0 0-2 2v4h2V5h14v14H5v-4H3v4a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2Z" />
					</svg>
				</span>
				<span class="account-route-shell__nav-label min-w-0 flex-1 truncate leading-[1.3] text-[13px] font-[500] text-[var(--color-brand-text-secondary)] group-hover:text-[#9A3412]">Esci</span>
			</button>
		</div>
	</aside>
</template>
