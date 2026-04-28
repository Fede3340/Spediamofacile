<script setup>
/**
 * ServiceCardRegular — card di un servizio aggiuntivo (contrassegno,
 * assicurazione, notifiche, ecc.). Include il pannello di configurazione
 * inline tramite ServiceConfigPanel. Estratto da StepServicesGrid.vue
 * senza modifiche logiche o stilistiche. Gli stili .service-surface*
 * sono definiti nel parent (StepServicesGrid.vue).
 */
import ServiceConfigPanel from './ServiceConfigPanel.vue';
import ServiceIcon from './ServiceIcon.vue';

const props = defineProps({
	service: { type: Object, required: true },
	serviceIndex: { type: Number, required: true },
	isExpanded: { type: Boolean, default: false },
	canConfigure: { type: Boolean, default: false },
	isInteractionLocked: { type: Boolean, default: false },
	removeLocked: { type: Boolean, default: false },
	activateLocked: { type: Boolean, default: false },
	serviceIconFilterIdle: { type: String, required: true },
	serviceIconFilterActive: { type: String, required: true },
	// Panel props
	serviceData: { type: Object, required: true },
	serviceCardErrors: { type: Object, required: true },
	contrassegnoIncassoOptions: { type: Array, required: true },
	contrassegnoRimborsoOptions: { type: Array, required: true },
	requiresContrassegnoDettaglio: { type: Boolean, default: false },
	insurancePackages: { type: Array, required: true },
	normalizeCurrencyInput: { type: Function, required: true },
});

const emit = defineEmits([
	'surface-click',
	'primary-action',
	'remove',
	'activate',
]);

const INTERACTIVE_SELECTOR = 'button, a, input, textarea, select, label';

const isNestedInteractiveTarget = (event) => {
	const target = event?.target;
	const currentTarget = event?.currentTarget;
	if (!(target instanceof HTMLElement)) return false;
	const interactiveAncestor = target.closest(INTERACTIVE_SELECTOR);
	return Boolean(interactiveAncestor && interactiveAncestor !== currentTarget);
};

const supportText = computed(() => {
	const service = props.service;
	if (!service) return '';
	const description = String(service.description || '').trim();
	const statusLabel = String(service.statusLabel || '').trim();
	if (statusLabel) return statusLabel;
	return description;
});

const collapsedPrimaryLabel = computed(() => {
	const service = props.service;
	if (!service) return '';
	if (props.canConfigure) {
		return service.isSelected ? 'Configura' : 'Apri';
	}
	return service.isSelected ? 'Rimuovi' : 'Aggiungi';
});

const handleSurfaceClick = (event) => {
	if (!props.service || props.isInteractionLocked || isNestedInteractiveTarget(event)) return;
	emit('surface-click', props.service, event);
};

const handleSurfaceKeydown = (event) => {
	if (!['Enter', ' '].includes(event?.key)) return;
	event.preventDefault();
	handleSurfaceClick(event);
};
</script>

<template>
	<article
		class="service-surface"
		:class="{
			'service-surface--selected': service.isSelected,
			'service-surface--active': service.isSelected,
			'service-surface--expanded': isExpanded,
		}">
		<div
			class="service-surface__row"
			:class="{ 'service-surface__row--locked': isInteractionLocked }"
			role="button"
			:tabindex="isInteractionLocked ? -1 : 0"
			@click="handleSurfaceClick"
			@keydown="handleSurfaceKeydown">
			<div class="service-surface__media" :class="{ 'service-surface__media--active': service.isSelected }">
				<ServiceIcon
					:name="service.key || 'default'"
					:size="32"
					class="service-surface__media-icon" />
			</div>

			<div class="service-surface__copy">
				<div class="service-surface__headline">
					<h5 class="service-surface__name">{{ service.name }}</h5>
					<span
						v-if="service.isSelected && canConfigure && !isExpanded"
						class="service-surface__badge">
						Fatto
					</span>
				</div>
				<p v-if="supportText" class="service-surface__text">
					{{ supportText }}
					<span v-if="service.priceLabel" class="service-surface__text-emphasis">{{ service.priceLabel }}</span>
				</p>
			</div>

			<div class="service-surface__aside">
				<SfButton
					variant="secondary"
					size="sm"
					:disabled="isInteractionLocked"
					@click.stop="emit('primary-action', service)">
					{{ collapsedPrimaryLabel }}
				</SfButton>
			</div>
		</div>

		<ServiceConfigPanel
			v-if="canConfigure && isExpanded"
			:service="service"
			:service-index="serviceIndex"
			:service-data="serviceData"
			:service-card-errors="serviceCardErrors"
			:contrassegno-incasso-options="contrassegnoIncassoOptions"
			:contrassegno-rimborso-options="contrassegnoRimborsoOptions"
			:requires-contrassegno-dettaglio="requiresContrassegnoDettaglio"
			:insurance-packages="insurancePackages"
			:normalize-currency-input="normalizeCurrencyInput"
			:remove-locked="removeLocked"
			:activate-locked="activateLocked"
			@remove="emit('remove', $event)"
			@activate="emit('activate', $event)" />
	</article>
</template>
