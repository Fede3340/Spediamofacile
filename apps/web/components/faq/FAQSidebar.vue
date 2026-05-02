<script setup>
const emit = defineEmits(['focus-search']);
</script>

<template>
	<aside class="faq-side" aria-label="Parla con un operatore">
		<div class="faq-side__card">
			<div class="faq-side__icon" aria-hidden="true">
				<svg
					xmlns="http://www.w3.org/2000/svg"
					width="22"
					height="22"
					viewBox="0 0 24 24"
					fill="currentColor">
					<path
						d="M20 15.5c-1.25 0-2.45-.2-3.57-.57a1 1 0 0 0-1.02.24l-2.2 2.2a15.05 15.05 0 0 1-6.59-6.58l2.2-2.21a.96.96 0 0 0 .25-1A11.36 11.36 0 0 1 8.5 4a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1 17 17 0 0 0 17 17 1 1 0 0 0 1-1v-3.5a1 1 0 0 0-1-1Z" />
				</svg>
			</div>
			<h3 class="faq-side__title">Parla con un operatore</h3>
			<p class="faq-side__text">
				Quando la FAQ non basta, un consulente SpedizioneFacile risponde via
				telefono, email o ticket.
			</p>

			<dl class="faq-side__list">
				<div class="faq-side__row">
					<dt>Numero verde</dt>
					<dd><a href="tel:800123456" class="faq-side__link">800 12 34 56</a></dd>
				</div>
				<div class="faq-side__row">
					<dt>Email</dt>
					<dd>
						<a
							href="mailto:supporto@spediamofacile.it"
							class="faq-side__link">
							supporto@spediamofacile.it
						</a>
					</dd>
				</div>
				<div class="faq-side__row">
					<dt>Orari</dt>
					<dd>Lun-Ven 9:00-18:00<br>Sab 9:00-13:00</dd>
				</div>
			</dl>

			<div class="faq-side__actions">
				<SfButton to="/contatti" class="faq-side__cta">Contattaci ora</SfButton>
				<SfButton variant="secondary" class="faq-side__cta" @click="emit('focus-search')">
					Cerca un'altra domanda
				</SfButton>
			</div>
		</div>
	</aside>
</template>

<style scoped>
.faq-side {
	display: flex;
	flex-direction: column;
	gap: 16px;
	position: relative;
}

@media (min-width: 1024px) {
	.faq-side {
		position: sticky;
		top: 90px;
		align-self: start;
	}
}

.faq-side__card {
	display: flex;
	flex-direction: column;
	gap: 12px;
	padding: 20px;
	background: #ffffff;
	border-radius: 14px;
	box-shadow: 0 2px 8px rgba(9, 88, 102, 0.05), 0 0 0 1px rgba(9, 88, 102, 0.06);
}

.faq-side__title {
	font-size: 0.875rem;
	font-weight: 700;
	color: var(--color-brand-text);
	margin: 0;
	letter-spacing: 0.04em;
	text-transform: uppercase;
}

.faq-side__list {
	display: flex;
	flex-direction: column;
	gap: 4px;
	margin: 0;
	padding: 0;
	list-style: none;
}

.faq-side__row {
	display: block;
}

.faq-side__link {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 8px;
	padding: 8px 12px;
	font-size: 0.875rem;
	font-weight: 500;
	color: var(--color-brand-text);
	background: transparent;
	border: none;
	border-radius: 8px;
	cursor: pointer;
	transition: background 150ms ease, color 150ms ease;
	width: 100%;
	text-align: left;
}

.faq-side__link:hover,
.faq-side__link.is-active {
	background: rgba(9, 88, 102, 0.08);
	color: var(--color-brand-primary);
}

.faq-side__icon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 40px;
	height: 40px;
	border-radius: 10px;
	background: rgba(9, 88, 102, 0.1);
	color: var(--color-brand-primary);
}

.faq-side__text {
	font-size: 0.8125rem;
	color: var(--color-brand-text-secondary, #5f6470);
	line-height: 1.4;
	margin: 0;
}

.faq-side__actions {
	display: flex;
	flex-direction: column;
	gap: 6px;
	width: 100%;
}
</style>
