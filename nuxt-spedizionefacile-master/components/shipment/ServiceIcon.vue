<script setup>
/** ServiceIcon — set di icone originali duotone per i servizi aggiuntivi */

defineProps({
	name: { type: String, required: true },
	size: { type: Number, default: 40 },
});
</script>

<template>
	<svg
		:width="size"
		:height="size"
		viewBox="0 0 48 48"
		fill="none"
		stroke-width="2"
		stroke-linecap="round"
		stroke-linejoin="round"
		class="service-icon"
		aria-hidden="true"
		focusable="false">

		<!-- SENZA ETICHETTA: stack isometrico pacco (matches HomepageServizi i=1) -->
		<template v-if="name === 'senza_etichetta'">
			<path d="M24 4 4 14l20 10 20-10-20-10Z" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			<path d="M4 34l20 10 20-10" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			<path d="M4 24l20 10 20-10" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			<path d="M24 4 4 14l20 10 20-10-20-10Z" fill="#E44203" fill-opacity="0.14" stroke="none" />
		</template>

		<!-- CONTRASSEGNO: POS/carta con cerchio euro (matches HomepageServizi i=3) -->
		<template v-else-if="name === 'contrassegno'">
			<rect x="4" y="12" width="40" height="24" rx="4" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			<circle cx="24" cy="24" r="4" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			<circle cx="24" cy="24" r="4" fill="#E44203" fill-opacity="0.14" stroke="none" />
			<path d="M12 24h.02M36 24h.02" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
		</template>

		<!-- ASSICURAZIONE: scudo pieno (matches HomepageServizi i=4) -->
		<template v-else-if="name === 'assicurazione'">
			<path d="M24 44s16-8 16-20V10l-16-6-16 6v14c0 12 16 20 16 20Z" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			<path d="M24 44s16-8 16-20V10l-16-6-16 6v14c0 12 16 20 16 20Z" fill="#E44203" fill-opacity="0.14" stroke="none" />
		</template>

		<!-- SPONDA IDRAULICA: camion + piattaforma sollevata -->
		<template v-else-if="name === 'sponda_idraulica'">
			<path d="M4 30h18V16H4v14z" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			<path d="M22 21h8l6 5v4H22" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			<circle cx="12" cy="35" r="3.5" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			<circle cx="30" cy="35" r="3.5" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			<path d="M36 30h6v8h-6z" fill="#E44203" fill-opacity="0.14" stroke="none" />
		</template>

		<!-- CONSEGNA AL PIANO: edificio + scala + freccia su -->
		<template v-else-if="name === 'consegna_al_piano'">
			<path d="M10 42V10h20v32" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			<path d="M10 18h20M10 26h20M10 34h20" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			<path d="M34 36v-14h8v14z" fill="#E44203" fill-opacity="0.14" stroke="none" />
			<path d="M38 34V18m-4 4l4-4 4 4" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
		</template>

		<!-- CONSEGNA SU APPUNTAMENTO: calendario + orologio -->
		<template v-else-if="name === 'consegna_appuntamento'">
			<rect x="6" y="10" width="26" height="26" rx="3" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			<path d="M6 18h26M13 6v8M25 6v8" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			<circle cx="34" cy="34" r="8" fill="#E44203" fill-opacity="0.14" stroke="none" />
			<circle cx="34" cy="34" r="7" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			<path d="M34 30v4l3 2" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
		</template>

		<!-- NOTIFICA SMS / EMAIL: campanella + pulse -->
		<template v-else-if="name === 'notifica' || name === 'sms' || name === 'notifications'">
			<path d="M18 9A6 6 0 0 1 30 9c0 8 3 10 3 10H15s3-2 3-10z" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			<path d="M22 34a2.5 2.5 0 0 0 4 0" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			<circle cx="36" cy="12" r="4" fill="#E44203" fill-opacity="0.14" stroke="none" />
		</template>

		<!-- CONSEGNA RAPIDA / SABATO: calendario + fulmine -->
		<template v-else-if="name === 'sabato' || name === 'consegna-rapida' || name === 'express'">
			<rect x="7" y="10" width="28" height="28" rx="3" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			<path d="M7 18h28M15 6v8M27 6v8" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			<path d="M23 22l-4 8h5l-2 8 8-10h-5l2-6z" fill="#E44203" fill-opacity="0.14" stroke="none" />
		</template>

		<!-- FIRMA: penna su documento -->
		<template v-else-if="name === 'firma'">
			<path d="M10 8h18l8 8v24H10z" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			<path d="M28 8v8h8" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			<path d="M15 30c2-3 6-3 8 0s6 3 8 0 V34 H15 Z" fill="#E44203" fill-opacity="0.14" stroke="none" />
		</template>

		<!-- TRACKING: onde radar (matches HomepageServizi i=2) -->
		<template v-else-if="name === 'tracking'">
			<path d="M9.8 38.2C2 30.4 2 17.6 9.8 9.8" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			<path d="M15.6 32.4c-4.6-4.6-4.6-12.2 0-16.8" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			<circle cx="24" cy="24" r="4" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			<circle cx="24" cy="24" r="4" fill="#E44203" fill-opacity="0.14" stroke="none" />
			<path d="M32.4 15.6c4.6 4.6 4.6 12.2 0 16.8" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			<path d="M38.2 9.8C46 17.6 46 30.2 38.2 38" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
		</template>

		<!-- FRAGILE: triangolo warning -->
		<template v-else-if="name === 'fragile'">
			<path d="M24 6L42 38H6L24 6z" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			<path d="M24 18v10" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			<circle cx="24" cy="33" r="1.6" fill="#E44203" fill-opacity="0.14" stroke="none" />
		</template>

		<!-- DEFAULT: pacco isometrico -->
		<template v-else>
			<path d="M24 4l18 10v20L24 44 6 34V14L24 4z" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			<path d="M24 24L6 14M24 24v20M24 24l18-10" class="service-icon__stroke" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			<path d="M24 4l18 10L24 24 6 14L24 4z" fill="#E44203" fill-opacity="0.14" stroke="none" />
		</template>
	</svg>
</template>

<!-- Stili estratti in assets/css/components/sf-service-icon.css (importato da main.css).
     Nota: i selettori parent-scope .service-surface:hover .service-icon non funzionavano
     con <style scoped> perché il padre vive in un componente diverso. Ora sono globali. -->
