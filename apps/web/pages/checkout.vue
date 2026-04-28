<script setup>
import { buildShipmentFlowLocation } from '~/utils/shipment';

const route = useRoute();

useSeoMeta({
	title: 'Checkout Spedizione BRT',
	description: 'Completa il pagamento della tua spedizione BRT in sicurezza.',
	robots: 'noindex, nofollow',
});

await navigateTo(
	buildShipmentFlowLocation(route, 'pagamento'),
	{ replace: true, redirectCode: 302 },
);
</script>

<template>
	<section class="min-h-[40vh]"></section>
</template>
