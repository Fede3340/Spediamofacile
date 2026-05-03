<script setup lang="ts">
/**
 * SfPagination — controllo pagination semplice.
 *
 * Pattern:
 *   <SfPagination v-model="page" :total="total" :per-page="20" />
 */

interface Props {
	modelValue: number;
	total: number;
	perPage?: number;
	/** Numero massimo di link visibili (default 5). */
	maxVisible?: number;
}

const props = withDefaults(defineProps<Props>(), {
	perPage: 20,
	maxVisible: 5,
});

const emit = defineEmits<{ 'update:modelValue': [value: number] }>();

const totalPages = computed(() => Math.max(1, Math.ceil(props.total / props.perPage)));

const visiblePages = computed(() => {
	const pages: (number | 'ellipsis')[] = [];
	const total = totalPages.value;
	const current = props.modelValue;
	const max = props.maxVisible;

	if (total <= max + 2) {
		for (let i = 1; i <= total; i++) pages.push(i);
		return pages;
	}

	pages.push(1);
	const start = Math.max(2, current - Math.floor(max / 2));
	const end = Math.min(total - 1, start + max - 1);
	if (start > 2) pages.push('ellipsis');
	for (let i = start; i <= end; i++) pages.push(i);
	if (end < total - 1) pages.push('ellipsis');
	pages.push(total);
	return pages;
});

function go(page: number) {
	if (page < 1 || page > totalPages.value || page === props.modelValue) return;
	emit('update:modelValue', page);
}
</script>

<template>
	<nav v-if="totalPages > 1" aria-label="Pagination" class="flex items-center justify-center gap-1">
		<button
			type="button"
			:disabled="modelValue === 1"
			class="inline-flex items-center justify-center h-9 w-9 rounded-control border border-brand-border bg-brand-card text-brand-text-secondary transition hover:bg-brand-bg-alt disabled:opacity-40 disabled:cursor-not-allowed"
			aria-label="Precedente"
			@click="go(modelValue - 1)"
		>
			<UIcon name="mdi:chevron-left" class="h-4 w-4" />
		</button>

		<template v-for="(p, idx) in visiblePages" :key="`${p}-${idx}`">
			<span
				v-if="p === 'ellipsis'"
				class="inline-flex items-center justify-center h-9 w-9 text-brand-text-muted"
				aria-hidden="true"
			>…</span>
			<button
				v-else
				type="button"
				:aria-current="p === modelValue ? 'page' : undefined"
				:class="[
					'inline-flex items-center justify-center h-9 w-9 rounded-control text-sm font-semibold transition',
					p === modelValue
						? 'bg-brand-primary text-white shadow-sf-sm'
						: 'border border-brand-border bg-brand-card text-brand-text-secondary hover:bg-brand-bg-alt',
				]"
				@click="go(p)"
			>
				{{ p }}
			</button>
		</template>

		<button
			type="button"
			:disabled="modelValue === totalPages"
			class="inline-flex items-center justify-center h-9 w-9 rounded-control border border-brand-border bg-brand-card text-brand-text-secondary transition hover:bg-brand-bg-alt disabled:opacity-40 disabled:cursor-not-allowed"
			aria-label="Successivo"
			@click="go(modelValue + 1)"
		>
			<UIcon name="mdi:chevron-right" class="h-4 w-4" />
		</button>
	</nav>
</template>
