/**
 * @file shipmentFlowStore — Pinia store shipmentFlowStore.
 */
import { defineStore } from 'pinia'

/**
 * @typedef {import('~/types').Address} Address
 * @typedef {import('~/types').Package} Package
 * @typedef {import('~/types').PendingShipment} PendingShipment
 * @typedef {import('~/types').PudoPoint} PudoPoint
 * @typedef {import('~/types').ShipmentDetails} ShipmentDetails
 * @typedef {import('~/types').ShipmentFlowStoreState} ShipmentFlowStoreState
 */

// Chiave per sessionStorage
const STORAGE_KEY = 'spedizionefacile_user_store'

/** @type {ShipmentDetails} */
const DEFAULT_SHIPMENT_DETAILS = {
	origin_city: '',
	origin_postal_code: '',
	origin_province: '',
	origin_country_code: 'IT',
	origin_country: 'Italia',
	destination_city: '',
	destination_postal_code: '',
	destination_province: '',
	destination_country_code: 'IT',
	destination_country: 'Italia',
	date: '',
}

// Debounce: evita troppe scritture consecutive su sessionStorage.
// Il deep watcher su 14 ref scatta spesso; con debounce scriviamo max 1 volta ogni 300ms.
/** @type {ReturnType<typeof setTimeout> | null} */
let debounceTimer = null
const DEBOUNCE_MS = 300

/**
 * Carica lo stato salvato da sessionStorage con validazione leggera della forma (XSS mitigation)
 * @returns {Partial<ShipmentFlowStoreState> | null}
 */
function loadFromSession() {
	if (!import.meta.client) return null
	try {
		const saved = sessionStorage.getItem(STORAGE_KEY)
		if (!saved) return null
		const parsed = JSON.parse(saved)
		if (typeof parsed !== 'object' || parsed === null || Array.isArray(parsed)) return null
		return parsed
	}
	catch {
		return null
	}
}

/**
 * Salva lo stato corrente in sessionStorage
 * @param {ShipmentFlowStoreState} state
 * @returns {void}
 */
function saveToSession(state) {
	if (!import.meta.client) return
	try {
		sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state))
	}
	catch {
		// sessionStorage pieno o non disponibile: ignoriamo
	}
}

export const useShipmentFlowStore = defineStore('shipmentFlow', () => {
	// --- STATO DEL FLUSSO DI SPEDIZIONE ---

	const stepNumber = ref(1) // Step corrente del processo (1-5)
	// Questo flag riguarda SOLO la hydration da sessionStorage client-side.
	// Non deve partire a true durante l'SSR, altrimenti il client eredita uno
	// stato "gia' idratato" e salta il restore del draft locale al primo paint.
	const hasPersistedHydration = ref(false)

	// --- DETTAGLI SPEDIZIONE (Step 1 — Preventivo) ---

	/** @type {import('vue').Ref<ShipmentDetails>} */
	const shipmentDetails = ref({ ...DEFAULT_SHIPMENT_DETAILS })

	if (!shipmentDetails.value.origin_country_code) shipmentDetails.value.origin_country_code = 'IT'
	if (!shipmentDetails.value.origin_country) shipmentDetails.value.origin_country = 'Italia'
	if (!shipmentDetails.value.destination_country_code) shipmentDetails.value.destination_country_code = 'IT'
	if (!shipmentDetails.value.destination_country) shipmentDetails.value.destination_country = 'Italia'

	const isQuoteStarted = ref(false) // true dopo il primo calcolo prezzo

	const totalPrice = ref(0) // Prezzo totale in euro (somma di tutti i pacchi)

	/** @type {import('vue').Ref<Package[]>} */
	const packages = ref([]) // Array pacchi: [{package_type, weight, first_size, ...}]

	// --- SERVIZI E CONTENUTO (Step 2) ---

	/** @type {import('vue').Ref<string[]>} */
	const servicesArray = ref([]) // Servizi selezionati (es. ["contrassegno"])

	// Descrizione contenuto del pacco (es. "Elettronica", "Abbigliamento")
	const contentDescription = ref('')

	// --- DATI PER IL RIEPILOGO E NAVIGAZIONE ALL'INDIETRO (Step 3-4) ---

	/** @type {import('vue').Ref<PendingShipment | null>} */
	// Payload completo della spedizione (usato da /riepilogo per mostrare il riepilogo)
	const pendingShipment = ref(null)

	/** @type {import('vue').Ref<Partial<Address> | null>} */
	// Dati indirizzo per pre-compilare i campi quando l'utente torna indietro
	const originAddressData = ref(null)
	/** @type {import('vue').Ref<Partial<Address> | null>} */
	const destinationAddressData = ref(null)
	const pickupDate = ref('')

	// --- MODIFICA CARRELLO ---

	/** @type {import('vue').Ref<number | string | null>} */
	// ID del pacco nel carrello che si sta modificando (null = nuova spedizione)
	const editingCartItemId = ref(null)

	// --- PUDO (Consegna presso punto BRT) ---

	/** @type {import('vue').Ref<'home' | 'pudo'>} */
	// Modalita' di consegna: 'home = domicilio, 'pudo' = punto BRT
	const deliveryMode = ref('home')
	/** @type {import('vue').Ref<PudoPoint | null>} */
	// Punto di ritiro selezionato (oggetto con pudo_id, name, address, ecc.)
	const selectedPudo = ref(null)
	const smsEmailNotification = ref(false)
	/** @type {import('vue').Ref<Record<string, unknown>>} */
	const serviceData = ref({})

	/**
	 * Applica lo stato persistito al reactive state del store.
	 * @param {Partial<ShipmentFlowStoreState> | null} saved
	 * @returns {void}
	 */
	function applyPersistedState(saved) {
		if (!saved || typeof saved !== 'object') return

		stepNumber.value = typeof saved.stepNumber === 'number' ? saved.stepNumber : 1
		shipmentDetails.value = {
			...DEFAULT_SHIPMENT_DETAILS,
			...(saved.shipmentDetails || {}),
		}
		isQuoteStarted.value = saved.isQuoteStarted ?? false
		totalPrice.value = typeof saved.totalPrice === 'number' ? saved.totalPrice : 0
		packages.value = Array.isArray(saved.packages) ? saved.packages : []
		servicesArray.value = Array.isArray(saved.servicesArray) ? saved.servicesArray : []
		contentDescription.value = saved.contentDescription ?? ''
		pendingShipment.value = saved.pendingShipment ?? null
		originAddressData.value = saved.originAddressData ?? null
		destinationAddressData.value = saved.destinationAddressData ?? null
		pickupDate.value = saved.pickupDate ?? ''
		editingCartItemId.value = saved.editingCartItemId ?? null
		deliveryMode.value = saved.deliveryMode ?? 'home'
		selectedPudo.value = saved.selectedPudo ?? null
		smsEmailNotification.value = saved.smsEmailNotification ?? false
		serviceData.value = saved.serviceData ?? {}
	}

	/**
	 * Idrata lo store dai dati salvati in sessionStorage (solo client).
	 * @returns {void}
	 */
	function hydrateFromSession() {
		if (hasPersistedHydration.value) return
		applyPersistedState(loadFromSession())
		hasPersistedHydration.value = true
	}

	// Salva in sessionStorage ogni volta che cambia qualcosa.
	// Debounced: accumula le modifiche e scrive una sola volta ogni 300ms
	// per evitare scritture eccessive su sessionStorage durante input rapidi.
	/**
	 * Persiste lo stato corrente in sessionStorage con debounce.
	 * @returns {void}
	 */
	function persist() {
		if (!hasPersistedHydration.value) return
		if (debounceTimer) clearTimeout(debounceTimer)
		debounceTimer = setTimeout(() => {
			saveToSession({
				stepNumber: stepNumber.value,
				shipmentDetails: shipmentDetails.value,
				isQuoteStarted: isQuoteStarted.value,
				totalPrice: totalPrice.value,
				packages: packages.value,
				servicesArray: servicesArray.value,
				contentDescription: contentDescription.value,
				pendingShipment: pendingShipment.value,
				originAddressData: originAddressData.value,
				destinationAddressData: destinationAddressData.value,
				pickupDate: pickupDate.value,
				editingCartItemId: editingCartItemId.value,
				deliveryMode: deliveryMode.value,
				selectedPudo: selectedPudo.value,
				smsEmailNotification: smsEmailNotification.value,
				serviceData: serviceData.value,
			})
		}, DEBOUNCE_MS)
	}

	// Osserva tutti i campi e persisti automaticamente
	watch(
		[
			stepNumber, shipmentDetails, isQuoteStarted, totalPrice, packages,
			servicesArray, contentDescription, pendingShipment, originAddressData,
			destinationAddressData, pickupDate, editingCartItemId, deliveryMode, selectedPudo,
			smsEmailNotification, serviceData,
		],
		persist,
		{ deep: true },
	)

	return {
		stepNumber,
		isQuoteStarted,
		shipmentDetails,
		packages,
		totalPrice,
		servicesArray,
		contentDescription,
		pendingShipment,
		originAddressData,
		destinationAddressData,
		pickupDate,
		editingCartItemId,
		deliveryMode,
		selectedPudo,
		smsEmailNotification,
		serviceData,
		hasPersistedHydration,
		hydrateFromSession,
	}
})
