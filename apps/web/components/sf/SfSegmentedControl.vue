<script setup>
/**
 * SfSegmentedControl — toggle/tab segmented control unificato.
 *
 * Sostituisce il pattern .sf-shared-segment-* (CSS-only in funnel-shared.css)
 * con un componente Vue tipato. Pattern uso:
 *
 *   <SfSegmentedControl
 *     v-model="invoiceType"
 *     :options="[
 *       { value: 'privato', label: 'Privato' },
 *       { value: 'azienda', label: 'Azienda' },
 *     ]"
 *     aria-label="Tipo profilo fatturazione" />
 */

const props = defineProps({
	modelValue: { type: [String, Number], default: '' },
	/** Array di {value, label, disabled?} */
	options: {
		type: Array,
		required: true,
		validator: (arr) => Array.isArray(arr) && arr.every((o) => o && 'value' in o && 'label' in o),
	},
	size: {
		type: String,
		default: 'md',
		validator: (v) => ['sm', 'md'].includes(v),
	},
	ariaLabel: { type: String, required: true },
	disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const handleClick = (value, optionDisabled) => {
	if (props.disabled || optionDisabled) return;
	if (value === props.modelValue) return;
	emit('update:modelValue', value);
};

const stripClass = computed(() => [
	'sf-shared-segment-strip',
	`sf-shared-segment-strip--${props.options.length === 2 ? 'two' : props.options.length === 3 ? 'three' : 'flex'}`,
	{ 'sf-shared-segment-strip--sm': props.size === 'sm' },
]);
</script>

<template>
	<div :class="stripClass" role="tablist" :aria-label="ariaLabel">
		<button
			v-for="option in options"
			:key="String(option.value)"
			type="button"
			role="tab"
			:aria-selected="option.value === modelValue"
			:aria-disabled="disabled || option.disabled || null"
			:tabindex="option.value === modelValue ? 0 : -1"
			class="sf-shared-segment-btn"
			:class="{
				'is-active': option.value === modelValue,
				'is-disabled': disabled || option.disabled,
			}"
			@click="handleClick(option.value, option.disabled)">
			{{ option.label }}
		</button>
	</div>
</template>

<style scoped>
/* I selettori .sf-shared-segment-* sono globali in assets/css/funnel-shared.css.
   Qui aggiungiamo solo override per disabled state che non era nel CSS condiviso. */
.sf-shared-segment-btn.is-disabled {
	opacity: 0.5;
	cursor: not-allowed;
}
</style>
