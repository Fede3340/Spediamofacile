<script setup>
/**
 * StepServicesGrid — orchestratore della griglia servizi (step 2 del
 * flusso spedizione). Coordina:
 *   - ServiceCardFeatured (servizio base)
 *   - ServiceCardRegular (servizi aggiuntivi) + ServiceConfigPanel inline
 * Mantiene gli stili .service-* scoped qui e in deep per i figli senza
 * necessità di stili duplicati (stylesheet ereditato tramite classi comuni).
 */
import ServiceCardFeatured from './ServiceCardFeatured.vue';
import ServiceCardRegular from './ServiceCardRegular.vue';

const props = defineProps({
	featuredService: { type: Object, default: null },
	regularServices: { type: Array, required: true },
	serviceData: { type: Object, required: true },
	serviceCardErrors: { type: Object, required: true },
	updateContrassegnoField: { type: Function, required: true },
	updateAssicurazioneValue: { type: Function, required: true },
	clearContrassegnoError: { type: Function, required: true },
	clearAssicurazioneError: { type: Function, required: true },
	isServiceExpanded: { type: Function, required: true },
	canConfigureService: { type: Function, required: true },
	getServiceConfigureLabel: { type: Function, required: true },
	contrassegnoIncassoOptions: { type: Array, required: true },
	contrassegnoRimborsoOptions: { type: Array, required: true },
	requiresContrassegnoDettaglio: { type: Boolean, default: false },
	insurancePackages: { type: Array, required: true },
	normalizeCurrencyInput: { type: Function, required: true },
	serviceIconFilterIdle: { type: String, required: true },
	serviceIconFilterActive: { type: String, required: true },
});

const emit = defineEmits([
	'toggle-featured-service',
	'toggle-regular-service',
	'handle-service-primary-action',
	'activate-configured-service',
	'remove-configured-service',
]);

// 250ms basta a evitare doppi click — 820ms faceva pensare "rotto" (P8 audit).
const INTERACTION_LOCK_MS = 250;
const interactionLocks = reactive({});

const withInteractionLock = (lockKey, callback) => {
	if (!lockKey || interactionLocks[lockKey]) return;
	interactionLocks[lockKey] = true;

	try {
		callback();
	} finally {
		setTimeout(() => {
			interactionLocks[lockKey] = false;
		}, INTERACTION_LOCK_MS);
	}
};

const handleToggleFeatured = () => {
	withInteractionLock('featured-service', () => {
		emit('toggle-featured-service');
	});
};

const handleRegularSurfaceClick = (service) => {
	if (!service) return;
	withInteractionLock(service.key, () => {
		if (!props.canConfigureService(service)) {
			emit('toggle-regular-service', service);
			return;
		}
		if (!props.isServiceExpanded(service.key)) {
			emit('handle-service-primary-action', service);
		}
	});
};

const handleCollapsedPrimaryAction = (service) => {
	if (!service) return;
	withInteractionLock(`${service.key}-primary`, () => {
		if (props.canConfigureService(service)) {
			emit('handle-service-primary-action', service);
			return;
		}
		emit('toggle-regular-service', service);
	});
};

const handleRemove = (service) => {
	if (!service) return;
	withInteractionLock(`${service.key}-remove`, () => emit('remove-configured-service', service));
};

const handleActivate = (service) => {
	if (!service) return;
	withInteractionLock(`${service.key}-activate`, () => emit('activate-configured-service', service));
};

const isServiceInteractionLocked = (serviceKey) =>
	Boolean(interactionLocks[serviceKey] || interactionLocks[`${serviceKey}-primary`]);
</script>

<template>
	<div class="service-stage-shell service-stage-shell--funnel-standard">
		<ServiceCardFeatured
			v-if="featuredService"
			:service="featuredService"
			:is-locked="Boolean(interactionLocks['featured-service'])"
			:service-icon-filter-idle="serviceIconFilterIdle"
			:service-icon-filter-active="serviceIconFilterActive"
			@toggle="handleToggleFeatured" />

		<section class="service-stage-section">
			<div class="service-stage-section__header">
				<div class="service-stage-section__icon">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-brand-primary">
						<line x1="16.5" y1="9.4" x2="7.5" y2="4.21" />
						<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
						<polyline points="3.27 6.96 12 12.01 20.73 6.96" />
						<line x1="12" y1="22.08" x2="12" y2="12" />
					</svg>
				</div>
				<h4 class="service-stage-section__title font-display m-0">Servizi aggiuntivi</h4>
			</div>

			<div class="service-stage-list">
				<ServiceCardRegular
					v-for="(service, serviceIndex) in regularServices"
					:key="service.key || serviceIndex"
					:service="service"
					:service-index="serviceIndex"
					:is-expanded="isServiceExpanded(service.key)"
					:can-configure="canConfigureService(service)"
					:is-interaction-locked="isServiceInteractionLocked(service.key)"
					:remove-locked="Boolean(interactionLocks[`${service.key}-remove`])"
					:activate-locked="Boolean(interactionLocks[`${service.key}-activate`])"
					:service-icon-filter-idle="serviceIconFilterIdle"
					:service-icon-filter-active="serviceIconFilterActive"
					:service-data="serviceData"
					:service-card-errors="serviceCardErrors"
					:update-contrassegno-field="updateContrassegnoField"
					:update-assicurazione-value="updateAssicurazioneValue"
					:clear-contrassegno-error="clearContrassegnoError"
					:clear-assicurazione-error="clearAssicurazioneError"
					:contrassegno-incasso-options="contrassegnoIncassoOptions"
					:contrassegno-rimborso-options="contrassegnoRimborsoOptions"
					:requires-contrassegno-dettaglio="requiresContrassegnoDettaglio"
					:insurance-packages="insurancePackages"
					:normalize-currency-input="normalizeCurrencyInput"
					@surface-click="handleRegularSurfaceClick"
					@primary-action="handleCollapsedPrimaryAction"
					@remove="handleRemove"
					@activate="handleActivate" />
			</div>
		</section>
	</div>
</template>
