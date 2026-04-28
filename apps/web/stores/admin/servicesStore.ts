/**
 * servicesStore — servizi utente (sezione "Servizi e supplementi" pannello admin).
 *
 * Estratto dalla sezione "services" di composables/useAdminPricing.js
 * (split atomico Pinia 2026-04-26). Comprende:
 *   - state servicePricing (etichetta, notifiche, sponda, contrassegno, assicurazione)
 *   - UI state della vista admin (tab attivo + filtri/search)
 *   - computed entries + filtraggio combinato (con supplementi/fee dal supplementsStore)
 */
import { defineStore } from 'pinia'
import {
	buildPricingRulesPayload,
	cloneForSnapshot,
	ADMIN_DEFAULT_SERVICE_PRICING,
	normalizePricingGroup,
} from '~/utils/adminPrezziHelpers'
import { useAdminSupplementsStore } from '~/stores/admin/supplementsStore'

export const useAdminServicesStore = defineStore('admin-services', () => {
	// ---------- STATE ----------
	const servicePricing = ref({})
	const originalServicePricing = ref({})

	// ---------- UI STATE ----------
	const adminView = ref('nazionale')
	const serviceSearch = ref('')
	const serviceFilter = ref('all')

	// ---------- COMPUTED ----------
	const servicePricingEntries = computed(() =>
		Object.entries(servicePricing.value || {}).map(([key, rule]) => ({
			key,
			rule,
			section: 'service_pricing' ,
		})),
	)

	const filteredServiceEntries = computed(() => {
		const supplements = useAdminSupplementsStore()
		const search = serviceSearch.value.trim().toLowerCase()
		const activeFilter = serviceFilter.value
		return [
			...(activeFilter === 'all' || activeFilter === 'service_pricing' ? servicePricingEntries.value : []),
			...(activeFilter === 'all' || activeFilter === 'automatic_supplements' ? supplements.automaticSupplementEntries : []),
			...(activeFilter === 'all' || activeFilter === 'operational_fees' ? supplements.operationalFeeEntries : []),
		].filter(({ rule }) => {
			if (!search) return true
			const r = rule as { label, description, note?: string }
			return `${r.label ?? ''} ${r.description ?? ''} ${r.note ?? ''}`.toLowerCase().includes(search)
		})
	})

	// ---------- HYDRATION ----------
	const applyDefaults = () => {
		servicePricing.value = normalizePricingGroup({}, ADMIN_DEFAULT_SERVICE_PRICING)
		originalServicePricing.value = cloneForSnapshot(servicePricing.value)
	}

	const hydrateFromApi = (data) => {
		servicePricing.value = normalizePricingGroup(
			(data.service_pricing ) || {},
			ADMIN_DEFAULT_SERVICE_PRICING,
		)
		originalServicePricing.value = cloneForSnapshot(servicePricing.value)
	}

	const persistApiResponse = (data, fallbackPayload) => {
		servicePricing.value = normalizePricingGroup(
			(data.service_pricing ) || (fallbackPayload.service_pricing ) || {},
			ADMIN_DEFAULT_SERVICE_PRICING,
		)
		originalServicePricing.value = cloneForSnapshot(servicePricing.value)
	}

	// ---------- PAYLOAD ----------
	const buildServicesPayload = () => ({
		service_pricing: buildPricingRulesPayload(servicePricing.value),
	})

	return {
		// state
		servicePricing,
		originalServicePricing,
		adminView,
		serviceSearch,
		serviceFilter,
		// computed
		servicePricingEntries,
		filteredServiceEntries,
		// hydration / payload
		applyDefaults,
		hydrateFromApi,
		persistApiResponse,
		buildServicesPayload,
	}
})
