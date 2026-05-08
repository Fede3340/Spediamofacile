<script setup lang="ts">
/**
 * SfSelect — select native unificato (no dropdown JS, accessibile out-of-the-box).
 */

interface Option {
	value: string | number;
	label: string;
	disabled?: boolean;
}

interface Props {
	modelValue?: string | number | null;
	options: Option[];
	placeholder?: string;
	disabled?: boolean;
	required?: boolean;
	invalid?: boolean;
	id?: string;
	size?: 'sm' | 'md' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
	modelValue: '',
	placeholder: '',
	disabled: false,
	required: false,
	invalid: false,
	id: '',
	size: 'md',
});

const emit = defineEmits<{ 'update:modelValue': [value: string | number] }>();

const autoId = useId();
const selectId = computed(() => props.id || `sf-select-${autoId}`);

const sizeClasses = computed(() => {
	if (props.size === 'sm') return 'h-9 text-sm pl-3 pr-9';
	if (props.size === 'lg') return 'h-12 text-base pl-4 pr-10';
	return 'h-11 text-sm pl-3.5 pr-9';
});

const classes = computed(() => [
	'w-full rounded-control border-[1.5px] bg-brand-card transition appearance-none cursor-pointer',
	'focus:ring-2 focus:ring-brand-primary/30 focus:border-brand-primary outline-none',
	'text-brand-text bg-no-repeat bg-[length:1.25em] bg-[right_0.75rem_center]',
	props.invalid
		? 'border-brand-error focus:ring-brand-error/30 focus:border-brand-error'
		: 'border-brand-border hover:border-brand-text-muted',
	props.disabled ? 'opacity-50 cursor-not-allowed bg-brand-bg-alt' : '',
	sizeClasses.value,
]);
</script>

<template>
	<div class="relative">
		<select
			:id="selectId"
			:value="modelValue ?? ''"
			:disabled="disabled"
			:required="required"
			:aria-invalid="invalid || undefined"
			:class="classes"
			@change="(e) => emit('update:modelValue', (e.target as HTMLSelectElement).value)"
		>
			<option v-if="placeholder" value="" disabled>
				{{ placeholder }}
			</option>
			<option
				v-for="opt in options"
				:key="opt.value"
				:value="opt.value"
				:disabled="opt.disabled"
			>
				{{ opt.label }}
			</option>
		</select>
		<UIcon
			name="mdi:chevron-down"
			class="absolute right-3 top-1/2 -translate-y-1/2 text-brand-text-muted pointer-events-none"
			aria-hidden="true"
		/>
	</div>
</template>
