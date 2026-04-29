<script setup>
import StepServicesGrid from '~/components/shipment/StepServicesGrid.vue';
import StepPickupDate from '~/components/shipment/StepPickupDate.vue';
import ServiceContentNotifications from '~/components/shipment/ServiceContentNotifications.vue';

/**
 * Template estratto da pages/la-tua-spedizione/[step].vue.
 * Step 2 del funnel: ritiro + servizi extra + contenuto/notifiche.
 */
defineProps({
	isOpen: { type: Boolean, required: true },
	summary: { type: String, default: '' },
	setPickupDateSectionRef: { type: Function, required: true },
	dateError: { type: [String, null], default: null },
	daysInMonth: { type: Array, default: () => [] },
	services: { type: Object, required: true },
	chooseDate: { type: Function, required: true },
	featuredService: { type: Object, default: null },
	regularServices: { type: Array, default: () => [] },
	serviceData: { type: Object, default: () => ({}) },
	serviceCardErrors: { type: Object, default: () => ({}) },
	isServiceExpanded: { type: Function, required: true },
	canConfigureService: { type: Function, required: true },
	getServiceConfigureLabel: { type: Function, required: true },
	contrassegnoIncassoOptions: { type: Array, default: () => [] },
	contrassegnoRimborsoOptions: { type: Array, default: () => [] },
	requiresContrassegnoDettaglio: { type: Boolean, default: false },
	insurancePackages: { type: Array, default: () => [] },
	normalizeCurrencyInput: { type: Function, required: true },
	serviceIconFilterIdle: { type: String, default: '' },
	serviceIconFilterActive: { type: String, default: '' },
	toggleFeaturedService: { type: Function, required: true },
	toggleRegularService: { type: Function, required: true },
	handleServicePrimaryAction: { type: Function, required: true },
	activateConfiguredService: { type: Function, required: true },
	removeConfiguredService: { type: Function, required: true },
	resolvedContentDescription: { type: String, default: '' },
	contentError: { type: [String, null], default: null },
	contentFieldHint: { type: String, default: '' },
	smsEmailNotification: { type: Boolean, default: false },
	notificationPriceLabel: { type: String, default: '' },
	updateContentDescription: { type: Function, required: true },
	updateContentError: { type: Function, required: true },
	updateSmsEmailNotification: { type: Function, required: true },
	visibleSubmitError: { type: String, default: '' },
	accordionTransitions: { type: Object, required: true },
});

defineEmits(['open', 'back', 'confirm', 'dismiss-error']);

const mounted = ref(false);
onMounted(() => { mounted.value = true; });
</script>

<template>
	<section
		class="shipment-stage-card"
		:class="{ 'shipment-stage-card--open': isOpen }">

		<div class="shipment-stage-card__accent" />

		<button
			type="button"
			class="shipment-stage-card__toggle"
			data-accordion-trigger="services"
			:aria-expanded="isOpen ? 'true' : 'false'"
			@click="$emit('open')">

			<div class="shipment-stage-card__badge">
				2
			</div>

			<div class="shipment-stage-card__copy">
				<h2 class="shipment-stage-card__title">Servizi</h2>
				<p v-if="!isOpen && mounted" class="shipment-stage-card__summary">
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
					<StepPickupDate
						:ref="setPickupDateSectionRef"
						:date-error="dateError"
						:days-in-month="daysInMonth"
						:services="services"
						@choose-date="chooseDate" />
					<StepServicesGrid
						:featured-service="featuredService"
						:regular-services="regularServices"
						:service-data="serviceData"
						:service-card-errors="serviceCardErrors"
						:is-service-expanded="isServiceExpanded"
						:can-configure-service="canConfigureService"
						:get-service-configure-label="getServiceConfigureLabel"
						:contrassegno-incasso-options="contrassegnoIncassoOptions"
						:contrassegno-rimborso-options="contrassegnoRimborsoOptions"
						:requires-contrassegno-dettaglio="requiresContrassegnoDettaglio"
						:insurance-packages="insurancePackages"
						:normalize-currency-input="normalizeCurrencyInput"
						:service-icon-filter-idle="serviceIconFilterIdle"
						:service-icon-filter-active="serviceIconFilterActive"
						@toggle-featured-service="toggleFeaturedService"
						@toggle-regular-service="toggleRegularService"
						@handle-service-primary-action="handleServicePrimaryAction"
						@activate-configured-service="activateConfiguredService"
						@remove-configured-service="removeConfiguredService" />
					<ServiceContentNotifications
						:content-description="resolvedContentDescription"
						:content-error="contentError"
						:content-field-hint="contentFieldHint"
						:sms-email-notification="smsEmailNotification"
						:notification-price-label="notificationPriceLabel"
						@update:content-description="updateContentDescription($event)"
						@update:content-error="updateContentError($event)"
						@update:sms-email-notification="updateSmsEmailNotification($event)" />
					<div class="shipment-stage-actions shipment-stage-actions--minimal">
						<div class="shipment-stage-actions__buttons">
							<button
								type="button"
								class="sf-flow-cta sf-flow-cta--secondary"
								@click="$emit('back')"
								aria-label="Torna ai colli">
								<span class="sf-flow-cta__arrow sf-flow-cta__arrow--leading">
									<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
										<path d="M19 12H5M12 19l-7-7 7-7" />
									</svg>
								</span>
								<span>Torna</span>
							</button>
							<button
								type="button"
								class="sf-flow-cta sf-flow-cta--primary"
								@click="$emit('confirm')"
								aria-label="Conferma servizi e prosegui agli indirizzi">
								<span>Conferma</span>
								<span class="sf-flow-cta__arrow">
									<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
										<path d="M5 12h14M12 5l7 7-7 7" />
									</svg>
								</span>
							</button>
						</div>
					</div>
					<div
						v-if="visibleSubmitError"
						class="shipment-stage-error"
						role="alert">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
							<circle cx="12" cy="12" r="10" />
							<line x1="12" y1="8" x2="12" y2="12" />
							<line x1="12" y1="16" x2="12.01" y2="16" />
						</svg>
						<span class="shipment-stage-error__message">{{ visibleSubmitError }}</span>
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
		</Transition>
	</section>
</template>
