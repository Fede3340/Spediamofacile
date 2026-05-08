<script setup>
/**
 * SfActionBanner — banner feedback success/error universale.
 * Pattern uso:
 *   const { message, showSuccess, showError } = useFlashMessage();
 *   <SfActionBanner :message="message" />
 *
 * Accetta sia FlashMessage object {type, text} che string legacy (auto-detect "errore").
 */
const props = defineProps({
	/** Oggetto FlashMessage {type:'success'|'error',text:string} o stringa legacy */
	message: { type: [Object, String], default: null },
	/** Override esplicito tone (sovrascrive auto-detect). */
	tone: { type: String, default: '' },
});

const text = computed(() => {
	if (!props.message) return '';
	return typeof props.message === 'string' ? props.message : props.message.text;
});

const finalTone = computed(() => {
	if (props.tone) return props.tone;
	if (typeof props.message === 'object' && props.message?.type) {
		return props.message.type === 'error' ? 'danger' : 'success';
	}
	const t = text.value.toLowerCase();
	return (t.includes('errore') || t.includes('error')) ? 'danger' : 'success';
});
</script>

<template>
	<Transition name="fade" mode="out-in">
		<SfAlert v-if="text" :tone="finalTone" role="alert">
			{{ text }}
		</SfAlert>
	</Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active { transition: opacity 200ms ease; }
.fade-enter-from,
.fade-leave-to { opacity: 0; }
</style>
