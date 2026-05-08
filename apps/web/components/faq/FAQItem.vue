<script setup>
import { highlightMatch } from '~/utils/faqs';

defineProps({
	item: { type: Object, required: true },
	searchQuery: { type: String, default: '' },
	open: { type: Boolean, default: false },
});
</script>

<template>
	<!-- eslint-disable vue/no-v-html -- highlightMatch escapa input prima di wrappare <mark>, output safe -->
	<details
		:id="`faq-${item.id}`"
		class="faq-details"
		:open="open">
		<summary class="faq-details__summary">
			<span class="faq-details__cat">{{ item.category }}</span>
			<span
				class="faq-details__q"
				v-html="highlightMatch(item.question, searchQuery)" />
			<span class="faq-details__chev" aria-hidden="true">
				<svg
					xmlns="http://www.w3.org/2000/svg"
					width="18"
					height="18"
					viewBox="0 0 24 24"
					fill="none"
					stroke="currentColor"
					stroke-width="2"
					stroke-linecap="round"
					stroke-linejoin="round">
					<path d="m6 9 6 6 6-6" />
				</svg>
			</span>
		</summary>
		<div class="faq-details__panel">
			<p
				class="faq-details__a"
				v-html="highlightMatch(item.answer, searchQuery)" />
			<div class="faq-details__meta">
				<a
					:href="`#faq-${item.id}`"
					class="faq-details__permalink"
					aria-label="Copia link a questa domanda">
					# Link diretto
				</a>
			</div>
		</div>
	</details>
</template>

<style scoped>
.faq-details {
	display: block;
	background: #ffffff;
	border-radius: 18px;
	box-shadow: 0 2px 8px rgba(9, 88, 102, 0.04), 0 0 0 1px rgba(9, 88, 102, 0.06);
	overflow: hidden;
	transition: box-shadow 200ms ease;
}

.faq-details[open] {
	box-shadow: 0 6px 18px rgba(9, 88, 102, 0.08), 0 0 0 1px rgba(9, 88, 102, 0.12);
}

.faq-details__summary {
	display: flex;
	align-items: center;
	gap: 12px;
	padding: 16px 20px;
	font-size: 0.9375rem;
	font-weight: 600;
	color: var(--color-brand-text);
	cursor: pointer;
	list-style: none;
}

.faq-details__summary::-webkit-details-marker {
	display: none;
}

.faq-details__cat {
	display: inline-flex;
	align-items: center;
	padding: 2px 8px;
	font-size: 0.6875rem;
	font-weight: 700;
	color: var(--color-brand-orange, #E44203);
	background: rgba(228, 66, 3, 0.08);
	border-radius: 999px;
	letter-spacing: 0.04em;
	text-transform: uppercase;
	flex-shrink: 0;
}

.faq-details__chev {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 24px;
	height: 24px;
	color: var(--color-brand-text-muted, #7a8190);
	margin-left: auto;
	flex-shrink: 0;
	transition: transform 200ms ease;
}

.faq-details[open] .faq-details__chev {
	transform: rotate(180deg);
}

.faq-details__panel {
	padding: 0 20px 20px;
	font-size: 0.875rem;
	color: var(--color-brand-text-secondary, #5f6470);
	line-height: 1.6;
}

.faq-details__meta {
	display: flex;
	align-items: center;
	gap: 12px;
	margin-top: 12px;
	padding-top: 12px;
	border-top: 1px solid rgba(9, 88, 102, 0.08);
	font-size: 0.75rem;
	color: var(--color-brand-text-muted, #7a8190);
}

.faq-details__permalink {
	display: inline-flex;
	align-items: center;
	gap: 4px;
	color: var(--color-brand-primary);
	text-decoration: none;
	font-weight: 600;
}

.faq-details__permalink:hover {
	text-decoration: underline;
}
</style>
