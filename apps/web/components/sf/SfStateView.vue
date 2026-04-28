<script setup>
/**
 * SfStateView — primitive per stati pagina (loading, empty, error).
 * Sostituisce 52+ inline empty/error/loading sparsi con UN pattern unificato.
 *
 * Pattern uso:
 *   <SfStateView v-if="loading" state="loading" message="Caricamento..." />
 *   <SfStateView v-else-if="error" state="error" :message="error" :action="{ label: 'Riprova', onClick: refetch }" />
 *   <SfStateView v-else-if="!items.length" state="empty" message="Nessun risultato" :action="{ label: 'Crea nuovo', to: '/new' }" />
 */
defineProps({
	state: {
		type: String,
		required: true,
		validator: (v) => ['loading', 'empty', 'error'].includes(v),
	},
	title: { type: String, default: '' },
	message: { type: String, default: '' },
	/** { label: string, onClick?: function, to?: string } */
	action: { type: Object, default: null },
});
</script>

<template>
	<div :class="['sf-state-view', `sf-state-view--${state}`]" role="status" :aria-live="state === 'error' ? 'assertive' : 'polite'">
		<div v-if="state === 'loading'" class="sf-state-view__spinner" aria-hidden="true"></div>

		<svg v-else-if="state === 'empty'" class="sf-state-view__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
			<path d="M3 7l1.5 12.5a2 2 0 002 1.5h11a2 2 0 002-1.5L21 7M3 7h18M3 7l1-3h16l1 3M9 11v6M15 11v6" />
		</svg>

		<svg v-else-if="state === 'error'" class="sf-state-view__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
			<circle cx="12" cy="12" r="10" />
			<path d="M12 8v4M12 16h.01" />
		</svg>

		<h2 v-if="title" class="sf-state-view__title">{{ title }}</h2>
		<p v-if="message" class="sf-state-view__message">{{ message }}</p>

		<SfButton v-if="action && action.to" :to="action.to" variant="primary" class="sf-state-view__action">
			{{ action.label }}
		</SfButton>
		<SfButton v-else-if="action && action.onClick" variant="primary" class="sf-state-view__action" @click="action.onClick">
			{{ action.label }}
		</SfButton>
	</div>
</template>

<style scoped>
.sf-state-view {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 12px;
	padding: 32px 16px;
	text-align: center;
}

.sf-state-view__spinner {
	width: 32px;
	height: 32px;
	border: 3px solid rgba(9, 88, 102, 0.18);
	border-top-color: var(--color-brand-primary, #095866);
	border-radius: 50%;
	animation: sf-state-spin 0.8s linear infinite;
}

@keyframes sf-state-spin {
	to { transform: rotate(360deg); }
}

.sf-state-view__icon {
	width: 40px;
	height: 40px;
	color: var(--color-brand-text-secondary, #5a6474);
}

.sf-state-view--error .sf-state-view__icon {
	color: #b42318;
}

.sf-state-view__title {
	margin: 0;
	font-size: 1rem;
	font-weight: 700;
	color: var(--color-brand-text, #1d2738);
}

.sf-state-view__message {
	margin: 0;
	font-size: 0.875rem;
	line-height: 1.5;
	color: var(--color-brand-text-secondary, #5a6474);
	max-width: 400px;
}

.sf-state-view__action {
	margin-top: 8px;
}
</style>
