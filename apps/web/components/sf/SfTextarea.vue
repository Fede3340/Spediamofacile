<script setup lang="ts">
/**
 * SfTextarea — textarea unificata.
 */

interface Props {
	modelValue?: string;
	placeholder?: string;
	disabled?: boolean;
	readonly?: boolean;
	required?: boolean;
	rows?: number;
	invalid?: boolean;
	id?: string;
	maxlength?: number;
}

const props = withDefaults(defineProps<Props>(), {
	modelValue: '',
	placeholder: '',
	disabled: false,
	readonly: false,
	required: false,
	rows: 4,
	invalid: false,
	id: '',
	maxlength: undefined,
});

const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

const autoId = useId();
const inputId = computed(() => props.id || `sf-textarea-${autoId}`);

const wrapperClasses = computed(() => [
	'w-full rounded-control border-[1.5px] bg-brand-card transition px-3.5 py-2.5 text-sm',
	'focus:ring-2 focus:ring-brand-primary/30 focus:border-brand-primary outline-none',
	'placeholder:text-brand-text-muted text-brand-text',
	props.invalid
		? 'border-brand-error focus:ring-brand-error/30 focus:border-brand-error'
		: 'border-brand-border hover:border-brand-text-muted',
	props.disabled ? 'opacity-50 cursor-not-allowed bg-brand-bg-alt' : '',
]);

function onInput(event: Event) {
	emit('update:modelValue', (event.target as HTMLTextAreaElement).value);
}
</script>

<template>
	<textarea
		:id="inputId"
		:value="modelValue"
		:placeholder="placeholder"
		:disabled="disabled"
		:readonly="readonly"
		:required="required"
		:rows="rows"
		:maxlength="maxlength"
		:aria-invalid="invalid || undefined"
		:class="wrapperClasses"
		@input="onInput"
	/>
</template>
