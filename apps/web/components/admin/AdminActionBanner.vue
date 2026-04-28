<script setup>
const props = defineProps({
	message: { type: String, default: '' },
	tone: { type: String, default: '' },
});

const isSuccess = computed(() => props.tone === 'success' || (!props.tone && props.message && !props.message.toLowerCase().includes('errore')));
const isError = computed(() => props.tone === 'error' || (!props.tone && props.message && props.message.toLowerCase().includes('errore')));
const alertClass = computed(() => isError.value ? 'ux-alert--critical' : 'ux-alert--success');
</script>

<template>
	<Transition name="fade" mode="out-in">
		<div v-if="message" :class="['ux-alert', alertClass]" role="alert">
			<svg v-if="isSuccess" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="ux-alert__icon" fill="currentColor" aria-hidden="true">
				<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
			</svg>
			<svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="ux-alert__icon" fill="currentColor" aria-hidden="true">
				<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
			</svg>
			<span>{{ message }}</span>
		</div>
	</Transition>
</template>
