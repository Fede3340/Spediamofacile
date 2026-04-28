<script setup>
import { buildShipmentFlowLocation } from '~/utils/shipment';

const route = useRoute();

useSeoMeta({
	title: 'Preventivo',
	description: 'Configura colli, servizi, indirizzi e pagamento in un flusso unico, chiaro e modificabile.',
});

await navigateTo(
	buildShipmentFlowLocation(route, route.query.step),
	{ replace: true, redirectCode: 301 },
);
</script>

<template>
	<section class="min-h-[40vh]"></section>
</template>
