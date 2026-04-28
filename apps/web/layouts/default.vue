<!-- Layout default — replica Prototipo Layout.tsx: sticky header, main con gradient
     #F8F9FB→#EEF0F3, scroll-to-top (>400px) e help button flottanti. -->
<script setup>
import { normalizeAuthTab, normalizeRequestedPath } from '~/utils/auth';

useCanonical();

const { isAuthenticatedForUi } = useAuthUiState();
const { openAuthModal } = useAuthModalStore();
const route = useRoute();
const router = useRouter();
const { isAccountRoute, isAuthPageRoute, isQuoteFlowRoute } = useShellRouteState();
const authUiHydrated = ref(false);
// Footer visibile ovunque tranne le pagine auth dedicate (/accedi, /registrati, /recupera-password).
// Le pagine /account/* mostrano il footer come il resto del sito per coerenza UX.
const showMarketingFooter = computed(() => !isAuthPageRoute.value);
const showFloatingUtilities = computed(
	() => authUiHydrated.value && !isAuthPageRoute.value && !isQuoteFlowRoute.value && !isAccountRoute.value,
);

const getRequestedPath = () => {
	const redirectQuery = Array.isArray(route.query.redirect) ? route.query.redirect[0] : route.query.redirect;

	if (typeof redirectQuery === 'string' && redirectQuery.startsWith('/')) {
		return redirectQuery;
	}

	return route.fullPath;
};

const showScrollTop = ref(false);
const showGuestHelp = ref(false);
const guestHelpPopoverRef = ref(null);

const scrollToTop = () => {
	window.scrollTo({ top: 0, behavior: 'smooth' });
};

const onScroll = () => {
	showScrollTop.value = window.scrollY > 400;
};

const closeGuestHelp = () => {
	showGuestHelp.value = false;
};

const toggleGuestHelp = () => {
	showGuestHelp.value = !showGuestHelp.value;
};

const openSupportAuthModal = () => {
	closeGuestHelp();
	openAuthModal({
		redirect: '/account/assistenza',
		tab: 'login',
	});
};

const onWindowKeydown = (event) => {
	if (event.key === 'Escape') {
		closeGuestHelp();
	}
};

const onDocumentPointerDown = (event) => {
	if (!showGuestHelp.value) return;
	const target = event.target;
	if (!(target instanceof Node)) return;
	if (guestHelpPopoverRef.value?.contains(target)) return;
	closeGuestHelp();
};

const syncRouteAuthOverlay = async () => {
	if (import.meta.server) return;
	if (isAuthPageRoute.value) return;

	const authModalQuery = Array.isArray(route.query.auth_modal) ? route.query.auth_modal[0] : route.query.auth_modal;
	const authForgotQuery = Array.isArray(route.query.auth_forgot) ? route.query.auth_forgot[0] : route.query.auth_forgot;

	if (!authModalQuery && !authForgotQuery) {
		return;
	}

	const redirectQuery = Array.isArray(route.query.redirect) ? route.query.redirect[0] : route.query.redirect;
	const modalOptions = {
		redirect: normalizeRequestedPath(redirectQuery, route.path || '/'),
		tab: normalizeAuthTab(authModalQuery),
		mode: authForgotQuery === '1' || authForgotQuery === 'true' ? 'forgot' : null,
	};

	const nextQuery = { ...route.query };
	delete nextQuery.auth_modal;
	delete nextQuery.auth_forgot;
	delete nextQuery.redirect;
	await router.replace({ path: route.path, query: nextQuery, hash: route.hash });
	await nextTick();
	openAuthModal(modalOptions);
};

watch(
	() => [route.query.auth_modal, route.query.auth_forgot, route.query.redirect, isAuthPageRoute.value],
	() => {
		void syncRouteAuthOverlay();
	},
	{ immediate: true },
);

onMounted(() => {
	authUiHydrated.value = true;
	onScroll();
	window.addEventListener('scroll', onScroll, { passive: true });
	window.addEventListener('keydown', onWindowKeydown);
	document.addEventListener('mousedown', onDocumentPointerDown);
	document.addEventListener('touchstart', onDocumentPointerDown, { passive: true });
});

onUnmounted(() => {
	window.removeEventListener('scroll', onScroll);
	window.removeEventListener('keydown', onWindowKeydown);
	document.removeEventListener('mousedown', onDocumentPointerDown);
	document.removeEventListener('touchstart', onDocumentPointerDown);
});
</script>

<template>
	<div class="w-full min-h-screen flex flex-col overflow-x-clip" style="font-family: 'Inter', sans-serif">
		<a
			href="#main-content"
			class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-[100] focus:bg-white focus:px-4 focus:py-2 focus:rounded-[18px] focus:shadow-lg focus:text-[var(--color-brand-primary)] focus:font-semibold">
			Vai al contenuto
		</a>
		<Header />

		<!-- Main content with prototype gradient bg -->
		<main
			id="main-content"
			class="flex-1 w-full max-w-full mx-auto overflow-x-clip"
			style="background: var(--gradient-page-surface)"
		>
			<slot />
		</main>

		<SiteFooter v-if="showMarketingFooter" />
		<ClientOnly>
			<!-- W5.1 perf: lazy-mount per scaricare il bundle del modal solo quando serve. -->
			<LazyAuthOverlayModal v-if="!isAuthPageRoute" />
		</ClientOnly>

		<!-- Scroll to top — placeholder fisso con opacity toggle (no mount/unmount → no CLS). -->
		<div
			class="fixed bottom-[80px] right-[16px] sm:bottom-[80px] sm:right-[20px] z-[950] w-[48px] h-[48px] transition-opacity duration-300"
			:class="showScrollTop && showFloatingUtilities ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'"
			:aria-hidden="showScrollTop && showFloatingUtilities ? 'false' : 'true'">
			<button
				type="button"
				@click="scrollToTop"
				class="w-full h-full rounded-full bg-[#095866] text-white flex items-center justify-center cursor-pointer hover:bg-[#0b6d7d] hover:scale-110 active:scale-95 transition-all duration-200"
				style="box-shadow: 0 4px 14px rgba(9, 88, 102, 0.25)"
				title="Torna su"
				aria-label="Torna in cima alla pagina"
				:tabindex="showScrollTop && showFloatingUtilities ? 0 : -1">
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<polyline points="18 15 12 9 6 15"></polyline>
				</svg>
			</button>
		</div>

		<!-- Help floating button — fixed, bottom-[24px] right-[16px] -->
		<div
			v-if="showFloatingUtilities"
			ref="guestHelpPopoverRef"
			class="fixed bottom-[24px] right-[16px] sm:bottom-[24px] sm:right-[20px] z-[950]">
			<NuxtLink
				v-show="isAuthenticatedForUi"
				to="/account/assistenza"
				class="layout-help-btn w-[48px] h-[48px] rounded-full text-white flex items-center justify-center cursor-pointer opacity-70 hover:opacity-100 hover:scale-110 active:scale-95 transition-all duration-200"
				title="Assistenza"
				aria-label="Assistenza">
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
				</svg>
			</NuxtLink>

			<button
				v-show="!isAuthenticatedForUi"
				type="button"
				@click="toggleGuestHelp"
				class="layout-help-btn w-[48px] h-[48px] rounded-full text-white flex items-center justify-center cursor-pointer opacity-70 hover:opacity-100 hover:scale-110 active:scale-95 transition-all duration-200"
				aria-label="Aiuto"
				:aria-expanded="showGuestHelp ? 'true' : 'false'"
				aria-controls="guest-help-popover"
				title="Aiuto">
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
				</svg>
			</button>

			<Transition name="popover-fade">
				<div
					v-if="showGuestHelp && !isAuthenticatedForUi"
					id="guest-help-popover"
					role="dialog"
					aria-label="Supporto"
					class="absolute bottom-[56px] right-0 w-[280px] bg-white border border-[var(--color-brand-border)] rounded-[18px] shadow-lg p-[14px]">
					<p class="text-[0.875rem] font-semibold text-[var(--color-brand-text)]">Serve supporto?</p>
					<p class="text-[0.8125rem] text-[#777] mt-[4px]">Puoi contattarci subito oppure accedere per aprire e seguire i ticket.</p>
					<div class="mt-[10px] grid grid-cols-1 gap-[8px]">
						<NuxtLink
							to="/contatti"
							class="inline-flex items-center justify-center h-[36px] rounded-full bg-[var(--color-brand-primary)] text-white text-[0.8125rem] font-semibold hover:bg-[var(--color-brand-primary-hover)] transition-colors"
							@click="closeGuestHelp">
							Contattaci
						</NuxtLink>
						<button
							type="button"
							class="inline-flex items-center justify-center h-[36px] rounded-full border border-[var(--color-brand-border)] text-[var(--color-brand-text)] text-[0.8125rem] font-semibold hover:bg-[var(--color-brand-bg)] transition-colors cursor-pointer"
							@click="openSupportAuthModal">
							Accedi per ticket
						</button>
					</div>
				</div>
			</Transition>
		</div>

		<!-- Banner consenso cookie GDPR (components/CookieBanner.vue).
			 Lazy auto-import: si carica solo client-side, monta solo se manca consenso. -->
		<ClientOnly>
			<LazyCookieBanner />
		</ClientOnly>

		<!-- Global live region for screen reader announcements -->
		<div id="a11y-live-region" aria-live="polite" aria-atomic="true" class="sr-only"></div>
	</div>
</template>

<style scoped>
/* Help button — prototype gradient */
.layout-help-btn {
	background: linear-gradient(135deg, #095866, #0a7489);
	box-shadow: 0 6px 20px rgba(9, 88, 102, 0.3);
	transition: transform var(--sf-t1) var(--sf-ease), box-shadow var(--sf-t1) var(--sf-ease);
}
.layout-help-btn:hover {
	transform: scale(1.08) translateY(-2px);
	box-shadow: 0 8px 24px rgba(9, 88, 102, 0.35);
}
.layout-help-btn:active {
	transform: scale(0.95);
}

/* Scroll-to-top fade transition */
.scroll-top-fade-enter-active {
	transition: opacity 0.3s ease, transform 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}
.scroll-top-fade-leave-active {
	transition: opacity 0.3s ease, transform 0.3s ease;
}
.scroll-top-fade-enter-from {
	opacity: 0;
	transform: scale(0.8) translateY(10px);
}
.scroll-top-fade-leave-to {
	opacity: 0;
	transform: scale(0.8) translateY(10px);
}

/* Popover fade transition */
.popover-fade-enter-active {
	transition: opacity 0.2s ease, transform 0.2s cubic-bezier(0.22, 1, 0.36, 1);
}
.popover-fade-leave-active {
	transition: opacity 0.15s ease, transform 0.15s ease;
}
.popover-fade-enter-from {
	opacity: 0;
	transform: translateY(4px);
}
.popover-fade-leave-to {
	opacity: 0;
	transform: translateY(4px);
}
</style>
