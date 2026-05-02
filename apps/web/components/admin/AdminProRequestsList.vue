<!-- Lista richieste Partner Pro per la pagina admin utenti. -->
<script setup>
defineProps({
	requests: { type: Array, default: () => [] },
	proRequestStatusConfig: { type: Object, default: () => ({}) },
	actionLoading: { type: [String, Number, null], default: null },
	formatDate: { type: Function, required: true },
});

const emit = defineEmits(['approve', 'reject']);
</script>

<template>
	<div class="rounded-card p-5 tablet:p-6 desktop:p-8 border border-brand-border bg-brand-card overflow-hidden">
		<h2 class="text-lg font-bold text-brand-text mb-5">Richieste Partner Pro</h2>

		<SfEmptyState
			v-if="!requests?.length"
			icon="mdi:star-outline"
			title="Nessuna richiesta Partner Pro"
			variant="centered" />

		<div v-else class="space-y-3">
			<div v-for="pr in requests" :key="pr.id" class="rounded-card border border-brand-border bg-brand-card p-4 tablet:p-5 transition-colors hover:border-brand-primary/40">
				<div class="flex flex-col gap-4 desktop:flex-row desktop:items-start desktop:justify-between">
					<div class="flex-1">
						<div class="flex flex-wrap items-center gap-2.5 mb-1.5">
							<span class="text-base font-bold text-brand-text">{{ pr.user?.name }} {{ pr.user?.surname }}</span>
							<span :class="['inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[0.6875rem] font-medium', proRequestStatusConfig[pr.status]?.bg || 'bg-brand-bg-alt', proRequestStatusConfig[pr.status]?.text || 'text-brand-text-secondary']">
								<UIcon v-if="pr.status === 'pending'" name="mdi:clock-outline" class="w-3 h-3" />
								<UIcon v-else-if="pr.status === 'approved'" name="mdi:check-circle" class="w-3 h-3" />
								<UIcon v-else name="mdi:close-circle" class="w-3 h-3" />
								{{ proRequestStatusConfig[pr.status]?.label || pr.status }}
							</span>
						</div>
						<p class="text-sm text-brand-text-secondary break-all">{{ pr.user?.email }}</p>
						<div class="mt-2.5 grid grid-cols-1 gap-2 tablet:grid-cols-2">
							<div v-if="pr.company_name" class="rounded-card bg-brand-bg-alt px-3 py-2.5 text-sm">
								<span class="text-brand-text-secondary">Azienda:</span>
								<span class="text-brand-text font-medium ml-1">{{ pr.company_name }}</span>
							</div>
							<div v-if="pr.vat_number" class="rounded-card bg-brand-bg-alt px-3 py-2.5 text-sm">
								<span class="text-brand-text-secondary">P.IVA:</span>
								<span class="font-mono text-brand-text ml-1">{{ pr.vat_number }}</span>
							</div>
						</div>
						<div v-if="pr.message" class="mt-2 bg-brand-bg-alt rounded-card p-3">
							<p class="text-sm text-brand-text">{{ pr.message }}</p>
						</div>
						<p class="text-xs text-brand-text-secondary mt-1.5">Richiesta: {{ formatDate(pr.created_at) }}</p>
					</div>

					<div v-if="pr.status === 'pending'" class="flex flex-col gap-2 shrink-0 tablet:flex-row desktop:min-w-[220px] desktop:justify-end">
						<SfButton :loading="actionLoading === `pro-${pr.id}`" :disabled="actionLoading === `pro-${pr.id}`" size="sm" @click="emit('approve', pr.id)">
							<template #leading>
								<UIcon name="mdi:check" class="w-4 h-4" />
							</template>
							Approva
						</SfButton>
						<SfButton variant="danger" :disabled="actionLoading === `pro-${pr.id}`" size="sm" @click="emit('reject', pr.id)">
							Rifiuta
						</SfButton>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>
