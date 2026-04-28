/**
 * composables/useWalletTopUp.js
 * Stripe setup, card form lifecycle, top-up payment logic for wallet recharge.
 *
 * Boundary wallet:
 * - questo file ricarica il saldo prepagato;
 * - il pagamento di un ordine con wallet vive in usePayment -> /api/wallet/pay.
 */
export function useWalletTopUp(props, emit) {
	const { user, refreshIdentity } = useSanctumAuth();
	const sanctum = useSanctumClient();
	const runtimeConfig = useRuntimeConfig();

	/* ── Stripe internals ── */
	const stripePublishableKey = ref('');
	const stripeReady = ref(false);
	const stripeLoading = ref(false);
	let stripe = null;

	const isValidKey = (v) => {
		const k = String(v || '').trim();
		return k.startsWith('pk_') && !k.includes('placeholder');
	};

	/* ── Top-up state ── */
	const topUpAmount = ref('');
	const isLoading = ref(false);
	const message = ref(null);
	const messageType = ref('success');
	const topUpAttemptKey = ref('');
	const topUpAttemptSignature = ref('');
	const presetAmounts = [5, 10, 20, 50];

	/* ── New-card form state ── */
	const showNewCardForm = ref(false);
	const isPreparingNewCardForm = ref(false);
	const cardHolderName = ref('');
	const setupClientSecret = ref(null);
	const elements = ref(null);
	const cardNumber = ref(null);
	const cardExpiry = ref(null);
	const cardCvc = ref(null);
	const cardError = ref(null);

	/* ── Stripe error i18n map ── */
	const STRIPE_ERRORS = {
		card_declined: "Carta rifiutata. Contatta la tua banca o prova con un'altra carta.",
		expired_card: 'Carta scaduta. Verifica la data di scadenza.',
		incorrect_cvc: 'Codice CVC non corretto. Verifica il codice di sicurezza.',
		processing_error: "Errore temporaneo durante l'elaborazione. Riprova tra qualche minuto.",
		incorrect_number: 'Numero carta non valido. Verifica il numero inserito.',
		invalid_number: 'Numero carta non valido. Verifica il numero inserito.',
		invalid_expiry_month: 'Mese di scadenza non valido.',
		invalid_expiry_year: 'Anno di scadenza non valido.',
		invalid_cvc: 'Codice CVC non valido.',
		incomplete_number: 'Numero carta incompleto.',
		incomplete_expiry: 'Data di scadenza incompleta.',
		incomplete_cvc: 'Codice CVC incompleto.',
		insufficient_funds: 'Fondi insufficienti sulla carta.',
		lost_card: 'Carta segnalata come smarrita. Contatta la tua banca.',
		stolen_card: 'Carta segnalata come rubata. Contatta la tua banca.',
	};

	const stripeErrorMsg = (err) => STRIPE_ERRORS[err?.code] || err?.message || 'Errore durante il salvataggio della carta. Riprova.';

	/* ── Helpers ── */

	const setFeedback = (msg, type = 'error') => {
		message.value = msg;
		messageType.value = type;
		setTimeout(() => {
			message.value = null;
		}, 5000);
	};

	const makeTopUpAttemptKey = () => {
		const suffix = globalThis?.crypto?.randomUUID?.() || `${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
		return `wallet-topup-${suffix}`;
	};

	const resolveTopUpAttemptKey = (amount, paymentMethodId) => {
		const signature = `${Number(amount).toFixed(2)}:${String(paymentMethodId || '')}`;
		if (!topUpAttemptKey.value || topUpAttemptSignature.value !== signature) {
			topUpAttemptSignature.value = signature;
			topUpAttemptKey.value = makeTopUpAttemptKey();
		}
		return topUpAttemptKey.value;
	};

	const resetTopUpAttemptKey = () => {
		topUpAttemptKey.value = '';
		topUpAttemptSignature.value = '';
	};

	const unmountCardElements = () => {
		cardNumber.value?.unmount();
		cardExpiry.value?.unmount();
		cardCvc.value?.unmount();
		cardNumber.value = null;
		cardExpiry.value = null;
		cardCvc.value = null;
		elements.value = null;
	};

	const clearNewCardForm = () => {
		cardHolderName.value = '';
		setupClientSecret.value = null;
		cardError.value = null;
		unmountCardElements();
	};

	/* ── Stripe loader ── */

	const ensureStripeLoaded = async () => {
		if (stripeReady.value && stripe) return true;
		if (!props.stripeConfigured || stripeLoading.value) return false;

		stripeLoading.value = true;
		try {
			const { loadStripe } = await import('@stripe/stripe-js');
			if (!stripePublishableKey.value) {
				try {
					const cfg = await sanctum('/api/settings/stripe');
					const k = String(cfg?.publishable_key || '').trim();
					stripePublishableKey.value = isValidKey(k) ? k : '';
				} catch {
					/* fallback below */
				}
				if (!stripePublishableKey.value) {
					const fb = String(runtimeConfig.public.stripeKey || '').trim();
					stripePublishableKey.value = isValidKey(fb) ? fb : '';
				}
			}
			if (!stripePublishableKey.value) {
				cardError.value = 'Chiave Stripe non disponibile. Ricarica la pagina.';
				stripeReady.value = false;
				return false;
			}
			stripe = await loadStripe(stripePublishableKey.value);
			stripeReady.value = Boolean(stripe);
			return stripeReady.value;
		} catch {
			stripeReady.value = false;
			cardError.value = 'Impossibile caricare Stripe. Ricarica la pagina e riprova.';
			return false;
		} finally {
			stripeLoading.value = false;
		}
	};

	/* ── Mount Stripe Elements ── */

	const mountNewCardFields = async () => {
		if (!stripe || !showNewCardForm.value) return;
		await nextTick();
		elements.value = stripe.elements();
		const style = {
			base: {
				color: '#252B42',
				fontFamily: '"Inter", sans-serif',
				fontSize: '15px',
				fontWeight: '400',
				'::placeholder': { color: '#a0a0a0' },
			},
			invalid: { color: '#dc2626' },
		};
		cardNumber.value = elements.value.create('cardNumber', { style, placeholder: '1234 5678 9012 3456' });
		cardNumber.value.mount('#wallet-card-number');
		cardExpiry.value = elements.value.create('cardExpiry', { style });
		cardExpiry.value.mount('#wallet-card-expiry');
		cardCvc.value = elements.value.create('cardCvc', { style, placeholder: '123' });
		cardCvc.value.mount('#wallet-card-cvc');
	};

	/* ── Open / close card form ── */

	const openNewCardForm = async () => {
		if (!props.stripeConfigured) {
			setFeedback('Le ricariche con carta non sono ancora attive su questo sito.');
			return;
		}
		cardError.value = null;
		message.value = null;
		showNewCardForm.value = true;
		isPreparingNewCardForm.value = true;
		clearNewCardForm();
		cardHolderName.value = [user.value?.name, user.value?.surname].filter(Boolean).join(' ').trim();

		if (!(await ensureStripeLoaded())) {
			isPreparingNewCardForm.value = false;
			return;
		}

		try {
			const res = await sanctum('/api/stripe/create-setup-intent', { method: 'POST' });
			if (!res?.client_secret) {
				cardError.value = res?.error || 'Impossibile inizializzare il modulo carta. Riprova.';
				return;
			}
			setupClientSecret.value = res.client_secret;
			await mountNewCardFields();
		} catch (err) {
			cardError.value = err?.data?.error || err?.data?.message || err?.message || 'Errore di connessione al sistema di pagamento.';
		} finally {
			isPreparingNewCardForm.value = false;
		}
	};

	const closeNewCardForm = () => {
		showNewCardForm.value = false;
		clearNewCardForm();
	};

	/* ── Save new card ── */

	const saveNewCardAndGetPaymentMethodId = async () => {
		if (!setupClientSecret.value) {
			cardError.value = 'Impossibile inizializzare il modulo carta. Riprova.';
			return null;
		}
		if (!cardHolderName.value.trim()) {
			cardError.value = 'Inserisci il nome del titolare della carta.';
			return null;
		}
		if (!(await ensureStripeLoaded()) || !stripe) {
			cardError.value = 'Stripe non disponibile. Ricarica la pagina e riprova.';
			return null;
		}

		const { setupIntent, error } = await stripe.confirmCardSetup(setupClientSecret.value, {
			payment_method: { card: cardNumber.value, billing_details: { name: cardHolderName.value.trim() } },
		});
		if (error) {
			cardError.value = stripeErrorMsg(error);
			return null;
		}
		if (!setupIntent?.payment_method) {
			cardError.value = 'Metodo di pagamento non trovato. Riprova.';
			return null;
		}

		const srv = await sanctum('/api/stripe/set-default-payment-method', {
			method: 'POST',
			body: { payment_method: setupIntent.payment_method },
		});
		if (srv?.error) {
			cardError.value = srv.error || 'Errore durante il salvataggio della carta.';
			return null;
		}

		await refreshIdentity();
		emit('paymentMethodUpdated');
		closeNewCardForm();
		return setupIntent.payment_method;
	};

	/* ── Computed ── */

	const canSubmitTopUp = computed(() => {
		const amt = Number(topUpAmount.value);
		if (isLoading.value || !props.stripeConfigured || amt < 1) return false;
		if (showNewCardForm.value) return Boolean(setupClientSecret.value && cardHolderName.value.trim());
		return Boolean(props.defaultPaymentMethod?.card);
	});

	const topUpButtonLabel = computed(() => {
		if (isLoading.value) return 'Elaborazione in corso...';
		const suffix = topUpAmount.value ? ` \u20AC${Number(topUpAmount.value).toFixed(2)}` : '';
		if (showNewCardForm.value) return `Salva carta e ricarica${suffix}`;
		if (!props.defaultPaymentMethod?.card) return 'Aggiungi una carta per ricaricare';
		return `Ricarica${suffix}`;
	});

	/* ── Top-up handler ── */

	const handleTopUp = async () => {
		if (!topUpAmount.value || topUpAmount.value < 1) {
			setFeedback('Inserisci un importo minimo di 1,00 EUR');
			return;
		}
		if (!props.stripeConfigured) {
			setFeedback('Le ricariche con carta non sono ancora attive su questo sito.');
			return;
		}

		isLoading.value = true;
		message.value = null;
		cardError.value = null;

		try {
			let pmId = props.defaultPaymentMethod?.card?.id || null;
			if (showNewCardForm.value || !pmId) pmId = await saveNewCardAndGetPaymentMethodId();
			if (!pmId) {
				setFeedback('Aggiungi e salva una carta valida per completare la ricarica.');
				return;
			}
			const idempotencyKey = resolveTopUpAttemptKey(topUpAmount.value, pmId);

			const result = await sanctum('/api/wallet/top-up', {
				method: 'POST',
				body: {
					amount: Number(topUpAmount.value),
					payment_method_id: pmId,
					idempotency_key: idempotencyKey,
				},
			});

			if (result?.success) {
				setFeedback(`Ricarica di \u20AC${Number(topUpAmount.value).toFixed(2)} completata!`, 'success');
				topUpAmount.value = '';
				resetTopUpAttemptKey();
				emit('topUpSuccess');
			} else {
				setFeedback(result?.message || 'Errore durante la ricarica.');
			}
		} catch (e) {
			setFeedback(e?.response?._data?.message || e?.data?.message || 'Errore imprevisto. Riprova.');
		} finally {
			isLoading.value = false;
		}
	};

	const selectPreset = (amount) => {
		topUpAmount.value = amount;
	};

	/* ── Cleanup ── */
	onBeforeUnmount(() => {
		clearNewCardForm();
	});

	return {
		/* top-up state */
		topUpAmount,
		isLoading,
		message,
		messageType,
		presetAmounts,
		/* card form state */
		showNewCardForm,
		isPreparingNewCardForm,
		cardHolderName,
		cardError,
		/* computed */
		canSubmitTopUp,
		topUpButtonLabel,
		/* actions */
		selectPreset,
		handleTopUp,
		openNewCardForm,
		closeNewCardForm,
	};
}
