<!--
  Componente: ShipmentDetailModal
  Modale dettaglio spedizione con indirizzi, collo, servizi e importo.
  Consolidato 2026-04-20: wrapper sottile su <SfModal> (primitive SF).
  API esterna invariata: props { open, detailItem, formatPrice } / emit 'update:open'.
-->
<script setup>
const props = defineProps({
	open: { type: Boolean, required: true },
	detailItem: { type: Object, default: null },
	formatPrice: { type: Function, required: true },
});

const emit = defineEmits(['update:open']);
</script>

<template>
	<SfModal
		:model-value="open"
		size="md"
		hide-close
		@update:model-value="(v) => emit('update:open', v)">
		<section class="sf-modal-content" aria-labelledby="detail-modal-title">
			<div class="sf-modal-header">
				<div class="sf-modal-header__main">
					<div class="sf-modal-icon" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M3,4A2,2 0 0,0 1,6V17H3A3,3 0 0,0 6,20A3,3 0 0,0 9,17H15A3,3 0 0,0 18,20A3,3 0 0,0 21,17H23V12L20,8H17V4M10,6L14,10L10,14V11H4V9H10M17,9.5H19.5L21.47,12H17M6,15.5A1.5,1.5 0 0,1 7.5,17A1.5,1.5 0 0,1 6,18.5A1.5,1.5 0 0,1 4.5,17A1.5,1.5 0 0,1 6,15.5M18,15.5A1.5,1.5 0 0,1 19.5,17A1.5,1.5 0 0,1 18,18.5A1.5,1.5 0 0,1 16.5,17A1.5,1.5 0 0,1 18,15.5Z"/></svg>
					</div>
					<div>
						<h3 id="detail-modal-title" class="sf-modal-title">Dettagli spedizione</h3>
						<p class="sf-modal-description">Riepilogo completo di tratta, indirizzi, collo e servizi della spedizione selezionata.</p>
					</div>
				</div>
				<button type="button" @click="emit('update:open', false)" class="sf-modal-close" aria-label="Chiudi dettagli spedizione">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
				</button>
			</div>
			<div class="sf-modal-divider" />
			<div v-if="detailItem" class="sf-modal-body space-y-[16px] pb-[24px]">
				<div class="bg-[#F8F9FB] rounded-[16px] p-[16px]">
					<h4 class="text-[0.75rem] font-bold text-[var(--color-brand-text-secondary)] uppercase tracking-wider mb-[8px]">Partenza</h4>
					<p class="text-[0.9375rem] font-semibold text-[var(--color-brand-text)]">{{ detailItem.origin_address?.name }}</p>
					<p class="text-[0.8125rem] text-[var(--color-brand-text)]">{{ detailItem.origin_address?.address }} {{ detailItem.origin_address?.address_number }}</p>
					<p class="text-[0.8125rem] text-[var(--color-brand-text)]">{{ detailItem.origin_address?.postal_code }} {{ detailItem.origin_address?.city }} <span v-if="detailItem.origin_address?.province">({{ detailItem.origin_address?.province }})</span></p>
					<p v-if="detailItem.origin_address?.telephone_number" class="text-[0.75rem] text-[var(--color-brand-text-secondary)] mt-[4px]">Tel: {{ detailItem.origin_address?.telephone_number }}</p>
				</div>
				<div class="bg-[#F8F9FB] rounded-[16px] p-[16px]">
					<h4 class="text-[0.75rem] font-bold text-[var(--color-brand-text-secondary)] uppercase tracking-wider mb-[8px]">Destinazione</h4>
					<p class="text-[0.9375rem] font-semibold text-[var(--color-brand-text)]">{{ detailItem.destination_address?.name }}</p>
					<p class="text-[0.8125rem] text-[var(--color-brand-text)]">{{ detailItem.destination_address?.address }} {{ detailItem.destination_address?.address_number }}</p>
					<p class="text-[0.8125rem] text-[var(--color-brand-text)]">{{ detailItem.destination_address?.postal_code }} {{ detailItem.destination_address?.city }} <span v-if="detailItem.destination_address?.province">({{ detailItem.destination_address?.province }})</span></p>
					<p v-if="detailItem.destination_address?.telephone_number" class="text-[0.75rem] text-[var(--color-brand-text-secondary)] mt-[4px]">Tel: {{ detailItem.destination_address?.telephone_number }}</p>
				</div>
				<div class="bg-[#F8F9FB] rounded-[16px] p-[16px]">
					<h4 class="text-[0.75rem] font-bold text-[var(--color-brand-text-secondary)] uppercase tracking-wider mb-[8px]">Collo</h4>
					<div class="grid grid-cols-2 gap-[8px] text-[0.8125rem] text-[var(--color-brand-text)]">
						<p><span class="text-[var(--color-brand-text-secondary)]">Tipo:</span> {{ detailItem.package_type }}</p>
						<p><span class="text-[var(--color-brand-text-secondary)]">Quantita:</span> {{ detailItem.quantity }}</p>
						<p><span class="text-[var(--color-brand-text-secondary)]">Peso:</span> {{ detailItem.weight }} kg</p>
						<p><span class="text-[var(--color-brand-text-secondary)]">Dimensioni:</span> {{ detailItem.first_size }}&times;{{ detailItem.second_size }}&times;{{ detailItem.third_size }} cm</p>
					</div>
				</div>
				<div class="bg-[#F8F9FB] rounded-[16px] p-[16px]">
					<h4 class="text-[0.75rem] font-bold text-[var(--color-brand-text-secondary)] uppercase tracking-wider mb-[8px]">Servizi</h4>
					<p class="text-[0.8125rem] text-[var(--color-brand-text)]">{{ detailItem.services?.service_type || 'Standard' }}</p>
					<p v-if="detailItem.services?.date" class="text-[0.75rem] text-[var(--color-brand-text-secondary)] mt-[4px]">Ritiro: {{ detailItem.services.date }}</p>
				</div>
				<div class="bg-[var(--color-brand-primary)]/5 rounded-[16px] p-[16px] flex items-center justify-between">
					<span class="text-[0.875rem] font-bold text-[var(--color-brand-text)]">Importo</span>
					<span class="text-[1.25rem] font-bold text-[var(--color-brand-primary)]">{{ formatPrice(detailItem.single_price) }}</span>
				</div>
			</div>
		</section>
	</SfModal>
</template>
