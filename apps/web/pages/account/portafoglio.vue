<!-- FILE: pages/account/portafoglio.vue -->
<script setup>
import { formatEuro } from '~/utils/price.js';

/* Preconnect to Stripe only on this page */
useHead({
	link: [
		{ rel: 'preconnect', href: 'https://js.stripe.com', crossorigin: '' },
		{ rel: 'preconnect', href: 'https://api.stripe.com', crossorigin: '' },
	],
});

definePageMeta({ middleware: ['app-auth'] });

useSeoMeta({
	title: 'Portafoglio account',
	ogTitle: 'Portafoglio account',
	description: 'Controlla saldo, ricariche e movimenti del portafoglio dal tuo account SpediamoFacile.',
	ogDescription: 'Saldo, ricariche e storico movimenti del portafoglio SpediamoFacile.',
	robots: 'noindex, nofollow',
});

const { user } = useSanctumAuth();
const { uiSnapshot } = useAuthUiState();
const sanctum = useSanctumClient();

/* Saldo del portafoglio (principale e commissioni) */
const balance = ref(null);
/* Lista dei movimenti (ricariche, pagamenti, commissioni, ecc.) */
const movements = ref([]);
/* Carta di pagamento predefinita dell'utente (per la ricarica) */
const defaultPaymentMethod = ref(null);
/* Indicatori di caricamento */
const isLoadingBalance = ref(true);
const isLoadingMovements = ref(true);
const isRefreshingWallet = ref(false);
/* Stato errori */
const balanceError = ref('');
const movementsError = ref('');
/* Stripe disponibilita' */
const stripeConfigured = ref(false);

const isValidStripePublishableKey = (value) => {
	const key = String(value || '').trim();
	return key.startsWith('pk_') && !key.includes('placeholder');
};

const getRequestErrorMessage = (error, fallback) => {
	return error?.response?._data?.message || error?.data?.message || error?.message || fallback;
};

const formatCardBrand = (brand) => {
	const value = String(brand || '').trim();
	if (!value) return 'Carta';
	return value.charAt(0).toUpperCase() + value.slice(1);
};

/* Controlla se l'utente è un Partner Pro */
const effectiveRole = computed(() => uiSnapshot.value.role || user.value?.role || null);
const isPro = computed(() => effectiveRole.value === 'Partner Pro');

const movementCount = computed(() => movements.value.length || 0);
const movementCountLabel = computed(() => {
	if (isLoadingMovements.value && !movementCount.value) return 'Caricamento';
	if (movementsError.value && !movementCount.value) return 'Da aggiornare';
	if (!movementCount.value) return 'Ancora nessuno';
	return `${movementCount.value} ${movementCount.value === 1 ? 'movimento' : 'movimenti'}`;
});

const defaultPaymentMethodLabel = computed(() => {
	if (defaultPaymentMethod.value?.card) {
		return `${formatCardBrand(defaultPaymentMethod.value.card.brand)} •••• ${defaultPaymentMethod.value.card.last4}`;
	}
	if (!stripeConfigured.value) return 'Ricariche non attive';
	return 'Da aggiungere';
});

const balanceOverviewValue = computed(() => {
	if (isLoadingBalance.value && !balance.value) return 'Caricamento';
	if (balanceError.value && !balance.value) return 'Saldo non disponibile';
	return `\u20AC${formatEuro(balance.value?.balance || 0)}`;
});

const commissionOverviewValue = computed(() => {
	if (isLoadingBalance.value && !balance.value) return 'Caricamento';
	if (balanceError.value && !balance.value) return 'Da verificare';
	return `\u20AC${formatEuro(balance.value?.commission_balance || 0)}`;
});

const walletHeaderStats = computed(() => [
	{
		label: 'Saldo',
		value:
			isLoadingBalance.value && !balance.value
				? 'Caricamento'
				: balanceError.value && !balance.value
					? 'Da verificare'
					: `\u20AC${formatEuro(balance.value?.balance || 0)}`,
	},
	{ label: 'Movimenti', value: movementCountLabel.value },
	{ label: 'Carta', value: defaultPaymentMethodLabel.value },
]);

const walletHeroHighlights = computed(() => {
	const items = [
		{
			label: 'Carta predefinita',
			value: defaultPaymentMethodLabel.value,
			description: stripeConfigured.value
				? 'Pronta per le prossime ricariche.'
				: 'Serve una carta salvata per ricaricare.',
		},
		{
			label: 'Storico',
			value: movementCountLabel.value,
			description: movementsError.value
				? 'Ultimo elenco da verificare.'
				: 'Pagamenti, ricariche e rimborsi.',
		},
	];

	if (isPro.value) {
		items.push({
			label: 'Commissioni Pro',
			value: commissionOverviewValue.value,
			description: 'Restano separate dal saldo wallet.',
		});
	} else {
		items.push({
			label: 'Ricarica',
			value: stripeConfigured.value ? 'Carta attiva' : 'Da configurare',
			description: stripeConfigured.value
				? 'Puoi confermare in pochi secondi.'
				: 'Attiva una carta per iniziare.',
		});
	}

	return items;
});

/* --- Data fetching --- */

const fetchBalance = async ({ showLoader = true } = {}) => {
	if (showLoader) isLoadingBalance.value = true;
	balanceError.value = '';

	try {
		const res = await sanctum('/api/wallet/balance');
		balance.value = res || { balance: 0, commission_balance: 0 };
	} catch (error) {
		balanceError.value = getRequestErrorMessage(error, 'Non sono riuscito a recuperare il saldo del portafoglio.');
		if (!balance.value) balance.value = null;
	} finally {
		isLoadingBalance.value = false;
	}
};

const fetchMovements = async ({ showLoader = true } = {}) => {
	if (showLoader) isLoadingMovements.value = true;
	movementsError.value = '';

	try {
		const res = await sanctum('/api/wallet/movements');
		movements.value = res?.data || res || [];
	} catch (error) {
		movementsError.value = getRequestErrorMessage(error, 'Non sono riuscito ad aggiornare i movimenti del portafoglio.');
		if (!movements.value.length) movements.value = [];
	} finally {
		isLoadingMovements.value = false;
	}
};

const fetchPaymentMethod = async () => {
	try {
		const res = await sanctum('/api/stripe/default-payment-method');
		defaultPaymentMethod.value = res;
	} catch {
		if (!defaultPaymentMethod.value) defaultPaymentMethod.value = null;
	}
};

const fetchStripeAvailability = async () => {
	const runtimeConfig = useRuntimeConfig();
	try {
		const config = await sanctum('/api/settings/stripe');
		const key = String(config?.publishable_key || '').trim();
		stripeConfigured.value = Boolean(config?.configured) && isValidStripePublishableKey(key);
	} catch {
		const fallbackKey = String(runtimeConfig.public.stripeKey || '').trim();
		stripeConfigured.value = isValidStripePublishableKey(fallbackKey);
	}
};

const refreshWalletData = async () => {
	isRefreshingWallet.value = true;

	try {
		await Promise.allSettled([fetchBalance(), fetchMovements(), fetchPaymentMethod(), fetchStripeAvailability()]);
	} finally {
		isRefreshingWallet.value = false;
	}
};

const retryBalance = async () => {
	await fetchBalance();
};

const retryMovements = async () => {
	await fetchMovements();
};

/* Ricarica dati dopo un top-up riuscito */
const onTopUpSuccess = async () => {
	await Promise.allSettled([fetchBalance({ showLoader: false }), fetchMovements({ showLoader: false }), fetchPaymentMethod()]);
};

/* Ricarica carta predefinita dopo salvataggio nuova carta */
const onPaymentMethodUpdated = async () => {
	await fetchPaymentMethod();
};

/* All'apertura della pagina, carica in parallelo */
onMounted(() => {
	refreshWalletData();
});
</script>

<template>
	<section class="sf-account-shell min-h-[600px] py-[20px] tablet:py-[24px] desktop:py-[28px]">
		<div class="my-container max-w-[1280px]">
			<AccountPageHeader
				eyebrow="Wallet"
				title="Portafoglio"
				description="Saldo, ricariche e movimenti in una vista piu ordinata e coerente col resto dell'account."
				current="Portafoglio">
				<template #meta>
					<div class="flex flex-wrap items-center gap-[8px]">
						<span
							v-for="stat in walletHeaderStats"
							:key="stat.label"
							class="sf-account-meta-pill">
							{{ stat.label }}: {{ stat.value }}
						</span>
					</div>
				</template>
			</AccountPageHeader>

			<!-- Wallet hero card compatto (P13: rimosso dot-pattern + 2 radial-gradient = rumore visivo). -->
			<div class="rounded-[16px] px-[20px] py-[18px] mb-[20px] sf-animate-in sf-animate-in-1 tablet:px-[24px] tablet:py-[20px]"
				style="background: linear-gradient(135deg, #F3FAFB 0%, #E6F2F4 100%); border: 1px solid rgba(9, 88, 102, 0.12);">
				<div class="flex flex-col gap-[18px]">
					<div class="flex flex-col gap-[14px] desktop:flex-row desktop:items-end desktop:justify-between">
						<div class="max-w-[560px]">
							<span class="text-[var(--color-brand-text-muted)] text-[11px] uppercase tracking-[0.12em] block mb-[6px] font-[700]">Wallet personale</span>
							<p class="text-[var(--color-brand-text)] text-[2rem] leading-none tracking-[-1px] font-[800] tablet:text-[2.5rem]">
								{{ balanceOverviewValue }}
							</p>
							<p class="mt-[10px] max-w-[520px] text-[0.875rem] leading-[1.55] text-[var(--color-brand-text-secondary)]">
								Saldo disponibile per spedizioni, ricariche e controllo movimenti in una sola superficie pulita.
							</p>
						</div>
						<div class="flex flex-wrap gap-[8px]">
							<button
								type="button"
								@click="refreshWalletData"
								:disabled="isRefreshingWallet"
								class="h-[38px] px-[14px] rounded-full bg-white text-[var(--color-brand-primary)] text-[12px] font-[700] flex items-center gap-[6px] cursor-pointer border border-[rgba(9,88,102,0.12)] hover:bg-[rgba(9,88,102,0.04)] transition-colors duration-[250ms] disabled:opacity-60">
								<span v-if="!isRefreshingWallet">
									<svg aria-hidden="true" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg>
								</span>
								<span v-else class="h-[14px] w-[14px] rounded-full border-2 border-[var(--color-brand-primary)] border-r-transparent animate-spin"></span>
								{{ isRefreshingWallet ? 'Aggiornamento...' : 'Aggiorna' }}
							</button>
							<NuxtLink to="/account/carte"
								class="h-[38px] px-[14px] rounded-full text-white text-[12px] font-[700] flex items-center gap-[6px] cursor-pointer transition-colors duration-[350ms] sf-cta-glow"
								style="background: var(--gradient-cta);">
								<svg aria-hidden="true" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
								Carte
							</NuxtLink>
							<!-- -- ARCHIVIATO 2026-04-20: CTA Prelievi (_archive/frontend-simplification-2026-04-20/features/prelievi-dedicati) -- -->
						</div>
					</div>
				</div>
			</div>

			<div class="grid grid-cols-1 gap-[20px] desktop:grid-cols-[minmax(0,0.94fr)_minmax(0,1.06fr)] desktop:items-start sf-animate-in sf-animate-in-2">
				<AccountWalletBalanceCards
					:balance="balance"
					:is-pro="isPro"
					:is-loading-balance="isLoadingBalance"
					:balance-error="balanceError"
					:default-payment-method-label="defaultPaymentMethodLabel"
					:movement-count-label="movementCountLabel"
					:stripe-configured="stripeConfigured"
					@retry-balance="retryBalance" />

				<AccountWalletTopUp
					:default-payment-method="defaultPaymentMethod"
					:stripe-configured="stripeConfigured"
					@top-up-success="onTopUpSuccess"
					@payment-method-updated="onPaymentMethodUpdated" />
			</div>

			<div class="mt-[20px] sf-animate-in sf-animate-in-3">
				<AccountWalletMovements
					:movements="movements"
					:is-loading-movements="isLoadingMovements"
					:movements-error="movementsError"
					@retry-movements="retryMovements" />
			</div>
		</div>
	</section>
</template>
