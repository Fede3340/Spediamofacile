<script setup>
import useAccountDashboard from '~/composables/useAccountDashboard';

useSeoMeta({
	title: 'Il tuo account',
	ogTitle: 'Il tuo account',
	description: 'Dashboard account SpediamoFacile con priorita, spedizioni, pagamenti e supporto.',
	ogDescription: 'Area account SpediamoFacile con viste di ruolo piu chiare, pulite e coerenti con il prototipo.',
	robots: 'noindex, nofollow',
});

definePageMeta({
	middleware: ['app-auth'],
});

const {
	isAdmin,
	customerOrdersLoading,
	highlightedCustomerOrders,
	recentCompletedCustomerOrders,
	personalHighlights,
	isLoggingOut,
	handleLogout,
} = useAccountDashboard();

const route = useRoute();

const redirectAdminToCanonicalConsole = async () => {
	if (!isAdmin.value || route.path !== '/account') {
		return;
	}

	await navigateTo('/account/amministrazione', { replace: true });
};

if (import.meta.server) {
	await redirectAdminToCanonicalConsole();
} else {
	watch(
		isAdmin,
		async () => {
			await redirectAdminToCanonicalConsole();
		},
		{ immediate: true },
	);
}
</script>

<template>
	<section class="sf-account-shell py-[20px] tablet:py-[24px] desktop:py-[28px]">
		<div class="my-container max-w-[1280px]">
			<AccountDashboardClient
				:customer-orders-loading="customerOrdersLoading"
				:highlighted-customer-orders="highlightedCustomerOrders"
				:recent-completed-customer-orders="recentCompletedCustomerOrders"
				:personal-highlights="personalHighlights"
				:is-logging-out="isLoggingOut"
				@logout="handleLogout"
			/>
		</div>
	</section>
</template>
