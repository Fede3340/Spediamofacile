<script setup>
import PudoSelector from '~/components/pudo/PudoSelector.vue';

const props = defineProps({
	deliveryMode: { type: String, required: true },
	destinationAddress: { type: Object, required: true },
	selectedPudo: { type: Object, default: null },
});

defineEmits(['update:delivery-mode', 'pudo-selected', 'pudo-deselected']);

const selectedPudoSummary = computed(() => {
	if (!props.selectedPudo) return '';

	return [
		String(props.selectedPudo.address || '').trim(),
		[props.selectedPudo.zip_code, props.selectedPudo.city].filter(Boolean).join(' ').trim(),
	].filter(Boolean).join(' - ');
});
</script>

<template>
	<div class="address-delivery-shell">
		<div
			class="sf-segmented-control sf-segmented-control--step address-delivery-segmented"
			role="group"
			aria-label="Modalità di consegna">
			<button
				type="button"
				class="sf-segmented-control__item address-delivery-segmented__item"
				:class="{ 'is-active': deliveryMode === 'home' }"
				:aria-pressed="deliveryMode === 'home' ? 'true' : 'false'"
				aria-label="Consegna a domicilio"
				@click="$emit('update:delivery-mode', 'home')">
				<span class="address-delivery-segmented__icon">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
						<polyline points="9 22 9 12 15 12 15 22"/>
					</svg>
				</span>
				<span>A domicilio</span>
			</button>

			<button
				type="button"
				class="sf-segmented-control__item address-delivery-segmented__item"
				:class="{ 'is-active': deliveryMode === 'pudo' }"
				:aria-pressed="deliveryMode === 'pudo' ? 'true' : 'false'"
				aria-label="Consegna in un punto BRT"
				@click="$emit('update:delivery-mode', 'pudo')">
				<span class="address-delivery-segmented__icon">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
						<circle cx="12" cy="10" r="3"/>
					</svg>
				</span>
				<span>Punto BRT</span>
			</button>
		</div>

		<div v-if="deliveryMode === 'pudo'" class="address-pudo-shell">
			<div v-if="selectedPudo" class="address-pudo-selected">
				<div class="address-pudo-selected__icon">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
						<circle cx="12" cy="10" r="3"/>
					</svg>
				</div>
				<div class="address-pudo-selected__copy">
					<p class="address-pudo-selected__name">{{ selectedPudo.name }}</p>
					<p class="address-pudo-selected__address">{{ selectedPudoSummary }}</p>
				</div>
			</div>

			<PudoSelector
				:initial-city="destinationAddress.city"
				:initial-zip="destinationAddress.postal_code"
				@select="$emit('pudo-selected', $event)"
				@deselect="$emit('pudo-deselected')" />
		</div>
	</div>
</template>

<style scoped>
.address-delivery-shell {
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.address-delivery-segmented {
	display: inline-flex;
	width: min(100%, 352px);
	align-self: flex-start;
}

.address-delivery-segmented__item {
	min-width: 148px;
	padding-inline: 16px;
	justify-content: center;
}

.address-delivery-segmented__icon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 22px;
	height: 22px;
	border-radius: 999px;
	background: rgba(9, 88, 102, 0.08);
	color: var(--color-brand-primary, #095866);
}

.address-delivery-segmented__item.is-active .address-delivery-segmented__icon {
	background: rgba(255, 255, 255, 0.22);
	color: #ffffff;
}

.address-pudo-shell {
	display: grid;
	gap: 12px;
}

.address-pudo-selected {
	display: grid;
	grid-template-columns: auto minmax(0, 1fr);
	gap: 12px;
	align-items: center;
	padding: 12px 14px;
	border-radius: 16px;
	border: 0;
	background: var(--color-border-soft, #E6E9EE);
	box-shadow: inset 0 1px 2px rgba(9, 88, 102, 0.06);
}

.address-pudo-selected__icon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 36px;
	height: 36px;
	border-radius: 12px;
	background: rgba(9, 88, 102, 0.08);
	color: var(--color-brand-primary, #095866);
}

.address-pudo-selected__copy {
	display: grid;
	gap: 2px;
	min-width: 0;
}

.address-pudo-selected__name {
	margin: 0;
	font-family: var(--font-montserrat);
	font-size: 0.9375rem;
	line-height: 1.2;
	font-weight: 800;
	color: var(--color-brand-text);
}

.address-pudo-selected__address {
	margin: 0;
	font-size: 0.8125rem;
	line-height: 1.4;
	color: var(--color-brand-text-secondary);
}

@media (max-width: 640px) {
	.address-delivery-segmented {
		width: min(100%, 100%);
	}

	.address-delivery-segmented__item {
		min-width: 0;
		flex: 1 1 0;
	}

	.address-pudo-selected {
		grid-template-columns: 1fr;
		align-items: flex-start;
	}
}
</style>
