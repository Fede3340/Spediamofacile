<script setup>
/**
 * SfButton — primitive bottone unificato sitewide.
 *
 * Sostituisce le varianti CSS sparse (.btn-cta, .btn-secondary, .btn-danger,
 * .btn-compact, .sf-btn--*, .action-btn--*) con UN solo componente che mappa
 * sui token e classi globali esistenti in assets/css/main.css.
 *
 * Pattern uso:
 *   <SfButton>Salva</SfButton>                                  // primary md
 *   <SfButton variant="secondary">Annulla</SfButton>
 *   <SfButton variant="danger" :loading="isDeleting">Elimina</SfButton>
 *   <SfButton size="sm" to="/account">Vai</SfButton>            // diventa <NuxtLink>
 *   <SfButton href="https://...">Apri esterno</SfButton>        // diventa <a target=_blank>
 *
 * Migrazione progressiva: i caller con classi inline (.btn-cta, .btn-secondary)
 * continuano a funzionare. Quando si tocca un componente lo si converte a
 * <SfButton> per uniformare. Vedi docs/MIGRATIONS.md.
 */

const props = defineProps({
	/** primary = CTA arancione | secondary = teal outline | danger = rosso | ghost = trasparente */
	variant: {
		type: String,
		default: 'primary',
		validator: (v) => ['primary', 'secondary', 'danger', 'ghost'].includes(v),
	},
	/** sm = compact 32px | md = standard 44px (touch target) | lg = hero 52px */
	size: {
		type: String,
		default: 'md',
		validator: (v) => ['sm', 'md', 'lg'].includes(v),
	},
	loading: { type: Boolean, default: false },
	/** Testo opzionale mostrato durante loading (sostituisce slot default). */
	loadingText: { type: String, default: '' },
	disabled: { type: Boolean, default: false },
	to: { type: [String, Object], default: null },
	href: { type: String, default: null },
	type: {
		type: String,
		default: 'button',
		validator: (v) => ['button', 'submit', 'reset'].includes(v),
	},
	block: { type: Boolean, default: false },
});

const VARIANT_CLASS = {
	primary: 'btn-cta',
	secondary: 'btn-secondary',
	danger: 'btn-danger',
	ghost: 'btn-ghost',
};

const SIZE_CLASS = { sm: 'btn-compact', md: '', lg: 'btn-lg' };

const buttonClasses = computed(() => {
	const v = VARIANT_CLASS[props.variant] || 'btn-cta';
	const s = SIZE_CLASS[props.size] || '';
	return ['inline-flex', 'items-center', 'justify-center', 'gap-[8px]', v, s, props.block ? 'w-full' : '']
		.filter(Boolean)
		.join(' ');
});

const isDisabled = computed(() => props.disabled || props.loading);
</script>

<template>
	<NuxtLink
		v-if="to"
		:to="to"
		:class="buttonClasses"
		:aria-disabled="isDisabled || null"
		:tabindex="isDisabled ? -1 : null">
		<span v-if="loading" class="sf-btn-spinner" aria-hidden="true"></span>
		<slot v-else-if="$slots.leading" name="leading" />
		<span v-if="loading && loadingText">{{ loadingText }}</span>
		<slot v-else />
	</NuxtLink>

	<a
		v-else-if="href"
		:href="href"
		target="_blank"
		rel="noopener noreferrer"
		:class="buttonClasses">
		<slot v-if="$slots.leading" name="leading" />
		<slot />
	</a>

	<button
		v-else
		:type="type"
		:disabled="isDisabled"
		:class="buttonClasses">
		<span v-if="loading" class="sf-btn-spinner" aria-hidden="true"></span>
		<slot v-else-if="$slots.leading" name="leading" />
		<span v-if="loading && loadingText">{{ loadingText }}</span>
		<slot v-else />
	</button>
</template>

<style scoped>
.sf-btn-spinner {
	width: 14px;
	height: 14px;
	border: 2px solid currentColor;
	border-right-color: transparent;
	border-radius: 50%;
	animation: sf-btn-spin 0.7s linear infinite;
}

@keyframes sf-btn-spin {
	to { transform: rotate(360deg); }
}
</style>
