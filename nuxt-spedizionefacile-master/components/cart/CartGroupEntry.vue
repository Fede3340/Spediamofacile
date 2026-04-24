<script setup>
import { buildShipmentFlowEditLocation } from '~/utils/shipment'

const props = defineProps({
  entry: { type: Object, required: true },
  expanded: { type: Boolean, default: false },
  formatPrice: { type: Function, required: true },
  unitPrice: { type: Function, required: true },
  getPackageIcon: { type: Function, required: true },
  quantityButtonClass: { type: String, default: '' },
})

const emit = defineEmits(['toggle', 'update-quantity', 'delete'])

const firstItem = computed(() => props.entry.items[0])
const toEditLocation = (itemId) => buildShipmentFlowEditLocation(itemId)
</script>

<template>
  <div class="bg-[#F5F6F9] rounded-[16px] ring-[1.5px] ring-[#DFE2E7] overflow-hidden transition-all duration-300 hover:-translate-y-[4px] hover:ring-[var(--color-brand-primary)] hover:shadow-[0_8px_24px_rgba(9,88,102,0.12)]" style="box-shadow: 0 1px 4px rgba(0,0,0,0.03)">

    <!-- Group header (clickable) -->
    <button
      type="button"
      @click="emit('toggle')"
      class="w-full flex items-start gap-[14px] p-[16px] sm:p-[18px] hover:bg-[rgba(9,88,102,0.03)] transition cursor-pointer text-left"
    >
      <!-- Icon box with group color accent -->
      <div class="w-[48px] h-[48px] rounded-[12px] bg-[#F8F9FB] ring-[1px] ring-[#DFE2E7] flex items-center justify-center shrink-0 relative">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" :stroke="entry.color" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 3h5v5"/><path d="M4 20L21 3"/><path d="M21 16v5h-5"/><path d="M15 15l6 6"/><path d="M4 4l5 5"/></svg>
        <!-- Colli count dot -->
        <span class="absolute -top-[4px] -right-[4px] w-[20px] h-[20px] rounded-full flex items-center justify-center text-white text-[10px]"
          :style="{ backgroundColor: entry.color, fontWeight: 700 }">
          {{ entry.items.length }}
        </span>
      </div>

      <!-- Content -->
      <div class="flex-1 min-w-0">
        <!-- Route -->
        <div class="flex items-center gap-[6px] mb-[2px] flex-wrap">
          <span class="text-[var(--color-brand-text)] text-[15px] sm:text-[16px]" style="font-weight: 700">{{ firstItem?.origin_address?.city || 'Partenza' }}</span>
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--color-brand-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
          <span class="text-[var(--color-brand-text)] text-[15px] sm:text-[16px]" style="font-weight: 700">{{ firstItem?.destination_address?.city || 'Destinazione' }}</span>
          <!-- BRT pill -->
          <span class="text-[var(--color-brand-text-secondary)] text-[13px] px-[6px] py-[1px] rounded-full ring-[1px] ring-[#DFE2E7] bg-[#FAFBFC] shrink-0 ml-[2px]" style="font-weight: 600">
            {{ firstItem?.services?.service_type?.split(',')[0]?.trim() || 'BRT' }}
          </span>
        </div>

        <!-- Badges row -->
        <div class="flex items-center gap-[6px] mt-[4px] flex-wrap">
          <span class="inline-flex items-center px-[8px] py-[2px] rounded-full bg-[#F8F9FB] ring-[1px] ring-[#DFE2E7] text-[12px] text-[var(--color-brand-text-secondary)]" style="font-weight: 600">
            {{ entry.items.length }} colli
          </span>
          <span class="inline-flex items-center gap-[3px] px-[8px] py-[2px] rounded-full text-[11px]"
            :style="{ backgroundColor: entry.color + '14', color: entry.color, fontWeight: 600 }">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 3h5v5"/><path d="M4 20L21 3"/><path d="M21 16v5h-5"/><path d="M15 15l6 6"/><path d="M4 4l5 5"/></svg>
            Spedizione unica
          </span>
        </div>

        <!-- Addresses -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-[2px] sm:gap-[12px] mt-[6px] text-[13px] text-[var(--color-brand-text-secondary)]">
          <span class="flex items-center gap-[4px]">
            <span class="w-[5px] h-[5px] rounded-full bg-[var(--color-brand-primary)] shrink-0"></span>
            {{ firstItem?.origin_address?.name || 'Mittente' }} &ndash; {{ firstItem?.origin_address?.city || 'N/D' }}
          </span>
          <span class="flex items-center gap-[4px]">
            <span class="w-[5px] h-[5px] rounded-full bg-[var(--color-brand-primary)] shrink-0"></span>
            {{ firstItem?.destination_address?.name || 'Destinatario' }} &ndash; {{ firstItem?.destination_address?.city || 'N/D' }}
          </span>
        </div>
      </div>

      <!-- Price + chevron -->
      <div class="flex items-center gap-[8px] shrink-0">
        <div class="text-right">
          <p class="text-[var(--color-brand-text)] text-[17px] tracking-tight" style="font-weight: 800">{{ formatPrice(entry.totalCents) }}</p>
          <p class="text-[11px] text-[var(--color-brand-text-muted)]">totale</p>
        </div>
        <div class="w-[32px] h-[32px] rounded-full bg-[#F8F9FB] ring-[1px] ring-[#DFE2E7] flex items-center justify-center transition-transform" :class="{ 'rotate-180': expanded }">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#777" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
      </div>
    </button>

    <!-- Expanded: individual parcels -->
    <div v-if="expanded" class="px-[16px] sm:px-[18px] pb-[16px]">
      <div class="border-t border-[#DFE2E7] pt-[12px] space-y-[8px]">
        <div
          v-for="(item, pIdx) in entry.items"
          :key="item.id"
          class="flex items-start gap-[14px] p-[12px] rounded-[10px]"
          :class="pIdx % 2 === 0 ? 'bg-[#F8F9FB]' : 'bg-white'"
        >
          <!-- Package icon -->
          <div class="w-[36px] h-[36px] rounded-[10px] bg-white ring-[1px] ring-[#DFE2E7] flex items-center justify-center shrink-0">
            <NuxtImg :src="getPackageIcon(item)" :alt="item.package_type || 'Tipo collo'" width="20" height="20" loading="lazy" decoding="async" class="w-[20px] h-[20px] object-contain" />
          </div>

          <!-- Info -->
          <div class="flex-1 min-w-0">
            <p class="text-[13px] text-[var(--color-brand-text)]" style="font-weight: 600">
              Collo {{ pIdx + 1 }}
              <span class="text-[var(--color-brand-text-muted)] ml-[4px]" style="font-weight: 400">{{ item.package_type || 'Pacco' }}</span>
            </p>
            <div class="flex items-center gap-[4px] mt-[3px] flex-wrap">
              <span class="inline-flex items-center px-[6px] py-[1px] rounded-full bg-white ring-[1px] ring-[#DFE2E7] text-[11px] text-[var(--color-brand-text-secondary)]" style="font-weight: 500">{{ item.weight }} kg</span>
              <span class="inline-flex items-center px-[6px] py-[1px] rounded-full bg-white ring-[1px] ring-[#DFE2E7] text-[11px] text-[var(--color-brand-text-secondary)]" style="font-weight: 500">{{ item.first_size }}&times;{{ item.second_size }}&times;{{ item.third_size }} cm</span>
            </div>
          </div>

          <!-- Price -->
          <div class="text-right shrink-0 min-w-[60px]">
            <span v-if="(item.quantity || 1) > 1" class="block text-[10px] text-[var(--color-brand-text-muted)]">{{ formatPrice(unitPrice(item)) }}/cad</span>
            <span class="text-[14px] text-[var(--color-brand-text)]" style="font-weight: 700">{{ formatPrice(item.single_price) }}</span>
          </div>

          <!-- Quantity + actions -->
          <div class="flex items-center gap-[6px] shrink-0">
            <!-- Quantity stepper -->
            <div class="flex items-center gap-[1px] bg-[#F8F9FB] rounded-full ring-[1px] ring-[#DFE2E7]">
              <button
                type="button"
                aria-label="Diminuisci quantità"
                @click="emit('update-quantity', item.id, (item.quantity || 1) - 1)"
                :disabled="(item.quantity || 1) <= 1"
                class="w-[28px] h-[28px] flex items-center justify-center cursor-pointer hover:bg-[rgba(9,88,102,0.08)] rounded-l-full transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
              >
                <svg aria-hidden="true" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#777" stroke-width="2" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/></svg>
              </button>
              <span class="w-[22px] text-center text-[11px] text-[var(--color-brand-text)]" style="font-weight: 700">{{ item.quantity || 1 }}</span>
              <button
                type="button"
                aria-label="Aumenta quantità"
                @click="emit('update-quantity', item.id, (item.quantity || 1) + 1)"
                :disabled="(item.quantity || 1) >= 100"
                class="w-[28px] h-[28px] flex items-center justify-center cursor-pointer hover:bg-[rgba(9,88,102,0.08)] rounded-r-full transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
              >
                <svg aria-hidden="true" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#777" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              </button>
            </div>
            <!-- Edit -->
            <NuxtLink
              :to="toEditLocation(item.id)"
              class="w-[28px] h-[28px] rounded-full bg-[#E6E9EE] flex items-center justify-center hover:bg-[#D5D9E0] cursor-pointer transition-colors"
              title="Modifica collo"
              aria-label="Modifica collo"
            >
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#777" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </NuxtLink>
            <!-- Delete -->
            <button
              type="button"
              @click="emit('delete', item.id)"
              class="w-[28px] h-[28px] rounded-full bg-[#E6E9EE] flex items-center justify-center hover:bg-[#FECACA] cursor-pointer transition-colors"
              title="Elimina collo"
              aria-label="Elimina collo"
            >
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Collapsed summary -->
    <div v-else class="px-[16px] sm:px-[18px] pb-[14px] pt-[2px]">
      <p class="text-[12px] text-[var(--color-brand-text-muted)]">
        {{ entry.items.map((i, idx) => `Collo ${idx + 1}: ${i.weight}kg`).join(' | ') }}
      </p>
    </div>
  </div>
</template>
