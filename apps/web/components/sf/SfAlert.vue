<script setup lang="ts">
/**
 * SfAlert — notifica inline (info, success, warning, danger).
 *
 * Pattern:
 *   <SfAlert tone="warning" title="Attenzione" dismissible @dismiss="...">
 *     Hai 3 ordini in attesa di conferma.
 *   </SfAlert>
 */

type Tone = 'info' | 'success' | 'warning' | 'danger';

interface Props {
	tone?: Tone;
	title?: string;
	icon?: string;
	dismissible?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
	tone: 'info',
	title: '',
	icon: '',
	dismissible: false,
});

const emit = defineEmits<{ dismiss: [] }>();

const TONE_CONFIG = {
	info: { bg: 'bg-brand-soft-bg', border: 'border-brand-soft-border', text: 'text-brand-soft-text', icon: 'mdi:information' },
	success: { bg: 'bg-brand-success-bg', border: 'border-brand-success/30', text: 'text-brand-success-fg', icon: 'mdi:check-circle' },
	warning: { bg: 'bg-amber-50', border: 'border-amber-200', text: 'text-amber-800', icon: 'mdi:alert' },
	danger: { bg: 'bg-red-50', border: 'border-red-200', text: 'text-red-800', icon: 'mdi:alert-circle' },
};

const config = computed(() => TONE_CONFIG[props.tone]);
const finalIcon = computed(() => props.icon || config.value.icon);
</script>

<template>
	<div
		role="alert"
		:class="[
			'flex gap-3 rounded-card border p-4',
			config.bg,
			config.border,
			config.text,
		]"
	>
		<UIcon :name="finalIcon" class="h-5 w-5 shrink-0 mt-0.5" aria-hidden="true" />
		<div class="flex-1 min-w-0">
			<div v-if="title" class="font-semibold mb-1">{{ title }}</div>
			<div class="text-sm leading-relaxed">
				<slot />
			</div>
		</div>
		<button
			v-if="dismissible"
			type="button"
			class="shrink-0 -mr-1 -mt-1 p-1 rounded hover:bg-black/5 transition focus-visible:outline-2 focus-visible:outline-current"
			aria-label="Chiudi"
			@click="emit('dismiss')"
		>
			<UIcon name="mdi:close" class="h-4 w-4" />
		</button>
	</div>
</template>
