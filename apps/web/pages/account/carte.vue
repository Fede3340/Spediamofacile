<script setup>
// account-carte styles are in checkout.css (loaded globally via main.css)

useHead({ link: [
	{ rel: 'preconnect', href: 'https://js.stripe.com', crossorigin: '' },
	{ rel: 'preconnect', href: 'https://api.stripe.com', crossorigin: '' },
] });
definePageMeta({ middleware: ['app-auth'] });
useSeoMeta({
	title: 'Carte account', ogTitle: 'Carte account',
	description: 'Gestisci carte salvate e metodo di pagamento predefinito dal tuo account SpediamoFacile.',
	ogDescription: 'Carte salvate e metodi di pagamento dell account SpediamoFacile.',
	robots: 'noindex, nofollow',
});

const { refreshIdentity, user } = useSanctumAuth();
const runtimeConfig = useRuntimeConfig();
const client = useSanctumClient();
const isAdmin = computed(() => user.value?.role === 'Admin');

/* ===== STRIPE CONFIG ===== */
const isValidKey = (v) => { const k = String(v || '').trim(); return k.startsWith('pk_') && !k.includes('placeholder'); };
const stripeConfigured = ref(false);
const stripePublishableKey = ref('');
const configLoading = ref(true);
const cardsFeatureAvailable = computed(() => stripeConfigured.value);
try {
	const config = await client('/api/settings/stripe');
	stripePublishableKey.value = String(config?.publishable_key || '').trim();
	stripeConfigured.value = Boolean(config?.configured) && isValidKey(stripePublishableKey.value);
} catch {
	const fb = String(runtimeConfig.public.stripeKey || '').trim();
	stripePublishableKey.value = isValidKey(fb) ? fb : '';
	stripeConfigured.value = isValidKey(fb);
}
configLoading.value = false;
const openAdminStripeSettings = () => navigateTo('/account/amministrazione/impostazioni');
let stripe = null;
if (isValidKey(stripePublishableKey.value)) {
	try {
		const { loadStripe } = await import('@stripe/stripe-js');
		stripe = await loadStripe(stripePublishableKey.value);
	} catch { /* stripe non caricabile */ }
}

/* ===== CARD MANAGEMENT ===== */
const cardElements = ref({ cardNumber: null, cardExpiry: null, cardCvc: null });
const clientSecret = ref(null);
const elements = ref(null);
const errorMessage = ref(null);
const cardHolderName = ref('');
const showFormPayments = ref(false);
const textMessage = ref('');
const textMessageType = ref('info');
const deleteConfirmId = ref(null);
// Auto-dismiss centralizzato: previene accumulo timer + leak su navigazione mid-message.
let textMessageTimer = null;
const setMessage = (msg, type = 'info', autoDismiss = false) => {
	textMessage.value = msg; textMessageType.value = type;
	if (textMessageTimer) { clearTimeout(textMessageTimer); textMessageTimer = null; }
	if (autoDismiss) textMessageTimer = setTimeout(() => { textMessage.value = ''; textMessageTimer = null; }, 3000);
};
onBeforeUnmount(() => { if (textMessageTimer) clearTimeout(textMessageTimer); });

const { data: payments, status, refresh } = useSanctumFetch('/api/stripe/payment-methods', { lazy: true });
const paymentItems = computed(() => (Array.isArray(payments.value?.data) ? payments.value.data : []));
const cardsStats = computed(() => ({
	total: paymentItems.value.length,
	defaults: paymentItems.value.filter((p) => p?.default).length,
}));
const BRAND_LABELS = { visa: 'Visa', mastercard: 'Mastercard', amex: 'Amex', discover: 'Discover' };
const defaultPaymentLabel = computed(() => {
	const d = paymentItems.value.find((p) => p?.default);
	if (!d) return 'Nessuna ancora';
	return `${BRAND_LABELS[String(d.brand || '').toLowerCase()] || d.brand || 'Carta'} •••• ${d.last4}`;
});
const cardsHeader = computed(() => showFormPayments.value
	? { title: 'Aggiungi carta', description: 'Salva un metodo di pagamento in modo sicuro per checkout, wallet e prossime spedizioni.' }
	: { title: 'Carte e pagamenti', description: 'Gestisci metodi salvati e wallet senza uscire dalla tua area account.' });

const NUM_BAD = 'Numero carta non valido. Verifica il numero inserito.';
const REPORT = (kind) => `Carta segnalata come ${kind}. Contatta la tua banca.`;
const STRIPE_ERRORS = {
	card_declined: "Carta rifiutata. Contatta la tua banca o prova con un'altra carta.",
	expired_card: 'Carta scaduta. Verifica la data di scadenza.',
	incorrect_cvc: 'Codice CVC non corretto. Verifica il codice di sicurezza.',
	processing_error: "Errore durante l'elaborazione. Riprova tra qualche minuto.",
	incorrect_number: NUM_BAD, invalid_number: NUM_BAD,
	invalid_expiry_month: 'Mese di scadenza non valido.', invalid_expiry_year: 'Anno di scadenza non valido.', invalid_cvc: 'Codice CVC non valido.',
	incomplete_number: 'Numero carta incompleto.', incomplete_expiry: 'Data di scadenza incompleta.', incomplete_cvc: 'Codice CVC incompleto.',
	insufficient_funds: 'Fondi insufficienti sulla carta.',
	lost_card: REPORT('smarrita'), stolen_card: REPORT('rubata'),
};
const getStripeErrorMessage = (e) => STRIPE_ERRORS[e.code] || e.message || 'Errore durante il salvataggio della carta. Riprova.';

const failAdd = (msg, clearText = false) => { errorMessage.value = msg; if (clearText) textMessage.value = null; };
const handleAddCard = async () => {
	if (!clientSecret.value) return failAdd('Impossibile procedere. Riprova.');
	setMessage('Salvataggio carta in corso...', 'info');
	errorMessage.value = null;
	try {
		if (!stripe) return failAdd('Stripe non disponibile. Ricarica la pagina.');
		const { setupIntent, error } = await stripe.confirmCardSetup(clientSecret.value, {
			payment_method: { card: cardElements.value.cardNumber, billing_details: { name: cardHolderName.value } },
		});
		if (error) return failAdd(getStripeErrorMessage(error), true);
		if (!setupIntent?.payment_method) return failAdd('Metodo di pagamento non trovato. Riprova.');
		const res = await client('/api/stripe/set-default-payment-method', { method: 'POST', body: { payment_method: setupIntent.payment_method } });
		if (res?.error) return failAdd(res.error || 'Errore server. Riprova.');
		await refresh();
		await refreshIdentity();
		setMessage('Carta aggiunta con successo!', 'success', true);
		showFormPayments.value = false;
	} catch { failAdd('Errore imprevisto. Riprova.'); }
};

const runCardAction = async (pending, success, errorMsg, fetcher, onSuccess) => {
	setMessage(pending, 'info');
	try {
		const data = await fetcher();
		if (data?.success) { await refresh(); onSuccess?.(); setMessage(success, 'success', true); }
	} catch { setMessage(errorMsg, 'error'); }
};
const setDefault = (pmId) => runCardAction('Impostazione carta predefinita...', 'Carta predefinita aggiornata.', 'Errore durante la modifica.',
	() => client('/api/stripe/change-default-payment-method', { method: 'POST', body: { payment_method_id: pmId } }));
const deleteCard = (pmId) => runCardAction('Eliminazione in corso...', 'Carta eliminata.', "Errore durante l'eliminazione.",
	() => client('/api/stripe/delete-card', { method: 'DELETE', body: { payment_method_id: pmId } }), () => { deleteConfirmId.value = null; });

const STRIPE_FIELDS = [
	{ key: 'cardNumber', mountId: '#card-number', opts: { placeholder: '1234 5678 9012 3456' } },
	{ key: 'cardExpiry', mountId: '#card-expiry', opts: {} },
	{ key: 'cardCvc', mountId: '#card-cvc', opts: { placeholder: '123' } },
];

const resetCardForm = () => {
	cardHolderName.value = '';
	for (const { key } of STRIPE_FIELDS) { cardElements.value[key]?.unmount(); cardElements.value[key] = null; }
	clientSecret.value = null; showFormPayments.value = false; elements.value = null; errorMessage.value = null;
};

const mountStripeElements = () => {
	elements.value = stripe.elements();
	const style = {
		base: { color: 'var(--color-brand-text)', fontFamily: '"Inter", sans-serif', fontSize: '15px', fontWeight: '400', '::placeholder': { color: '#a0a0a0' } },
		invalid: { color: '#dc2626' },
	};
	for (const { key, mountId, opts } of STRIPE_FIELDS) {
		const el = elements.value.create(key, { style, ...opts });
		el.mount(mountId);
		cardElements.value[key] = el;
	}
};

const setError = (msg) => { errorMessage.value = msg; setMessage(msg, 'error'); };

const togglePaymentForm = async () => {
	if (showFormPayments.value) { resetCardForm(); return; }
	clientSecret.value = null; errorMessage.value = null;
	if (!stripe) return setError('Stripe non disponibile. Ricarica la pagina o configura le chiavi API.');
	try {
		const response = await client('/api/stripe/create-setup-intent', { method: 'POST' });
		if (!response?.client_secret) return setError(response?.error || 'Impossibile inizializzare il modulo di pagamento. Riprova.');
		clientSecret.value = response.client_secret;
		showFormPayments.value = true;
		await nextTick();
		mountStripeElements();
	} catch (err) {
		setError(err?.data?.error || err?.data?.message || err?.message || 'Errore di connessione al sistema di pagamento.');
	}
};

const feedbackTone = computed(() => {
	if (textMessageType.value === 'error') return 'danger';
	if (textMessageType.value === 'success') return 'success';
	return 'info';
});

const headerAction = computed(() => {
	if (showFormPayments.value) return null;
	if (cardsFeatureAvailable.value) return { label: 'Aggiungi carta', icon: 'mdi:plus', iconSize: 17, variant: 'btn-primary', onClick: togglePaymentForm };
	if (isAdmin.value) return { label: 'Impostazioni Stripe', icon: 'mdi:cog', iconSize: 15, variant: 'btn-secondary', onClick: openAdminStripeSettings, title: 'Gestisci la configurazione globale di Stripe' };
	return null;
});
</script>

<template>
	<AccountPageSection max-width="">
			<AccountPageHeader :title="cardsHeader.title" :description="cardsHeader.description" current="Carte">
				<template #actions>
					<div class="flex flex-wrap items-center gap-[8px]">
						<span class="sf-section-chip">{{ cardsStats.total }} salvate</span>
						<span class="sf-section-chip">{{ cardsStats.defaults }} predefinita</span>
						<button
							v-if="headerAction"
							type="button"
							:class="[headerAction.variant, 'btn-compact inline-flex min-h-[42px] items-center justify-center gap-[6px] px-[16px] text-[0.8125rem]']"
							:title="headerAction.title"
							@click="headerAction.onClick">
							<UIcon :name="headerAction.icon" :style="{ width: headerAction.iconSize + 'px', height: headerAction.iconSize + 'px' }" />
							{{ headerAction.label }}
						</button>
					</div>
				</template>
			</AccountPageHeader>

			<!-- Banner duplicato "Pagamenti pronti" rimosso (P13): ripeteva cardsStats già nei chip header.
			     Tenuto solo "Metodo predefinito" come info utile distinta. -->
			<div v-if="!showFormPayments" class="mb-[20px] rounded-[18px] bg-[#F8FCFD] px-[16px] py-[14px]" style="box-shadow: 0 1px 3px rgba(9,88,102,0.06);">
				<p class="text-[0.75rem] font-semibold uppercase tracking-[1px] text-[var(--color-brand-primary)]">Metodo predefinito</p>
				<p class="mt-[6px] text-[1rem] font-bold text-[var(--color-brand-text)]">{{ defaultPaymentLabel }}</p>
				<p class="mt-[4px] text-[0.875rem] leading-[1.5] text-[var(--color-brand-text-secondary)]">
					{{ cardsStats.defaults ? 'La carta principale resta in evidenza e puoi cambiarla in un tocco.' : 'Appena scegli una carta predefinita la vedrai qui, sempre pronta per checkout e wallet.' }}
				</p>
			</div>

			<!-- Feedback (banner unificato pattern canonico, tone derivato da textMessageType) -->
			<SfActionBanner :message="textMessage" :tone="feedbackTone" class="mb-[16px]" />

			<!-- Stripe not configured banner -->
			<div
				v-if="!stripeConfigured && !configLoading && !showFormPayments"
				class="mb-[20px] p-[16px] bg-amber-50 rounded-[18px] sf-animate-in sf-animate-in-2"
				style="box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
				<div class="flex items-start gap-[12px]">
					<div class="w-[36px] h-[36px] rounded-full bg-amber-100 flex items-center justify-center shrink-0">
						<UIcon name="mdi:alert" class="w-5 h-5 text-amber-600" />
					</div>
					<div class="flex-1">
						<h3 class="text-[0.875rem] font-bold text-[var(--color-brand-text)] mb-[4px]">Stripe non configurato</h3>
						<p class="text-[0.75rem] text-[var(--color-brand-text-secondary)] leading-[1.5] mb-[10px]">
							{{ isAdmin
								? 'Per abilitare carte, checkout e ricariche wallet configura Stripe dal pannello amministrazione.'
								: "I pagamenti con carta non sono ancora attivi su questo sito. Quando Stripe sara' configurato dall'amministratore potrai aggiungere qui le tue carte, usarle al checkout e ricaricare il wallet." }}
						</p>
						<button v-if="isAdmin" class="btn-secondary btn-compact inline-flex items-center gap-[6px]" @click="openAdminStripeSettings">
							<UIcon name="mdi:cog" class="w-[15px] h-[15px]" />
							Vai alle impostazioni admin
						</button>
					</div>
				</div>
			</div>

			<!-- Card list -->
			<AccountCarteList
				v-if="!showFormPayments"
				:payments="payments" :status="status" :cards-feature-available="cardsFeatureAvailable" :is-admin="isAdmin" :delete-confirm-id="deleteConfirmId"
				@toggle-form="togglePaymentForm" @set-default="setDefault" @delete="deleteCard"
				@ask-delete="deleteConfirmId = $event" @cancel-delete="deleteConfirmId = null" @open-admin-settings="openAdminStripeSettings" />

			<!-- Add card form -->
			<AccountCarteForm v-if="showFormPayments" v-model:card-holder-name="cardHolderName" :error-message="errorMessage" @save="handleAddCard" @cancel="togglePaymentForm" />
	</AccountPageSection>
</template>
