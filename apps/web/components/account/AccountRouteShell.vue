<script setup>
/**
 * AccountRouteShell — layout wrapper dell'area account (sidebar + content).
 * Orchestratore che risolve il ruolo utente (admin/pro/client), computa CTA
 * e attive di menu, e delega il rendering a:
 *   - AccountSidebar (desktop >= lg)
 *   - AccountMobileDrawer (< lg topbar + drawer)
 * I nav groups sono definiti in utils/accountNavigationGroups.ts.
 */
import '~/assets/css/account.css';
import AccountSidebar from './AccountSidebar.vue';
import AccountMobileDrawer from './AccountMobileDrawer.vue';
import {
	adminNavGroups,
	clientNavGroups,
	proNavGroups,
} from '~/utils/account';

const route = useRoute();
const mobileOpen = ref(false);

const { uiSnapshot } = useAuthUiState();
const { clearSnapshot } = useAuthUiSnapshotPersistence();
const { user, logout } = useSanctumAuth();

const resolveRoleKey = (...candidates) => {
	for (const candidate of candidates) {
		const normalized = String(candidate || '').trim().toLowerCase();
		if (!normalized) continue;
		if (normalized.includes('admin')) return 'admin';
		if (normalized.includes('partner pro') || normalized === 'pro' || normalized.includes(' pro')) return 'pro';
		if (normalized.includes('cliente') || normalized.includes('client')) return 'client';
	}

	return 'client';
};

const pickProfileField = (...candidates) => {
	for (const candidate of candidates) {
		const value = String(candidate || '').trim();
		if (value) return value;
	}

	return '';
};

// Source of truth: il ruolo live di Sanctum viene prima dello snapshot UI.
// Lo snapshot resta solo come fallback per il primo paint o sessioni in bootstrap.
const roleKey = computed(() => resolveRoleKey(user.value?.role, uiSnapshot.value.role));

const isAdmin = computed(() => roleKey.value === 'admin');
const isPro = computed(() => roleKey.value === 'pro');

const resolvedName = computed(() => pickProfileField(user.value?.name, uiSnapshot.value.name));
const resolvedSurname = computed(() => pickProfileField(user.value?.surname, user.value?.last_name, uiSnapshot.value.surname));

const fullName = computed(() => {
	return `${resolvedName.value} ${resolvedSurname.value}`.trim() || 'Account SpediamoFacile';
});

const initials = computed(() =>
	fullName.value
		.split(' ')
		.filter(Boolean)
		.slice(0, 2)
		.map((part) => part.charAt(0).toUpperCase())
		.join('') || 'SF',
);

const roleLabel = computed(() => {
	if (isAdmin.value) return 'Amministratore';
	if (isPro.value) return 'Partner Pro';
	return 'Cliente';
});

const isAdminConsoleRootRoute = computed(() => route.path === '/account/amministrazione');

const collapsedAdminGroups = ref(new Set());

const navGroups = computed(() => {
	if (isAdmin.value) return adminNavGroups;
	if (isPro.value) return proNavGroups;
	return clientNavGroups;
});

const canCollapseGroup = (group) => {
	if (!isAdmin.value || !group?.title || !group?.key) return false;
	return true;
};

const isGroupOpen = (group) => {
	if (!canCollapseGroup(group)) return true;
	return !collapsedAdminGroups.value.has(group.key);
};

const toggleGroup = (group) => {
	if (!canCollapseGroup(group)) return;

	const next = new Set(collapsedAdminGroups.value);
	if (next.has(group.key)) next.delete(group.key);
	else next.add(group.key);
	collapsedAdminGroups.value = next;
};

const primaryCta = computed(() => (
	isAdmin.value
		? { label: 'Console', to: '/account/amministrazione', tone: 'admin' }
		: { label: 'Nuova spedizione', to: '/preventivo', tone: 'accent' }
));

const secondaryCta = computed(() => (
	isAdmin.value
		? null
		: null
));

const totalBadges = computed(() => navGroups.value.reduce((sum, group) =>
	sum + (group.items || []).reduce((groupSum, item) => groupSum + Number(item.badge || 0), 0), 0,
));

const isItemActive = (item) => {
	if (item.to === '/account/carte') {
		return route.path === '/account/portafoglio' && String(route.query.tab || '') === 'metodi';
	}

	if (item.to === '/account/portafoglio') {
		return route.path === '/account/portafoglio' && String(route.query.tab || '') !== 'metodi';
	}

	if (item.exact) return route.path === item.to;
	return route.path === item.to || route.path.startsWith(`${item.to}/`);
};

const closeDrawer = () => {
	mobileOpen.value = false;
};

const toggleDrawer = () => {
	mobileOpen.value = !mobileOpen.value;
};

const handleLogout = async () => {
	closeDrawer();
	try {
		await logout();
	} catch {
		// Se logout lato API fallisce, il middleware app-auth porta comunque fuori dalle route protette.
	}
	clearSnapshot();
	try {
		await refreshNuxtData();
	} catch {
		// Il redirect pubblico riallinea comunque navbar e CTA.
	}
	await navigateTo('/');
};

watch(() => route.fullPath, () => {
	mobileOpen.value = false;
});

watch(isAdmin, (value) => {
	if (value) return;
	collapsedAdminGroups.value = new Set();
}, { immediate: true });
</script>

<template>
	<div class="account-route-shell__frame flex w-full items-start">
		<AccountSidebar
			:full-name="fullName"
			:initials="initials"
			:role-label="roleLabel"
			:is-admin="isAdmin"
			:is-admin-console-root-route="isAdminConsoleRootRoute"
			:primary-cta="primaryCta"
			:secondary-cta="secondaryCta"
			:nav-groups="navGroups"
			:can-collapse-group="canCollapseGroup"
			:is-group-open="isGroupOpen"
			:toggle-group="toggleGroup"
			:is-item-active="isItemActive"
			@logout="handleLogout" />

		<AccountMobileDrawer
			:mobile-open="mobileOpen"
			:full-name="fullName"
			:initials="initials"
			:role-label="roleLabel"
			:is-admin="isAdmin"
			:is-admin-console-root-route="isAdminConsoleRootRoute"
			:primary-cta="primaryCta"
			:secondary-cta="secondaryCta"
			:nav-groups="navGroups"
			:total-badges="totalBadges"
			:is-item-active="isItemActive"
			@toggle="toggleDrawer"
			@close="closeDrawer"
			@logout="handleLogout" />

		<div class="account-route-shell__content min-w-0 flex-1">
			<slot />
		</div>
	</div>
</template>
