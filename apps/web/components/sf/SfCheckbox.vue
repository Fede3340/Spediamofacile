<script setup lang="ts">
/**
 * SfCheckbox — checkbox custom con label inline.
 */

interface Props {
	modelValue?: boolean;
	label?: string;
	disabled?: boolean;
	required?: boolean;
	id?: string;
	indeterminate?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
	modelValue: false,
	label: '',
	disabled: false,
	required: false,
	id: '',
	indeterminate: false,
});

const emit = defineEmits<{ 'update:modelValue': [value: boolean] }>();

const autoId = useId();
const inputId = computed(() => props.id || `sf-cb-${autoId}`);
</script>

<template>
	<label
		:for="inputId"
		class="inline-flex items-center gap-2.5 cursor-pointer select-none text-sm text-brand-text"
		:class="disabled ? 'opacity-50 cursor-not-allowed' : ''"
	>
		<input
			:id="inputId"
			type="checkbox"
			:checked="modelValue"
			:disabled="disabled"
			:required="required"
			:indeterminate.prop="indeterminate"
			class="peer sr-only"
			@change="(e) => emit('update:modelValue', (e.target as HTMLInputElement).checked)"
		>
		<span
			class="inline-flex h-5 w-5 items-center justify-center rounded-md border-2 border-brand-border bg-brand-card transition shrink-0
				peer-checked:bg-brand-primary peer-checked:border-brand-primary
				peer-focus-visible:ring-2 peer-focus-visible:ring-brand-primary/30
				peer-indeterminate:bg-brand-primary peer-indeterminate:border-brand-primary"
			aria-hidden="true"
		>
			<UIcon
				v-if="!indeterminate"
				name="mdi:check"
				class="text-white opacity-0 peer-checked:opacity-100 transition-opacity h-4 w-4"
				:class="modelValue ? 'opacity-100' : 'opacity-0'"
			/>
			<UIcon v-else name="mdi:minus" class="text-white h-4 w-4" />
		</span>
		<span v-if="label || $slots.default" class="leading-tight">
			<slot>{{ label }}</slot>
		</span>
	</label>
</template>
