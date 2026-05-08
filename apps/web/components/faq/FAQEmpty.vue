<script setup>
const emit = defineEmits(['reset']);
</script>

<template>
	<div class="faq-empty" role="status">
		<div class="faq-empty__icon" aria-hidden="true">
			<svg
				xmlns="http://www.w3.org/2000/svg"
				width="38"
				height="38"
				viewBox="0 0 24 24"
				fill="none"
				stroke="currentColor"
				stroke-width="2"
				stroke-linecap="round"
				stroke-linejoin="round">
				<circle cx="11" cy="11" r="8" />
				<path d="m21 21-4.3-4.3" />
				<path d="M8 11h6" />
			</svg>
		</div>
		<h3 class="faq-empty__title">Nessuna domanda trovata</h3>
		<p class="faq-empty__text">
			Non abbiamo trovato risposte per la tua ricerca. Prova a cambiare parole
			chiave o resetta i filtri.
		</p>
		<div class="faq-empty__actions">
			<SfButton variant="secondary" @click="emit('reset')">
				Resetta filtri
			</SfButton>
			<SfButton to="/contatti">
				Contatta un operatore
			</SfButton>
		</div>
	</div>
</template>

<style scoped>
.faq-empty {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 16px;
	padding: 48px 24px;
	background: #ffffff;
	border-radius: 18px;
	box-shadow: 0 2px 8px rgba(9, 88, 102, 0.04);
	text-align: center;
}

.faq-empty__icon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 56px;
	height: 56px;
	border-radius: 50%;
	background: rgba(9, 88, 102, 0.08);
	color: var(--color-brand-primary);
}

.faq-empty__title {
	font-size: 1.125rem;
	font-weight: 700;
	color: var(--color-brand-text);
	margin: 0;
}

.faq-empty__text {
	font-size: 0.875rem;
	color: var(--color-brand-text-secondary, #5f6470);
	margin: 0;
	max-width: 420px;
}

.faq-empty__actions {
	display: flex;
	gap: 12px;
	flex-wrap: wrap;
	justify-content: center;
}
</style>
