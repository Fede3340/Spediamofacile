/**
 * @file useFunnelValidation — validazione step funnel preventivo.
 * Estratto da composables/useFunnel.js. Validatori riusabili (packages, services).
 *
 * @typedef {'weight'|'first_size'|'second_size'|'third_size'} PackageFieldKey
 */
import { computed } from 'vue';

export const PACKAGE_VALIDATION_LABELS = {
	weight: 'Peso',
	first_size: 'Lato 1',
	second_size: 'Lato 2',
	third_size: 'Lato 3',
};

export const PACKAGE_VALIDATION_KEYS = {
	weight: 'peso',
	first_size: 'first_size',
	second_size: 'second_size',
	third_size: 'third_size',
};

/** Composable validazione step-by-step funnel spedizione. */
export function useFunnelValidation(options) {
	const {
		sv,
		editablePackages,
		calcPriceWithWeight,
		calcPriceWithVolume,
		recalcPackageQuantity,
		validateInlineServiceDetails,
	} = options;

	const packagesError = ref('');

	const getPackageValidationKey = (fieldKey, packIndex) =>
		`${PACKAGE_VALIDATION_KEYS[fieldKey]}_${packIndex}`;

	const getPackageMetricError = (packIndex, fieldKey) =>
		sv.getError(getPackageValidationKey(fieldKey, packIndex));

	const getPackageMetricClass = (packIndex, fieldKey) =>
		sv.errorClass(getPackageValidationKey(fieldKey, packIndex), 'package-metric-input');

	const focusFirstInvalidPackageField = () => {
		nextTick(() => {
			const firstErrorSelector = [
				'#package-weight-0',
				'#package-first_size-0',
				'#package-second_size-0',
				'#package-third_size-0',
				'#package-quantity-0',
			].join(', ');
			const target =
				document.querySelector(firstErrorSelector) ||
				document.querySelector('.package-metric-input, .quantity-stepper__input');
			if (!(target instanceof HTMLElement)) return;
			target.scrollIntoView({ behavior: 'smooth', block: 'center' });
			window.setTimeout(() => {
				target.focus?.({ preventScroll: true });
			}, 120);
		});
	};

	const focusFirstInvalidServiceField = () => {
		nextTick(() => {
			const expandedCard = document.querySelector('.service-surface--expanded');
			if (!expandedCard) return;

			const focusTarget = expandedCard.querySelector(
				'.service-panel__input, .sf-shared-segment, .service-panel__footer .btn-primary',
			);

			focusTarget?.focus?.({ preventScroll: true });
		});
	};

	const sanitizePackageWeightValue = (value) =>
		String(value ?? '')
			.replace(',', '.')
			.replace(/[^0-9.]/g, '');

	const sanitizePackageDimensionValue = (value) =>
		String(value ?? '').replace(/[^0-9]/g, '');

	const onPackageQuantityInput = (pack) => {
		packagesError.value = '';
		recalcPackageQuantity(pack);
	};

	const onPackageWeightInput = (pack, packIndex) => {
		packagesError.value = '';
		pack.weight = sanitizePackageWeightValue(pack.weight);
		calcPriceWithWeight(pack);
		sv.onInput(
			getPackageValidationKey('weight', packIndex),
			() => sv.validatePeso(getPackageValidationKey('weight', packIndex), pack.weight),
		);
	};

	const onPackageWeightBlur = (pack, packIndex) => {
		pack.weight = sanitizePackageWeightValue(pack.weight);
		calcPriceWithWeight(pack);
		sv.onBlur(
			getPackageValidationKey('weight', packIndex),
			() => sv.validatePeso(getPackageValidationKey('weight', packIndex), pack.weight),
		);
	};

	const onPackageDimensionInput = (pack, packIndex, key) => {
		packagesError.value = '';
		pack[key] = sanitizePackageDimensionValue(pack[key]);
		calcPriceWithVolume(pack);
		sv.onInput(
			getPackageValidationKey(key, packIndex),
			() => sv.validateDimensione(getPackageValidationKey(key, packIndex), pack[key], PACKAGE_VALIDATION_LABELS[key]),
		);
	};

	const onPackageDimensionBlur = (pack, packIndex, key) => {
		pack[key] = sanitizePackageDimensionValue(pack[key]);
		calcPriceWithVolume(pack);
		sv.onBlur(
			getPackageValidationKey(key, packIndex),
			() => sv.validateDimensione(getPackageValidationKey(key, packIndex), pack[key], PACKAGE_VALIDATION_LABELS[key]),
		);
	};

	const validatePackagesStep = () => {
		packagesError.value = '';

		const packages = editablePackages.value || [];
		if (!packages.length) {
			packagesError.value = 'Aggiungi almeno un collo prima di continuare.';
			return false;
		}

		let isValid = true;
		packages.forEach((pack, packIndex) => {
			const quantity = Number.parseInt(String(pack?.quantity ?? ''), 10);
			if (!Number.isFinite(quantity) || quantity < 1) {
				pack.quantity = 1;
			}

			(['weight', 'first_size', 'second_size', 'third_size']).forEach((fieldKey) => {
				const validationKey = getPackageValidationKey(fieldKey, packIndex);
				sv.markTouched(validationKey);
				const validator =
					fieldKey === 'weight'
						? () => sv.validatePeso(validationKey, pack.weight)
						: () => sv.validateDimensione(validationKey, pack[fieldKey], PACKAGE_VALIDATION_LABELS[fieldKey]);

				if (!validator()) {
					isValid = false;
				}
			});
		});

		if (!isValid) {
			focusFirstInvalidPackageField();
		}

		return isValid;
	};

	/**
	 * Validates the services step inline panels (content description, insurance,
	 * cash-on-delivery, etc.) and returns whether the step can advance.
	 */
	const validateServicesStep = () => {
		const ok = validateInlineServiceDetails();
		if (!ok) focusFirstInvalidServiceField();
		return ok;
	};

	return {
		packagesError,
		getPackageValidationKey,
		getPackageMetricError,
		getPackageMetricClass,
		focusFirstInvalidPackageField,
		focusFirstInvalidServiceField,
		sanitizePackageWeightValue,
		sanitizePackageDimensionValue,
		onPackageQuantityInput,
		onPackageWeightInput,
		onPackageWeightBlur,
		onPackageDimensionInput,
		onPackageDimensionBlur,
		validatePackagesStep,
		validateServicesStep,
	};
}
