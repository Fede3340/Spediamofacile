/**
 * confirmDialogStore — Pinia store per dialog di conferma globale (promise-based).
 *
 * Pattern singola istanza: se un dialog è già aperto e ne viene richiesto un altro,
 * il primo viene risolto con `false` (annullato) prima di aprire il nuovo. Questo
 * evita finestre di conferma sovrapposte (anti-pattern UX).
 *
 * Sostituisce useState() di useConfirmDialog.js con store ispezionabile in DevTools.
 */
import { defineStore } from 'pinia';
const defaultState = {
    open: false,
    title: '',
    message: '',
    confirmText: 'Conferma',
    cancelText: 'Annulla',
    tone: 'default',
};
// Resolver vivo a livello modulo (fuori dalla store factory perché Pinia
// non vuole valori non-serializzabili come functions nello state).
let pendingResolve = null;
export const useConfirmDialogStore = defineStore('confirmDialog', () => {
    const state = ref({ ...defaultState });
    const isOpen = computed(() => state.value.open);
    /**
     * Apre il dialog e restituisce una Promise che si risolve true (confirm)
     * o false (cancel). Eventuale dialog precedente viene annullato.
     */
    function confirm(opts) {
        if (pendingResolve) {
            pendingResolve(false);
            pendingResolve = null;
        }
        return new Promise((resolve) => {
            pendingResolve = resolve;
            state.value = {
                open: true,
                title: opts.title,
                message: opts.message ?? '',
                confirmText: opts.confirmText ?? 'Conferma',
                cancelText: opts.cancelText ?? 'Annulla',
                tone: opts.tone ?? 'default',
            };
        });
    }
    /** Helper interno usato dal componente <SfConfirmDialog/> al click conferma/annulla. */
    function resolveDialog(value) {
        if (pendingResolve) {
            pendingResolve(value);
            pendingResolve = null;
        }
        state.value = { ...state.value, open: false };
    }
    return {
        state,
        isOpen,
        confirm,
        resolveDialog,
    };
});
