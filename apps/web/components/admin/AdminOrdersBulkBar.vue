<!--
  AdminOrdersBulkBar.vue — Toolbar azioni multiple per ordini admin.
  Visibile solo quando almeno un ordine è selezionato.
  Azioni supportate: marca pagato, esporta CSV selezione, scarica fatture ZIP, deseleziona tutto.
-->
<script setup>
const props = defineProps({
	selectedCount: { type: Number, default: 0 },
	totalCount: { type: Number, default: 0 },
	loadingAction: { type: String, default: '' },
	// Ordini realmente selezionati (oggetti) per abilitare/disabilitare azioni
	selectedOrders: { type: Array, default: () => [] },
});

const emit = defineEmits(['mark-paid', 'export-csv', 'download-invoices', 'clear-selection']);

const canMarkPaidCount = computed(() =>
	props.selectedOrders.filter(o => o.status === 'pending_transfer' || o.status === 'awaiting_bank_transfer').length,
);

const isLoading = (action) => props.loadingAction === action;
</script>

<template>
	<Transition
		enter-active-class="transition duration-200 ease-out"
		enter-from-class="opacity-0 -translate-y-1"
		enter-to-class="opacity-100 translate-y-0"
		leave-active-class="transition duration-150 ease-in"
		leave-from-class="opacity-100 translate-y-0"
		leave-to-class="opacity-0 -translate-y-1">
		<div
			v-if="selectedCount > 0"
			class="sticky top-2 z-20 mb-3 flex flex-col tablet:flex-row tablet:items-center tablet:justify-between gap-3 rounded-card border border-brand-primary/30 bg-brand-primary/[0.06] px-4 py-3 shadow-sf-sm backdrop-blur"
			role="region"
			aria-label="Barra azioni multiple ordini">
			<div class="flex items-center gap-3">
				<div class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-brand-primary/15 text-brand-primary">
					<UIcon name="mdi:check-circle" class="h-5 w-5" />
				</div>
				<div>
					<p class="text-sm font-bold text-brand-text">
						{{ selectedCount }} {{ selectedCount === 1 ? 'ordine selezionato' : 'ordini selezionati' }}
					</p>
					<p class="text-xs text-brand-text-secondary">
						<span v-if="canMarkPaidCount > 0">
							{{ canMarkPaidCount }} con bonifico in attesa ·
						</span>
						<span>{{ totalCount }} ordini totali in vista</span>
					</p>
				</div>
			</div>

			<div class="flex flex-wrap items-center gap-2">
				<SfButton
					v-if="canMarkPaidCount > 0"
					variant="secondary"
					size="sm"
					:loading="isLoading('mark-paid')"
					@click="emit('mark-paid')">
					<template #leading><UIcon name="mdi:check" class="h-4 w-4" /></template>
					Marca pagati ({{ canMarkPaidCount }})
				</SfButton>
				<SfButton
					variant="secondary"
					size="sm"
					:loading="isLoading('export-csv')"
					@click="emit('export-csv')">
					<template #leading><UIcon name="mdi:download" class="h-4 w-4" /></template>
					Esporta CSV
				</SfButton>
				<SfButton
					variant="secondary"
					size="sm"
					:loading="isLoading('download-invoices')"
					@click="emit('download-invoices')">
					<template #leading><UIcon name="mdi:file-document-multiple-outline" class="h-4 w-4" /></template>
					Fatture ZIP
				</SfButton>
				<SfButton variant="ghost" size="sm" @click="emit('clear-selection')">
					Deseleziona
				</SfButton>
			</div>
		</div>
	</Transition>
</template>
