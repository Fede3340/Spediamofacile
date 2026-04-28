<!--
  Componente: ShipmentOrderPackageCard
  Mostra i dettagli di un singolo collo nell'ordine: tipo, peso, dimensioni, prezzo,
  indirizzi mittente/destinatario, eventuale punto BRT, e servizio.
-->
<script setup>
const props = defineProps({
	pkg: { type: Object, required: true },
	index: { type: Number, required: true },
	hasPudo: { type: Boolean, default: false },
	formatPrice: { type: Function, required: true },
});
</script>

<template>
	<div class="bg-white rounded-[16px] p-[24px] border border-[var(--color-brand-border)]">
		<h3 class="font-montserrat text-[1rem] font-[800] text-[var(--color-brand-text)] mb-[16px]">Collo #{{ index + 1 }}</h3>

		<div class="grid grid-cols-2 desktop:grid-cols-4 gap-[14px] mb-[16px]">
			<div>
				<p class="text-[0.6875rem] text-[var(--color-brand-text-secondary)] uppercase font-medium mb-[2px]">Tipo</p>
				<p class="text-[0.875rem] text-[var(--color-brand-text)]">{{ pkg.package_type || 'Pacco' }}</p>
			</div>
			<div>
				<p class="text-[0.6875rem] text-[var(--color-brand-text-secondary)] uppercase font-medium mb-[2px]">Peso</p>
				<p class="text-[0.875rem] text-[var(--color-brand-text)]">{{ pkg.weight }} kg</p>
			</div>
			<div>
				<p class="text-[0.6875rem] text-[var(--color-brand-text-secondary)] uppercase font-medium mb-[2px]">Dimensioni</p>
				<p class="text-[0.875rem] text-[var(--color-brand-text)]">{{ pkg.first_size }} x {{ pkg.second_size }} x {{ pkg.third_size }} cm</p>
			</div>
			<div>
				<p class="text-[0.6875rem] text-[var(--color-brand-text-secondary)] uppercase font-medium mb-[2px]">Prezzo</p>
				<p class="text-[0.875rem] text-[var(--color-brand-text)]">{{ formatPrice(pkg.single_price) }}</p>
			</div>
		</div>

		<!-- Mittente -->
		<div v-if="pkg.origin_address" class="bg-[#F8F9FB] rounded-[16px] p-[16px] mb-[10px]">
			<p class="text-[0.75rem] text-[var(--color-brand-text-secondary)] uppercase font-medium mb-[6px]">Mittente</p>
			<p class="text-[0.875rem] font-semibold text-[var(--color-brand-text)]">{{ pkg.origin_address.name }}</p>
			<p class="text-[0.8125rem] text-[var(--color-brand-text-secondary)]">{{ pkg.origin_address.address }} {{ pkg.origin_address.address_number }}</p>
			<p class="text-[0.8125rem] text-[var(--color-brand-text-secondary)]">{{ pkg.origin_address.postal_code }} {{ pkg.origin_address.city }} ({{ pkg.origin_address.province }})</p>
			<p v-if="pkg.origin_address.telephone_number" class="text-[0.8125rem] text-[var(--color-brand-text-secondary)]">Tel: {{ pkg.origin_address.telephone_number }}</p>
		</div>

		<!-- Badge PUDO -->
		<div v-if="hasPudo" class="bg-[var(--color-brand-primary)]/10 rounded-full px-[14px] py-[10px] flex items-center gap-[8px] mb-[10px]">
			<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--color-brand-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
			<span class="text-[0.8125rem] font-bold text-[var(--color-brand-primary)]">Consegna presso Punto BRT</span>
		</div>

		<!-- Destinatario -->
		<div v-if="pkg.destination_address" class="bg-[#F8F9FB] rounded-[16px] p-[16px]">
			<p class="text-[0.75rem] text-[var(--color-brand-text-secondary)] uppercase font-medium mb-[6px]">Destinatario</p>
			<p class="text-[0.875rem] font-semibold text-[var(--color-brand-text)]">{{ pkg.destination_address.name }}</p>
			<p class="text-[0.8125rem] text-[var(--color-brand-text-secondary)]">{{ pkg.destination_address.address }} {{ pkg.destination_address.address_number }}</p>
			<p class="text-[0.8125rem] text-[var(--color-brand-text-secondary)]">{{ pkg.destination_address.postal_code }} {{ pkg.destination_address.city }} ({{ pkg.destination_address.province }})</p>
			<p v-if="pkg.destination_address.telephone_number" class="text-[0.8125rem] text-[var(--color-brand-text-secondary)]">Tel: {{ pkg.destination_address.telephone_number }}</p>
		</div>

		<!-- Servizio -->
		<div v-if="pkg.services" class="mt-[10px] bg-[#F8F9FB] rounded-[16px] p-[16px]">
			<p class="text-[0.75rem] text-[var(--color-brand-text-secondary)] uppercase font-medium mb-[6px]">Servizio</p>
			<p class="text-[0.875rem] text-[var(--color-brand-text)]">{{ pkg.services.service_type || 'Standard' }}</p>
			<p v-if="pkg.services.date" class="text-[0.8125rem] text-[var(--color-brand-text-secondary)]">Data: {{ pkg.services.date }}</p>
		</div>
	</div>
</template>
