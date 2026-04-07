<!--
  FILE: pages/account/carte.vue
  SCOPO: Gestione carte Stripe — orchestratore con sotto-componenti AccountCarteList e AccountCarteForm.
  La logica Stripe (loadStripe, setup-intent, confirm) resta qui perche' dipende dal ciclo di vita della pagina.
-->
<script setup>
// account-carte styles are in checkout.css (loaded globally via main.css)

useHead({
	link: [
		{ rel: 'preconnect', href: 'https://js.stripe.com', crossorigin: '' },
		{ rel: 'preconnect', href: 'https://api.stripe.com', crossorigin: '' },
	],
});

definePageMeta({ middleware: ['app-auth'] });

useSeoMeta({
	title: 'Carte account | SpediamoFacile',
	ogTitle: 'Carte account | SpediamoFacile',
	description: 'Gestisci carte salvate e metodo di pagamento predefinito dal tuo account SpediamoFacile.',
	ogDescription: 'Carte salvate e metodi di pagamento dell account SpediamoFacile.',
});

const { refreshIdentity, user } = useSanctumAuth();
const runtimeConfig = useRuntimeConfig();
const client = useSanctumClient();
const isAdmin = computed(() => user.value?.role === 'Admin');

/* ===== STRIPE CONFIG ===== */
const stripeConfigured = ref(false);
const stripePublishableKey = ref('');
const configLoading = ref(true);
const isValidStripePublishableKey = (value) => {
	const key = String(value || '').trim();
	return key.startsWith('pk_') && !key.includes('placeholder');
};
const cardsFeatureAvailable = computed(() => stripeConfigured.value);

try {
	const config = await client('/api/settings/stripe');
	const key = String(config?.publishable_key || '').trim();
	stripePublishableKey.value = key;
	stripeConfigured.value = Boolean(config?.configured) && isValidStripePublishableKey(key);
} catch (e) {
	const fallbackKey = String(runtimeConfig.public.stripeKey || '').trim();
	stripePublishableKey.value = isValidStripePublishableKey(fallbackKey) ? fallbackKey : '';
	stripeConfigured.value = isValidStripePublishableKey(fallbackKey);
}
configLoading.value = false;

const openAdminStripeSettings = () => navigateTo('/account/amministrazione/impostazioni');

let stripe = null;
if (isValidStripePublishableKey(stripePublishableKey.value)) {
	try {
		const { loadStripe } = await import('@stripe/stripe-js');
		stripe = await loadStripe(stripePublishableKey.value);
	} catch (e) {
		/* stripe non caricabile */
	}
}

/* ===== CARD MANAGEMENT ===== */
const cardNumber = ref(null);
const cardExpiry = ref(null);
const cardCvc = ref(null);
const clientSecret = ref(null);
const elements = ref(null);
const errorMessage = ref(null);
const cardHolderName = ref('');
const showFormPayments = ref(false);
const textMessage = ref('');
const textMessageType = ref('info');
const deleteConfirmId = ref(null);

const { data: payments, status, refresh } = useSanctumFetch('/api/stripe/payment-methods', { lazy: true });

const getStripeErrorMessage = (error) => {
	const errorMap = {
		card_declined: "Carta rifiutata. Contatta la tua banca o prova con un'altra carta.",
		expired_card: 'Carta scaduta. Verifica la data di scadenza.',
		incorrect_cvc: 'Codice CVC non corretto. Verifica il codice di sicurezza.',
		processing_error: "Errore durante l'elaborazione. Riprova tra qualche minuto.",
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
	return errorMap[error.code] || error.message || 'Errore durante il salvataggio della carta. Riprova.';
};

const handleAddCard = async () => {
	if (!clientSecret.value) {
		errorMessage.value = 'Impossibile procedere. Riprova.';
		return;
	}
	textMessage.value = 'Salvataggio carta in corso...';
	textMessageType.value = 'info';
	errorMessage.value = null;
	try {
		if (!stripe) {
			errorMessage.value = 'Stripe non disponibile. Ricarica la pagina.';
			return;
		}
		const { setupIntent, error } = await stripe.confirmCardSetup(clientSecret.value, {
			payment_method: { card: cardNumber.value, billing_details: { name: cardHolderName.value } },
		});
		if (error) {
			errorMessage.value = getStripeErrorMessage(error);
			textMessage.value = null;
			return;
		}
		if (!setupIntent?.payment_method) {
			errorMessage.value = 'Metodo di pagamento non trovato. Riprova.';
			return;
		}
		const serverResponse = await client('/api/stripe/set-default-payment-method', {
			method: 'POST',
			body: { payment_method: setupIntent.payment_method },
		});
		if (serverResponse?.error) {
			errorMessage.value = serverResponse.error || 'Errore server. Riprova.';
			return;
		}
		await refresh();
		await refreshIdentity();
		textMessage.value = 'Carta aggiunta con successo!';
		textMessageType.value = 'success';
		showFormPayments.value = false;
		setTimeout(() => {
			textMessage.value = '';
		}, 3000);
	} catch (err) {
		errorMessage.value = 'Errore imprevisto. Riprova.';
	}
};

const setDefault = async (pmId) => {
	textMessage.value = 'Impostazione carta predefinita...';
	textMessageType.value = 'info';
	try {
		const data = await client('/api/stripe/change-default-payment-method', { method: 'POST', body: { payment_method_id: pmId } });
		if (data?.success) {
			textMessage.value = 'Carta predefinita aggiornata.';
			textMessageType.value = 'success';
			await refresh();
			setTimeout(() => {
				textMessage.value = '';
			}, 3000);
		}
	} catch (e) {
		textMessage.value = 'Errore durante la modifica.';
		textMessageType.value = 'error';
	}
};

const deleteCard = async (pmId) => {
	textMessage.value = 'Eliminazione in corso...';
	textMessageType.value = 'info';
	try {
		const data = await client('/api/stripe/delete-card', { method: 'DELETE', body: { payment_method_id: pmId } });
		if (data?.success) {
			await refresh();
			deleteConfirmId.value = null;
			textMessage.value = 'Carta eliminata.';
			textMessageType.value = 'success';
			setTimeout(() => {
				textMessage.value = '';
			}, 3000);
		}
	} catch (error) {
		textMessage.value = "Errore durante l'eliminazione.";
		textMessageType.value = 'error';
	}
};

const togglePaymentForm = async () => {
	if (showFormPayments.value) {
		cardHolderName.value = '';
		cardNumber.value?.unmount();
		cardExpiry.value?.unmount();
		cardCvc.value?.unmount();
		cardNumber.value = null;
		cardExpiry.value = null;
		cardCvc.value = null;
		clientSecret.value = null;
		showFormPayments.value = false;
		elements.value = null;
		errorMessage.value = null;
	} else {
		clientSecret.value = null;
		errorMessage.value = null;
		if (!stripe) {
			errorMessage.value = 'Stripe non disponibile. Ricarica la pagina o configura le chiavi API.';
			textMessage.value = errorMessage.value;
			textMessageType.value = 'error';
			return;
		}
		try {
			const response = await client('/api/stripe/create-setup-intent', { method: 'POST' });
			if (!response?.client_secret) {
				errorMessage.value = response?.error || 'Impossibile inizializzare il modulo di pagamento. Riprova.';
				textMessage.value = errorMessage.value;
				textMessageType.value = 'error';
				return;
			}
			clientSecret.value = response.client_secret;
			showFormPayments.value = true;
			await nextTick();
			elements.value = stripe.elements();
			const style = {
				base: {
					color: 'var(--color-brand-text)',
					fontFamily: '"Inter", sans-serif',
					fontSize: '15px',
					fontWeight: '400',
					'::placeholder': { color: '#a0a0a0' },
				},
				invalid: { color: '#dc2626' },
			};
			cardNumber.value = elements.value.create('cardNumber', { style, placeholder: '1234 5678 9012 3456' });
			cardNumber.value.mount('#card-number');
			cardExpiry.value = elements.value.create('cardExpiry', { style });
			cardExpiry.value.mount('#card-expiry');
			cardCvc.value = elements.value.create('cardCvc', { style, placeholder: '123' });
			cardCvc.value.mount('#card-cvc');
		} catch (err) {
			const msg = err?.data?.error || err?.data?.message || err?.message || 'Errore di connessione al sistema di pagamento.';
			errorMessage.value = msg;
			textMessage.value = msg;
			textMessageType.value = 'error';
		}
	}
};
</script>

<template>
	<section class="min-h-[600px] py-[28px] desktop:py-[56px] bg-white">
		<div class="my-container">
			<AccountPageHeader
				:title="showFormPayments ? 'Aggiungi carta' : 'Carte e pagamenti'"
				description="Carte e pagamenti salvati."
				:crumbs="
					showFormPayments
						? [{ label: 'Account', to: '/account' }, { label: 'Carte e pagamenti', to: '/account/carte' }, { label: 'Aggiungi carta' }]
						: [{ label: 'Account', to: '/account' }, { label: 'Carte e pagamenti' }]
				">
				<template v-if="!showFormPayments" #actions>
					<div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-[8px]">
						<button
							v-if="isAdmin"
							type="button"
							@click="openAdminStripeSettings"
							class="btn-cta btn-compact w-full sm:w-auto"
							title="Gestisci la configurazione globale di Stripe">
							<svg
								width="15"
								height="15"
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								stroke-width="2"
								stroke-linecap="round"
								stroke-linejoin="round"
								class="align-middle mr-[4px] inline">
								<circle cx="12" cy="12" r="3" />
								<path
									d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
							</svg>
							Impostazioni Stripe
						</button>
						<button
							v-if="cardsFeatureAvailable"
							type="button"
							@click="togglePaymentForm"
							class="btn-cta btn-compact w-full sm:w-auto inline-flex items-center justify-center gap-[6px]">
							<svg
								width="17"
								height="17"
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								stroke-width="2.5"
								stroke-linecap="round"
								stroke-linejoin="round">
								<line x1="12" y1="5" x2="12" y2="19" />
								<line x1="5" y1="12" x2="19" y2="12" />
							</svg>
							Aggiungi carta
						</button>
					</div>
				</template>
			</AccountPageHeader>

			<!-- Wallet link bar -->
			<div
				v-if="!showFormPayments"
				class="mb-[16px] rounded-[12px] border border-[var(--color-brand-border)] bg-white px-[16px] py-[14px] shadow-sm desktop:px-[18px]">
				<div class="flex flex-col gap-[10px] tablet:flex-row tablet:items-center tablet:justify-between">
					<div>
						<p class="text-[0.75rem] font-semibold uppercase tracking-[0.08em] text-[var(--color-brand-primary)]">Metodi salvati</p>
						<p class="mt-[3px] text-[0.875rem] leading-[1.5] text-[#667281]">
							{{ payments?.data?.length ? `${payments.data.length} carte salvate.` : 'Carte pronte per checkout e wallet.' }}
						</p>
					</div>
					<NuxtLink
						to="/account/portafoglio"
						class="btn-secondary btn-compact inline-flex min-h-[42px] items-center justify-center px-[14px] py-[8px] text-[0.8125rem] font-semibold">
						Apri portafoglio
					</NuxtLink>
				</div>
			</div>

			<!-- Feedback -->
			<div
				v-if="textMessage"
				:class="[
					'mb-[16px] px-[14px] py-[10px] rounded-[12px] text-[0.8125rem] font-medium transition-all',
					textMessageType === 'success'
						? 'bg-[#f0fdf4] text-[#166534] ring-[1px] ring-[#166534]/10'
						: textMessageType === 'error'
							? 'bg-[#FFF5F2] text-[#E44203] ring-[1px] ring-[#E44203]/10'
							: 'bg-[#eef7f8] text-[#095866] border border-[#B8DDE3]',
				]">
				{{ textMessage }}
			</div>

			<!-- Stripe not configured banner -->
			<div
				v-if="!stripeConfigured && !configLoading && !showFormPayments"
				class="mb-[20px] p-[16px] bg-amber-50 border border-amber-200 rounded-[12px]">
				<div class="flex items-start gap-[12px]">
					<div class="w-[40px] h-[40px] rounded-[50px] bg-amber-100 flex items-center justify-center shrink-0">
						<svg
							width="20"
							height="20"
							viewBox="0 0 24 24"
							fill="none"
							stroke="#d97706"
							stroke-width="2"
							stroke-linecap="round"
							stroke-linejoin="round">
							<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
							<line x1="12" y1="9" x2="12" y2="13" />
							<line x1="12" y1="17" x2="12.01" y2="17" />
						</svg>
					</div>
					<div class="flex-1">
						<h3 class="text-[0.875rem] font-bold text-[var(--color-brand-text)] mb-[4px]">Stripe non configurato</h3>
						<p class="text-[0.75rem] text-[var(--color-brand-text-secondary)] leading-[1.5] mb-[10px]">
							<span v-if="isAdmin">Per abilitare carte, checkout e ricariche wallet configura Stripe dal pannello amministrazione.</span>
							<span v-else>
								I pagamenti con carta non sono ancora attivi su questo sito. Quando Stripe sarà configurato dall'amministratore potrai
								aggiungere qui le tue carte, usarle al checkout e ricaricare il wallet.
							</span>
						</p>
						<button v-if="isAdmin" @click="openAdminStripeSettings" class="btn-secondary btn-compact inline-flex items-center gap-[6px]">
							<svg
								width="15"
								height="15"
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								stroke-width="2"
								stroke-linecap="round"
								stroke-linejoin="round">
								<circle cx="12" cy="12" r="3" />
								<path
									d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
							</svg>
							Vai alle impostazioni admin
						</button>
					</div>
				</div>
			</div>

			<!-- Card list -->
			<AccountCarteList
				v-if="!showFormPayments"
				:payments="payments"
				:status="status"
				:cards-feature-available="cardsFeatureAvailable"
				:is-admin="isAdmin"
				:delete-confirm-id="deleteConfirmId"
				@toggle-form="togglePaymentForm"
				@set-default="setDefault"
				@delete="deleteCard"
				@ask-delete="deleteConfirmId = $event"
				@cancel-delete="deleteConfirmId = null"
				@open-admin-settings="openAdminStripeSettings" />

			<!-- Add card form -->
			<AccountCarteForm
				v-if="showFormPayments"
				v-model:card-holder-name="cardHolderName"
				:error-message="errorMessage"
				@save="handleAddCard"
				@cancel="togglePaymentForm" />
		</div>
	</section>
</template>
