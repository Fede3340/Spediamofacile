<script setup>
const props = defineProps({
	message: { type: String, default: '' },
	tone: { type: String, default: '' },
});

const isError = computed(() => props.tone === 'error' || (!props.tone && props.message && props.message.toLowerCase().includes('errore')));
const finalTone = computed(() => isError.value ? 'danger' : 'success');
</script>

<template>
	<Transition name="fade" mode="out-in">
		<SfAlert v-if="message" :tone="finalTone" role="alert">
			{{ message }}
		</SfAlert>
	</Transition>
</template>
