import type { Ref } from 'vue'

type StepValidationOptions = {
	contentError: Ref<string | null>
	dateError: Ref<string | null>
	deliveryMode: Ref<string>
	destinationAddress: Ref<StepAddress>
	originAddress: Ref<StepAddress>
	sanctumClient: (...args: unknown[]) => Promise<unknown>
	services: Ref<Record<string, unknown>>
	shipmentFlowStore: Record<string, unknown>
}
type StepAddress = {
	full_name?: string
	address?: string
	address_number?: string
	city?: string
	postal_code?: string
	country?: string
	province?: string
	telephone_number?: string
	email?: string
	[key: string]: string | undefined
}

export const useShipmentStepValidation = ({
	contentError,
	dateError,
	deliveryMode,
	destinationAddress,
	originAddress,
	sanctumClient,
	services,
	shipmentFlowStore,
}: StepValidationOptions) => {
	const debugValidationLog = (label: string, payload: unknown = '') => {
		if (!import.meta.client) return
		if (localStorage.getItem('sf_debug_shipment') !== '1') return
		// eslint-disable-next-line no-console -- debug runtime opt-in via localStorage
		console.info(`[shipment-validation-debug] ${label}`, payload)
	}
	const debugValidationError = (label: string, error: unknown) => {
		// eslint-disable-next-line no-console -- traccia errori validazione lato client
		if (import.meta.client) console.error(`[shipment-validation-debug] ${label}`, error)
	}

	let sv
	try {
		sv = useSmartValidation()
		debugValidationLog('smart validation ready')
	} catch (error) {
		debugValidationError('smart validation failed', error)
		throw error
	}

	let autocomplete
	try {
		autocomplete = useShipmentLocationAutocomplete({
			deliveryMode,
			destinationAddress,
			originAddress,
			sanctumClient,
			sv,
			shipmentFlowStore,
		})
		debugValidationLog('location autocomplete ready')
	} catch (error) {
		debugValidationError('location autocomplete failed', error)
		throw error
	}

	let formValidation
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
			applyLocationToSection: autocomplete.applyLocationToSection as (...args: unknown[]) => unknown,
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
		})
		debugValidationLog('form validation ready')
	} catch (error) {
		debugValidationError('form validation failed', error)
		throw error
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
	}
}
