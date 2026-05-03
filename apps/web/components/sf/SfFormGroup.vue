<script setup lang="ts">
/**
 * SfFormGroup — wrapper label + input + hint + error.
 *
 * Pattern uso:
 *   <SfFormGroup label="Email" required hint="Lo useremo per accesso" :error="errors.email">
 *     <SfInput v-model="email" type="email" />
 *   </SfFormGroup>
 *
 * Genera id automatico, lo lega a aria-describedby (hint) e aria-errormessage (error).
 * Slot default = control (input/select/textarea/checkbox).
 */

interface Props {
	label?: string;
	required?: boolean;
	hint?: string;
	error?: string;
	id?: string;
	/** Layout orizzontale (label sx, control dx). Default verticale. */
	horizontal?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
	label: '',
	required: false,
	hint: '',
	error: '',
	id: '',
	horizontal: false,
});

const autoId = useId();
const fieldId = computed(() => props.id || `sf-field-${autoId}`);
const hintId = computed(() => `${fieldId.value}-hint`);
const errorId = computed(() => `${fieldId.value}-error`);
const describedBy = computed(() =>
	[props.hint ? hintId.value : null, props.error ? errorId.value : null].filter(Boolean).join(' ') || undefined,
);
</script>

<template>
	<div :class="horizontal ? 'grid sm:grid-cols-[200px_1fr] sm:items-start sm:gap-4' : 'flex flex-col gap-1.5'">
		<label
			v-if="label"
			:for="fieldId"
			class="text-sm font-semibold text-brand-text"
		>
			{{ label }}
			<span v-if="required" class="text-brand-error" aria-hidden="true">*</span>
		</label>

		<div class="flex flex-col gap-1.5">
			<slot :id="fieldId" :described-by="describedBy" :invalid="!!error" />

			<p v-if="hint && !error" :id="hintId" class="text-xs text-brand-text-muted">
				{{ hint }}
			</p>
			<p v-if="error" :id="errorId" role="alert" class="text-xs text-brand-error">
				{{ error }}
			</p>
		</div>
	</div>
</template>
