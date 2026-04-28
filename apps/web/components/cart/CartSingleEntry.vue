<script setup>
import { buildShipmentFlowEditLocation } from '~/utils/shipment'

defineProps({
  entry: { type: Object, required: true },
  formatPrice: { type: Function, required: true },
  unitPrice: { type: Function, required: true },
  getPackageIcon: { type: Function, required: true },
  quantityButtonCompactClass: { type: String, default: '' },
  quantityButtonMobileClass: { type: String, default: '' },
})

const emit = defineEmits(['update-quantity', 'delete'])

const toEditLocation = (itemId) => buildShipmentFlowEditLocation(itemId)
</script>

<template>
  <div class="bg-[#F5F6F9] rounded-[16px] ring-[1.5px] ring-[#DFE2E7] overflow-hidden transition-all duration-300 hover:-translate-y-[4px] hover:ring-[var(--color-brand-primary)] hover:shadow-[0_8px_24px_rgba(9,88,102,0.12)]" style="box-shadow: 0 1px 4px rgba(0,0,0,0.03)">
    <!-- Desktop layout -->
    <div class="hidden desktop:flex items-center gap-[16px] p-[16px_20px]">
      <div class="w-[44px] h-[44px] rounded-[12px] bg-white ring-[1.5px] ring-[#DFE2E7] flex items-center justify-center shrink-0">
        <NuxtImg :src="getPackageIcon(entry.item)" :alt="entry.item.package_type || 'Tipo collo'" width="28" height="28" loading="lazy" decoding="async" />
      </div>
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-[8px]">
          <span class="text-[0.9375rem] font-semibold text-[var(--color-brand-text)]">{{ entry.item.origin_address?.city || 'Partenza' }}</span>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--color-brand-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
          <span class="text-[0.9375rem] font-semibold text-[var(--color-brand-text)]">{{ entry.item.destination_address?.city || 'Destinazione' }}</span>
        </div>
        <p class="text-[0.8125rem] text-[var(--color-brand-text-muted)] mt-[2px]">
          {{ entry.item.package_type || 'Pacco' }} <span class="mx-[4px]">&middot;</span>
          {{ entry.item.weight }} kg <span class="mx-[4px]">&middot;</span>
          {{ entry.item.first_size }}x{{ entry.item.second_size }}x{{ entry.item.third_size }} cm
        </p>
      </div>
      <span class="text-[0.75rem] text-[var(--color-brand-text-secondary)] bg-white px-[8px] py-[3px] rounded-[8px] ring-[1px] ring-[#DFE2E7] shrink-0" style="font-weight:600">{{ entry.item.services?.service_type?.split(',')[0]?.trim() || 'BRT' }}</span>
      <div class="text-[0.75rem] text-[var(--color-brand-text-secondary)] shrink-0 max-w-[200px]">
        <div class="flex items-center gap-[4px]">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--color-brand-primary)" stroke-width="2" class="shrink-0"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          <span class="truncate">{{ entry.item.origin_address?.name?.split(' ')[0] || '' }} - {{ entry.item.origin_address?.city || '' }}</span>
        </div>
        <div class="flex items-center gap-[4px] mt-[2px]">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--color-brand-primary)" stroke-width="2" class="shrink-0"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
          <span class="truncate">{{ entry.item.destination_address?.name?.split(' ')[0] || '' }} - {{ entry.item.destination_address?.city || '' }}</span>
        </div>
      </div>
      <div class="flex items-center gap-[4px] shrink-0">
        <button type="button" @click="emit('update-quantity', entry.item.id, (entry.item.quantity || 1) - 1)" :disabled="(entry.item.quantity || 1) <= 1" :class="quantityButtonCompactClass">-</button>
        <span class="min-w-[20px] text-center font-semibold text-[0.8125rem] text-[var(--color-brand-text)]">{{ entry.item.quantity || 1 }}</span>
        <button type="button" @click="emit('update-quantity', entry.item.id, (entry.item.quantity || 1) + 1)" :disabled="(entry.item.quantity || 1) >= 100" :class="quantityButtonCompactClass">+</button>
      </div>
      <div class="text-right shrink-0 min-w-[80px]">
        <span v-if="(entry.item.quantity || 1) > 1" class="block text-[0.6875rem] text-[var(--color-brand-text-muted)]">{{ formatPrice(unitPrice(entry.item)) }}/cad</span>
        <span class="text-[0.9375rem] font-bold text-[var(--color-brand-text)]">{{ formatPrice(entry.item.single_price) }}</span>
      </div>
      <div class="flex items-center gap-[8px] shrink-0">
        <NuxtLink :to="toEditLocation(entry.item.id)" class="text-[var(--color-brand-primary)] hover:text-[var(--color-brand-primary-hover)] cursor-pointer" title="Modifica" aria-label="Modifica spedizione">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        </NuxtLink>
        <button type="button" @click="emit('delete', entry.item.id)" class="text-red-500 hover:text-red-600 cursor-pointer" title="Elimina" aria-label="Elimina spedizione">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
        </button>
      </div>
    </div>

    <!-- Mobile layout -->
    <div class="desktop:hidden p-[16px]">
      <div class="flex items-center justify-between mb-[8px]">
        <div class="min-w-0 flex-1 mr-[10px]">
          <p class="text-[0.875rem] font-semibold text-[var(--color-brand-text)] truncate">{{ entry.item.origin_address?.city || 'Partenza' }} &rarr; {{ entry.item.destination_address?.city || 'Destinazione' }}</p>
          <p class="text-[0.75rem] text-[var(--color-brand-text-muted)]">{{ entry.item.weight }} kg &middot; {{ entry.item.first_size }}x{{ entry.item.second_size }}x{{ entry.item.third_size }} cm</p>
        </div>
        <div class="text-right shrink-0">
          <span v-if="(entry.item.quantity || 1) > 1" class="block text-[0.6875rem] text-[var(--color-brand-text-muted)]">{{ formatPrice(unitPrice(entry.item)) }}/cad</span>
          <span class="text-[0.9375rem] font-bold text-[var(--color-brand-text)]">{{ formatPrice(entry.item.single_price) }}</span>
        </div>
      </div>
      <div class="flex items-center justify-between mt-[6px]">
        <div class="flex items-center gap-[8px]">
          <button type="button" @click="emit('update-quantity', entry.item.id, (entry.item.quantity || 1) - 1)" :disabled="(entry.item.quantity || 1) <= 1" :class="quantityButtonMobileClass">-</button>
          <span class="min-w-[24px] text-center font-semibold text-[0.875rem] text-[var(--color-brand-text)]">{{ entry.item.quantity || 1 }}x</span>
          <button type="button" @click="emit('update-quantity', entry.item.id, (entry.item.quantity || 1) + 1)" :disabled="(entry.item.quantity || 1) >= 100" :class="quantityButtonMobileClass">+</button>
        </div>
        <div class="flex items-center gap-[12px]">
          <NuxtLink :to="toEditLocation(entry.item.id)" class="inline-flex items-center gap-[4px] text-[0.8125rem] text-[var(--color-brand-primary)] font-semibold hover:opacity-80 cursor-pointer min-h-[44px] px-[4px]">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Modifica
          </NuxtLink>
          <button type="button" @click="emit('delete', entry.item.id)" class="text-[0.8125rem] text-red-500 font-semibold hover:opacity-80 cursor-pointer min-h-[44px] px-[4px]">Elimina</button>
        </div>
      </div>
    </div>
  </div>
</template>
