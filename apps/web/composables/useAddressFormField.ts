import { computed, inject, type Ref } from 'vue'
import { shipmentFormHandlersKey } from '~/utils/injectionKeys'

type AddressType = 'origin' | 'dest'

/**
 * Logica condivisa fra i 3 fieldset (Name, Location, Contact) di AddressFormFields.
 * Centralizza idPrefix, autocomplete, helpers a11y e binding handler iniettati.
 */
export function useAddressFormField(
	type: Ref<AddressType>,
	address: Ref<Record<string, unknown>>,
	readonly: Ref<boolean>,
) {
	const handlers = inject(shipmentFormHandlersKey)
	if (!handlers) throw new Error('useAddressFormField: shipmentFormHandlersKey non iniettata')

	const idPrefix = computed(() => (type.value === 'origin' ? '' : 'dest_'))
	const inputNamePrefix = computed(() => (type.value === 'origin' ? 'shipment-origin' : 'shipment-dest'))
	const autocompleteSection = computed(() => (type.value === 'origin' ? 'section-origin' : 'section-destination'))
	const getAutocomplete = (purpose: string) => `${autocompleteSection.value} shipping ${purpose}`

	const sharedInputAttrs = {
		autocapitalize: 'off',
		autocorrect: 'off',
		spellcheck: 'false',
		'data-lpignore': 'true',
		'data-1p-ignore': 'true',
		'data-form-type': 'other',
	}

	const readonlyClass = computed(() =>
		readonly.value
			? '!bg-white !border-[#CBD5DF] !text-[var(--color-brand-text-secondary)] cursor-not-allowed'
			: '',
	)

	const addressField = (field: string) => String((address.value as Record<string, unknown>)?.[field] || '')
	const setAddressField = (field: string, value: unknown) => handlers.updateAddressField(type.value, field, value)

	const errorId = (field: string) => `${idPrefix.value}${field}_error`
	const ariaInvalid = (field: string) => Boolean(handlers.getFieldError(type.value, field))
	const ariaDescribedBy = (field: string) => (handlers.getFieldError(type.value, field) ? errorId(field) : undefined)

	return {
		handlers,
		typeKey: type,
		idPrefix,
		inputNamePrefix,
		autocompleteSection,
		getAutocomplete,
		sharedInputAttrs,
		readonlyClass,
		addressField,
		setAddressField,
		ariaInvalid,
		ariaDescribedBy,
	}
}
