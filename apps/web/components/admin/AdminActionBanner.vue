<script setup>
/**
 * AdminActionBanner — shim back-compat per <SfActionBanner>.
 * Pattern moderno: <SfActionBanner :message="..." />.
 */
defineProps({
	message: { type: [String, Object], default: '' },
	tone: { type: String, default: '' },
});
</script>

<template>
	<SfActionBanner :message="message" :tone="tone" />
</template>
