<script setup>
/*
  AccountPageHeader — hero unificato per tutte le pagine /account/*.
  Pattern caratteristico:
    - kicker uppercase arancione 11px (es. "ACCOUNT", "WALLET", "STORICO")
    - heading 22-28px font-display teal
    - description secondary 14-15px
    - slot identity / meta / actions
*/
const props = defineProps({
	title: { type: String, required: true },
	description: { type: String, default: '' },
	eyebrow: { type: String, default: 'Account' },
	/**
	 * Shortcut opzionale: se valorizzato (es. "Profilo") costruisce
	 * automaticamente il breadcrumb "Account / {current}", evitando di
	 * ripetere ovunque la boilerplate `[{ label: 'Account', to: '/account' },
	 * { label: '...' }]`. Per livelli intermedi (es. Amministrazione / X)
	 * usare `crumbs` esplicito, che ha precedenza.
	 */
	current: { type: String, default: '' },
	crumbs: {
		type: Array,
		default: () => [],
	},
	backTo: { type: String, default: '' },
	backLabel: { type: String, default: "Torna all'account" },
	centered: { type: Boolean, default: false },
});

const resolvedCrumbs = computed(() => {
	if (Array.isArray(props.crumbs) && props.crumbs.length) return props.crumbs;
	if (props.current) {
		return [
			{ label: 'Account', to: '/account' },
			{ label: props.current },
		];
	}
	return [{ label: 'Account', to: '/account' }];
});
</script>

<template>
	<div class="mb-4 md:mb-6">
		<div class="relative overflow-hidden rounded-card border border-brand-border bg-gradient-to-b from-brand-card to-brand-bg-alt shadow-sf p-4 md:p-6">
			<div
				v-if="resolvedCrumbs.length || backTo"
				class="relative z-10 flex flex-wrap items-center justify-between gap-2 mb-2.5 pb-2.5 border-b border-brand-primary/10"
			>
				<NuxtLink
					v-if="backTo"
					:to="backTo"
					class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-primary transition hover:gap-2 hover:underline"
				>
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor">
						<path d="M20,11V13H8L13.5,18.5L12.08,19.92L4.16,12L12.08,4.08L13.5,5.5L8,11H20Z" />
					</svg>
					{{ backLabel }}
				</NuxtLink>

				<nav
					v-else-if="resolvedCrumbs.length"
					class="flex flex-wrap items-center gap-1.5 text-xs text-brand-text-muted"
					aria-label="Percorso di navigazione"
				>
					<template v-for="(crumb, index) in resolvedCrumbs" :key="`${crumb.label}-${index}`">
						<NuxtLink
							v-if="crumb.to"
							:to="crumb.to"
							class="inline-flex items-center font-bold text-brand-primary transition hover:underline"
						>
							{{ crumb.label }}
						</NuxtLink>
						<span
							v-else
							class="font-semibold text-brand-text"
							:aria-current="index === resolvedCrumbs.length - 1 ? 'page' : undefined"
						>{{ crumb.label }}</span>
						<span
							v-if="index < resolvedCrumbs.length - 1"
							class="text-brand-text-muted/60"
							aria-hidden="true"
						>/</span>
					</template>
				</nav>
			</div>

			<div :class="['relative z-10 grid gap-2', centered ? 'place-items-center text-center' : '']">
				<div
					:class="[
						'flex flex-col gap-3',
						$slots.identity ? 'lg:flex-row lg:items-center lg:gap-5' : '',
						centered ? 'items-center text-center' : '',
					]"
				>
					<div v-if="$slots.identity" class="flex items-center gap-3 shrink-0">
						<slot name="identity" />
					</div>

					<div :class="['flex flex-col gap-1', centered ? 'items-center text-center max-w-[720px]' : '']">
						<p
							v-if="eyebrow"
							class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-widest text-brand-accent mb-2.5"
						>
							{{ eyebrow }}
						</p>
						<h1 class="font-display text-xl sm:text-2xl md:text-[1.75rem] font-extrabold text-brand-primary leading-tight tracking-tight">
							{{ title }}
						</h1>
						<p
							v-if="description"
							class="text-[13px] sm:text-sm md:text-base text-brand-text-secondary leading-relaxed mt-1.5"
						>
							{{ description }}
						</p>
						<div v-if="$slots.meta" class="flex flex-wrap gap-2 mt-2.5">
							<slot name="meta" />
						</div>
					</div>
				</div>

				<div
					v-if="$slots.actions"
					:class="[
						'flex flex-wrap gap-2',
						centered ? 'w-full justify-center' : 'w-full lg:w-auto lg:shrink-0',
					]"
				>
					<slot name="actions" />
				</div>
			</div>

			<div v-if="$slots.default" class="mt-4">
				<slot />
			</div>
		</div>
	</div>
</template>
