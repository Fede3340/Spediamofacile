<script setup>
/**
 * AccountPageSection — wrapper container standard per pagine account/admin.
 * Sostituisce i 3 pattern (`my-container max-w-7xl`, `my-container space-y-*`, `my-container`)
 * con un'unica struttura coerente.
 *
 * Uso:
 *   <AccountPageSection>
 *     <AccountPageHeader title="..." />
 *     <SfCard>...</SfCard>
 *   </AccountPageSection>
 *
 * Props opzionali per casi che richiedono override (admin tabelle larghe, etc.).
 */
defineProps({
	/** Larghezza max content (Tailwind utility). */
	maxWidth: { type: String, default: 'max-w-7xl' },
	/** Spacing verticale fra elementi (Tailwind utility). */
	spacing: { type: String, default: 'space-y-6' },
	/** Padding sezione (Tailwind utility). */
	padding: { type: String, default: 'py-5 tablet:py-6 desktop:py-7' },
});
</script>

<template>
	<section :class="['w-full', padding]">
		<div :class="['my-container', maxWidth, spacing]">
			<slot />
		</div>
	</section>
</template>
