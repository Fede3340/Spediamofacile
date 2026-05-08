/**
 * useFlashMessage — feedback success/error universale per pagine SpediamoFacile.
 * Pattern unico per tutte le pagine: account, admin, public.
 *
 * Uso:
 *   const { message, showSuccess, showError, clear } = useFlashMessage();
 *   showSuccess('Indirizzo salvato');
 *   showError(err, 'Impossibile salvare');
 *
 * Auto-dismiss dopo `autoDismissMs` (default 5s). Cleanup automatico su unmount/HMR.
 */

export type FlashMessage = { type: 'success' | 'error'; text: string } | null;

type FlashError = {
	response?: { _data?: { message?: string } }
	data?: { message?: string }
	message?: string
};

interface FlashMessageOptions {
	autoDismissMs?: number;
}

export const useFlashMessage = (options: FlashMessageOptions = {}) => {
	const autoDismissMs = options.autoDismissMs ?? 5000;
	const message = ref<FlashMessage>(null);
	let dismissTimer: ReturnType<typeof setTimeout> | null = null;

	const scheduleDismiss = () => {
		if (dismissTimer) clearTimeout(dismissTimer);
		dismissTimer = setTimeout(() => {
			message.value = null;
			dismissTimer = null;
		}, autoDismissMs);
	};

	const showSuccess = (text: string) => {
		message.value = { type: 'success', text };
		scheduleDismiss();
	};

	const showError = (e: unknown, fallback: string) => {
		const errObj = e as FlashError;
		message.value = {
			type: 'error',
			text: errObj?.response?._data?.message || errObj?.data?.message || errObj?.message || fallback,
		};
		scheduleDismiss();
	};

	const clear = () => {
		if (dismissTimer) {
			clearTimeout(dismissTimer);
			dismissTimer = null;
		}
		message.value = null;
	};

	onScopeDispose(() => {
		if (dismissTimer) {
			clearTimeout(dismissTimer);
			dismissTimer = null;
		}
	});

	return { message, showSuccess, showError, clear };
};
