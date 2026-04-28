<script setup>
/**
 * ServiceCardFeatured — card "Servizio base" (featured) dello step servizi.
 * Estratta da StepServicesGrid.vue senza modifiche logiche o stilistiche.
 * Gli stili .service-surface* sono definiti nel parent (StepServicesGrid.vue).
 */
import ServiceIcon from './ServiceIcon.vue';

const props = defineProps({
	service: { type: Object, required: true },
	isLocked: { type: Boolean, default: false },
	serviceIconFilterIdle: { type: String, required: true },
	serviceIconFilterActive: { type: String, required: true },
});

const emit = defineEmits(['toggle']);

const INTERACTIVE_SELECTOR = 'button, a, input, textarea, select, label';

const isNestedInteractiveTarget = (event) => {
	const target = event?.target;
	const currentTarget = event?.currentTarget;
	if (!(target instanceof HTMLElement)) return false;
	const interactiveAncestor = target.closest(INTERACTIVE_SELECTOR);
	return Boolean(interactiveAncestor && interactiveAncestor !== currentTarget);
};

const descriptionText = computed(() => {
	const source = props.service?.isSelected
		? props.service?.statusLabel || props.service?.description || ''
		: props.service?.description || '';
	return String(source || '').trim();
});

const featuredPriceLabel = computed(() => String(props.service?.currentPriceLabel || props.service?.priceLabel || '').trim());

const handleClick = (event) => {
	if (!props.service || props.isLocked || isNestedInteractiveTarget(event)) return;
	emit('toggle');
};

const handleKeydown = (event) => {
	if (!['Enter', ' '].includes(event?.key)) return;
	event.preventDefault();
	handleClick(event);
};
</script>

<template>
	<section class="service-stage-section">
		<div class="service-stage-section__header">
			<div class="service-stage-section__icon">
				<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#095866" stroke-width="2">
					<polyline points="6 9 6 2 18 2 18 9" />
					<path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
					<rect x="6" y="14" width="12" height="8" />
				</svg>
			</div>
			<div>
				<h4 class="service-stage-section__title">Servizio base</h4>
			</div>
		</div>

		<button
			type="button"
			class="service-surface service-surface--featured"
			:class="{
				'service-surface--selected': service.isSelected,
				'service-surface--active': service.isSelected,
			}"
			:disabled="isLocked"
			@click="handleClick"
			@keydown="handleKeydown">
			<div
				class="service-surface__row service-surface__row--featured"
				:class="{ 'service-surface__row--locked': isLocked }">
				<div class="service-surface__media" :class="{ 'service-surface__media--active': service.isSelected }">
					<ServiceIcon
						:name="service.key || 'senza_etichetta'"
						:size="26"
						class="service-surface__media-icon" />
				</div>

				<div class="service-surface__copy service-surface__copy--featured">
					<div class="service-surface__eyebrow service-surface__eyebrow--featured">
						<span class="service-surface__badge service-surface__badge--accent">Consigliato</span>
						<span v-if="service.isSelected" class="service-surface__badge">Attivo</span>
					</div>
					<div class="service-surface__headline service-surface__headline--featured">
						<h5 class="service-surface__name service-surface__name--featured">{{ service.name }}</h5>
					</div>
					<p v-if="descriptionText" class="service-surface__text service-surface__text--featured">
						{{ descriptionText }}
					</p>
				</div>

				<div class="service-surface__aside service-surface__aside--featured">
					<div v-if="featuredPriceLabel" class="service-surface__featured-summary">
						<span class="service-surface__featured-summary-label">Costo servizio</span>
						<span class="service-surface__featured-summary-value">{{ featuredPriceLabel }}</span>
					</div>
					<span class="service-surface__aside-note">Etichetta gestita dal corriere</span>
				</div>
			</div>
		</button>
	</section>
</template>
