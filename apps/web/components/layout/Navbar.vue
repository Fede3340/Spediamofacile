<script setup>
const navLinks = [
  { page: '/servizi', text: 'Servizi', icon: 'truck' },
  { page: '/preventivo', to: '/la-tua-spedizione/2?step=colli', text: 'Preventivo', icon: 'price' },
  { page: '/traccia', text: 'Traccia', icon: 'tracking' },
  { page: '/guide', text: 'Guide', icon: 'book' },
  { page: '/contatti', text: 'Contatti', icon: 'message' },
];

const { accountLabel, isAuthenticatedForUi, isAuthUiPending, mobileAccountLabel } = useAuthUiState();
const { openAuthModal } = useAuthStore();
const { cart } = useCartFetch();
const route = useRoute();
const { isAccountRoute, isAuthMinimalShellRoute, isQuoteFlowRoute } = useShellRouteState();

const mobileMenuOpen = ref(false);
const navbarBottomRef = ref(null);
const menuTopPx = ref(0);

const showMobileQuoteCta = computed(() => !isAuthMinimalShellRoute.value && !isQuoteFlowRoute.value && !isAccountRoute.value);
const mobileQuoteTarget = computed(() => (route.path === '/' ? { path: '/', hash: '#preventivo' } : { path: '/preventivo' }));
const cartCount = computed(() => cart?.value?.data?.length || cart?.data?.length || 0);
const isCartActive = computed(() => route.path === '/carrello');
const isAccountActive = computed(() => route.path === '/account' || route.path.startsWith('/account/'));

const normalizeRoutePath = (path) => (!path || path === '/' ? '/' : path.replace(/\/+$/, ''));

// Pill "Preventivo" attivo SOLO durante il flusso (non in homepage che ha CTA hero).
const isPreventivoNavActive = computed(() => {
  const p = normalizeRoutePath(route.path);
  return p === '/preventivo' || p.startsWith('/la-tua-spedizione') || p.startsWith('/riepilogo') || p.startsWith('/checkout');
});

const isNavActive = (page) => {
  if (page === '/preventivo') return isPreventivoNavActive.value;
  const cur = normalizeRoutePath(route.path); const tgt = normalizeRoutePath(page);
  return cur === tgt || cur.startsWith(tgt + '/');
};

const getRequestedPath = () => {
  const r = Array.isArray(route.query.redirect) ? route.query.redirect[0] : route.query.redirect;
  if (['/autenticazione', '/login', '/registrazione'].includes(route.path)) return (typeof r === 'string' && r.startsWith('/')) ? r : '/';
  return route.fullPath;
};

const updateMenuPosition = () => {
  if (navbarBottomRef.value) menuTopPx.value = navbarBottomRef.value.getBoundingClientRect().bottom;
};

const openGuestAuthModal = (tab = 'login') => {
  mobileMenuOpen.value = false;
  openAuthModal({ tab, redirect: getRequestedPath() });
};

watch(mobileMenuOpen, (val) => { if (val) nextTick(() => updateMenuPosition()); });
const stopRouteWatch = watch(() => route.fullPath, () => {
  mobileMenuOpen.value = false;
  if (typeof window !== 'undefined') requestAnimationFrame(updateMenuPosition);
});

onMounted(() => { window.addEventListener('resize', updateMenuPosition); });
onBeforeUnmount(() => { stopRouteWatch(); window.removeEventListener('resize', updateMenuPosition); });
</script>

<template>
  <div class="relative w-full">
    <div
      ref="navbarBottomRef"
      class="relative z-50 flex items-center justify-between"
      :class="isAuthMinimalShellRoute ? 'h-[52px] sm:h-[58px]' : 'h-[56px] sm:h-[64px] lg:h-[70px]'">
      <div class="flex min-w-0 flex-1 items-center gap-[6px] sm:gap-[8px] lg:flex-initial">
        <NuxtLink to="/" class="flex items-center h-full outline-none shrink-0" @click="mobileMenuOpen = false">
          <Logo :is-navbar="true" />
        </NuxtLink>
      </div>

      <nav v-if="!isAuthMinimalShellRoute" class="hidden lg:flex justify-center flex-1">
        <ul class="flex items-center justify-center gap-x-[6px]">
          <li v-for="nav in navLinks" :key="nav.page">
            <NuxtLink
              :to="nav.to || nav.page"
              class="navbar-link-pill"
              :class="isNavActive(nav.page) ? 'navbar-link-pill--active' : ''"
              :aria-current="isNavActive(nav.page) ? 'page' : undefined"
              active-class="" exact-active-class="">
              {{ nav.text }}
            </NuxtLink>
          </li>
        </ul>
      </nav>

      <div v-if="!isAuthMinimalShellRoute" class="flex shrink-0 items-center gap-[8px] sm:gap-[10px]">
        <NuxtLink
          v-if="showMobileQuoteCta"
          :to="mobileQuoteTarget"
          class="inline-flex lg:hidden navbar-mobile-quote"
          @click="mobileMenuOpen = false">
          Preventivo
        </NuxtLink>

        <button
          v-if="isAuthUiPending || !isAuthenticatedForUi"
          type="button"
          class="hidden lg:inline-flex items-center gap-[6px] navbar-login-btn"
          @click="openGuestAuthModal('login')">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          Accedi
        </button>
        <NuxtLink
          v-else
          to="/account"
          class="hidden lg:inline-flex items-center gap-[6px] navbar-login-btn"
          :class="isAccountActive ? 'navbar-login-btn--active' : ''"
          :aria-current="isAccountActive ? 'page' : undefined"
          active-class="" exact-active-class="">
          <div class="navbar-avatar-circle">
            <span class="text-white text-[10px] font-[800]">{{ (accountLabel || '?')[0] }}</span>
          </div>
          {{ accountLabel }}
        </NuxtLink>

        <NuxtLink v-slot="{ href, navigate }" to="/carrello" custom>
          <a
            :href="href"
            class="navbar-cart-btn"
            :class="isCartActive ? 'navbar-cart-btn--active' : ''"
            @click="navigate">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            <span class="hidden sm:inline text-sm tracking-[-0.2px]" style="font-weight: 600">Carrello</span>
            <span v-if="cartCount > 0" class="navbar-cart-badge">{{ cartCount }}</span>
          </a>
        </NuxtLink>

        <button
          type="button"
          class="lg:hidden navbar-hamburger-btn"
          :aria-label="mobileMenuOpen ? 'Chiudi menu' : 'Apri menu'"
          :aria-expanded="mobileMenuOpen"
          @click="mobileMenuOpen = !mobileMenuOpen">
          <div class="navbar-hamburger" :class="{ 'navbar-hamburger--open': mobileMenuOpen }">
            <span class="navbar-hamburger__line navbar-hamburger__line--1"/>
            <span class="navbar-hamburger__line navbar-hamburger__line--2"/>
            <span class="navbar-hamburger__line navbar-hamburger__line--3"/>
          </div>
        </button>
      </div>
    </div>

    <NavbarMobileMenu
      v-if="!isAuthMinimalShellRoute"
      :open="mobileMenuOpen"
      :menu-top-px="menuTopPx"
      :nav-links="navLinks"
      :auth-ui-pending="isAuthUiPending"
      :show-authenticated-ui="isAuthenticatedForUi"
      :mobile-account-button-label="mobileAccountLabel"
      :is-nav-active-fn="isNavActive"
      @close="mobileMenuOpen = false"
      @open-auth="openGuestAuthModal('login')"
      @open-register="openGuestAuthModal('register')"/>
  </div>
</template>

<style scoped>
.navbar-link-pill {
  display: inline-flex; align-items: center; justify-content: center; cursor: pointer;
  height: 42px; padding: 0 16px; border-radius: 9999px;
  background: transparent; color: var(--color-brand-text-secondary);
  font-size: 15px; line-height: 1; letter-spacing: -0.3px; font-weight: 600;
  transition: color var(--sf-t1) var(--sf-ease), background-color var(--sf-t1) var(--sf-ease), border-color var(--sf-t1) var(--sf-ease), box-shadow var(--sf-t1) var(--sf-ease), transform var(--sf-t1) var(--sf-ease);
}
.navbar-link-pill--active {
  position: relative; color: var(--color-brand-primary); font-weight: 700;
  background: var(--interaction-active-teal);
  box-shadow: inset 0 0 0 1.5px var(--interaction-active-teal-border);
}
.navbar-link-pill--active::after {
  content: ''; position: absolute; bottom: 4px; left: 50%; transform: translateX(-50%);
  width: 18px; height: 2px; border-radius: 999px; background: var(--color-brand-accent);
  transition: width var(--sf-t1) var(--sf-ease);
}
.navbar-link-pill:hover:not(.navbar-link-pill--active) { color: var(--color-brand-primary); background: var(--interaction-hover-teal); }
.navbar-link-pill:focus-visible { outline: 3px solid rgba(9, 88, 102, 0.45); outline-offset: 2px; }
.navbar-link-pill:active:not(.navbar-link-pill--active) { background: var(--interaction-press-teal); }

.navbar-login-btn {
  height: 42px; padding: 0 20px; border-radius: 9999px;
  color: var(--color-brand-primary); background: var(--interaction-hover-teal-soft);
  border: 1px solid transparent; font-size: 15px; letter-spacing: -0.2px; font-weight: 600; cursor: pointer;
  transition: color var(--sf-t1) var(--sf-ease), background-color var(--sf-t1) var(--sf-ease), border-color var(--sf-t1) var(--sf-ease), box-shadow var(--sf-t1) var(--sf-ease), transform var(--sf-t1) var(--sf-ease);
}
.navbar-login-btn:hover { background: var(--interaction-hover-teal); border: 1px solid rgba(9, 88, 102, 0.08); box-shadow: 0 1px 3px rgba(9, 88, 102, 0.06); }
.navbar-login-btn:active { background: var(--interaction-active-teal); }
.navbar-login-btn--active, .navbar-login-btn--active:hover {
  background: var(--color-brand-primary, #095866); color: #ffffff;
  border: 1px solid rgba(9, 88, 102, 0.14); box-shadow: 0 2px 8px rgba(9, 88, 102, 0.12); outline: none;
}
.navbar-login-btn--active .navbar-avatar-circle { background: rgba(255, 255, 255, 0.18); border: 1px solid rgba(255, 255, 255, 0.35); }

.navbar-avatar-circle {
  width: 28px; height: 28px; border-radius: 9999px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, var(--color-brand-primary), var(--color-teal-400));
}

.navbar-cart-btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  height: 42px; min-width: 42px; padding: 0 16px; border-radius: 9999px;
  color: #fff; font-size: 15px; font-weight: 600; letter-spacing: -0.2px;
  background: linear-gradient(135deg, var(--color-brand-accent) 0%, var(--color-brand-accent-hover) 100%); cursor: pointer;
  transition: color var(--sf-t2) var(--sf-ease), background-color var(--sf-t2) var(--sf-ease), border-color var(--sf-t2) var(--sf-ease), box-shadow var(--sf-t2) var(--sf-ease), transform var(--sf-t2) var(--sf-ease);
}
.navbar-cart-btn:hover { box-shadow: 0 4px 16px rgba(228, 66, 3, 0.25); }
.navbar-cart-btn--active { border: 1px solid rgba(228, 66, 3, 0.18); box-shadow: 0 2px 8px rgba(228, 66, 3, 0.18); outline: none; }

.navbar-cart-badge {
  display: inline-flex; min-width: 18px; height: 18px; align-items: center; justify-content: center;
  border-radius: 999px; padding: 0 6px; background: #ffffff; color: var(--color-brand-accent);
  font-size: 10px; line-height: 1; font-weight: 700;
}

.navbar-hamburger-btn {
  width: 44px; height: 44px; min-width: 44px; padding: 0; border-radius: 9999px;
  display: inline-flex; align-items: center; justify-content: center;
  background: var(--color-brand-primary); color: #fff; cursor: pointer;
  transition: color var(--sf-t2) var(--sf-ease), background-color var(--sf-t2) var(--sf-ease), border-color var(--sf-t2) var(--sf-ease), box-shadow var(--sf-t2) var(--sf-ease), transform var(--sf-t2) var(--sf-ease);
}
.navbar-hamburger-btn:hover { background: var(--color-brand-primary-light); box-shadow: 0 4px 14px rgba(9, 88, 102, 0.2); }
.navbar-hamburger-btn:active { transform: scale(0.95); }

.navbar-hamburger { position: relative; width: 20px; height: 14px; }
.navbar-hamburger__line {
  display: block; position: absolute; left: 0; width: 100%; height: 2px; border-radius: 2px;
  background: currentColor;
  transition: transform var(--sf-t2) var(--sf-ease), opacity var(--sf-t1) var(--sf-ease);
}
.navbar-hamburger__line--1 { top: 0; }
.navbar-hamburger__line--2 { top: 6px; }
.navbar-hamburger__line--3 { top: 12px; }
.navbar-hamburger--open .navbar-hamburger__line--1 { transform: translateY(6px) rotate(45deg); }
.navbar-hamburger--open .navbar-hamburger__line--2 { opacity: 0; transform: scaleX(0); }
.navbar-hamburger--open .navbar-hamburger__line--3 { transform: translateY(-6px) rotate(-45deg); }

.navbar-mobile-quote {
  display: inline-flex; align-items: center; justify-content: center;
  height: 34px; padding: 0 16px; border-radius: 9999px; color: #fff;
  background: linear-gradient(135deg, var(--color-brand-accent) 0%, var(--color-brand-accent-hover) 100%);
  font-size: 12.5px; line-height: 1; font-weight: 700; letter-spacing: -0.15px; white-space: nowrap;
  transition: transform var(--sf-t1) var(--sf-ease), filter var(--sf-t1) var(--sf-ease);
}

@media (min-width: 1280px) {
  .navbar-link-pill, .navbar-login-btn, .navbar-cart-btn { height: 44px; font-size: 15px; }
  .navbar-link-pill { padding-inline: 20px; }
  .navbar-login-btn { padding-inline: 24px; }
  .navbar-cart-btn { min-width: 44px; }
}
@media (min-width: 1024px) {
  .navbar-mobile-quote { display: none !important; }
  .navbar-hamburger-btn { display: none !important; }
}
@media (max-width: 22.5rem) {
  .navbar-mobile-quote { padding-inline: 10px; font-size: 12px; }
}
</style>
