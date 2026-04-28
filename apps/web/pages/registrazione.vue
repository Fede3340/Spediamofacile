<script setup>
import '~/assets/css/autenticazione.css';
import { buildLegacyAuthOverlayRedirect } from '~/utils/auth';

definePageMeta({
	layout: false,
	middleware: ['guest-auth'],
});

const route = useRoute();

useSeoMeta({
	title: 'Registrati',
	description: 'Crea il tuo account SpediamoFacile e inizia a spedire con BRT a prezzi vantaggiosi.',
});

await navigateTo(
	buildLegacyAuthOverlayRedirect(route, { defaultTab: 'register' }),
	{ redirectCode: 302, replace: true },
);
</script>

<template>
	<div />
</template>
