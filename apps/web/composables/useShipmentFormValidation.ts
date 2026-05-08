/**
 * Validazione form spedizione (step 2: indirizzi mittente/destinatario + servizi).
 * Restituisce gli helper richiesti da useShipmentStepValidation.
 *
 * Logica vera nel `sv` (smart validator); questo composable connette gli error
 * refs alle sezioni e fornisce focus management.
 */

interface UseShipmentFormValidationArgs {
	contentError: { value: string | null }
	dateError: { value: string | null }
	sv: {
		getError?: (key: string) => string | null
		validateAll?: () => boolean
		[k: string]: unknown
	}
	[k: string]: unknown
}

export function useShipmentFormValidation(args: UseShipmentFormValidationArgs) {
	const { sv, contentError, dateError } = args

	const fieldClass = (key: string) => {
		const error = sv?.getError?.(key)
		return error ? 'border-brand-error' : ''
	}

	const fieldErrorText = (key: string) => sv?.getError?.(key) ?? null
	const getFieldError = (key: string) => sv?.getError?.(key) ?? null
	const getFieldAssist = (_key: string): string | null => null
	const applyFieldAssist = (_key: string, _value: unknown) => undefined
	const softenErrorMessage = (msg: string | null | undefined) => (msg ? String(msg) : '')

	const formErrorSummary = (): string[] => {
		const errors: string[] = []
		if (contentError?.value) errors.push(contentError.value)
		if (dateError?.value) errors.push(dateError.value)
		return errors
	}

	const showGlobalFormSummary = () => formErrorSummary().length > 0

	const focusFormError = (key: string) => {
		if (typeof document === 'undefined') return
		const el = document.querySelector(`[data-field="${key}"]`)
		if (el && 'focus' in el && typeof (el as HTMLElement).focus === 'function') {
			;(el as HTMLElement).focus()
		}
	}

	const focusFirstFormError = () => {
		if (typeof document === 'undefined') return
		const el = document.querySelector('.border-brand-error, [data-error="true"]')
		if (el && 'focus' in el && typeof (el as HTMLElement).focus === 'function') {
			;(el as HTMLElement).focus()
		}
	}

	const focusContentDescriptionField = () => focusFormError('content_description')

	const validateForm = () => {
		if (typeof sv?.validateAll === 'function') {
			return sv.validateAll()
		}
		return formErrorSummary().length === 0
	}

	return {
		applyFieldAssist,
		// Hint testuali per i 3 campi (vuoti per ora; saranno popolati dalla validation reale).
		contentFieldHint: '',
		destinationSectionHint: '',
		fieldClass,
		fieldErrorText,
		focusContentDescriptionField,
		focusFirstFormError,
		focusFormError,
		formErrorSummary,
		getFieldAssist,
		getFieldError,
		originSectionHint: '',
		showGlobalFormSummary,
		softenErrorMessage,
		validateForm,
	}
}
