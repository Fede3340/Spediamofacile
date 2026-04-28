<script setup>
defineProps({
  open: { type: Boolean, default: false },
  menuTopPx: { type: Number, default: 80 },
  navLinks: { type: Array, required: true },
  authUiPending: { type: Boolean, default: false },
  showAuthenticatedUi: { type: Boolean, default: false },
  mobileAccountButtonLabel: { type: String, default: 'Accedi o Registrati' },
  isNavActiveFn: { type: Function, required: true },
})

const emit = defineEmits(['close', 'open-auth', 'open-register'])
</script>

<template>
  <ClientOnly>
    <Teleport to="body">
      <!-- Backdrop — prototype: bg-black/20 backdrop-blur-[2px] -->
      <Transition name="backdrop-fade">
        <div
          v-if="open"
          class="lg:hidden fixed inset-0 z-[9998] navbar-mobile-backdrop"
          @click="emit('close')"
        ></div>
      </Transition>

      <!-- Menu panel — prototype: absolute, mx-[12px], rounded-[18px], slide+fade -->
      <Transition name="mobile-slide">
        <div
          v-if="open"
          class="lg:hidden fixed left-[12px] right-[12px] z-[9999] bg-white overflow-hidden navbar-mobile-panel"
          :style="{ top: menuTopPx + 'px' }"
        >
          <!-- Nav links -->
          <nav class="py-[6px]">
            <ul class="flex flex-col">
              <li v-for="nav in navLinks" :key="nav.page">
                <NuxtLink
                  :to="nav.to || nav.page"
                  active-class="" exact-active-class=""
                  class="navbar-mobile-menu-link"
                  :class="isNavActiveFn(nav.page) ? 'navbar-mobile-menu-link--active' : ''"
                  @click="emit('close')"
                >
                  <!-- Icon box — prototype: w-[36px] h-[36px] rounded-[10px] -->
                  <div
                    class="navbar-mobile-menu-link__icon"
                    :class="isNavActiveFn(nav.page) ? 'navbar-mobile-menu-link__icon--active' : ''"
                  >
                    <svg v-if="nav.icon === 'truck'" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    <svg v-else-if="nav.icon === 'price'" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    <svg v-else-if="nav.icon === 'book'" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    <svg v-else-if="nav.icon === 'tracking'" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                  </div>
                  <span class="flex-1">{{ nav.text }}</span>
                  <!-- Chevron right -->
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="navbar-mobile-menu-link__chevron shrink-0"><path d="m9 18 6-6-6-6"/></svg>
                </NuxtLink>
              </li>
            </ul>
          </nav>

          <!-- Separator — prototype: border-t border-[#f0f0f0] -->
          <div class="navbar-mobile-menu-separator mx-0"></div>

          <!-- Auth buttons — prototype: px-[16px] py-[12px] -->
          <div class="px-[16px] py-[12px]">
            <template v-if="authUiPending">
              <div class="flex flex-col gap-[8px]">
                <div class="navbar-mobile-btn-login opacity-0 pointer-events-none select-none" aria-hidden="true">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  Accedi o Registrati
                </div>
              </div>
            </template>
            <template v-else-if="!showAuthenticatedUi">
              <div class="flex flex-col gap-[8px]">
                <button
                  type="button"
                  class="navbar-mobile-btn-login"
                  @click="emit('open-auth')"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  Accedi o Registrati
                </button>
              </div>
            </template>
            <template v-else>
              <div class="flex flex-col gap-[8px]">
                <NuxtLink
                  to="/account"
                  active-class="" exact-active-class=""
                  class="navbar-mobile-btn-login"
                  @click="emit('close')"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                  Il mio account
                </NuxtLink>
              </div>
            </template>
          </div>
        </div>
      </Transition>
    </Teleport>
  </ClientOnly>
</template>
