<!-- AdminTableLayout.vue — Layout unificato per liste admin. -->
<script setup>
defineProps({
	items: { type: Array, required: true },
	columns: { type: Array, required: true },
	rowKey: { type: String, default: 'id' },
});
</script>

<template>
	<div class="w-full">
		<!-- Stato vuoto -->
		<div v-if="!items?.length" class="py-10 px-6 text-center">
			<slot name="empty">
				<p class="text-sm text-brand-text-muted">Nessun elemento da mostrare.</p>
			</slot>
		</div>

		<template v-else>
			<!-- Mobile: cards (< 720px) -->
			<div class="flex flex-col gap-3 tablet:hidden">
				<slot
					v-for="item in items"
					:key="item[rowKey]"
					name="mobile-card"
					:item="item" />
			</div>

			<!-- Desktop: table (>= 720px) -->
			<div class="hidden tablet:block w-full overflow-x-auto">
				<table class="w-full border-collapse">
					<thead>
						<tr>
							<th
								v-for="col in columns"
								:key="col.key"
								:style="col.width ? { width: col.width } : null"
								class="text-[0.6875rem] font-bold uppercase tracking-wider text-brand-text-muted px-3 py-2.5 text-left">
								{{ col.label }}
							</th>
						</tr>
					</thead>
					<tbody>
						<slot
							v-for="item in items"
							:key="item[rowKey]"
							name="desktop-row"
							:item="item" />
					</tbody>
				</table>
			</div>
		</template>
	</div>
</template>
