<!-- COMPONENTE: SfConfirmDialog (organism) -->
<script setup>
import { storeToRefs } from 'pinia';
import { useConfirmDialogStore } from '~/stores/confirmDialogStore';
const store = useConfirmDialogStore();
const { state } = storeToRefs(store);
const _resolve = store.resolveDialog;
const titleId = `sf-confirm-title-${useId()}`;
const descId = `sf-confirm-desc-${useId()}`;
const dialogRef = ref(null);
let previousActive = null;
const visible = computed(() => state.value.open);
const isDanger = computed(() => state.value.tone === 'danger');
function answer(value) {
    _resolve(value);
}
function getFocusables() {
    if (!dialogRef.value)
        return [];
    const sel = 'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';
    return Array.from(dialogRef.value.querySelectorAll(sel))
        .filter((el) => !el.hasAttribute('inert'));
}
function onKeydown(e) {
    if (e.key === 'Escape') {
        e.preventDefault();
        answer(false);
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
watch(visible, async (open) => {
    if (open) {
        previousActive = document.activeElement || null;
        lockScroll(true);
        await nextTick();
        // Focus su bottone conferma di default (sicuro per default 'default'; per danger
        // l'utente deve comunque azionare deliberatamente)
        const focusables = getFocusables();
        const confirmBtn = focusables.find((f) => f.dataset.role === 'confirm');
        (confirmBtn || focusables[0] || dialogRef.value)?.focus();
    }
    else {
        lockScroll(false);
        previousActive?.focus?.();
    }
});
onBeforeUnmount(() => { lockScroll(false); });
</script>

<template>
  <Teleport to="body">
    <Transition name="sf-confirm-fade">
      <div
        v-if="visible"
        class="sf-confirm"
        @keydown="onKeydown"
      >
        <div class="sf-confirm__backdrop" @click="answer(false)" />
        <div
          ref="dialogRef"
          class="sf-confirm__panel"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="titleId"
          :aria-describedby="state.message ? descId : undefined"
          tabindex="-1"
        >
          <div :class="['sf-confirm__icon', isDanger ? 'sf-confirm__icon--danger' : 'sf-confirm__icon--default']" aria-hidden="true">
            <svg v-if="isDanger" width="28" height="28" viewBox="0 0 24 24" fill="none">
              <path
d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <svg v-else width="28" height="28" viewBox="0 0 24 24" fill="none">
              <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
              <path d="M12 8v5m0 3h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </div>
          <h2 :id="titleId" class="sf-confirm__title">{{ state.title }}</h2>
          <p v-if="state.message" :id="descId" class="sf-confirm__message">{{ state.message }}</p>
          <div class="sf-confirm__actions">
            <button
              type="button"
              class="sf-confirm__btn sf-confirm__btn--cancel"
              data-role="cancel"
              @click="answer(false)"
            >
              {{ state.cancelText || 'Annulla' }}
            </button>
            <button
              type="button"
              :class="['sf-confirm__btn', isDanger ? 'sf-confirm__btn--danger' : 'sf-confirm__btn--primary']"
              data-role="confirm"
              @click="answer(true)"
            >
              {{ state.confirmText || 'Conferma' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.sf-confirm {
  position: fixed; inset: 0; z-index: 110;
  display: flex; align-items: center; justify-content: center;
  padding: 20px;
}
.sf-confirm__backdrop {
  position: absolute; inset: 0;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
}
.sf-confirm__panel {
  position: relative;
  background: var(--color-brand-card, #fff);
  border-radius: 14px;
  box-shadow: var(--shadow-lg, 0 16px 32px rgba(15,23,42,.10), 0 24px 48px rgba(15,23,42,.08));
  width: 100%;
  max-width: 420px;
  padding: 28px 24px 22px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  outline: none;
}
.sf-confirm__panel:focus-visible {
  box-shadow: var(--shadow-lg), var(--shadow-focus, 0 0 0 3px rgba(9,88,102,.6));
}
.sf-confirm__icon {
  width: 56px; height: 56px;
  border-radius: 9999px;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 14px;
}
.sf-confirm__icon--default {
  background: var(--color-brand-secondary-soft-bg, #f3f8f9);
  color: var(--color-brand-primary, #095866);
}
.sf-confirm__icon--danger {
  background: rgba(228, 66, 3, 0.10);
  color: var(--color-brand-accent, #E44203);
}
.sf-confirm__title {
  font-family: var(--font-montserrat, 'Montserrat', sans-serif);
  font-size: var(--font-size-lg, 20px);
  font-weight: 700;
  color: var(--color-brand-text, #1D2738);
  margin: 0 0 6px;
  line-height: 1.3;
}
.sf-confirm__message {
  font-family: var(--font-inter, 'Inter', sans-serif);
  font-size: var(--font-size-sm, 14px);
  color: var(--color-brand-text-secondary, #5A6474);
  line-height: 1.5;
  margin: 0 0 20px;
}
.sf-confirm__actions {
  display: flex;
  gap: 10px;
  width: 100%;
  margin-top: 4px;
}
.sf-confirm__btn {
  flex: 1;
  height: 44px;
  border-radius: 14px;
  font-family: var(--font-inter, 'Inter', sans-serif);
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  border: 1.5px solid transparent;
  transition: background-color var(--sf-t1) var(--sf-ease), border-color var(--sf-t1) var(--sf-ease), box-shadow var(--sf-t1) var(--sf-ease), transform var(--sf-t1) var(--sf-ease);
  display: inline-flex; align-items: center; justify-content: center;
  white-space: nowrap;
}
.sf-confirm__btn:active { transform: translateY(1px); }
.sf-confirm__btn:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus, 0 0 0 3px rgba(9,88,102,.6));
}
.sf-confirm__btn--cancel {
  background: transparent;
  color: var(--color-brand-text, #1D2738);
  border-color: var(--color-brand-border, #E9EBEC);
}
.sf-confirm__btn--cancel:hover {
  background: var(--color-brand-bg-alt, #f3f4f6);
}
.sf-confirm__btn--primary {
  background: var(--color-brand-primary, #095866);
  color: #fff;
}
.sf-confirm__btn--primary:hover {
  background: var(--color-brand-primary-hover, #074a56);
}
.sf-confirm__btn--danger {
  background: var(--color-brand-accent, #E44203);
  color: #fff;
}
.sf-confirm__btn--danger:hover {
  background: var(--color-brand-accent-hover, #c93800);
}
.sf-confirm__btn--danger:focus-visible {
  box-shadow: 0 0 0 3px rgba(228, 66, 3, 0.45);
}

.sf-confirm-fade-enter-active,
.sf-confirm-fade-leave-active {
  transition: opacity 200ms cubic-bezier(0.22, 1, 0.36, 1);
}
.sf-confirm-fade-enter-from,
.sf-confirm-fade-leave-to { opacity: 0; }
.sf-confirm-fade-enter-active .sf-confirm__panel,
.sf-confirm-fade-leave-active .sf-confirm__panel {
  transition: transform 220ms cubic-bezier(0.22, 1, 0.36, 1);
}
.sf-confirm-fade-enter-from .sf-confirm__panel { transform: scale(0.96) translateY(6px); }

@media (prefers-reduced-motion: reduce) {
  .sf-confirm-fade-enter-active,
  .sf-confirm-fade-leave-active,
  .sf-confirm-fade-enter-active .sf-confirm__panel,
  .sf-confirm-fade-leave-active .sf-confirm__panel {
    transition: none;
  }
}
</style>
