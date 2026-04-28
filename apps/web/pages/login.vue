<script setup>
import '~/assets/css/autenticazione.css';
import { buildLegacyAuthOverlayRedirect } from '~/utils/auth';

definePageMeta({
	layout: false,
	middleware: ['guest-auth'],
});

const route = useRoute();

useSeoMeta({
	title: 'Accedi',
	description: 'Accedi al tuo account SpediamoFacile per gestire spedizioni, portafoglio e ordini.',
});

await navigateTo(
	buildLegacyAuthOverlayRedirect(route, { defaultTab: 'login' }),
	{ redirectCode: 302, replace: true },
);
</script>

<template>
	<div />
</template>
