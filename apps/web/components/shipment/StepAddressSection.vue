<script setup>
import ShipmentAddressFormFields from '~/components/shipment/AddressFormFields.vue';
import ShipmentAddressPudoSection from '~/components/shipment/AddressPudoSection.vue';

const props = defineProps({
	isOpen: { type: Boolean, default: false },
	originAddress: { type: Object, required: true },
	destinationAddress: { type: Object, required: true },
	deliveryMode: { type: String, required: true },
	savedAddresses: { type: Array, default: () => [] },
	loadingSavedAddresses: { type: Boolean, default: false },
	showOriginAddressSelector: { type: Boolean, default: false },
	showDestAddressSelector: { type: Boolean, default: false },
	isAuthenticated: { type: Boolean, default: false },
	selectedPudo: { type: Object, default: null },
	canSaveOriginAddress: { type: Boolean, default: false },
	canSaveDestAddress: { type: Boolean, default: false },
	savingOriginAddress: { type: Boolean, default: false },
	savingDestAddress: { type: Boolean, default: false },
	showBusinessFields: { type: Boolean, default: false },
});

const suggestions = inject('shipmentSuggestions');

const emit = defineEmits([
	'update:delivery-mode',
	'save-address',
	'toggle-address-selector',
	'apply-saved-address',
	'open-auth-modal',
	'pudo-selected',
	'pudo-deselected',
]);

const originSelectorRef = defineModel('originSelectorRef', { type: Object });
const destSelectorRef = defineModel('destSelectorRef', { type: Object });

const originSelectorShellRef = ref(null);
const destSelectorShellRef = ref(null);
watch(
	originSelectorShellRef,
	(el) => {
		originSelectorRef.value = el ?? null;
	},
	{ immediate: true },
);

watch(
	destSelectorShellRef,
	(el) => {
		destSelectorRef.value = el ?? null;
	},
	{ immediate: true },
);

// sharedFieldProps non più necessario — AddressFormFields usa inject direttamente

const openAddressBook = (target) => {
	if (!props.isAuthenticated) {
		emit('open-auth-modal', 'login');
		return;
	}

	emit('toggle-address-selector', target);
};

const applyAddressBookEntry = (address, target) => {
	emit('apply-saved-address', address, target);
};

const canUseOriginAddressBook = computed(() => props.isAuthenticated && props.savedAddresses.length > 0);
const canUseDestAddressBook = computed(() => props.isAuthenticated && props.savedAddresses.length > 0);
const showOriginActions = computed(() => canUseOriginAddressBook.value || props.canSaveOriginAddress);
const showDestActions = computed(() => props.deliveryMode !== 'pudo' && (canUseDestAddressBook.value || props.canSaveDestAddress));

</script>

<template>
	<div v-if="isOpen" class="address-stage-shell address-stage-shell--funnel-standard sf-section-block">
		<div class="address-stage-shell__content sf-section-block__body">
			<div class="address-stage-grid">
				<div class="address-entry-card address-entry-card--stacked">
					<div class="address-entry-card__head">
						<div class="address-entry-card__heading">
							<div class="address-entry-card__title-stack">
								<h2 class="address-entry-card__title route-card__title">Partenza (mittente)</h2>
							</div>
						</div>
					</div>
					<ShipmentAddressFormFields
						type="origin"
						:address="originAddress"
						:city-suggestions="suggestions.originCitySuggestions.value"
						:province-suggestions="suggestions.originProvinceSuggestions.value"
						:cap-suggestions="suggestions.originCapSuggestions.value"
						:show-business-fields="showBusinessFields" />
					<div
						v-if="showOriginActions"
						ref="originSelectorShellRef"
						class="address-entry-card__footer address-entry-card__footer--actions-only">
						<div class="address-entry-card__actions address-entry-card__actions--footer">
							<button
								v-if="canUseOriginAddressBook"
								type="button"
								class="btn-secondary btn-compact address-entry-card__link-action"
								@click.stop="openAddressBook('origin')">
								Rubrica
							</button>
							<SfButton
								v-if="props.canSaveOriginAddress"
								size="sm"
								class="address-entry-card__link-action"
								:loading="savingOriginAddress"
								@click.stop="$emit('save-address', 'origin')">
								<span>{{ savingOriginAddress ? 'Salvataggio...' : 'Salva mittente' }}</span>
							</SfButton>
						</div>

						<div v-if="showOriginAddressSelector && isAuthenticated" class="address-stage-menu address-stage-menu--card address-entry-card__header-menu">
							<div v-if="loadingSavedAddresses" class="address-stage-menu__empty-text">Caricamento in corso...</div>
							<div v-else-if="savedAddresses.length > 0" class="address-stage-menu__list">
								<button
									v-for="address in savedAddresses"
									:key="`origin-address-${address.id}`"
									type="button"
									class="address-stage-menu__item"
									@click="applyAddressBookEntry(address, 'origin')">
									<span class="address-stage-menu__route">{{ address.name }}</span>
									<span class="address-stage-menu__meta">{{ address.city }}</span>
								</button>
							</div>
							<div v-else class="address-stage-menu__empty">
								<p class="address-stage-menu__empty-text">Rubrica vuota.</p>
								<NuxtLink to="/account/indirizzi" class="address-stage-menu__link btn-secondary btn-compact">Rubrica</NuxtLink>
							</div>
						</div>
					</div>
				</div>

				<div class="address-entry-card address-entry-card--stacked">
					<div class="address-entry-card__head">
						<div class="address-entry-card__heading">
							<div class="address-entry-card__title-stack">
								<h2 class="address-entry-card__title route-card__title">Destinazione</h2>
							</div>
						</div>
					</div>
					<div class="address-entry-card__mode">
						<div class="address-entry-card__mode-shell">
							<ShipmentAddressPudoSection
								:delivery-mode="deliveryMode"
								:destination-address="destinationAddress"
								:selected-pudo="selectedPudo"
								@update:delivery-mode="$emit('update:delivery-mode', $event)"
								@pudo-selected="$emit('pudo-selected', $event)"
								@pudo-deselected="$emit('pudo-deselected')" />
						</div>
					</div>

					<ShipmentAddressFormFields
						v-if="deliveryMode !== 'pudo'"
						type="dest"
						:address="destinationAddress"
						:city-suggestions="suggestions.destCitySuggestions.value"
						:province-suggestions="suggestions.destProvinceSuggestions.value"
						:cap-suggestions="suggestions.destCapSuggestions.value"
						:show-business-fields="showBusinessFields" />
					<div
						v-if="showDestActions"
						ref="destSelectorShellRef"
						class="address-entry-card__footer address-entry-card__footer--actions-only">
						<div class="address-entry-card__actions address-entry-card__actions--footer">
							<button
								v-if="canUseDestAddressBook"
								type="button"
								class="btn-secondary btn-compact address-entry-card__link-action"
								@click.stop="openAddressBook('dest')">
								Rubrica
							</button>
							<SfButton
								v-if="props.canSaveDestAddress"
								size="sm"
								class="address-entry-card__link-action"
								:loading="savingDestAddress"
								@click.stop="$emit('save-address', 'dest')">
								<span>{{ savingDestAddress ? 'Salvataggio...' : 'Salva destinatario' }}</span>
							</SfButton>
						</div>

						<div v-if="showDestAddressSelector && isAuthenticated" class="address-stage-menu address-stage-menu--card address-entry-card__header-menu">
							<div v-if="loadingSavedAddresses" class="address-stage-menu__empty-text">Caricamento in corso...</div>
							<div v-else-if="savedAddresses.length > 0" class="address-stage-menu__list">
								<button
									v-for="address in savedAddresses"
									:key="`dest-address-${address.id}`"
									type="button"
									class="address-stage-menu__item"
									@click="applyAddressBookEntry(address, 'dest')">
									<span class="address-stage-menu__route">{{ address.name }}</span>
									<span class="address-stage-menu__meta">{{ address.city }}</span>
								</button>
							</div>
							<div v-else class="address-stage-menu__empty">
								<p class="address-stage-menu__empty-text">Rubrica vuota.</p>
								<NuxtLink to="/account/indirizzi" class="address-stage-menu__link btn-secondary btn-compact">Rubrica</NuxtLink>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<style scoped>
.address-stage-shell {
	scroll-margin-top: 116px;
}

.address-stage-shell__content {
	padding: 0;
}

.address-stage-grid {
	display: grid;
	gap: 28px;
}

.address-entry-card {
	display: grid;
	gap: 18px;
	background: #e5e9ef;
	border: 1px solid rgba(9, 88, 102, 0.12);
	border-radius: 20px;
	padding: 26px 24px 24px;
	box-shadow:
		inset 0 1px 1px rgba(255, 255, 255, 0.46),
		0 8px 22px rgba(15, 23, 42, 0.03);
}

.address-entry-card__head {
	display: flex;
	align-items: center;
	justify-content: flex-start;
	gap: 12px;
	padding-bottom: 14px;
	border-bottom: 1px solid rgba(9, 88, 102, 0.08);
}

.address-entry-card__header-menu {
	position: absolute;
	top: calc(100% + 6px);
	right: 0;
	z-index: 20;
	min-width: 260px;
}

.address-entry-card__heading {
	display: grid;
	gap: 0;
	min-width: 0;
}

.address-entry-card__title-stack {
	display: grid;
	gap: 0;
	min-width: 0;
}

.address-entry-card__title {
	margin: 0;
}

.address-entry-card--stacked :deep(.address-entry-card__title::before) {
	display: none;
}

.address-entry-card__caption {
	margin: 0;
	font-size: 0.78125rem;
	line-height: 1.4;
	font-weight: 550;
	color: var(--color-brand-text-secondary);
}

.address-entry-card__actions {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	justify-content: flex-end;
	gap: 6px;
	position: relative;
	padding: 4px 5px;
	border-radius: 999px;
	background: rgba(255, 255, 255, 0.78);
	border: 1px solid rgba(9, 88, 102, 0.09);
	box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.52);
}

.address-entry-card__actions--footer {
	justify-content: flex-end;
}

.address-entry-card__footer--actions-only {
	position: relative;
	grid-template-columns: minmax(0, 1fr);
	justify-items: end;
	padding-top: 4px;
}

.address-entry-card__mode {
	display: grid;
	gap: 12px;
	padding-top: 2px;
}

.address-entry-card__mode-shell {
	width: min(100%, 360px);
	justify-self: start;
}

.address-entry-card__link-action {
	min-height: 38px;
	padding-inline: 16px;
	border-radius: 999px;
	white-space: nowrap;
}

.address-entry-card__footer {
	display: grid;
	grid-template-columns: minmax(0, 1fr) auto;
	align-items: center;
	gap: 14px;
	padding-top: 14px;
	border-top: 1px solid rgba(9, 88, 102, 0.08);
}

.address-entry-card__footer-copy {
	margin: 0;
	font-size: 0.875rem;
	line-height: 1.45;
	color: var(--color-brand-text-secondary);
	max-width: 44ch;
}

@media (min-width: 768px) {
	.address-entry-card {
		padding: 26px 24px 24px;
	}

	.address-stage-grid {
		gap: 28px;
	}
}

@media (max-width: 639px) {
	.address-entry-card__head {
		padding-bottom: 12px;
	}

	.address-entry-card__footer {
		width: 100%;
		grid-template-columns: minmax(0, 1fr);
	}

	.address-entry-card__actions {
		width: 100%;
		flex-wrap: wrap;
		justify-content: flex-end;
	}

	.address-entry-card__header-menu {
		right: auto;
		left: 0;
		min-width: 240px;
	}
}
</style>
