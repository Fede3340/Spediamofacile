// === utils/shipment.js — Helper funnel spedizione ===
// Consolidamento di:
//   - utils/shipmentFlowState.ts     (state machine, stage, routes, derive)
//   - utils/clientSubmissionId.ts    (idempotency key per checkout/preventivo)
// Tutti gli export originali sono preservati identici.
// Nota: la logica di pricing servizi rimane in utils/shipmentServicePricing.js
// (file complesso di 718 LOC, non coinvolto in questo consolidamento).

// ─────────────────────────────────────────────────────────────────
// SEZIONE 1 — ex utils/shipmentFlowState.ts
// ─────────────────────────────────────────────────────────────────

// Alias legacy `summary` deve puntare al payment route: il vecchio step Conferma è stato unificato nel Pagamento, e i deep-link esistenti devono continuare a funzionare.
export const SHIPMENT_FLOW_ROUTES = Object.freeze({
	quote: '/preventivo',
	packages: '/la-tua-spedizione/2?step=colli',
	services: '/la-tua-spedizione/2?step=servizi',
	addresses: '/la-tua-spedizione/2?step=indirizzi',
	summary: '/la-tua-spedizione/2?step=pagamento',
	payment: '/la-tua-spedizione/2?step=pagamento',
})

/**
 * @typedef {'quote' | 'packages' | 'services' | 'addresses' | 'summary' | 'payment'} ShipmentFlowStage
 */

/**
 * @typedef {Object} ShipmentFlowState
 * @property {boolean} quote_ready
 * @property {boolean} services_ready
 * @property {boolean} addresses_ready
 * @property {boolean} summary_ready
 * @property {string} last_valid_route
 */

/**
 * @typedef {Object} RouteLike
 * @property {string} [path]
 * @property {string} [fullPath]
 * @property {Record<string, string | string[] | undefined | null>} [query]
 * @property {string} [hash]
 */

/**
 * @typedef {Object} AddressDraft
 * @property {string} [name]
 * @property {string} [full_name]
 * @property {string} [address]
 * @property {string} [address_number]
 * @property {string} [intercom_code]
 * @property {string} [city]
 * @property {string} [postal_code]
 * @property {string} [province]
 * @property {string} [country]
 * @property {string} [additional_information]
 * @property {string} [telephone_number]
 * @property {string} [email]
 * @property {string} [type]
 */

/**
 * @typedef {Object} PackageDraft
 * @property {string} [package_type]
 * @property {number | string} [quantity]
 * @property {number | string} [weight]
 * @property {number | string} [first_size]
 * @property {number | string} [second_size]
 * @property {number | string} [third_size]
 */

/**
 * @typedef {Object} StepAddressState
 * @property {string} full_name
 * @property {string} additional_information
 * @property {string} address
 * @property {string} address_number
 * @property {string} intercom_code
 * @property {string} country
 * @property {string} city
 * @property {string} postal_code
 * @property {string} province
 * @property {string} telephone_number
 * @property {string} email
 * @property {string} type
 */

/**
 * @typedef {Object} ShipmentFlowDeriveInput
 * @property {PackageDraft[]} [packages]
 * @property {string} [pickup_date]
 * @property {string} [content_description]
 * @property {AddressDraft | null} [origin_address]
 * @property {AddressDraft | null} [destination_address]
 * @property {{ service_type?: string, date?: string, serviceData?: Record<string, unknown>, sms_email_notification?: boolean }} [services]
 * @property {Record<string, unknown>} [service_data]
 * @property {boolean} [sms_email_notification]
 * @property {string} [delivery_mode]
 * @property {unknown} [selected_pudo]
 * @property {Partial<ShipmentFlowState>} [flow_state]
 */

/**
 * @typedef {Object} UserStoreLikeInput
 * @property {{ origin_city?: string, destination_city?: string, date?: string }} [shipmentDetails]
 * @property {PackageDraft[]} [packages]
 * @property {{ packages?: PackageDraft[], origin_address?: AddressDraft | null, destination_address?: AddressDraft | null, content_description?: string, pickup_date?: string, delivery_mode?: string, pudo?: unknown, services?: { date?: string } } | null} [pendingShipment]
 * @property {string} [deliveryMode]
 * @property {unknown} [selectedPudo]
 * @property {string} [pickupDate]
 * @property {string} [contentDescription]
 * @property {AddressDraft | null} [originAddressData]
 * @property {AddressDraft | null} [destinationAddressData]
 */

// Helper interno: signature diversa dalla versione esportata in utils/auth.js
// (qui forza string | undefined, mentre in auth è generico). Resta privato.
/**
 * @param {unknown} value
 * @returns {string | undefined}
 */
const getRouteQueryValue = (value) => {
	if (Array.isArray(value)) return value[0]
	return value
}

/**
 * @param {unknown} value
 * @param {string} [fallback]
 * @returns {string}
 */
const normalizeShipmentStep = (value, fallback = 'colli') => {
	const normalized = String(getRouteQueryValue(value) || '').trim()
	return normalized || fallback
}

/**
 * Costruisce una location Nuxt per una route dentro il funnel spedizione.
 * @param {RouteLike} [routeLike]
 * @param {string} [step]
 * @returns {{ path: string, query: Record<string, string>, hash: string | undefined }}
 */
export const buildShipmentFlowLocation = (routeLike = {}, step = 'colli') => ({
	path: '/la-tua-spedizione/2',
	query: {
		...(routeLike?.query || {}),
		step: normalizeShipmentStep(step),
	},
	hash: routeLike?.hash,
})

/**
 * Costruisce la location per modificare un item del carrello dentro il flusso.
 * @param {number | string} editId
 * @param {string} [step]
 * @returns {{ path: string, query: Record<string, string> }}
 */
export const buildShipmentFlowEditLocation = (editId, step = 'pagamento') => ({
	path: '/la-tua-spedizione/2',
	query: {
		step: normalizeShipmentStep(step, 'pagamento'),
		edit: String(editId),
	},
})

/**
 * @param {unknown} value
 * @returns {boolean}
 */
const hasMeaningfulText = (value) => String(value || '').trim().length > 0

/**
 * @param {unknown} value
 * @returns {boolean}
 */
const hasPositiveQueryId = (value) => {
	const raw = Array.isArray(value) ? value[0] : value
	if (raw === undefined || raw === null || raw === '') return false
	const parsed = Number(raw)
	return Number.isFinite(parsed) ? parsed > 0 : hasMeaningfulText(raw)
}

/**
 * @param {unknown} serviceType
 * @returns {string[]}
 */
const normalizeServiceTypeList = (serviceType) => String(serviceType || '')
	.split(',')
	.map((value) => value.trim())
	.filter(Boolean)

/**
 * @param {unknown} packages
 * @returns {boolean}
 */
const hasPackages = (packages) => Array.isArray(packages) && packages.length > 0

/**
 * @param {unknown} value
 * @returns {number}
 */
const toPositiveNumber = (value) => {
	const normalized = String(value ?? '')
		.replace(',', '.')
		.replace(/[^0-9.]/g, '')
	const parsed = Number(normalized)
	return Number.isFinite(parsed) ? parsed : 0
}

/**
 * @param {PackageDraft} [pack]
 * @returns {boolean}
 */
const hasCompletePackageDetails = (pack = {}) => (
	hasMeaningfulText(pack?.package_type)
	&& toPositiveNumber(pack?.quantity) >= 1
	&& toPositiveNumber(pack?.weight) > 0
	&& toPositiveNumber(pack?.first_size) > 0
	&& toPositiveNumber(pack?.second_size) > 0
	&& toPositiveNumber(pack?.third_size) > 0
)

/**
 * @param {unknown} packages
 * @returns {boolean}
 */
const hasPackagesReady = (packages) => hasPackages(packages) && packages.every((pack) => hasCompletePackageDetails(pack))

/**
 * @param {AddressDraft | null | undefined} [address]
 * @returns {boolean}
 */
const hasAddressDraft = (address = {}) => {
	const a = address || {}
	return (
		hasMeaningfulText(a.name || a.full_name)
		&& hasMeaningfulText(a.address)
		&& hasMeaningfulText(a.city)
		&& hasMeaningfulText(a.postal_code)
	)
}

/**
 * Deriva lo stato del flusso da un payload sessione/server.
 * @param {ShipmentFlowDeriveInput} [data]
 * @returns {ShipmentFlowState}
 */
export const deriveShipmentFlowState = (data = {}) => {
	const quoteReady = hasPackagesReady(data?.packages)
	const pickupDate = data?.pickup_date || data?.services?.date || ''
	const contentDescription = data?.content_description || ''
	const servicesReady = quoteReady && hasMeaningfulText(contentDescription) && hasMeaningfulText(pickupDate)
	const addressesReady = servicesReady
		&& hasAddressDraft(data?.origin_address)
		&& hasAddressDraft(data?.destination_address)
	const summaryReady = addressesReady

	let lastValidRoute = SHIPMENT_FLOW_ROUTES.packages
	if (summaryReady) {
		lastValidRoute = SHIPMENT_FLOW_ROUTES.summary
	}
	else if (servicesReady) {
		lastValidRoute = SHIPMENT_FLOW_ROUTES.addresses
	}
	else if (quoteReady) {
		lastValidRoute = SHIPMENT_FLOW_ROUTES.services
	}

	return {
		quote_ready: quoteReady,
		services_ready: servicesReady,
		addresses_ready: addressesReady,
		summary_ready: summaryReady,
		last_valid_route: lastValidRoute,
	}
}

/**
 * Se il payload contiene già flow_state persistito, lo normalizza usando derive come fallback.
 * @param {ShipmentFlowDeriveInput} [data]
 * @returns {ShipmentFlowState}
 */
export const resolveShipmentFlowState = (data = {}) => {
	const raw = data?.flow_state
	const fallback = deriveShipmentFlowState(data)
	if (!raw || typeof raw !== 'object') return fallback

	return {
		quote_ready: Boolean(raw.quote_ready ?? fallback.quote_ready),
		services_ready: Boolean(raw.services_ready ?? fallback.services_ready),
		addresses_ready: Boolean(raw.addresses_ready ?? fallback.addresses_ready),
		summary_ready: Boolean(raw.summary_ready ?? fallback.summary_ready),
		last_valid_route: hasMeaningfulText(raw.last_valid_route)
			? String(raw.last_valid_route)
			: fallback.last_valid_route,
	}
}

/**
 * @param {Partial<ShipmentFlowState>} [flowState]
 * @returns {number}
 */
const getFlowStateRank = (flowState = {}) => {
	if (flowState?.summary_ready) return 4
	if (flowState?.addresses_ready) return 3
	if (flowState?.services_ready) return 2
	if (flowState?.quote_ready) return 1
	return 0
}

/**
 * Fra più snapshot di flow-state sceglie quello più avanzato.
 * @param {...(Partial<ShipmentFlowState> | null | undefined)} states
 * @returns {ShipmentFlowState}
 */
export const pickMostAdvancedShipmentFlowState = (...states) => states
	.filter((state) => !!state && typeof state === 'object')
	.reduce((best, candidate) => {
		if (!best) return candidate
		return getFlowStateRank(candidate) > getFlowStateRank(best) ? candidate : best
	}, null) || deriveShipmentFlowState({})

/**
 * Determina lo stage corrispondente a una route (quote/packages/services/ecc.).
 * @param {RouteLike} routeLike
 * @returns {ShipmentFlowStage | null}
 */
export const getShipmentFlowStage = (routeLike) => {
	const path = String(routeLike?.path || routeLike?.fullPath || '')
	const query = routeLike?.query || {}
	const stepQuery = normalizeShipmentStep(query.step, '')

	if (path === SHIPMENT_FLOW_ROUTES.quote || path === '/' || path === '/#preventivo') return 'quote'
	if (path.startsWith('/la-tua-spedizione')) {
		if (stepQuery === 'colli') return 'packages'
		// Alias storico: step=conferma e' stato unificato nel payment step.
		if (stepQuery === 'pagamento' || stepQuery === 'conferma') return 'payment'
		if (stepQuery === 'indirizzi' || stepQuery === 'ritiro' || stepQuery === 'addresses') return 'addresses'
		if (stepQuery === 'servizi' || stepQuery === 'services') return 'services'
		return 'services'
	}
	// Trampolini legacy: /riepilogo reindirizza a pagamento via riepilogo.vue.
	if (path.startsWith('/riepilogo')) return 'payment'
	if (path.startsWith('/checkout')) return 'payment'
	return null
}

/**
 * Riconosce route di ripresa (edit, pagamento con order_id) per bypassare il guard.
 * @param {RouteLike} routeLike
 * @returns {boolean}
 */
export const isShipmentFlowResumeException = (routeLike) => {
	const path = String(routeLike?.path || routeLike?.fullPath || '')
	const query = routeLike?.query || {}
	const stepQuery = normalizeShipmentStep(query.step, '')

	if (path.startsWith('/la-tua-spedizione') && hasPositiveQueryId(query.edit)) return true
	if (
		path.startsWith('/la-tua-spedizione')
		&& hasPositiveQueryId(query.order_id)
		&& ['pagamento', 'conferma', 'payment'].includes(stepQuery)
	) return true
	return false
}

/**
 * Decide se l'utente può accedere a una route del funnel dato lo stato corrente.
 * @param {RouteLike} routeLike
 * @param {Partial<ShipmentFlowState> | null | undefined} flowState
 * @returns {boolean}
 */
export const canAccessShipmentFlowRoute = (routeLike, flowState) => {
	if (isShipmentFlowResumeException(routeLike)) return true

	const stage = getShipmentFlowStage(routeLike)
	if (!stage || stage === 'quote' || stage === 'packages') return true
	if (stage === 'services') return Boolean(flowState?.quote_ready)
	if (stage === 'addresses') return Boolean(flowState?.services_ready)
	// stage === 'summary' e' un alias storico di 'payment' (unificati in Sprint 2.1).
	if (stage === 'summary' || stage === 'payment') return Boolean(flowState?.addresses_ready)
	return true
}

/**
 * Converte uno stato flusso nello step numerico (1-4) esposto all'UI.
 * @param {Partial<ShipmentFlowState> | null | undefined} flowState
 * @returns {1 | 2 | 3 | 4}
 */
export const getShipmentFlowStepNumber = (flowState) => {
	if (flowState?.summary_ready) return 4
	if (flowState?.services_ready) return 3
	if (flowState?.quote_ready) return 2
	return 1
}

/**
 * Estrae la lista di servizi selezionati dal payload della sessione.
 * @param {ShipmentFlowDeriveInput} [data]
 * @returns {string[]}
 */
export const extractShipmentServicesArray = (data = {}) => normalizeServiceTypeList(data?.services?.service_type || '')

/**
 * Normalizza un indirizzo draft nella forma attesa dagli step address.
 * @param {AddressDraft | null} [address]
 * @returns {StepAddressState | null}
 */
export const toStepAddressState = (address = null) => {
	if (!address || typeof address !== 'object') return null

	return {
		full_name: address.name || '',
		additional_information: address.additional_information || '',
		address: address.address || '',
		address_number: address.address_number || '',
		intercom_code: address.intercom_code || '',
		country: address.country || 'Italia',
		city: address.city || '',
		postal_code: address.postal_code || '',
		province: address.province || '',
		telephone_number: address.telephone_number || '',
		email: address.email || '',
		type: address.type || '',
	}
}

/**
 * @typedef {Object} BuiltPendingShipment
 * @property {AddressDraft} origin_address
 * @property {AddressDraft} destination_address
 * @property {{ service_type?: string, date?: string, serviceData: Record<string, unknown>, sms_email_notification: boolean }} services
 * @property {PackageDraft[]} packages
 * @property {string} content_description
 * @property {string} delivery_mode
 * @property {unknown} pudo
 * @property {boolean} sms_email_notification
 * @property {string} [client_submission_id]
 */

/**
 * Costruisce il payload completo di pendingShipment partendo dai dati sessione.
 * @param {ShipmentFlowDeriveInput} [data]
 * @returns {BuiltPendingShipment | null}
 */
export const buildPendingShipmentFromSession = (data = {}) => {
	const flowState = resolveShipmentFlowState(data)
	if (!flowState.summary_ready) return null
	if (!hasPackages(data?.packages)) return null
	if (!data?.origin_address || !data?.destination_address) return null
	const clientSubmissionId = typeof data?.client_submission_id === 'string'
		? data.client_submission_id.trim()
		: ''

	return {
		origin_address: data.origin_address,
		destination_address: data.destination_address,
		services: {
			...(data.services || {}),
			serviceData: {
				...((data.service_data || data?.services?.serviceData || {})),
				sms_email_notification: Boolean(
					data?.sms_email_notification
					?? data?.services?.sms_email_notification
					?? data?.service_data?.sms_email_notification,
				),
			},
			sms_email_notification: Boolean(
				data?.sms_email_notification
				?? data?.services?.sms_email_notification
				?? data?.service_data?.sms_email_notification,
			),
		},
		packages: Array.isArray(data.packages) ? [...data.packages] : [],
		content_description: data.content_description || '',
		delivery_mode: data.delivery_mode || 'home',
		pudo: data.selected_pudo || null,
		sms_email_notification: Boolean(
			data?.sms_email_notification
			?? data?.services?.sms_email_notification
			?? data?.service_data?.sms_email_notification,
		),
		client_submission_id: clientSubmissionId || undefined,
	}
}

/**
 * Deriva il flow-state partendo dallo store locale (sessionStorage).
 * @param {UserStoreLikeInput} [shipmentFlowStore]
 * @returns {ShipmentFlowState}
 */
export const deriveShipmentFlowStateFromUserStore = (shipmentFlowStore = {}) => {
	const shipmentDetails = shipmentFlowStore?.shipmentDetails || {}
	const packages = Array.isArray(shipmentFlowStore?.packages) ? shipmentFlowStore?.packages : []
	const pendingShipment = shipmentFlowStore?.pendingShipment || {}
	const pendingPackages = Array.isArray(pendingShipment?.packages) ? pendingShipment.packages : []
	const deliveryMode = shipmentFlowStore?.deliveryMode || pendingShipment?.delivery_mode || 'home'
	const selectedPudo = shipmentFlowStore?.selectedPudo || pendingShipment?.pudo || null

	const quoteReady = hasPackagesReady(packages)
	const pickupDate = shipmentFlowStore?.pickupDate || shipmentDetails?.date || pendingShipment?.pickup_date || ''
	const contentDescription = shipmentFlowStore?.contentDescription || pendingShipment?.content_description || ''
	const servicesReady = quoteReady && hasMeaningfulText(contentDescription) && hasMeaningfulText(pickupDate)

	const originAddress = shipmentFlowStore?.originAddressData || pendingShipment?.origin_address || null
	const destinationAddress = shipmentFlowStore?.destinationAddressData || pendingShipment?.destination_address || null
	const hasDestinationReady = deliveryMode === 'pudo'
		? Boolean(selectedPudo) || hasAddressDraft(destinationAddress)
		: hasAddressDraft(destinationAddress)
	const addressesReady = servicesReady
		&& hasAddressDraft(originAddress)
		&& hasDestinationReady
	const pendingServicesReady = hasMeaningfulText(pendingShipment?.content_description || '')
		&& hasMeaningfulText(pendingShipment?.pickup_date || pendingShipment?.services?.date || '')
	const pendingDestinationReady = deliveryMode === 'pudo'
		? Boolean(selectedPudo) || hasAddressDraft(pendingShipment?.destination_address || null)
		: hasAddressDraft(pendingShipment?.destination_address || null)
	const pendingSummaryReady = hasPackagesReady(pendingPackages)
		&& pendingServicesReady
		&& hasAddressDraft(pendingShipment?.origin_address || null)
		&& pendingDestinationReady
	const summaryReady = addressesReady || pendingSummaryReady

	let lastValidRoute = SHIPMENT_FLOW_ROUTES.packages
	if (summaryReady) {
		lastValidRoute = SHIPMENT_FLOW_ROUTES.summary
	}
	else if (servicesReady) {
		lastValidRoute = SHIPMENT_FLOW_ROUTES.addresses
	}
	else if (quoteReady) {
		lastValidRoute = SHIPMENT_FLOW_ROUTES.services
	}

	return {
		quote_ready: quoteReady,
		services_ready: servicesReady,
		addresses_ready: addressesReady,
		summary_ready: summaryReady,
		last_valid_route: lastValidRoute,
	}
}

/**
 * @typedef {Object} TrimmableUserStore
 * @property {unknown} [pendingShipment]
 * @property {unknown} [originAddressData]
 * @property {unknown} [destinationAddressData]
 * @property {unknown} [selectedPudo]
 * @property {unknown} [servicesArray]
 * @property {unknown} [contentDescription]
 * @property {unknown} [pickupDate]
 * @property {unknown} [smsEmailNotification]
 * @property {unknown} [serviceData]
 * @property {unknown} [packages]
 * @property {unknown} [totalPrice]
 * @property {unknown} [isQuoteStarted]
 * @property {unknown} [stepNumber]
 */

/**
 * Azzera le parti di store che eccedono lo stato valido (es. quando si torna indietro).
 * @param {TrimmableUserStore | null | undefined} shipmentFlowStore
 * @param {Partial<ShipmentFlowState> | null | undefined} flowState
 * @returns {void}
 */
export const trimUserStoreToFlowState = (shipmentFlowStore, flowState) => {
	if (!shipmentFlowStore || !flowState) return

	if (!flowState.summary_ready) {
		shipmentFlowStore.pendingShipment = null
	}

	if (!flowState.addresses_ready) {
		shipmentFlowStore.originAddressData = null
		shipmentFlowStore.destinationAddressData = null
		shipmentFlowStore.selectedPudo = null
	}

	if (!flowState.services_ready) {
		shipmentFlowStore.servicesArray = []
		shipmentFlowStore.contentDescription = ''
		shipmentFlowStore.pickupDate = ''
		shipmentFlowStore.smsEmailNotification = false
		shipmentFlowStore.serviceData = {}
	}

	if (!flowState.quote_ready) {
		shipmentFlowStore.packages = []
		shipmentFlowStore.totalPrice = 0
		shipmentFlowStore.isQuoteStarted = false
	}

	shipmentFlowStore.stepNumber = getShipmentFlowStepNumber(flowState)
}

// ─────────────────────────────────────────────────────────────────
// SEZIONE 2 — ex utils/clientSubmissionId.ts
// ─────────────────────────────────────────────────────────────────

// Idempotency key client-side: protegge preventivo e checkout da doppio submit e retry di rete (usato da backend Stripe/ordini per deduplica).
/**
 * @param {unknown} value
 * @returns {string}
 */
const normalizeSubmissionId = (value) => String(value ?? '').trim()

/**
 * Genera un nuovo ID submission client (idempotency key).
 * @returns {string}
 */
export const createClientSubmissionId = () => (
	`sub-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 8)}`
)

/**
 * @typedef {Object} SubmissionSource
 * @property {unknown} [client_submission_id]
 */

/**
 * @typedef {SubmissionSource & { data?: NestedSubmissionSource | null, pendingShipment?: NestedSubmissionSource | null }} NestedSubmissionSource
 */

/**
 * Cerca il primo client_submission_id valido fra le sorgenti passate (flat).
 * @param {...(SubmissionSource | null | undefined)} sources
 * @returns {string | null}
 */
export const readClientSubmissionId = (...sources) => {
	for (const source of sources) {
		if (!source || typeof source !== 'object') continue
		const submissionId = normalizeSubmissionId(source.client_submission_id)
		if (submissionId) return submissionId
	}

	return null
}

/**
 * Cerca ricorsivamente client_submission_id nelle chiavi pendingShipment/data.
 * @param {...(NestedSubmissionSource | null | undefined)} sources
 * @returns {string | null}
 */
export const readNestedClientSubmissionId = (...sources) => {
	const queue = [...sources]
	const visited = new Set()

	while (queue.length > 0) {
		const source = queue.shift()
		if (!source || typeof source !== 'object') continue
		if (visited.has(source)) continue
		visited.add(source)

		const submissionId = normalizeSubmissionId(source.client_submission_id)
		if (submissionId) return submissionId

		const nestedCandidates = [source.pendingShipment, source.data]
		for (const candidate of nestedCandidates) {
			if (candidate && typeof candidate === 'object') {
				queue.push(candidate)
			}
		}
	}

	return null
}

/**
 * Se target non ha un client_submission_id, ne genera uno e lo salva in place.
 * @param {SubmissionSource | null | undefined} target
 * @returns {string}
 */
export const ensureClientSubmissionId = (target) => {
	const existing = readClientSubmissionId(target)
	if (existing) return existing

	const created = createClientSubmissionId()
	if (target && typeof target === 'object') {
		target.client_submission_id = created
	}

	return created
}
