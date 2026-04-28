<script setup>
import '~/assets/css/autenticazione.css';
import { buildLegacyAuthOverlayRedirect } from '~/utils/auth';

definePageMeta({
	layout: false,
	middleware: ['guest-auth'],
});

useSeoMeta({
	title: 'Recupera password',
	robots: 'noindex, nofollow',
});

const route = useRoute();

await navigateTo(
	buildLegacyAuthOverlayRedirect(route, {
		defaultTab: 'login',
		forceForgot: true,
	}),
	{ redirectCode: 302, replace: true },
);
</script>

<template>
	<div />
</template>
