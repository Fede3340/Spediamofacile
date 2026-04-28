<script setup>
/**
 * SfInput — primitive input testo unificato.
 * Sostituisce 93+ inline Tailwind input sparsi (border, padding, focus state non standard).
 *
 * Supporta v-model, label, hint, error, prefix/suffix slot per icone.
 *
 * Pattern uso:
 *   <SfInput v-model="email" label="Email" type="email" />
 *   <SfInput v-model="phone" type="tel" :error="errors.phone" hint="Solo numeri" />
 *   <SfInput v-model="search" placeholder="Cerca...">
 *     <template #prefix><svg>...</svg></template>
 *   </SfInput>
 */
import { useId } from 'vue';

const autoId = useId();
const props = defineProps({
	modelValue: { type: [String, Number], default: '' },
	label: { type: String, default: '' },
	type: { type: String, default: 'text' },
	placeholder: { type: String, default: '' },
	hint: { type: String, default: '' },
	error: { type: String, default: '' },
	disabled: { type: Boolean, default: false },
	required: { type: Boolean, default: false },
	autocomplete: { type: String, default: 'off' },
	// Default deterministico (useId crea un id stabile su SSR + client → hydration safe).
	// Prima usavamo Math.random() che generava ids diversi su server/client.
	id: { type: String, default: () => `sf-input-${autoId}` },
});

const emit = defineEmits(['update:modelValue', 'blur', 'focus']);

const onInput = (event) => emit('update:modelValue', event.target.value);
</script>

<template>
	<div class="sf-input-field">
		<label v-if="label" :for="id" class="sf-input-label">
			{{ label }}<span v-if="required" aria-hidden="true" class="sf-input-required">*</span>
		</label>
		<div class="sf-input-wrap" :class="{ 'sf-input-wrap--error': error, 'sf-input-wrap--disabled': disabled }">
			<span v-if="$slots.prefix" class="sf-input-prefix">
				<slot name="prefix" />
			</span>
			<input
				:id="id"
				:value="modelValue"
				:type="type"
				:placeholder="placeholder"
				:disabled="disabled"
				:required="required"
				:autocomplete="autocomplete"
				:aria-invalid="error ? 'true' : null"
				:aria-describedby="error ? `${id}-error` : (hint ? `${id}-hint` : null)"
				class="sf-input-control"
				@input="onInput"
				@blur="emit('blur', $event)"
				@focus="emit('focus', $event)" />
			<span v-if="$slots.suffix" class="sf-input-suffix">
				<slot name="suffix" />
			</span>
		</div>
		<p v-if="error" :id="`${id}-error`" class="sf-input-error" role="alert">{{ error }}</p>
		<p v-else-if="hint" :id="`${id}-hint`" class="sf-input-hint">{{ hint }}</p>
	</div>
</template>

<style scoped>
.sf-input-field {
	display: flex;
	flex-direction: column;
	gap: 6px;
}

.sf-input-label {
	font-size: 0.8125rem;
	font-weight: 600;
	color: var(--color-brand-text, #1d2738);
}

.sf-input-required { color: var(--color-brand-accent, #e44203); margin-left: 2px; }

.sf-input-wrap {
	display: flex;
	align-items: center;
	gap: 8px;
	background: #fff;
	border: 1.5px solid var(--color-border-strong, #dfe2e7);
	border-radius: 12px;
	padding: 0 12px;
	min-height: 44px; /* WCAG touch target */
	transition: border-color .18s ease, box-shadow .18s ease;
}

.sf-input-wrap:focus-within {
	border-color: var(--color-brand-primary, #095866);
	box-shadow: 0 0 0 3px rgba(9, 88, 102, 0.14);
}

.sf-input-wrap--error {
	border-color: #b42318;
}

.sf-input-wrap--error:focus-within {
	box-shadow: 0 0 0 3px rgba(180, 35, 24, 0.14);
}

.sf-input-wrap--disabled {
	background: var(--color-brand-bg-soft, #f5f6f9);
	cursor: not-allowed;
}

.sf-input-control {
	flex: 1;
	border: none;
	background: transparent;
	outline: none;
	font-size: 1rem;
	color: var(--color-brand-text, #1d2738);
	min-width: 0;
}

.sf-input-control::placeholder {
	color: var(--color-text-ghost, #98a1ae);
}

.sf-input-control:disabled {
	cursor: not-allowed;
}

.sf-input-prefix, .sf-input-suffix {
	display: inline-flex;
	align-items: center;
	color: var(--color-brand-text-secondary, #5a6474);
}

.sf-input-error {
	margin: 0;
	font-size: 0.75rem;
	color: #b42318;
}

.sf-input-hint {
	margin: 0;
	font-size: 0.75rem;
	color: var(--color-brand-text-secondary, #5a6474);
}
</style>
