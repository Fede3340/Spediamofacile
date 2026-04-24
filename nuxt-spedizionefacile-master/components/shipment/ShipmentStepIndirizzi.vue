<script setup>
import StepAddressSection from '~/components/shipment/StepAddressSection.vue';

/**
 * Template estratto da pages/la-tua-spedizione/[step].vue.
 * Step 3 del funnel: indirizzi partenza/destinazione + PUDO.
 */
const props = defineProps({
	isOpen: { type: Boolean, required: true },
	summary: { type: String, default: '' },
	showAddressFields: { type: Boolean, required: true },
	originAddress: { type: Object, required: true },
	destinationAddress: { type: Object, required: true },
	isBusinessProfile: { type: Boolean, default: false },
	deliveryMode: { type: String, required: true },
	savedAddresses: { type: Array, default: () => [] },
	loadingSavedAddresses: { type: Boolean, default: false },
	showOriginAddressSelector: { type: Boolean, default: false },
	showDestAddressSelector: { type: Boolean, default: false },
	isAuthenticated: { type: Boolean, default: false },
	canSaveOriginAddress: { type: Boolean, default: false },
	canSaveDestAddress: { type: Boolean, default: false },
	savingOriginAddress: { type: Boolean, default: false },
	savingDestAddress: { type: Boolean, default: false },
	selectedPudo: { type: [Object, null], default: null },
	originSelectorRef: { type: [Object, null], default: null },
	destSelectorRef: { type: [Object, null], default: null },
	isSubmitting: { type: Boolean, default: false },
	isProceedingToPayment: { type: Boolean, default: false },
	isAddingToCart: { type: Boolean, default: false },
	isSavingConfigured: { type: Boolean, default: false },
	canAdvanceFromAddresses: { type: Boolean, default: false },
	addressReadinessItems: { type: Array, default: () => [] },
	visibleSubmitError: { type: String, default: '' },
	accordionTransitions: { type: Object, required: true },
	saveAddressToBook: { type: Function, required: true },
	toggleAddressSelector: { type: Function, required: true },
	applySavedAddress: { type: Function, required: true },
	openShipmentAuthModal: { type: Function, required: true },
	onPudoSelected: { type: Function, required: true },
	onPudoDeselected: { type: Function, required: true },
});

defineEmits([
	'open',
	'back',
	'confirm',
	'add-to-cart',
	// 'save-configured' -- ARCHIVIATO 2026-04-20 (_archive/frontend-simplification-2026-04-20/features/spedizioni-configurate)
	'dismiss-error',
	'update:deliveryMode',
	'update:originSelectorRef',
	'update:destSelectorRef',
]);

const inlineVisibleSubmitError = computed(() => {
	const message = String(props.visibleSubmitError || '').trim();

	if (!message) return '';

	return /numero massimo di tentativi|hai superato|riprova tra|attendi(?: ancora)?|\d+\s*(second|minut)|too many (attempts|requests)|rate limit/i.test(message)
		? ''
		: message;
});
</script>

<template>
	<section
		class="shipment-stage-card"
		:class="{ 'shipment-stage-card--open': isOpen }">

		<div class="shipment-stage-card__accent" />

		<button
			type="button"
			class="shipment-stage-card__toggle"
			data-accordion-trigger="addresses"
			:aria-expanded="isOpen ? 'true' : 'false'"
			@click="$emit('open')">

			<div class="shipment-stage-card__badge">
				3
			</div>

			<div class="shipment-stage-card__copy">
				<h2 class="shipment-stage-card__title">Indirizzi</h2>
				<p v-if="!isOpen" class="shipment-stage-card__summary">
					{{ summary }}
				</p>
			</div>

			<span
				class="shipment-stage-card__indicator"
				:class="{ 'shipment-stage-card__indicator--open': isOpen }"
				aria-hidden="true">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M6 9l6 6 6-6" />
				</svg>
			</span>
		</button>

		<Transition
			@before-enter="accordionTransitions.onBeforeEnter"
			@enter="accordionTransitions.onEnter"
			@after-enter="accordionTransitions.onAfterEnter"
			@before-leave="accordionTransitions.onBeforeLeave"
			@leave="accordionTransitions.onLeave"
			@after-leave="accordionTransitions.onAfterLeave">
			<div
				v-if="isOpen"
				class="shipment-stage-card__body">
				<div class="shipment-stage-card__body-inner">
					<StepAddressSection
						:is-open="showAddressFields"
						:origin-address="originAddress"
						:destination-address="destinationAddress"
						:show-business-fields="isBusinessProfile"
						:delivery-mode="deliveryMode"
						:saved-addresses="savedAddresses"
						:loading-saved-addresses="loadingSavedAddresses"
						:show-origin-address-selector="showOriginAddressSelector"
						:show-dest-address-selector="showDestAddressSelector"
						:is-authenticated="isAuthenticated"
						:can-save-origin-address="canSaveOriginAddress"
						:can-save-dest-address="canSaveDestAddress"
						:saving-origin-address="savingOriginAddress"
						:saving-dest-address="savingDestAddress"
						:selected-pudo="selectedPudo"
						:origin-selector-ref="originSelectorRef"
						:dest-selector-ref="destSelectorRef"
						@update:origin-selector-ref="$emit('update:originSelectorRef', $event)"
						@update:dest-selector-ref="$emit('update:destSelectorRef', $event)"
						@update:delivery-mode="$emit('update:deliveryMode', $event)"
						@save-address="saveAddressToBook"
						@toggle-address-selector="toggleAddressSelector"
						@apply-saved-address="applySavedAddress"
						@open-auth-modal="openShipmentAuthModal"
						@pudo-selected="onPudoSelected"
						@pudo-deselected="onPudoDeselected" />
					<div class="shipment-step-indirizzi__footer">
						<div
							v-if="!canAdvanceFromAddresses && addressReadinessItems.some((item) => !item.done)"
							class="shipment-readiness-hint shipment-step-indirizzi__hint"
							role="status"
							aria-live="polite">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
								<circle cx="12" cy="12" r="10" />
								<line x1="12" y1="8" x2="12" y2="12" />
								<line x1="12" y1="16" x2="12.01" y2="16" />
							</svg>
							<span class="shipment-readiness-hint__label">Completa prima:</span>
							<span
								v-for="item in addressReadinessItems.filter((i) => !i.done)"
								:key="item.key"
								class="shipment-readiness-hint__pill">
								{{ item.label }}
							</span>
						</div>
						<div class="shipment-stage-actions shipment-stage-actions--minimal shipment-step-indirizzi__actions">
							<div class="shipment-stage-actions__buttons">
								<button
									type="button"
									class="sf-flow-cta sf-flow-cta--secondary"
									@click="$emit('back')"
									aria-label="Torna ai servizi">
									<span class="sf-flow-cta__arrow sf-flow-cta__arrow--leading">
										<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
											<path d="M19 12H5M12 19l-7-7 7-7" />
										</svg>
									</span>
									<span>Torna</span>
								</button>
								<button
									type="button"
									class="sf-flow-cta sf-flow-cta--secondary"
									:disabled="isSubmitting || isProceedingToPayment || isAddingToCart || isSavingConfigured || !canAdvanceFromAddresses"
									@click="$emit('add-to-cart')"
									aria-label="Aggiungi la spedizione al carrello">
									<span class="sf-flow-cta__icon">
										<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
											<circle cx="9" cy="21" r="1" />
											<circle cx="20" cy="21" r="1" />
											<path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6" />
										</svg>
									</span>
									<span>{{ isAddingToCart ? 'Aggiunta...' : 'Carrello' }}</span>
								</button>
								<button
									type="button"
									class="sf-flow-cta sf-flow-cta--primary"
									:disabled="isSubmitting || isProceedingToPayment || isAddingToCart || isSavingConfigured || !canAdvanceFromAddresses"
									:title="!canAdvanceFromAddresses ? 'Completa i campi indirizzo obbligatori per procedere' : ''"
									@click="$emit('confirm')"
									aria-label="Vai al pagamento">
									<span>{{ (isSubmitting || isProceedingToPayment) ? 'Apertura...' : 'Vai al pagamento' }}</span>
									<span class="sf-flow-cta__arrow">
										<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
											<path d="M5 12h14M12 5l7 7-7 7" />
										</svg>
									</span>
								</button>
							</div>
						</div>
						<div
							v-if="inlineVisibleSubmitError"
							class="shipment-stage-error shipment-step-indirizzi__error"
							role="alert">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
								<circle cx="12" cy="12" r="10" />
								<line x1="12" y1="8" x2="12" y2="12" />
								<line x1="12" y1="16" x2="12.01" y2="16" />
							</svg>
							<span class="shipment-stage-error__message">{{ inlineVisibleSubmitError }}</span>
							<button
								type="button"
								class="shipment-stage-error__dismiss"
								@click="$emit('dismiss-error')"
								aria-label="Chiudi messaggio di errore">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
									<line x1="18" y1="6" x2="6" y2="18" />
									<line x1="6" y1="6" x2="18" y2="18" />
								</svg>
							</button>
						</div>
					</div>
				</div>
			</div>
		</Transition>
	</section>
</template>

<style scoped>
.shipment-step-indirizzi__footer {
	display: grid;
	gap: 12px;
	padding-top: 4px;
}

.shipment-step-indirizzi__hint,
.shipment-step-indirizzi__error {
	margin-top: 0;
}

.shipment-step-indirizzi__actions {
	margin-top: 0;
}
</style>
