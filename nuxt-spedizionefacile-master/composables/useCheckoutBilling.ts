/**
 * useCheckoutBilling — billing/invoice logic for checkout.
 *
 * Handles fatturazione type, invoice subject type, billing form data,
 * auto-fill from shipping address, and the billing payload for the API.
 */

import type { ComputedRef, Ref } from 'vue'
import type { Address, BillingData, CartItem } from '~/types'

interface UseCheckoutBillingDeps {
	displayPackages: ComputedRef<CartItem[] | undefined>
}

interface FatturaData {
	nome_completo: string
	ragione_sociale: string
	p_iva: string
	codice_fiscale: string
	indirizzo: string
	city: string
	province: string
	postal_code: string
	pec: string
	codice_sdi: string
}

interface BillingPayload extends Partial<BillingData> {
	type: 'ricevuta' | 'fattura'
	same_as_shipping?: boolean
	shipping_reference?: {
		name: string | null
		address: string | null
		city: string | null
		province: string | null
		postal_code: string | null
	} | null
}

interface UseCheckoutBillingReturn {
	fatturazioneType: Ref<'ricevuta' | 'fattura'>
	invoiceSubjectType: Ref<'privato' | 'azienda'>
	fatturaData: Ref<FatturaData>
	billingShippingFullAddress: ComputedRef<string>
	billingPayload: ComputedRef<BillingPayload>
	validateBilling: () => string | null
}

export function useCheckoutBilling({ displayPackages }: UseCheckoutBillingDeps): UseCheckoutBillingReturn {
	const fatturazioneType = ref<'ricevuta' | 'fattura'>('ricevuta')
	const invoiceSubjectType = ref<'privato' | 'azienda'>('azienda')
	const fatturaData = ref<FatturaData>({
		nome_completo: '',
		ragione_sociale: '',
		p_iva: '',
		codice_fiscale: '',
		indirizzo: '',
		city: '',
		province: '',
		postal_code: '',
		pec: '',
		codice_sdi: '',
	})

	const billingShippingSource = computed<Address | null>(() => {
		const pkg = displayPackages.value?.[0]
		return pkg?.origin_address || pkg?.destination_address || null
	})

	const billingShippingAddressLine = computed(() => {
		const address = billingShippingSource.value
		if (!address) return ''
		return [address.address, address.address_number].filter(Boolean).join(' ').trim()
	})

	const billingShippingFullAddress = computed(() => {
		const address = billingShippingSource.value
		if (!address) return ''
		return [
			[address.address, address.address_number].filter(Boolean).join(' ').trim(),
			[address.postal_code, address.city].filter(Boolean).join(' '),
			address.province ? `(${address.province})` : '',
		]
			.filter(Boolean)
			.join(', ')
	})

	const applyShippingDataToBilling = (): void => {
		const address = billingShippingSource.value
		if (!address) return

		if (invoiceSubjectType.value === 'privato') {
			fatturaData.value.nome_completo = fatturaData.value.nome_completo || address.name || ''
		} else if (!fatturaData.value.ragione_sociale) {
			fatturaData.value.ragione_sociale = address.name || ''
		}

		fatturaData.value.indirizzo = fatturaData.value.indirizzo || billingShippingAddressLine.value
		fatturaData.value.city = fatturaData.value.city || address.city || ''
		fatturaData.value.province = fatturaData.value.province || address.province || ''
		fatturaData.value.postal_code = fatturaData.value.postal_code || address.postal_code || ''
	}

	watch(
		[invoiceSubjectType, billingShippingSource],
		() => {
			applyShippingDataToBilling()
		},
		{ immediate: true },
	)

	watch(invoiceSubjectType, (subjectType) => {
		if (subjectType === 'privato') {
			fatturaData.value.ragione_sociale = ''
			fatturaData.value.p_iva = ''
		}
		applyShippingDataToBilling()
	})

	watch(fatturazioneType, (type) => {
		if (type === 'fattura') {
			applyShippingDataToBilling()
		}
	})

	const billingPayload = computed<BillingPayload>(() => {
		if (fatturazioneType.value !== 'fattura') {
			return { type: 'ricevuta' }
		}

		return {
			type: 'fattura',
			subject_type: invoiceSubjectType.value,
			same_as_shipping: false,
			nome_completo: fatturaData.value.nome_completo?.trim() || undefined,
			ragione_sociale: fatturaData.value.ragione_sociale?.trim() || undefined,
			p_iva: fatturaData.value.p_iva?.trim() || undefined,
			codice_fiscale: fatturaData.value.codice_fiscale?.trim() || undefined,
			indirizzo: fatturaData.value.indirizzo?.trim() || undefined,
			city: fatturaData.value.city?.trim() || undefined,
			province: fatturaData.value.province?.trim() || undefined,
			postal_code: fatturaData.value.postal_code?.trim() || undefined,
			pec: invoiceSubjectType.value === 'azienda' ? fatturaData.value.pec?.trim() || undefined : undefined,
			codice_sdi: invoiceSubjectType.value === 'azienda' ? fatturaData.value.codice_sdi?.trim() || undefined : undefined,
			shipping_reference: billingShippingSource.value
				? {
						name: billingShippingSource.value.name || null,
						address: billingShippingAddressLine.value || null,
						city: billingShippingSource.value.city || null,
						province: billingShippingSource.value.province || null,
						postal_code: billingShippingSource.value.postal_code || null,
					}
				: null,
		}
	})

	/**
	 * Validates billing data before payment. Returns an error string or null if valid.
	 */
	const validateBilling = (): string | null => {
		if (fatturazioneType.value !== 'fattura') return null

		if (!fatturaData.value.indirizzo?.trim()) {
			return 'Indirizzo di fatturazione obbligatorio.'
		}
		if (!fatturaData.value.city?.trim() || !fatturaData.value.province?.trim() || !fatturaData.value.postal_code?.trim()) {
			return 'Completa città, provincia e CAP del documento fiscale.'
		}
		if (invoiceSubjectType.value === 'azienda') {
			if (!fatturaData.value.ragione_sociale?.trim()) {
				return 'Ragione sociale obbligatoria per fattura azienda.'
			}
			if (!fatturaData.value.p_iva?.trim()) {
				return 'P.IVA obbligatoria per fattura azienda.'
			}
			const pivaClean = fatturaData.value.p_iva.replace(/\s/g, '')
			if (!/^\d{11}$/.test(pivaClean)) {
				return 'P.IVA non valida. Deve contenere 11 cifre.'
			}
		} else {
			if (!fatturaData.value.nome_completo?.trim()) {
				return 'Nome e cognome obbligatori per fattura privato.'
			}
			if (!fatturaData.value.codice_fiscale?.trim()) {
				return 'Codice fiscale obbligatorio per fattura privato.'
			}
		}
		return null
	}

	return {
		fatturazioneType,
		invoiceSubjectType,
		fatturaData,
		billingShippingFullAddress,
		billingPayload,
		validateBilling,
	}
}
