<script setup lang="ts" generic="T extends Record<string, unknown>">
/**
 * SfTable — tabella unificata con supporto sort, click row, slot custom.
 *
 * Pattern:
 *   <SfTable :rows="orders" :columns="[
 *     { key: 'id', label: 'ID', sortable: true },
 *     { key: 'status', label: 'Stato' },
 *     { key: 'total', label: 'Totale', align: 'right' },
 *   ]" @sort="onSort" @row-click="onRow">
 *     <template #status="{ row }">
 *       <SfStatusPill :status="row.status" />
 *     </template>
 *   </SfTable>
 */

interface Column {
	key: string;
	label: string;
	sortable?: boolean;
	align?: 'left' | 'center' | 'right';
	width?: string;
}

interface Props {
	rows: T[];
	columns: Column[];
	/** Stato sort attuale (`{ key, direction: 'asc' | 'desc' }`). */
	sort?: { key: string; direction: 'asc' | 'desc' } | null;
	loading?: boolean;
	emptyTitle?: string;
	emptyDescription?: string;
	emptyIcon?: string;
	/** Cursor pointer + emit row-click. */
	hoverable?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
	sort: null,
	loading: false,
	emptyTitle: 'Nessun risultato',
	emptyDescription: '',
	emptyIcon: 'mdi:inbox-outline',
	hoverable: false,
});

const emit = defineEmits<{
	sort: [value: { key: string; direction: 'asc' | 'desc' }];
	'row-click': [row: T, index: number];
}>();

function onHeaderClick(col: Column) {
	if (!col.sortable) return;
	const next: 'asc' | 'desc' = props.sort?.key === col.key && props.sort?.direction === 'asc' ? 'desc' : 'asc';
	emit('sort', { key: col.key, direction: next });
}

function alignClass(col: Column) {
	if (col.align === 'right') return 'text-right';
	if (col.align === 'center') return 'text-center';
	return 'text-left';
}

const isEmpty = computed(() => !props.loading && props.rows.length === 0);
</script>

<template>
	<div class="bg-brand-card border border-brand-border rounded-card overflow-hidden">
		<div class="overflow-x-auto">
			<table class="w-full">
				<thead class="bg-brand-bg-alt border-b border-brand-border">
					<tr>
						<th
							v-for="col in columns"
							:key="col.key"
							scope="col"
							:class="[
								'px-4 py-3 text-xs font-bold uppercase tracking-wide text-brand-text-secondary',
								alignClass(col),
								col.sortable ? 'cursor-pointer select-none hover:text-brand-primary transition' : '',
							]"
							:style="col.width ? { width: col.width } : undefined"
							@click="onHeaderClick(col)"
						>
							<span class="inline-flex items-center gap-1">
								{{ col.label }}
								<template v-if="col.sortable">
									<UIcon
										v-if="sort?.key === col.key"
										:name="sort.direction === 'asc' ? 'mdi:arrow-up' : 'mdi:arrow-down'"
										class="h-3 w-3"
									/>
									<UIcon v-else name="mdi:unfold-more-horizontal" class="h-3 w-3 opacity-40" />
								</template>
							</span>
						</th>
					</tr>
				</thead>

				<tbody>
					<template v-if="loading">
						<tr v-for="n in 4" :key="`skeleton-${n}`" class="border-b border-brand-border last:border-b-0">
							<td v-for="col in columns" :key="col.key" class="px-4 py-3">
								<div class="h-4 bg-brand-bg-alt rounded animate-pulse" />
							</td>
						</tr>
					</template>

					<tr v-else-if="isEmpty">
						<td :colspan="columns.length" class="px-4 py-12">
							<SfEmptyState :icon="emptyIcon" :title="emptyTitle" :description="emptyDescription" variant="centered" />
						</td>
					</tr>

					<tr
						v-for="(row, idx) in rows"
						v-else
						:key="idx"
						:class="[
							'border-b border-brand-border last:border-b-0 transition',
							hoverable ? 'cursor-pointer hover:bg-brand-bg-alt' : '',
						]"
						@click="hoverable && emit('row-click', row, idx)"
					>
						<td
							v-for="col in columns"
							:key="col.key"
							:class="['px-4 py-3 text-sm text-brand-text', alignClass(col)]"
						>
							<slot :name="col.key" :row="row" :value="row[col.key]" :index="idx">
								{{ row[col.key] ?? '' }}
							</slot>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</template>
