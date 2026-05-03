<script setup lang="ts">
/**
 * SfInput — input testuale unificato.
 *
 * Pattern uso:
 *   <SfFormGroup label="Email" :error="errors.email">
 *     <SfInput v-model="email" type="email" placeholder="nome@esempio.it" />
 *   </SfFormGroup>
 *
 * Si autobinda all'id passato dal SfFormGroup parent (tramite slot scope) o ne
 * genera uno proprio. Stato error attivato passando :invalid="true".
 */

type InputType = 'text' | 'email' | 'password' | 'number' | 'tel' | 'url' | 'search' | 'date';

interface Props {
	modelValue?: string | number | null;
	type?: InputType;
	placeholder?: string;
	disabled?: boolean;
	readonly?: boolean;
	required?: boolean;
	autocomplete?: string;
	invalid?: boolean;
	id?: string;
	size?: 'sm' | 'md' | 'lg';
	/** Stato icona leading (mdi:* o equivalente). */
	leadingIcon?: string;
	/** Stato icona trailing. */
	trailingIcon?: string;
}

const props = withDefaults(defineProps<Props>(), {
	modelValue: '',
	type: 'text',
	placeholder: '',
	disabled: false,
	readonly: false,
	required: false,
	autocomplete: 'off',
	invalid: false,
	id: '',
	size: 'md',
	leadingIcon: '',
	trailingIcon: '',
});

const emit = defineEmits<{
	'update:modelValue': [value: string | number];
	'blur': [event: FocusEvent];
	'focus': [event: FocusEvent];
}>();

const autoId = useId();
const inputId = computed(() => props.id || `sf-input-${autoId}`);

const sizeClasses = computed(() => {
	if (props.size === 'sm') return 'h-9 text-sm px-3';
	if (props.size === 'lg') return 'h-12 text-base px-4';
	return 'h-11 text-sm px-3.5';
});

const wrapperClasses = computed(() => [
	'flex items-center gap-2 w-full rounded-control border bg-brand-card transition',
	'focus-within:ring-2 focus-within:ring-brand-primary/30 focus-within:border-brand-primary',
	props.invalid
		? 'border-brand-error focus-within:ring-brand-error/30 focus-within:border-brand-error'
		: 'border-brand-border hover:border-brand-text-muted',
	props.disabled ? 'opacity-50 cursor-not-allowed bg-brand-bg-alt' : '',
	sizeClasses.value,
]);

function onInput(event: Event) {
	const target = event.target as HTMLInputElement;
	emit('update:modelValue', props.type === 'number' ? Number(target.value) : target.value);
}
</script>

<template>
	<div :class="wrapperClasses">
		<UIcon v-if="leadingIcon" :name="leadingIcon" class="text-brand-text-muted shrink-0" />
		<input
			:id="inputId"
			:type="type"
			:value="modelValue"
			:placeholder="placeholder"
			:disabled="disabled"
			:readonly="readonly"
			:required="required"
			:autocomplete="autocomplete"
			:aria-invalid="invalid || undefined"
			class="flex-1 bg-transparent outline-none placeholder:text-brand-text-muted text-brand-text disabled:cursor-not-allowed"
			@input="onInput"
			@blur="(e) => emit('blur', e)"
			@focus="(e) => emit('focus', e)"
		>
		<UIcon v-if="trailingIcon" :name="trailingIcon" class="text-brand-text-muted shrink-0" />
	</div>
</template>
