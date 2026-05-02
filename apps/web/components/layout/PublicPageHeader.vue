<!-- Hero unificato pagine content (contatti, servizi, guide, traccia, faq, chi-siamo, privacy/termini/cookie).
     Centrato, con variante compact per flussi operativi come il preventivo. -->
<script setup>
const props = defineProps({
  crumbs: { type: Array, default: () => [] },
  eyebrow: { type: String, default: '' },
  title: { type: String, required: true },
  description: { type: String, default: '' },
  kicker: { type: String, default: '' },
  variant: { type: String, default: 'default' },
});
const eyebrowText = computed(() => props.eyebrow || props.kicker || '');
const hasBreadcrumbs = computed(() => props.crumbs.length > 0);
const hasExtra = useSlots().default !== undefined;
const hasActions = useSlots().actions !== undefined;
const headerClass = computed(() => ({
    'public-page-header--compact': props.variant === 'compact',
}));
const breadcrumbClass = computed(() => ({
    'public-page-breadcrumb--compact': props.variant === 'compact',
}));
</script>

<template>
	<section class="public-page-header" :class="headerClass" aria-labelledby="public-page-header-title">
		<div class="my-container public-page-header__inner">
			<span class="public-page-header__accent" aria-hidden="true" />
			<p v-if="eyebrowText" class="public-page-header__eyebrow">{{ eyebrowText }}</p>
			<h1 id="public-page-header-title" class="public-page-header__title">{{ title }}</h1>
			<p v-if="description" class="public-page-header__lead">{{ description }}</p>
			<div v-if="hasActions" class="public-page-header__actions">
				<slot name="actions" />
			</div>
			<div v-if="hasExtra" class="public-page-header__extra">
				<slot />
			</div>
		</div>
	</section>
	<!-- Breadcrumb left-aligned sotto l'hero (coerente con pattern "navigazione separata") -->
	<nav v-if="hasBreadcrumbs" class="public-page-breadcrumb" :class="breadcrumbClass" aria-label="Percorso di navigazione">
		<div class="my-container public-page-breadcrumb__inner">
			<template v-for="(crumb, index) in crumbs" :key="`${crumb.label}-${index}`">
				<NuxtLink v-if="crumb.to" :to="crumb.to" class="public-page-breadcrumb__link">
					{{ crumb.label }}
				</NuxtLink>
				<span v-else class="public-page-breadcrumb__current" aria-current="page">{{ crumb.label }}</span>
				<span v-if="index < crumbs.length - 1" class="public-page-breadcrumb__sep" aria-hidden="true">/</span>
			</template>
		</div>
	</nav>
</template>

<style scoped>
.public-page-header {
	background: var(--color-brand-page-gradient, linear-gradient(180deg, #f8f9fb 0%, #eef0f3 100%));
	border-bottom: 1px solid var(--color-brand-border, #e9ebec);
	padding: clamp(44px, 6.4vw, 72px) 0 clamp(12px, 2vw, 20px);
	text-align: center;
}
.public-page-header--compact {
	padding: clamp(30px, 4.4vw, 46px) 0 clamp(8px, 1.5vw, 14px);
}
.public-page-header__inner {
	max-width: 820px;
	margin: 0 auto;
	display: flex;
	flex-direction: column;
	align-items: center;
}
.public-page-header__accent {
	display: block;
	width: 56px;
	height: 4px;
	background: var(--color-brand-accent, #e44203);
	border-radius: 2px;
	margin: 0 auto 20px;
}
.public-page-header__eyebrow {
	font-size: 0.8125rem;
	letter-spacing: 0.12em;
	text-transform: uppercase;
	color: var(--color-brand-primary, #095866);
	font-weight: 700;
	margin: 0 0 14px;
}
.public-page-header__title {
	font-family: var(--font-montserrat, 'Montserrat', sans-serif);
	font-size: clamp(2rem, 4.5vw, 3.25rem);
	font-weight: 800;
	line-height: 1.1;
	letter-spacing: -0.02em;
	color: var(--color-brand-text, #1d2738);
	margin: 0 0 16px;
	text-wrap: balance;
}
.public-page-header__lead {
	font-size: clamp(1rem, 1.6vw, 1.125rem);
	line-height: 1.6;
	color: var(--color-brand-text-secondary, #5a6474);
	max-width: 680px;
	margin: 0 auto 20px;
	text-wrap: pretty;
}
.public-page-header__actions {
	display: flex;
	flex-wrap: wrap;
	justify-content: center;
	gap: 10px;
	margin-top: 20px;
}
.public-page-header__extra {
	margin-top: 24px;
	width: 100%;
	display: flex;
	flex-direction: column;
	align-items: center;
}

.public-page-breadcrumb {
	background: transparent;
}
.public-page-breadcrumb__inner {
	display: flex;
	align-items: center;
	flex-wrap: wrap;
	gap: 6px;
	padding-top: 14px;
	padding-bottom: 18px;
	font-size: 0.8125rem;
}
.public-page-breadcrumb--compact .public-page-breadcrumb__inner {
	padding-top: 12px;
	padding-bottom: 12px;
}
.public-page-breadcrumb__link {
	color: var(--color-brand-primary, #095866);
	text-decoration: none;
	font-weight: 500;
	transition: opacity var(--sf-t1) var(--sf-ease);
}
.public-page-breadcrumb__link:hover {
	opacity: 0.72;
}
.public-page-breadcrumb__sep {
	color: var(--color-text-faint, #8a919c);
}
.public-page-breadcrumb__current {
	color: var(--color-brand-text, #1d2738);
	font-weight: 600;
}

@media (max-width: 47.99rem) {
	.public-page-header {
		padding: 42px 0 12px;
	}
	.public-page-header--compact {
		padding: 30px 0 8px;
	}
	.public-page-header__accent {
		margin-bottom: 16px;
	}
	.public-page-header__eyebrow {
		margin-bottom: 12px;
	}
	.public-page-breadcrumb__inner {
		padding-top: 12px;
		padding-bottom: 16px;
	}
	.public-page-breadcrumb--compact .public-page-breadcrumb__inner {
		padding-top: 10px;
		padding-bottom: 12px;
	}
}
</style>
