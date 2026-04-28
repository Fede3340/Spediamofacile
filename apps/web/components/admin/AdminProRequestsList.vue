<!--
  Lista richieste Partner Pro per la pagina admin utenti.
-->
<script setup>
const props = defineProps({
	requests: { type: Array, default: () => [] },
	proRequestStatusConfig: { type: Object, default: () => ({}) },
	actionLoading: { type: [String, Number, null], default: null },
	formatDate: { type: Function, required: true },
});

const emit = defineEmits(['approve', 'reject']);
</script>

<template>
	<div class="rounded-[16px] p-[20px] tablet:p-[24px] desktop:p-[32px] border border-[var(--color-brand-border)] overflow-hidden">
		<h2 class="text-[1.125rem] font-bold text-[var(--color-brand-text)] mb-[20px]">Richieste Partner Pro</h2>

		<div v-if="!requests?.length" class="text-center py-[28px] text-[var(--color-brand-text-secondary)]">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[40px] h-[40px] text-[var(--color-brand-text-muted)] mx-auto mb-[12px]" fill="currentColor"><path d="M12,17.27L18.18,21L16.54,13.97L22,9.24L14.81,8.62L12,2L9.19,8.62L2,9.24L7.45,13.97L5.82,21L12,17.27Z"/></svg>
			<p>Nessuna richiesta Partner Pro.</p>
		</div>

		<div v-else class="space-y-[12px]">
			<div v-for="pr in requests" :key="pr.id" class="rounded-[12px] border-[1.5px] border-[#DFE2E7] bg-white p-[16px] tablet:p-[18px] transition-colors hover:border-[#D0D7DA]">
				<div class="flex flex-col gap-[16px] desktop:flex-row desktop:items-start desktop:justify-between">
					<div class="flex-1">
						<div class="flex flex-wrap items-center gap-[10px] mb-[6px]">
							<span class="text-[0.9375rem] font-bold text-[var(--color-brand-text)]">{{ pr.user?.name }} {{ pr.user?.surname }}</span>
							<span :class="['inline-flex items-center gap-[4px] px-[10px] py-[3px] rounded-full text-[0.6875rem] font-medium', proRequestStatusConfig[pr.status]?.bg || 'bg-gray-50', proRequestStatusConfig[pr.status]?.text || 'text-gray-700']">
								<svg v-if="pr.status === 'pending'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[12px] h-[12px]" fill="currentColor"><path d="M12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22C6.47,22 2,17.5 2,12A10,10 0 0,1 12,2M12.5,7V12.25L17,14.92L16.25,16.15L11,13V7H12.5Z"/></svg>
								<svg v-else-if="pr.status === 'approved'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[12px] h-[12px]" fill="currentColor"><path d="M12 2C6.5 2 2 6.5 2 12S6.5 22 12 22 22 17.5 22 12 17.5 2 12 2M10 17L5 12L6.41 10.59L10 14.17L17.59 6.58L19 8L10 17Z"/></svg>
								<svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[12px] h-[12px]" fill="currentColor"><path d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M12,4A8,8 0 0,0 4,12C4,13.85 4.63,15.55 5.68,16.91L16.91,5.68C15.55,4.63 13.85,4 12,4M12,20A8,8 0 0,0 20,12C20,10.15 19.37,8.45 18.32,7.09L7.09,18.32C8.45,19.37 10.15,20 12,20Z"/></svg>
								{{ proRequestStatusConfig[pr.status]?.label || pr.status }}
							</span>
						</div>
						<p class="text-[0.8125rem] text-[var(--color-brand-text-secondary)] break-all">{{ pr.user?.email }}</p>
						<div class="mt-[10px] grid grid-cols-1 gap-[8px] tablet:grid-cols-2">
							<div v-if="pr.company_name" class="rounded-[16px] bg-[#F8FAFB] px-[12px] py-[10px] text-[0.8125rem]">
								<span class="text-[var(--color-brand-text-secondary)]">Azienda:</span>
								<span class="text-[var(--color-brand-text)] font-medium ml-[4px]">{{ pr.company_name }}</span>
							</div>
							<div v-if="pr.vat_number" class="rounded-[16px] bg-[#F8FAFB] px-[12px] py-[10px] text-[0.8125rem]">
								<span class="text-[var(--color-brand-text-secondary)]">P.IVA:</span>
								<span class="font-mono text-[var(--color-brand-text)] ml-[4px]">{{ pr.vat_number }}</span>
							</div>
						</div>
						<div v-if="pr.message" class="mt-[8px] bg-[#F5F6F9] rounded-[16px] p-[12px]">
							<p class="text-[0.8125rem] text-[var(--color-brand-text)]">{{ pr.message }}</p>
						</div>
						<p class="text-[0.75rem] text-[var(--color-brand-text-secondary)] mt-[6px]">Richiesta: {{ formatDate(pr.created_at) }}</p>
					</div>

					<div v-if="pr.status === 'pending'" class="flex flex-col gap-[8px] shrink-0 tablet:flex-row desktop:min-w-[220px] desktop:justify-end">
						<button @click="emit('approve', pr.id)" :disabled="actionLoading === `pro-${pr.id}`" class="inline-flex min-h-[42px] items-center justify-center gap-[4px] px-[16px] py-[8px] bg-[#095866] hover:bg-[#07404a] text-white rounded-[16px] text-[0.8125rem] font-medium transition-colors cursor-pointer disabled:opacity-50">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-[16px] h-[16px]" fill="currentColor"><path d="M21,7L9,19L3.5,13.5L4.91,12.09L9,16.17L19.59,5.59L21,7Z"/></svg> {{ actionLoading === `pro-${pr.id}` ? '...' : 'Approva' }}
						</button>
						<button @click="emit('reject', pr.id)" :disabled="actionLoading === `pro-${pr.id}`" class="inline-flex min-h-[42px] items-center justify-center px-[16px] py-[8px] bg-red-50 hover:bg-red-100 text-red-700 rounded-[16px] text-[0.8125rem] font-medium transition-colors cursor-pointer border border-red-200 disabled:opacity-50">
							Rifiuta
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>
