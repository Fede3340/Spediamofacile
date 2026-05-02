<!-- AdminFilterBar.vue — Barra filtri a pill per liste admin. -->
<script setup>
defineProps({
	filters: { type: Array, required: true },
	activeFilter: { type: String, required: true },
	size: { type: String, default: 'md', validator: (v) => ['sm', 'md'].includes(v) },
});

defineEmits(['change']);
</script>

<template>
	<div
		class="flex flex-wrap items-center gap-2"
		role="tablist"
		aria-label="Filtri lista">
		<button
			v-for="f in filters"
			:key="f.key"
			type="button"
			role="tab"
			:aria-selected="activeFilter === f.key"
			:class="[
				'inline-flex items-center gap-2 rounded-pill border font-semibold transition focus-visible:outline-2 focus-visible:outline-brand-primary',
				size === 'sm' ? 'px-3 py-1 text-xs' : 'px-4 py-1.5 text-sm',
				activeFilter === f.key
					? 'bg-brand-primary text-white border-brand-primary shadow-sf-sm'
					: 'bg-brand-card text-brand-text-secondary border-brand-border hover:bg-brand-bg-alt hover:text-brand-text',
			]"
			@click="$emit('change', f.key)">
			<span>{{ f.label }}</span>
			<span
				v-if="typeof f.count === 'number'"
				:class="[
					'inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full text-xs font-semibold',
					activeFilter === f.key ? 'bg-white/20 text-white' : 'bg-brand-bg-alt text-brand-text-muted',
				]"
				aria-hidden="true">
				{{ f.count }}
			</span>
		</button>
	</div>
</template>
