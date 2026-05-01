<script setup>
/**
 * SfEmptyState — placeholder unificato "no results" / "vuoto".
 *
 * Sostituisce le card empty-state custom di admin/account con un componente
 * coerente: icona opzionale, titolo, descrizione, slot azione. Pattern uso:
 *
 *   <SfEmptyState title="Nessun ordine trovato" description="Crea la tua prima spedizione." />
 *   <SfEmptyState title="..." action-label="Vai al preventivo" action-to="/preventivo" />
 *   <SfEmptyState title="...">
 *     <template #action>
 *       <SfButton variant="primary">Custom CTA</SfButton>
 *     </template>
 *   </SfEmptyState>
 */

const props = defineProps({
	title: { type: String, required: true },
	description: { type: String, default: '' },
	/** Nome icona iconify (es. "mdi:package-variant"). Slot `icon` ha precedenza. */
	iconName: { type: String, default: '' },
	actionLabel: { type: String, default: '' },
	actionTo: { type: [String, Object], default: null },
});

const showActionButton = computed(() => Boolean(props.actionLabel && props.actionTo));
</script>

<template>
	<div class="sf-empty-state">
		<div v-if="$slots.icon || iconName" class="sf-empty-state__icon" aria-hidden="true">
			<slot name="icon">
				<Icon v-if="iconName" :name="iconName" />
			</slot>
		</div>

		<h3 class="sf-empty-state__title">{{ title }}</h3>
		<p v-if="description" class="sf-empty-state__description">{{ description }}</p>

		<div v-if="$slots.action || showActionButton" class="sf-empty-state__action">
			<slot name="action">
				<SfButton v-if="showActionButton" variant="primary" :to="actionTo">{{ actionLabel }}</SfButton>
			</slot>
		</div>
	</div>
</template>

<style scoped>
.sf-empty-state {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	gap: 12px;
	padding: 48px 24px;
	border-radius: var(--sf-radius-card, 16px);
	border: 1px dashed var(--color-brand-border);
	background: var(--color-brand-bg-alt);
	text-align: center;
	color: var(--color-brand-text);
}
.sf-empty-state__icon {
	width: 56px;
	height: 56px;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	border-radius: 999px;
	background: var(--color-brand-card);
	color: var(--color-brand-primary);
	font-size: 28px;
}
.sf-empty-state__title {
	margin: 0;
	font-size: 1.0625rem;
	font-weight: 700;
	color: var(--color-brand-text);
}
.sf-empty-state__description {
	margin: 0;
	max-width: 460px;
	font-size: 0.9375rem;
	line-height: 1.5;
	color: var(--color-brand-text-secondary);
}
.sf-empty-state__action {
	margin-top: 8px;
}
</style>
