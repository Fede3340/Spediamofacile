<!-- AdminTableLayout.vue — Layout unificato per liste admin. -->
<script setup>
import '~/assets/css/admin.css';

defineProps({
	items: { type: Array, required: true },
	columns: { type: Array, required: true },
	rowKey: { type: String, default: 'id' },
});
</script>

<template>
	<div class="admin-table-layout">
		<!-- Stato vuoto -->
		<div v-if="!items?.length" class="admin-table-layout__empty">
			<slot name="empty">
				<p class="admin-table-layout__empty-text">Nessun elemento da mostrare.</p>
			</slot>
		</div>

		<template v-else>
			<!-- Mobile: cards (< 720px) -->
			<div class="admin-table-layout__mobile">
				<slot
					v-for="item in items"
					:key="item[rowKey]"
					name="mobile-card"
					:item="item" />
			</div>

			<!-- Desktop: table (>= 720px) -->
			<div class="admin-table-layout__desktop-wrap">
				<table class="admin-table-layout__desktop">
					<thead>
						<tr>
							<th
								v-for="col in columns"
								:key="col.key"
								:style="col.width ? { width: col.width } : null"
								class="admin-thead-cell">
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

