<script setup>
const props = defineProps({
	packages: { type: Array, required: true },
	packageTypeList: { type: Array, default: () => [] },
	isEuropeMonocollo: { type: Boolean, default: false },
	europeRestrictionMessage: { type: String, default: '' },
	packageSummaryLabel: { type: String, default: '' },
	dimensionsSummaryLabel: { type: String, default: '' },
	packagesError: { type: String, default: '' },
	getMetricInputClass: { type: Function, required: true },
	getMetricError: { type: Function, required: true },
	onAddPackage: { type: Function, required: true },
	onDeletePack: { type: Function, required: true },
	onUpdatePackageType: { type: Function, required: true },
	onIncrementQuantity: { type: Function, required: true },
	onDecrementQuantity: { type: Function, required: true },
	onQuantityInput: { type: Function, required: true },
	onWeightInput: { type: Function, required: true },
	onWeightBlur: { type: Function, required: true },
	onDimensionInput: { type: Function, required: true },
	onDimensionBlur: { type: Function, required: true },
});

const metrics = [
	{ key: 'weight', label: 'Peso', unit: 'kg' },
	{ key: 'first_size', label: 'Lung.', unit: 'cm' },
	{ key: 'second_size', label: 'Larg.', unit: 'cm' },
	{ key: 'third_size', label: 'Alt.', unit: 'cm' },
];
</script>

<template>
	<div class="packages-stage-shell">
		<p v-if="isEuropeMonocollo && europeRestrictionMessage" class="packages-stage-shell__notice">
			{{ europeRestrictionMessage }}
		</p>

		<ul class="package-entry-list" role="list">
			<li
				v-for="(pack, packIndex) in packages"
				:key="pack._qid || packIndex"
				class="package-entry">
				<div class="package-entry__header">
					<span class="package-entry__header-balance" aria-hidden="true"></span>
					<div class="package-entry__type-switcher-shell">
						<div
							class="package-type-switcher package-type-switcher--shared sf-shared-segment-strip sf-shared-segment-strip--compact"
							:aria-label="`Tipo collo ${packIndex + 1}`">
							<button
								v-for="option in packageTypeList"
								:key="option.text"
								type="button"
								:class="[
									'package-type-switcher__button',
									'sf-shared-segment',
									'sf-shared-segment--compact',
									String(pack?.package_type || '').trim() === String(option?.text || '').trim()
										? 'package-type-switcher__button--active sf-shared-segment--active'
										: '',
								]"
								@click="onUpdatePackageType(pack, option.text)">
								<span class="package-type-switcher__icon-wrap sf-shared-segment__icon" aria-hidden="true">
									<img
										:src="`/img/quote/first-step/${option.img}`"
										:alt="option.text"
										:width="option.width"
										:height="option.height"
										class="package-type-switcher__icon-image"
										loading="eager"
										decoding="async"
										draggable="false" />
								</span>
								<span class="sf-shared-segment__title">{{ option.text }}</span>
							</button>
						</div>
					</div>

					<div class="package-entry__header-actions">
						<button
							v-if="packages.length > 1"
							type="button"
							class="package-entry__delete"
							@click="onDeletePack(pack._qid || packIndex)"
							:aria-label="`Elimina collo ${packIndex + 1}`">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M3 6h18" />
								<path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2" />
								<path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
								<path d="M10 11v6M14 11v6" />
							</svg>
						</button>
						<span v-else class="package-entry__header-balance" aria-hidden="true"></span>
					</div>
				</div>

				<div class="package-entry__grid packages-stage-grid">
					<div class="package-field-card package-field-card--quantity">
						<label class="package-field-card__label" :for="`package-quantity-${packIndex}`">Q.ta</label>
						<div class="package-field-card__input-wrap package-field-card__input-wrap--stepper">
							<div class="quantity-stepper quantity-stepper--embedded">
								<button
									type="button"
									class="quantity-stepper__button"
									:disabled="isEuropeMonocollo"
									@click="onDecrementQuantity(pack)"
									:aria-label="`Riduci quantita collo ${packIndex + 1}`">
									<span class="quantity-stepper__symbol" aria-hidden="true">-</span>
								</button>
								<input
									:id="`package-quantity-${packIndex}`"
									v-model="pack.quantity"
									:name="`shipment-quantity-${pack._qid || packIndex}`"
									type="text"
									inputmode="numeric"
									pattern="[0-9]*"
									autocomplete="new-password"
									autocapitalize="off"
									autocorrect="off"
									spellcheck="false"
									aria-autocomplete="none"
									data-lpignore="true"
									data-1p-ignore="true"
									data-form-type="other"
									class="quantity-stepper__input"
									:readonly="isEuropeMonocollo"
									@input="onQuantityInput(pack)" />
								<button
									type="button"
									class="quantity-stepper__button"
									:disabled="isEuropeMonocollo"
									@click="onIncrementQuantity(pack)"
									:aria-label="`Aumenta quantita collo ${packIndex + 1}`">
									<span class="quantity-stepper__symbol" aria-hidden="true">+</span>
								</button>
							</div>
						</div>
					</div>

					<div
						v-for="metric in metrics"
						:key="`${metric.key}-${packIndex}`"
						class="package-field-card">
						<label class="package-field-card__label" :for="`package-${metric.key}-${packIndex}`">{{ metric.label }}</label>
						<div class="package-field-card__input-wrap">
							<input
								:id="`package-${metric.key}-${packIndex}`"
								v-model="pack[metric.key]"
								:name="`shipment-${metric.key}-${pack._qid || packIndex}`"
								type="text"
								inputmode="decimal"
								autocomplete="new-password"
								autocapitalize="off"
								autocorrect="off"
								spellcheck="false"
								aria-autocomplete="none"
								data-lpignore="true"
								data-1p-ignore="true"
								data-form-type="other"
								placeholder="0"
								:class="getMetricInputClass(packIndex, metric.key)"
								@input="metric.key === 'weight' ? onWeightInput(pack, packIndex) : onDimensionInput(pack, packIndex, metric.key)"
								@blur="metric.key === 'weight' ? onWeightBlur(pack, packIndex) : onDimensionBlur(pack, packIndex, metric.key)" />
							<span class="package-field-card__unit">{{ metric.unit }}</span>
						</div>
						<div v-if="getMetricError(packIndex, metric.key)" class="package-field-card__feedback packages-stage-feedback">
							<p v-if="getMetricError(packIndex, metric.key)" class="package-field-card__error" role="alert">
								{{ getMetricError(packIndex, metric.key) }}
							</p>
						</div>
					</div>
				</div>
			</li>
		</ul>

		<div class="add-package-button-wrapper">
			<SfButton
				variant="secondary"
				size="sm"
				class="add-package-btn"
				:disabled="isEuropeMonocollo"
				@click="onAddPackage()">
				<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M12 5v14" />
					<path d="M5 12h14" />
				</svg>
				Aggiungi collo
			</SfButton>
		</div>

		<div v-if="packagesError" class="packages-stage-shell__feedback-slot">
			<p class="preventivo-inline-error" role="alert">
				{{ packagesError }}
			</p>
		</div>
	</div>
</template>

<style scoped>
.packages-stage-shell {
	display: grid;
	gap: 14px;
}

.packages-stage-grid {
	align-items: start;
}

.packages-stage-shell__notice {
	margin: 0;
	padding: 10px 12px;
	border-radius: 12px;
	border: 1px solid rgba(9, 88, 102, 0.12);
	background: rgba(9, 88, 102, 0.05);
	color: var(--color-brand-primary);
	font-size: 0.8125rem;
	line-height: 1.45;
	font-weight: 650;
}

.packages-stage-shell__feedback-slot {
	display: flex;
	align-items: flex-start;
	justify-content: center;
	padding-top: 2px;
}

.package-field-card__error {
	display: block;
	margin: 0;
	line-height: 1.4;
}

.package-entry__header {
	display: grid;
	grid-template-columns: minmax(44px, 1fr) auto minmax(44px, 1fr);
	align-items: center;
	gap: 10px;
	margin-bottom: 14px;
}

.package-entry__header-balance,
.package-entry__header-actions {
	display: flex;
	align-items: center;
	min-width: 44px;
}

.package-entry__header-actions {
	justify-content: flex-end;
}

.package-entry__type-switcher-shell {
	display: flex;
	justify-content: center;
	align-items: center;
	min-width: 0;
	width: 100%;
}

.package-type-switcher--shared {
	margin-left: auto;
	margin-right: auto;
	justify-content: center;
}

/* White icon on active teal background (fix invisible teal-on-teal) */
.package-type-switcher__button--active .package-type-switcher__icon-image {
	filter: brightness(0) invert(1) !important;
}

.packages-stage-feedback {
	display: flex !important;
	align-items: flex-start;
	padding-top: 7px;
	overflow: visible;
}

.package-entry__delete {
	opacity: 1;
}

@media (max-width: 47.99rem) {
	.packages-stage-shell {
		gap: 12px;
	}

	.package-entry__header {
		grid-template-columns: 1fr;
		gap: 8px;
	}

	.package-entry__header-balance,
	.package-entry__header-actions {
		display: none;
	}

	.package-entry__type-switcher-shell {
		justify-content: center;
	}

	.packages-stage-feedback {
		padding-top: 6px;
	}
}
</style>
