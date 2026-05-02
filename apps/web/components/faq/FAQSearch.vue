<script setup>
import { ref } from 'vue';

defineProps({
	modelValue: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue']);

const searchInputRef = ref(null);
defineExpose({
	focus: () => searchInputRef.value?.focus(),
});
</script>

<template>
	<label class="faq-search" :class="{ 'is-filled': modelValue.length > 0 }">
		<span class="sr-only">Cerca tra le domande frequenti</span>
		<svg class="faq-search__icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
			<circle cx="11" cy="11" r="8" />
			<path d="m21 21-4.3-4.3" />
		</svg>
		<input
			ref="searchInputRef"
			:value="modelValue"
			type="search"
			class="faq-search__input"
			placeholder="Cerca: contrassegno, ritiro, fattura, reclamo..."
			autocomplete="off"
			aria-label="Cerca tra le domande frequenti"
			@input="emit('update:modelValue', $event.target.value)">
		<button
			v-if="modelValue"
			type="button"
			class="faq-search__clear"
			aria-label="Cancella ricerca"
			@click="emit('update:modelValue', '')">
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<path d="M18 6 6 18" />
				<path d="m6 6 12 12" />
			</svg>
		</button>
	</label>
</template>

<style scoped>
.faq-search {
	display: flex;
	align-items: center;
	gap: 10px;
	padding: 10px 14px;
	background: #ffffff;
	border-radius: 10px;
	box-shadow: 0 0 0 1px rgba(9, 88, 102, 0.1);
	transition: box-shadow 150ms ease;
}

.faq-search:focus-within {
	box-shadow: 0 0 0 2px var(--color-brand-primary);
}

.faq-search__icon {
	display: inline-flex;
	color: var(--color-brand-text-muted, #7a8190);
	flex-shrink: 0;
}

.faq-search__input {
	flex: 1;
	border: none;
	outline: none;
	background: transparent;
	font-size: 0.9375rem;
	color: var(--color-brand-text);
	padding: 0;
	min-width: 0;
}

.faq-search__clear {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 24px;
	height: 24px;
	border: none;
	background: rgba(9, 88, 102, 0.08);
	color: var(--color-brand-primary);
	border-radius: 999px;
	cursor: pointer;
	flex-shrink: 0;
}
</style>
