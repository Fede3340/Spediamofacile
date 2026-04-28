/**
 * @file useShipmentStepValidation — Composable useShipmentStepValidation.
 */
export const useShipmentStepValidation = ({
	contentError,
	dateError,
	deliveryMode,
	destinationAddress,
	originAddress,
	sanctumClient,
	services,
	shipmentFlowStore,
}) => {
	const debugValidationLog = (label, payload = "") => {
		if (!import.meta.client) return;
		if (localStorage.getItem('sf_debug_shipment') !== '1') return;
		console.info(`[shipment-validation-debug] ${label}`, payload);
	};
	const debugValidationError = (label, error) => {
		if (!import.meta.client) return;
		console.error(`[shipment-validation-debug] ${label}`, error);
	};

	let sv;
	try {
		sv = useSmartValidation();
		debugValidationLog("smart validation ready");
	} catch (error) {
		debugValidationError("smart validation failed", error);
		throw error;
	}

	let autocomplete;
	try {
		autocomplete = useShipmentLocationAutocomplete({
			deliveryMode,
			destinationAddress,
			originAddress,
			sanctumClient,
			sv,
			shipmentFlowStore,
		});
		debugValidationLog("location autocomplete ready");
	} catch (error) {
		debugValidationError("location autocomplete failed", error);
		throw error;
	}

	let formValidation;
	try {
		formValidation = useShipmentFormValidation({
			contentError,
			dateError,
			deliveryMode,
			destinationAddress,
			originAddress,
			services,
			sv,
			shipmentFlowStore,
			applyLocationToSection: autocomplete.applyLocationToSection,
			getSectionAddress: autocomplete.getSectionAddress,
			getSectionCountryCode: autocomplete.getSectionCountryCode,
			locationLinkHints: autocomplete.locationLinkHints,
			normalizeLocationText: autocomplete.normalizeLocationText,
			validateAddressLocationLink: autocomplete.validateAddressLocationLink,
			validateProvinceField: autocomplete.validateProvinceField,
			originCitySuggestions: autocomplete.originCitySuggestions,
			originCapSuggestions: autocomplete.originCapSuggestions,
			destCitySuggestions: autocomplete.destCitySuggestions,
			destCapSuggestions: autocomplete.destCapSuggestions,
		});
		debugValidationLog("form validation ready");
	} catch (error) {
		debugValidationError("form validation failed", error);
		throw error;
	}

	return {
		applyFieldAssist: formValidation.applyFieldAssist,
		contentFieldHint: formValidation.contentFieldHint,
		destinationSectionHint: formValidation.destinationSectionHint,
		fieldClass: formValidation.fieldClass,
		fieldErrorText: formValidation.fieldErrorText,
		focusContentDescriptionField: formValidation.focusContentDescriptionField,
		focusFirstFormError: formValidation.focusFirstFormError,
		focusFormError: formValidation.focusFormError,
		formErrorSummary: formValidation.formErrorSummary,
		getFieldAssist: formValidation.getFieldAssist,
		getFieldError: formValidation.getFieldError,
		originSectionHint: formValidation.originSectionHint,
		showGlobalFormSummary: formValidation.showGlobalFormSummary,
		softenErrorMessage: formValidation.softenErrorMessage,
		validateForm: formValidation.validateForm,
		destCapSuggestions: autocomplete.destCapSuggestions,
		destCitySuggestions: autocomplete.destCitySuggestions,
		destProvinceSuggestions: autocomplete.destProvinceSuggestions,
		formatCapSuggestionLabel: autocomplete.formatCapSuggestionLabel,
		formatCitySuggestionLabel: autocomplete.formatCitySuggestionLabel,
		normalizeLocationText: autocomplete.normalizeLocationText,
		onCapFocus: autocomplete.onCapFocus,
		onCapInput: autocomplete.onCapInput,
		onCityFocus: autocomplete.onCityFocus,
		onCityInput: autocomplete.onCityInput,
		onNameInput: autocomplete.onNameInput,
		onProvinceFocus: autocomplete.onProvinceFocus,
		onProvinciaInput: autocomplete.onProvinciaInput,
		onTelefonoInput: autocomplete.onTelefonoInput,
		originCapSuggestions: autocomplete.originCapSuggestions,
		originCitySuggestions: autocomplete.originCitySuggestions,
		originProvinceSuggestions: autocomplete.originProvinceSuggestions,
		selectCap: autocomplete.selectCap,
		selectCity: autocomplete.selectCity,
		selectProvincia: autocomplete.selectProvincia,
		smartBlur: autocomplete.smartBlur,
		sv,
	};
};
