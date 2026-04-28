<script setup>
useSeoMeta({
  title: 'Carrello',
  ogTitle: 'Carrello',
  description: 'Rivedi le tue spedizioni nel carrello, modifica quantità e procedi al checkout su SpediamoFacile.',
  ogDescription: 'Rivedi le tue spedizioni nel carrello, modifica quantità e procedi al checkout su SpediamoFacile.',
});

const {
  cart, refresh, status, isAuthenticated,
  promoSettings,
  filterProvenienza, filterRiferimento, filteredCartItems, uniqueCities,
  showDeleteConfirm, deleteLoading, askDelete, confirmDelete,
  showEmptyConfirm, emptyCartLoading, emptyCart,
  formatPrice, unitPrice, formatDate, getPackageIcon,
  quantityUpdating, updateQuantity, quantityButtonClass, quantityButtonCompactClass, quantityButtonMobileClass,
  addressGroups, groupColors, expandedGroups, toggleGroup, isGroupExpanded, displayEntries,
  couponCode, couponMessage, couponApplied, couponDiscount, appliedTotal,
  showCouponField, showCouponPanel, applyCoupon, removeCoupon, displayTotal,
  openCheckoutWithAuthGate,
} = useCarrello();
</script>

<template>
  <div class="cart-page min-h-screen" style="background: #ffffff">

    <!-- ===== CART WITH ITEMS ===== -->
    <div v-if="cart?.data?.length > 0" class="max-w-[1280px] mx-auto px-[14px] sm:px-[40px] pt-[24px] sm:pt-[32px] pb-[80px]">

      <!-- Promo banner -->
      <div v-if="promoSettings?.active && promoSettings?.label_text" class="flex justify-center mb-[16px]">
        <span :style="{ backgroundColor: promoSettings.label_color || '#095866' }"
          class="inline-flex items-center gap-[6px] px-[16px] py-[8px] rounded-full text-white text-[14px] font-bold tracking-wide shadow-sm">
          <img v-if="promoSettings.label_image" :src="promoSettings.label_image" alt="" loading="lazy" decoding="async" width="40" height="20" class="h-[20px] w-auto" />
          {{ promoSettings.label_text }}
        </span>
      </div>

      <!-- Header -->
      <div class="mb-[20px]">
        <h1 class="font-montserrat text-[var(--color-brand-text)] text-[16px] sm:text-[18px] tracking-[-0.3px]" style="font-weight: 800">Carrello</h1>
        <p class="text-[var(--color-brand-text-secondary)] text-[14px] mt-[4px]" style="font-weight: 400">
          {{ cart.data.length }} spedizion{{ cart.data.length === 1 ? 'e' : 'i' }} &middot; Totale {{ displayTotal }}
        </p>
      </div>

      <!-- Grid: items left, sidebar right -->
      <div class="grid grid-cols-1 lg:grid-cols-[1fr_340px] gap-[28px] items-start">

        <!-- LEFT COLUMN: search + items -->
        <div class="flex flex-col gap-[10px]">

          <!-- Toolbar: cerca + filtro provenienza in una sola riga compatta.
               Utile soprattutto per utenti con >=5 spedizioni in carrello (PMI/aziende).
               Su mobile impila verticalmente, da 640px in su affianco. -->
          <div class="flex flex-col sm:flex-row gap-[8px]">
            <!-- Search bar -->
            <div class="relative flex-1">
              <input
                type="text"
                v-model="filterRiferimento"
                placeholder="Cerca spedizione..."
                class="w-full h-[40px] rounded-[12px] bg-white ring-[1.5px] ring-[#DFE2E7] focus:ring-[3px] focus:ring-[var(--color-brand-primary)]/60 pl-[34px] pr-[12px] text-[13px] text-[var(--color-brand-text)] placeholder:text-[var(--color-brand-text-muted)] outline-none transition-all"
                style="font-weight: 500"
              />
              <!-- Search icon -->
              <svg class="absolute left-[12px] top-1/2 -translate-y-1/2 text-[var(--color-brand-text-muted)]" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </div>

            <!-- Filter by city (optional, only shown when multiple cities) -->
            <div v-if="uniqueCities.length > 1" class="relative sm:w-[200px]">
              <select
                v-model="filterProvenienza"
                class="w-full h-[40px] rounded-[12px] bg-white ring-[1.5px] ring-[#DFE2E7] focus:ring-[3px] focus:ring-[var(--color-brand-primary)]/60 pl-[34px] pr-[12px] text-[13px] text-[var(--color-brand-text)] appearance-none cursor-pointer outline-none transition-all"
                style="font-weight: 500"
              >
                <option value="">Tutte le provenienze</option>
                <option v-for="city in uniqueCities" :key="city" :value="city">{{ city }}</option>
              </select>
              <!-- MapPin icon -->
              <svg class="absolute left-[12px] top-1/2 -translate-y-1/2 text-[var(--color-brand-text-muted)] pointer-events-none" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
          </div>

          <!-- Cart entries -->
          <template v-for="(entry, eIdx) in displayEntries" :key="'e-'+eIdx">
            <CartGroupEntry v-if="entry.type === 'group'"
              :entry="entry"
              :expanded="isGroupExpanded(entry.groupIndex)"
              :format-price="formatPrice"
              :unit-price="unitPrice"
              :get-package-icon="getPackageIcon"
              :quantity-button-class="quantityButtonClass"
              @toggle="toggleGroup(entry.groupIndex)"
              @update-quantity="updateQuantity"
              @delete="askDelete"
            />
            <CartSingleEntry v-else
              :entry="entry"
              :format-price="formatPrice"
              :unit-price="unitPrice"
              :get-package-icon="getPackageIcon"
              :quantity-button-compact-class="quantityButtonCompactClass"
              :quantity-button-mobile-class="quantityButtonMobileClass"
              @update-quantity="updateQuantity"
              @delete="askDelete"
            />
          </template>

          <!-- Empty filter result — pattern sf-empty-state compatto -->
          <div v-if="displayEntries.length === 0 && cart?.data?.length > 0" class="sf-empty-state" role="status">
            <div class="sf-empty-state__icon" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
              </svg>
            </div>
            <h3 class="sf-empty-state__title">Nessuna spedizione trovata</h3>
            <p class="sf-empty-state__copy">Nessuna spedizione corrisponde ai filtri attivi. Prova ad allargare la ricerca.</p>
          </div>
        </div>

        <!-- RIGHT COLUMN: sidebar (sticky) -->
        <div class="flex flex-col gap-[12px] lg:sticky lg:top-[24px] relative">

          <!-- Summary card -->
          <CartTotals
            :cart-meta="cart?.meta"
            :coupon-applied="couponApplied"
            :coupon-discount="couponDiscount"
            :applied-total="appliedTotal"
            :display-total="displayTotal"
            :display-entries="displayEntries"
            :coupon-code="couponCode"
            :coupon-message="couponMessage"
            :show-coupon-field="showCouponField"
            :show-coupon-panel="showCouponPanel"
            @toggle-coupon="showCouponField = !showCouponField"
            @apply-coupon="applyCoupon"
            @remove-coupon="removeCoupon"
            @update:coupon-code="couponCode = $event"
          />

          <!-- CTA: Checkout -->
          <SfButton size="lg" block aria-label="Procedi al checkout" @click="openCheckoutWithAuthGate">
            Procedi al checkout
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
          </SfButton>

          <!-- Info sicurezza sotto il CTA -->
          <p class="flex items-center justify-center gap-[6px] text-[12px] text-[var(--color-brand-text-secondary)]" style="font-weight: 500">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--color-brand-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            Pagamento sicuro, crittografato SSL
          </p>

          <!-- Bottom actions -->
          <div class="flex gap-[8px]">
            <SfButton variant="secondary" size="sm" to="/la-tua-spedizione/2?step=colli" class="flex-1" aria-label="Continua a configurare colli">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
              Aggiungi spedizione
            </SfButton>
            <SfButton variant="ghost" size="sm" aria-label="Svuota carrello" @click="showEmptyConfirm = true">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
              Svuota
            </SfButton>
          </div>

          <!-- Salva carrello (solo guest): invito a creare account per persistere -->
          <button
            v-if="!isAuthenticated"
            type="button"
            @click="openCheckoutWithAuthGate('register')"
            class="mt-[4px] inline-flex items-center justify-center gap-[6px] text-[12px] text-[var(--color-brand-primary)] hover:text-[var(--color-brand-primary-hover)] transition-colors"
            style="font-weight: 600"
            aria-label="Salva il carrello creando un account"
          >
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Salva carrello: crea account
          </button>
        </div>
      </div>
    </div>

    <!-- ===== EMPTY CART — pattern sf-empty-state ===== -->
    <div v-else-if="status !== 'pending'" class="min-h-screen flex items-center justify-center px-[20px]">
      <div class="sf-empty-state max-w-[480px] w-full" role="status">
        <div class="sf-empty-state__icon" aria-hidden="true">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
            <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
            <line x1="12" y1="22.08" x2="12" y2="12"/>
          </svg>
        </div>
        <h1 class="sf-empty-state__title">Il tuo carrello è vuoto</h1>
        <p class="sf-empty-state__copy">Calcola un preventivo e aggiungi la tua prima spedizione: bastano pochi secondi.</p>
        <div class="sf-empty-state__actions">
          <NuxtLink to="/preventivo" class="sf-empty-state__cta" aria-label="Calcola preventivo">
            <span>Calcola preventivo</span>
            <svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M5 12h14"/><path d="m13 5 7 7-7 7"/>
            </svg>
          </NuxtLink>
          <NuxtLink to="/servizi" class="sf-empty-state__cta sf-empty-state__cta--ghost" aria-label="Vedi i servizi disponibili">
            <span>Esplora i servizi</span>
          </NuxtLink>
        </div>
      </div>
    </div>

    <!-- ===== LOADING STATE: 3 card skeleton unificate via SfSkeleton ===== -->
    <div v-else class="max-w-[1280px] mx-auto px-[14px] sm:px-[40px] pt-[24px] sm:pt-[32px] pb-[80px]" aria-busy="true" aria-live="polite">
      <div class="mb-[20px]">
        <SfSkeleton variant="title" />
      </div>
      <div class="grid grid-cols-1 lg:grid-cols-[1fr_340px] gap-[28px] items-start">
        <div class="flex flex-col gap-[12px]">
          <div
            v-for="n in 3"
            :key="n"
            class="rounded-[16px] border border-[rgba(9,88,102,0.08)] bg-white px-[18px] py-[16px]">
            <SfSkeleton variant="text-block" />
          </div>
        </div>
        <SfSkeleton variant="card" />
      </div>
    </div>

    <!-- Confirm dialogs -->
    <AccountConfirmDialog v-model:open="showDeleteConfirm" title="Conferma eliminazione"
      description="Sei sicuro di voler rimuovere questa spedizione dal carrello? L'azione non può essere annullata."
      confirm-label="Elimina" :loading="deleteLoading" @confirm="confirmDelete" />

    <AccountConfirmDialog v-model:open="showEmptyConfirm" title="Svuota carrello"
      description="Sei sicuro di voler svuotare tutto il carrello? Tutte le spedizioni verranno rimosse."
      confirm-label="Svuota tutto" :loading="emptyCartLoading" @confirm="emptyCart" />

    <!-- Auth: AuthOverlayModal globale aperto via useAuthModal (vedi useCarrello.openCheckoutWithAuthGate) -->
  </div>
</template>
