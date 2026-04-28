<!-- AdminFilterBar.vue — Barra filtri a pill per liste admin. -->
<script setup>
import '~/assets/css/admin.css';

defineProps({
	filters: { type: Array, required: true },
	activeFilter: { type: String, required: true },
	size: { type: String, default: 'md', validator: (v) => ['sm', 'md'].includes(v) },
});

defineEmits(['change']);
</script>

<template>
	<div
		:class="['admin-filter-bar', `admin-filter-bar--${size}`]"
		role="tablist"
		aria-label="Filtri lista">
		<button
			v-for="f in filters"
			:key="f.key"
			type="button"
			role="tab"
			:aria-selected="activeFilter === f.key"
			:class="['admin-filter-bar__pill', { 'admin-filter-bar__pill--active': activeFilter === f.key }]"
			@click="$emit('change', f.key)">
			<span class="admin-filter-bar__label">{{ f.label }}</span>
			<span
				v-if="typeof f.count === 'number'"
				class="admin-filter-bar__count"
				aria-hidden="true">
				{{ f.count }}
			</span>
		</button>
	</div>
</template>

