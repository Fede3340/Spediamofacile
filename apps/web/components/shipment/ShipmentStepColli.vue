<script setup>
import StepPackagesSection from '~/components/shipment/StepPackagesSection.vue';

/**
 * Template estratto da pages/la-tua-spedizione/[step].vue.
 * Step 1 del funnel: configurazione colli (tipo, peso, dimensioni).
 * Logica e stato restano nel parent: qui SOLO template + props.
 */
defineProps({
	isOpen: { type: Boolean, required: true },
	summary: { type: String, default: '' },
	editablePackages: { type: Array, required: true },
	packageTypeList: { type: Array, default: () => [] },
	isEuropeMonocollo: { type: Boolean, default: false },
	europeRestrictionMessage: { type: String, default: '' },
	summaryPackageLabel: { type: String, default: '' },
	summaryDimensionsLabel: { type: String, default: '' },
	packagesError: { type: String, default: '' },
	getPackageMetricClass: { type: Function, required: true },
	getPackageMetricError: { type: Function, required: true },
	handleAddPackage: { type: Function, required: true },
	handleDeletePackage: { type: Function, required: true },
	handleUpdatePackageType: { type: Function, required: true },
	incrementQuantity: { type: Function, required: true },
	decrementQuantity: { type: Function, required: true },
	onPackageQuantityInput: { type: Function, required: true },
	onPackageWeightInput: { type: Function, required: true },
	onPackageWeightBlur: { type: Function, required: true },
	onPackageDimensionInput: { type: Function, required: true },
	onPackageDimensionBlur: { type: Function, required: true },
	visibleSubmitError: { type: String, default: '' },
	accordionTransitions: { type: Object, required: true },
});

defineEmits(['open', 'confirm', 'dismiss-error']);
</script>

<template>
	<section
		class="shipment-stage-card"
		:class="{ 'shipment-stage-card--open': isOpen }">

		<div class="shipment-stage-card__accent" />

		<button
			type="button"
			class="shipment-stage-card__toggle"
			data-accordion-trigger="packages"
			:aria-expanded="isOpen ? 'true' : 'false'"
			@click="$emit('open')">
			<div class="shipment-stage-card__badge">
				1
			</div>

			<div class="shipment-stage-card__copy">
				<h2 class="shipment-stage-card__title">Colli</h2>
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
				class="shipment-stage-card__body shipment-stage-card__body--packages">
				<div class="shipment-stage-card__body-inner">
					<StepPackagesSection
						:packages="editablePackages"
						:package-type-list="packageTypeList"
						:is-europe-monocollo="isEuropeMonocollo"
						:europe-restriction-message="europeRestrictionMessage"
						:package-summary-label="summaryPackageLabel"
						:dimensions-summary-label="summaryDimensionsLabel"
						:packages-error="packagesError"
						:get-metric-input-class="getPackageMetricClass"
						:get-metric-error="getPackageMetricError"
						:on-add-package="handleAddPackage"
						:on-delete-pack="handleDeletePackage"
						:on-update-package-type="handleUpdatePackageType"
						:on-increment-quantity="incrementQuantity"
						:on-decrement-quantity="decrementQuantity"
						:on-quantity-input="onPackageQuantityInput"
						:on-weight-input="onPackageWeightInput"
						:on-weight-blur="onPackageWeightBlur"
						:on-dimension-input="onPackageDimensionInput"
						:on-dimension-blur="onPackageDimensionBlur" />
					<div class="shipment-stage-actions shipment-stage-actions--minimal">
						<div class="shipment-stage-actions__buttons">
							<button
								type="button"
								class="sf-flow-cta sf-flow-cta--primary"
								@click="$emit('confirm')"
								aria-label="Conferma colli e prosegui ai servizi">
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
