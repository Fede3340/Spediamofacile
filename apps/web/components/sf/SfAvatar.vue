<script setup lang="ts">
/**
 * SfAvatar — avatar utente con fallback iniziali.
 */

interface Props {
	src?: string;
	alt?: string;
	/** Nome utente per derivare iniziali se src mancante. */
	name?: string;
	size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
	/** Forma (default circle). */
	variant?: 'circle' | 'square';
}

const props = withDefaults(defineProps<Props>(), {
	src: '',
	alt: '',
	name: '',
	size: 'md',
	variant: 'circle',
});

const SIZE_CLASS = {
	xs: 'h-6 w-6 text-[10px]',
	sm: 'h-8 w-8 text-xs',
	md: 'h-10 w-10 text-sm',
	lg: 'h-12 w-12 text-base',
	xl: 'h-16 w-16 text-lg',
};

const initials = computed(() => {
	if (!props.name) return '';
	return props.name
		.trim()
		.split(/\s+/)
		.slice(0, 2)
		.map((part) => part[0]?.toUpperCase() || '')
		.join('');
});

const classes = computed(() => [
	'inline-flex items-center justify-center font-bold text-white shrink-0 select-none overflow-hidden',
	'bg-gradient-to-br from-brand-primary to-brand-accent shadow-sf-sm',
	SIZE_CLASS[props.size],
	props.variant === 'circle' ? 'rounded-full' : 'rounded-lg',
]);
</script>

<template>
	<span :class="classes" :aria-label="alt || name">
		<img v-if="src" :src="src" :alt="alt || name" class="h-full w-full object-cover">
		<span v-else-if="initials" aria-hidden="true">{{ initials }}</span>
		<UIcon v-else name="mdi:account" class="h-1/2 w-1/2" aria-hidden="true" />
	</span>
</template>
