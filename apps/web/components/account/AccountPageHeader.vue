<script setup>
import '~/assets/css/account.css';

/*
  AccountPageHeader — hero unificato per tutte le pagine /account/*.
  Pattern caratteristico:
    - accent-bar arancione verticale 3px a sinistra (via CSS ::before)
    - kicker uppercase arancione 11px (es. "ACCOUNT", "WALLET", "STORICO")
    - heading 22-28px 800 teal
    - description secondary 14-15px
    - meta pills / actions slot
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
	<div class="sf-account-page-header sf-animate-in">
		<div class="sf-account-page-header__surface">
			<div v-if="resolvedCrumbs.length || backTo" class="sf-account-page-header__topline">
				<NuxtLink v-if="backTo" :to="backTo" class="sf-account-page-header__backlink">
					<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[16px] h-[16px]" fill="currentColor">
						<path d="M20,11V13H8L13.5,18.5L12.08,19.92L4.16,12L12.08,4.08L13.5,5.5L8,11H20Z" />
					</svg>
					{{ backLabel }}
				</NuxtLink>

				<nav v-else-if="resolvedCrumbs.length" class="sf-account-page-header__crumbs" aria-label="Percorso di navigazione">
					<template v-for="(crumb, index) in resolvedCrumbs" :key="`${crumb.label}-${index}`">
						<NuxtLink v-if="crumb.to" :to="crumb.to" class="sf-account-page-header__crumb-link">
							{{ crumb.label }}
						</NuxtLink>
						<span
							v-else
							class="sf-account-page-header__crumb-current"
							:aria-current="index === resolvedCrumbs.length - 1 ? 'page' : undefined">{{ crumb.label }}</span>
						<span v-if="index < resolvedCrumbs.length - 1" class="sf-account-page-header__crumb-divider" aria-hidden="true">/</span>
					</template>
				</nav>
			</div>

			<div :class="['sf-account-page-header__body', centered ? 'items-center text-center' : '']">
				<div
					:class="[
						'sf-account-page-header__main',
						$slots.identity ? 'sf-account-page-header__main--identity' : '',
						centered ? 'items-center text-center' : '',
					]">
					<div v-if="$slots.identity" class="sf-account-page-header__identity">
						<slot name="identity" />
					</div>

					<div :class="['sf-account-page-header__intro', centered ? 'items-center text-center max-w-[720px]' : '']">
						<p v-if="eyebrow" class="sf-account-kicker mb-[10px]">{{ eyebrow }}</p>
						<h1 class="sf-page-title">{{ title }}</h1>
						<p v-if="description" class="sf-section-description mt-[6px]">{{ description }}</p>
						<div v-if="$slots.meta" class="sf-account-page-header__meta mt-[10px]">
							<slot name="meta" />
						</div>
					</div>
				</div>

				<div
					v-if="$slots.actions"
					:class="['sf-account-page-header__actions', centered ? 'w-full justify-center' : 'w-full desktop:w-auto desktop:shrink-0']">
					<slot name="actions" />
				</div>
			</div>

			<div v-if="$slots.default" class="sf-account-page-header__content">
				<slot />
			</div>
		</div>
	</div>
</template>
