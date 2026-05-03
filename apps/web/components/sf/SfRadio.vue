<script setup lang="ts">
/**
 * SfRadio — radio button custom con label inline.
 *
 * Uso in gruppo:
 *   <SfRadio v-model="payment" value="stripe" label="Carta" />
 *   <SfRadio v-model="payment" value="bonifico" label="Bonifico" />
 */

interface Props {
	modelValue?: string | number | null;
	value: string | number;
	label?: string;
	disabled?: boolean;
	name?: string;
	id?: string;
}

const props = withDefaults(defineProps<Props>(), {
	modelValue: '',
	label: '',
	disabled: false,
	name: '',
	id: '',
});

const emit = defineEmits<{ 'update:modelValue': [value: string | number] }>();

const autoId = useId();
const inputId = computed(() => props.id || `sf-radio-${autoId}`);
const isChecked = computed(() => props.modelValue === props.value);
</script>

<template>
	<label
		:for="inputId"
		class="inline-flex items-center gap-2.5 cursor-pointer select-none text-sm text-brand-text"
		:class="disabled ? 'opacity-50 cursor-not-allowed' : ''"
	>
		<input
			:id="inputId"
			type="radio"
			:name="name"
			:value="value"
			:checked="isChecked"
			:disabled="disabled"
			class="peer sr-only"
			@change="emit('update:modelValue', value)"
		>
		<span
			class="inline-flex h-5 w-5 items-center justify-center rounded-full border-2 border-brand-border bg-brand-card transition shrink-0
				peer-checked:border-brand-primary
				peer-focus-visible:ring-2 peer-focus-visible:ring-brand-primary/30"
			aria-hidden="true"
		>
			<span class="h-2.5 w-2.5 rounded-full bg-brand-primary transition-transform" :class="isChecked ? 'scale-100' : 'scale-0'" />
		</span>
		<span v-if="label || $slots.default" class="leading-tight">
			<slot>{{ label }}</slot>
		</span>
	</label>
</template>
