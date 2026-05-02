<script setup>
import { computed } from 'vue';

const props = defineProps({
	user: { type: Object, default: null },
});

defineEmits(['close']);

const fullName = computed(() => props.user
	? `${props.user.name || ''} ${props.user.surname || ''}`.trim()
	: '');

const initials = computed(() => {
	if (!props.user) return '?';
	const f = (props.user.name?.[0] || '').toUpperCase();
	const l = (props.user.surname?.[0] || '').toUpperCase();
	return `${f}${l}` || '?';
});
</script>

<template>
	<header class="flex items-center justify-between gap-3 px-5 py-4 border-b border-brand-border bg-brand-bg-alt">
		<div class="flex items-center gap-3 min-w-0">
			<div class="inline-flex items-center justify-center w-11 h-11 rounded-full bg-brand-soft-bg text-brand-primary font-extrabold text-sm" aria-hidden="true">
				{{ initials }}
			</div>
			<div class="min-w-0">
				<h2 id="drawer-title" class="m-0 text-base font-extrabold leading-tight text-brand-text truncate max-w-[280px]">{{ fullName || 'Utente' }}</h2>
				<p class="mt-0.5 text-sm text-brand-text-secondary truncate max-w-[280px]">{{ user?.email || '—' }}</p>
			</div>
		</div>
		<button
			type="button"
			class="inline-flex items-center justify-center w-8 h-8 rounded-control border border-brand-border bg-brand-card text-brand-text-secondary cursor-pointer shrink-0 transition hover:bg-brand-bg-alt hover:text-brand-text"
			aria-label="Chiudi dettaglio"
			@click="$emit('close')">
			<UIcon name="mdi:close" class="w-[18px] h-[18px]" />
		</button>
	</header>
</template>
