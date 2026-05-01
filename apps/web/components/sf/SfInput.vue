<script setup>
/**
 * SfInput — primitive input testuale unificato.
 *
 * Wrapper su <input> con label, error message, help text. Usa solo CSS
 * variables `var(--color-brand-*)` (no hex hardcoded). Pattern uso:
 *
 *   <SfInput v-model="email" type="email" label="Email" :error="emailError" />
 *   <SfInput v-model="phone" type="tel" label="Telefono" required />
 *   <SfInput v-model="city" label="Citta" help-text="Inserisci almeno 3 lettere" />
 */

const props = defineProps({
	modelValue: { type: [String, Number], default: '' },
	/** Tipo input HTML */
	type: {
		type: String,
		default: 'text',
		validator: (v) => ['text', 'email', 'tel', 'number', 'password', 'search', 'url'].includes(v),
	},
	label: { type: String, default: '' },
	/** Messaggio errore — se presente input mostra stato error */
	error: { type: String, default: '' },
	/** Testo descrittivo sotto l'input (ignorato se error presente) */
	helpText: { type: String, default: '' },
	required: { type: Boolean, default: false },
	readonly: { type: Boolean, default: false },
	disabled: { type: Boolean, default: false },
	placeholder: { type: String, default: '' },
	/** Override id auto-generato */
	id: { type: String, default: '' },
	/** Lega autocomplete del browser (es. "email", "tel", "section-shipping street-address") */
	autocomplete: { type: String, default: '' },
	/** maxlength attributo HTML */
	maxlength: { type: [Number, String], default: null },
});

defineEmits(['update:modelValue', 'blur', 'focus', 'input']);

const generatedId = useId();
const inputId = computed(() => props.id || `sf-input-${generatedId}`);
const errorId = computed(() => `${inputId.value}-error`);
const hintId = computed(() => `${inputId.value}-hint`);

const ariaDescribedBy = computed(() => {
	if (props.error) return errorId.value;
	if (props.helpText) return hintId.value;
	return null;
});

const wrapperClass = computed(() => ({
	'sf-input': true,
	'sf-input--error': Boolean(props.error),
	'sf-input--disabled': props.disabled,
	'sf-input--readonly': props.readonly,
}));
</script>

<template>
	<div :class="wrapperClass">
		<label v-if="label" :for="inputId" class="sf-input__label">
			{{ label }}<span v-if="required" class="sf-input__required" aria-hidden="true">*</span>
		</label>
		<input
			:id="inputId"
			:type="type"
			:value="modelValue"
			:placeholder="placeholder"
			:required="required"
			:readonly="readonly"
			:disabled="disabled"
			:autocomplete="autocomplete || null"
			:maxlength="maxlength || null"
			:aria-invalid="error ? 'true' : null"
			:aria-describedby="ariaDescribedBy"
			class="sf-input__field"
			@input="$emit('update:modelValue', $event.target.value); $emit('input', $event)"
			@blur="$emit('blur', $event)"
			@focus="$emit('focus', $event)" >
		<p v-if="error" :id="errorId" class="sf-input__error" role="alert">{{ error }}</p>
		<p v-else-if="helpText" :id="hintId" class="sf-input__hint">{{ helpText }}</p>
	</div>
</template>

<style scoped>
.sf-input {
	display: flex;
	flex-direction: column;
	gap: 6px;
}
.sf-input__label {
	font-size: 0.8125rem;
	font-weight: 600;
	color: var(--color-brand-text);
	letter-spacing: 0.01em;
}
.sf-input__required {
	color: var(--color-brand-accent);
	margin-left: 2px;
}
.sf-input__field {
	width: 100%;
	min-height: 44px;
	padding: 10px 14px;
	border: 1px solid var(--color-brand-border);
	border-radius: var(--sf-radius-control, 14px);
	background: var(--color-brand-card);
	color: var(--color-brand-text);
	font-size: 1rem;
	line-height: 1.4;
	transition: border-color 120ms ease, box-shadow 120ms ease;
}
.sf-input__field:focus {
	outline: none;
	border-color: var(--color-brand-primary);
	box-shadow: 0 0 0 3px rgba(9, 88, 102, 0.12);
}
.sf-input__field::placeholder {
	color: var(--color-brand-text-muted);
}
.sf-input--error .sf-input__field {
	border-color: var(--color-brand-error);
}
.sf-input--error .sf-input__field:focus {
	box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
}
.sf-input--disabled .sf-input__field,
.sf-input--readonly .sf-input__field {
	background: var(--color-brand-bg-alt);
	color: var(--color-brand-text-secondary);
	cursor: not-allowed;
}
.sf-input__error {
	margin: 0;
	font-size: 0.8125rem;
	color: var(--color-brand-error);
	line-height: 1.4;
}
.sf-input__hint {
	margin: 0;
	font-size: 0.8125rem;
	color: var(--color-brand-text-muted);
	line-height: 1.4;
}
</style>
