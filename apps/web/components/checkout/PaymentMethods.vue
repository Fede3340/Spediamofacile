<!--
  Payment methods: card (saved/new), bank transfer, wallet.
  All Stripe card-element logic lives in the parent composable; we just get refs/props.
-->
<script setup>
import '~/assets/css/shipment-flow.css';

const props = defineProps({
	paymentMethod: { type: String, required: true },
	paymentMethodOptions: { type: Array, required: true },
	cardPaymentsUnavailable: { type: Boolean, default: false },
	cardPaymentsNotice: { type: String, default: '' },
	/* card sub-panel */
	hasSavedCard: { type: Boolean, default: false },
	defaultPayment: { type: Object, default: null },
	useNewCard: { type: Boolean, default: true },
	shouldShowCardForm: { type: Boolean, default: false },
	stripeLoading: { type: Boolean, default: false },
	cardError: { type: String, default: '' },
	saveCardForFuture: { type: Boolean, default: false },
	/* Function ref callback — parent passes (el) => { composableRef.value = el } */
	cardRefCallback: { type: Function, default: null },
	/* wallet saldo prepagato */
	walletFormatted: { type: String, default: '' },
	walletLoaded: { type: Boolean, default: false },
	walletSufficient: { type: Boolean, default: false },
	/* wallet express: Apple Pay / Google Pay (Stripe PaymentRequestButton) */
	canMakePayment: { type: Boolean, default: false },
	isAppleAvailable: { type: Boolean, default: false },
	isGoogleAvailable: { type: Boolean, default: false },
	paymentRequestError: { type: String, default: '' },
	/* Function ref callback per il container del Stripe PaymentRequestButton */
	paymentRequestRefCallback: { type: Function, default: null },
	/* Callback chiamata dopo mount del DOM container, per montare il bottone Stripe */
	onPaymentRequestReady: { type: Function, default: null },
});

const emit = defineEmits(['select-payment-method', 'update:useNewCard', 'update:saveCardForFuture']);

// Flag per prevenire click spammabili sui pulsanti "Carta salvata / Usa nuova carta":
// durante la Transition payment-panel (fade 160ms) i click ripetuti causerebbero
// animazioni a raffica. Con questo guard, il pulsante e' inerte finche' la transizione
// non completa, poi torna attivo.
const isCardFormTransitioning = ref(false);
const onCardFormBeforeEnter = () => { isCardFormTransitioning.value = true; };
const onCardFormAfterEnter = () => { isCardFormTransitioning.value = false; };
const onCardFormBeforeLeave = () => { isCardFormTransitioning.value = true; };
const onCardFormAfterLeave = () => { isCardFormTransitioning.value = false; };

const handleUseNewCard = (value) => {
	if (isCardFormTransitioning.value) return;
	if (props.useNewCard === value) return;
	emit('update:useNewCard', value);
};

// Checkbox "Salva carta per pagamenti futuri":
// uso un ref locale sincronizzato bidirezionalmente con la prop saveCardForFuture.
// Motivo: il vecchio binding :checked + @change non propagava affidabilmente lo
// stato al parent (probabilmente a causa del re-render dentro la Transition).
// Con v-model + ref locale + watch l'aggiornamento e' reattivo e stabile.
const localSaveCard = ref(!!props.saveCardForFuture);
watch(() => props.saveCardForFuture, (v) => {
	if (localSaveCard.value !== v) localSaveCard.value = !!v;
});
watch(localSaveCard, (v) => {
	if (v !== props.saveCardForFuture) emit('update:saveCardForFuture', v);
});

// Mount del Stripe PaymentRequestButton dopo che container e' in DOM.
// Il parent gestisce il ref via callback; qui chiediamo solo a parent di montare.
watchEffect(async () => {
	if (props.canMakePayment && typeof props.onPaymentRequestReady === 'function') {
		await nextTick();
		await props.onPaymentRequestReady();
	}
});

const walletQuickLabel = computed(() => {
	if (props.isAppleAvailable && props.isGoogleAvailable) return 'Apple Pay e Google Pay disponibili';
	if (props.isAppleAvailable) return 'Apple Pay disponibile';
	if (props.isGoogleAvailable) return 'Google Pay disponibile';
	return '';
});
</script>

<template>
	<div class="checkout-stage-card checkout-stage-card--payment checkout-motion-card [--checkout-delay:80ms]">
		<div class="checkout-panel-head">
			<span class="checkout-panel-head__icon">
				<svg
					width="18"
					height="18"
					viewBox="0 0 24 24"
					fill="none"
					stroke="currentColor"
					stroke-width="2"
					stroke-linecap="round"
					stroke-linejoin="round">
					<rect x="1" y="4" width="22" height="16" rx="2" ry="2" />
					<line x1="1" y1="10" x2="23" y2="10" />
				</svg>
			</span>
			<div class="checkout-panel-head__copy">
				<p class="checkout-panel-head__title">Metodo di pagamento</p>
				<p class="checkout-panel-head__text">Scegli come pagare.</p>
			</div>
		</div>

		<!-- Wallet rapido: Apple Pay / Google Pay (Stripe PaymentRequestButton).
		     Mostrato SOLO se almeno un provider wallet express è effettivamente
		     disponibile; evita il box vuoto "oppure scegli un altro metodo". -->
		<div v-if="canMakePayment && (isAppleAvailable || isGoogleAvailable)" class="payment-wallet-section">
			<div class="payment-wallet-section__label">
				<span>Pagamento rapido</span>
				<small>{{ walletQuickLabel }}</small>
			</div>
			<div
				:ref="paymentRequestRefCallback"
				class="payment-wallet-section__button"></div>
			<p v-if="paymentRequestError" class="payment-wallet-section__error">{{ paymentRequestError }}</p>
			<div class="payment-wallet-section__divider">
				<span>oppure scegli un altro metodo</span>
			</div>
		</div>

		<div class="checkout-payment-options-grid checkout-payment-options-grid--final" role="tablist" aria-label="Metodo di pagamento">
			<button
				v-for="option in paymentMethodOptions"
				:key="option.key"
				type="button"
				@click="emit('select-payment-method', option.key)"
				role="tab"
				:aria-pressed="paymentMethod === option.key"
				:aria-selected="paymentMethod === option.key"
				:disabled="option.key === 'carta' && cardPaymentsUnavailable"
				:class="[
					'checkout-payment-option',
					paymentMethod === option.key ? 'checkout-payment-option--active' : 'checkout-payment-option--idle',
					option.key === 'carta' && cardPaymentsUnavailable ? 'checkout-payment-option--disabled' : '',
				]">
				<span v-if="option.badge" class="checkout-payment-option__badge">{{ option.badge }}</span>
				<span class="checkout-payment-option__main">
					<span class="checkout-payment-option__icon-shell">
						<svg v-if="option.key === 'carta'" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<rect x="1" y="4" width="22" height="16" rx="2" ry="2" />
							<line x1="1" y1="10" x2="23" y2="10" />
						</svg>
						<svg
							v-else-if="option.key === 'bonifico'"
							width="20"
							height="20"
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							stroke-width="2">
							<path d="M3 10h18" />
							<path d="M5 10V7a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v3" />
							<rect x="4" y="10" width="16" height="9" rx="2" />
							<path d="M8 14h2" />
							<path d="M14 14h2" />
						</svg>
						<svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4" />
							<path d="M3 5v14a2 2 0 0 0 2 2h16v-5" />
							<path d="M18 12a2 2 0 0 0 0 4h4v-4Z" />
						</svg>
					</span>
					<span class="checkout-payment-option__copy">
						<span class="checkout-payment-option__title">{{ option.title }}</span>
						<span class="checkout-payment-option__text">{{ option.description }}</span>
					</span>
				</span>
			</button>
		</div>

		<div v-if="cardPaymentsUnavailable" class="checkout-payment-notice">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" class="shrink-0 mt-[1px]" fill="#8f5b00" aria-hidden="true">
				<path d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z" />
			</svg>
			<div>{{ cardPaymentsNotice }}</div>
		</div>

		<div class="payment-panel-shell checkout-payment-panel" :data-payment-method="paymentMethod">
			<!-- Card panel -->
			<div v-if="paymentMethod === 'carta' && !cardPaymentsUnavailable" class="space-y-[14px]">
				<div class="checkout-payment-choice-stack">
					<button
						v-if="hasSavedCard"
						type="button"
						@click="handleUseNewCard(false)"
						:disabled="isCardFormTransitioning"
						:class="[
							'checkout-payment-choice',
							!useNewCard ? 'checkout-payment-choice--selected' : 'checkout-payment-choice--idle',
						]">
						<span class="checkout-payment-choice__brand">{{ defaultPayment.card.brand?.toUpperCase() }}</span>
						<div class="checkout-payment-choice__copy">
							<p class="checkout-payment-choice__eyebrow">Carta salvata</p>
							<p class="checkout-payment-choice__title">
								•••• •••• •••• {{ defaultPayment.card.last4 }}
							</p>
							<p class="checkout-payment-choice__text">Scade {{ defaultPayment.card.exp_month }}/{{ defaultPayment.card.exp_year }}</p>
						</div>
						<span :class="['checkout-payment-choice__radio', !useNewCard ? 'checkout-payment-choice__radio--selected' : '']"></span>
					</button>

					<div
						role="button"
						:tabindex="isCardFormTransitioning ? -1 : 0"
						:aria-disabled="isCardFormTransitioning"
						@click="handleUseNewCard(true)"
						@keydown.enter.prevent="handleUseNewCard(true)"
						@keydown.space.prevent="handleUseNewCard(true)"
						:class="[
							'checkout-payment-choice checkout-payment-choice--expandable',
							!hasSavedCard || useNewCard ? 'checkout-payment-choice--selected' : 'checkout-payment-choice--idle',
							isCardFormTransitioning ? 'is-transitioning' : '',
						]">
						<div class="checkout-payment-choice__header">
							<span class="checkout-payment-choice__icon-shell">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<rect x="1" y="4" width="22" height="16" rx="2" ry="2" />
									<line x1="1" y1="10" x2="23" y2="10" />
								</svg>
							</span>
							<div class="checkout-payment-choice__copy">
								<p class="checkout-payment-choice__title">Usa una nuova carta</p>
								<p class="checkout-payment-choice__text">Inserisci una carta diversa per questo pagamento.</p>
							</div>
							<span
								:class="[
									'checkout-payment-choice__radio',
									!hasSavedCard || useNewCard ? 'checkout-payment-choice__radio--selected' : '',
								]"></span>
						</div>

						<!-- Transition rimossa: l'utente trovava fastidioso il fade ad ogni click.
						     Il form appare/scompare istantaneo. -->
						<div v-if="shouldShowCardForm" class="checkout-payment-card-form checkout-payment-card-form--embedded">
								<div class="checkout-payment-card-form__head">
									<div class="checkout-payment-card-form__intro">
										<p class="checkout-payment-card-form__text">Inserisci la carta qui.</p>
									</div>
								</div>

								<div id="card-element" :ref="cardRefCallback" class="checkout-payment-card-form__element"></div>
								<p v-if="stripeLoading" class="checkout-payment-card-form__helper">
									Preparazione del modulo carta in corso...
								</p>
								<p v-if="cardError" class="checkout-payment-card-form__error">{{ cardError }}</p>
								<!-- Checkbox "Salva carta": label che wrappa tutto così click OVUNQUE
								     (quadrato + testo) triggera il toggle nativo dell'input.
								     @click.stop sul label blocca il bubbling al div[role=button] genitore. -->
								<label class="checkout-payment-card-form__save" @click.stop>
									<input
										type="checkbox"
										v-model="localSaveCard"
										class="checkout-payment-card-form__checkbox" />
									<span>Salva carta per pagamenti futuri (puoi revocare in qualsiasi momento dal tuo account)</span>
								</label>
							</div>
					</div>
				</div>
			</div>

			<!-- Bank transfer -->
			<div v-else-if="paymentMethod === 'bonifico'" class="checkout-payment-alt">
				<p class="checkout-payment-alt__title">Pagamento tramite bonifico</p>
				<p class="checkout-payment-alt__text">
					Riceverai via email le coordinate bancarie appena confermi l'ordine. L'attivazione avviene alla ricezione del bonifico.
				</p>
			</div>

			<!-- Wallet -->
			<div v-else-if="paymentMethod === 'wallet'" class="checkout-payment-alt">
				<p class="checkout-payment-alt__title">Pagamento tramite Wallet</p>
				<p class="checkout-payment-alt__text">
					Saldo disponibile:
					<span class="font-semibold text-[var(--color-brand-primary)]">{{ walletFormatted }}</span>
				</p>
				<p v-if="walletLoaded && !walletSufficient" class="checkout-payment-alt__error">
					Saldo insufficiente. Ricarica il wallet per procedere.
				</p>
				<p v-else-if="walletLoaded" class="checkout-payment-alt__success">
					Saldo sufficiente per completare il pagamento.
				</p>
			</div>
		</div>
	</div>
</template>

