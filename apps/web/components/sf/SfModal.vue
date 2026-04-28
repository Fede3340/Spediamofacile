<script setup>import { computed, nextTick, onBeforeUnmount, ref, useId, watch } from 'vue';
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: '' },
  size: { type: String, default: 'md' },
  closeOnBackdrop: { type: Boolean, default: true },
  persistent: { type: Boolean, default: false },
  hideClose: { type: Boolean, default: false },
});
const emit = defineEmits();
const titleId = `sf-modal-title-${useId()}`;
const dialogRef = ref(null);
let previousActive = null;
const panelClasses = computed(() => ['sf-modal__panel', `sf-modal__panel--${props.size}`]);
function close() {
    if (props.persistent)
        return;
    emit('update:modelValue', false);
    emit('close');
}
function onBackdrop() { if (props.closeOnBackdrop)
    close(); }
function getFocusables() {
    if (!dialogRef.value)
        return [];
    const selector = 'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';
    return Array.from(dialogRef.value.querySelectorAll(selector))
        .filter((el) => !el.hasAttribute('inert'));
}
function onKeydown(e) {
    if (e.key === 'Escape' && !props.persistent) {
        e.preventDefault();
        close();
        return;
    }
    if (e.key !== 'Tab')
        return;
    const focusables = getFocusables();
    if (focusables.length === 0) {
        e.preventDefault();
        dialogRef.value?.focus();
        return;
    }
    const first = focusables[0];
    const last = focusables[focusables.length - 1];
    const active = document.activeElement;
    if (e.shiftKey && active === first) {
        e.preventDefault();
        last.focus();
    }
    else if (!e.shiftKey && active === last) {
        e.preventDefault();
        first.focus();
    }
}
function lockScroll(lock) {
    if (typeof document === 'undefined')
        return;
    document.body.style.overflow = lock ? 'hidden' : '';
}
watch(() => props.modelValue, async (open) => {
    if (open) {
        previousActive = document.activeElement || null;
        lockScroll(true);
        await nextTick();
        const focusables = getFocusables();
        (focusables[0] || dialogRef.value)?.focus();
    }
    else {
        lockScroll(false);
        previousActive?.focus?.();
    }
}, { immediate: true });
onBeforeUnmount(() => { lockScroll(false); });
</script>

<template>
	<Teleport to="body">
		<Transition name="sf-modal-fade">
			<div
				v-if="modelValue"
				class="sf-modal"
				@keydown="onKeydown"
			>
				<div class="sf-modal__backdrop" @click="onBackdrop" />
				<div
					ref="dialogRef"
					:class="panelClasses"
					role="dialog"
					aria-modal="true"
					:aria-labelledby="title ? titleId : undefined"
					tabindex="-1"
				>
					<header v-if="title || !hideClose" class="sf-modal__header">
						<h2 v-if="title" :id="titleId" class="sf-modal__title">{{ title }}</h2>
						<button
							v-if="!hideClose"
							type="button" class="sf-modal__close"
							aria-label="Chiudi" @click="close"
						>
							<UIcon name="mdi:close" />
						</button>
					</header>
					<div class="sf-modal__body"><slot /></div>
					<footer v-if="$slots.footer" class="sf-modal__footer">
						<slot name="footer" />
					</footer>
				</div>
			</div>
		</Transition>
	</Teleport>
</template>

<style scoped>
.sf-modal { position: fixed; inset: 0; z-index: 100; display: flex; align-items: center; justify-content: center; padding: 20px; }
.sf-modal__backdrop { position: absolute; inset: 0; background: rgba(15, 23, 42, 0.55); }
.sf-modal__panel {
	position: relative; background: var(--color-brand-card);
	border-radius: var(--radius-lg); box-shadow: var(--shadow-lg);
	display: flex; flex-direction: column; max-height: calc(100vh - 40px); width: 100%;
	outline: none;
}
.sf-modal__panel:focus-visible { box-shadow: var(--shadow-lg), var(--shadow-focus); }
.sf-modal__panel--sm { max-width: 400px; }
.sf-modal__panel--md { max-width: 560px; }
.sf-modal__panel--lg { max-width: 760px; }
.sf-modal__panel--xl { max-width: 960px; }
.sf-modal__header {
	display: flex; align-items: center; justify-content: space-between;
	padding: 20px 24px; border-bottom: 1px solid var(--color-brand-border); gap: var(--gap-3);
}
.sf-modal__title {
	font-family: var(--font-montserrat); font-size: var(--font-size-lg);
	font-weight: 700; color: var(--color-brand-text); margin: 0;
}
.sf-modal__close {
	background: transparent; border: none; cursor: pointer; padding: 6px;
	color: var(--color-brand-text-muted); border-radius: var(--radius-sm); display: inline-flex;
}
.sf-modal__close:hover { color: var(--color-brand-text); background: var(--color-brand-bg-alt); }
.sf-modal__close:focus-visible { outline: none; box-shadow: var(--shadow-focus); }
.sf-modal__body { padding: 24px; overflow: auto; flex: 1; font-family: var(--font-inter); color: var(--color-brand-text); }
.sf-modal__footer {
	padding: 16px 24px; border-top: 1px solid var(--color-brand-border);
	display: flex; justify-content: flex-end; gap: var(--gap-2);
}
.sf-modal-fade-enter-active, .sf-modal-fade-leave-active { transition: opacity var(--duration-fast) var(--ease-out); }
.sf-modal-fade-enter-from, .sf-modal-fade-leave-to { opacity: 0; }
@media (prefers-reduced-motion: reduce) {
	.sf-modal-fade-enter-active, .sf-modal-fade-leave-active { transition: none; }
}
</style>
