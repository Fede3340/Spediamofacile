/**
 * preventivoHelpers — helper puri usati dal modulo Preventivo Rapido.
 *
 * Re-esporta i contratti canonici definiti in `quickQuoteContract.js`
 * sotto i nomi storici usati dai consumer del preventivo, e aggiunge
 * piccole utility di formattazione/comparazione locali al modulo.
 *
 * Nessun side effect UI, nessuna chiamata rete: tutto SSR-safe.
 */

import {
	clonePackagesForQuote,
	cloneShipmentDetailsForQuote,
} from "~/utils/quickQuoteContract"

/**
 * Costruisce lo snapshot del payload di preventivo a partire dallo store.
 * Lo stesso oggetto viene serializzato e usato sia per la POST a
 * `/api/session/first-step` sia per il calcolo della signature di dedup.
 */
export function buildQuotePayloadSnapshotFor(shipmentFlowStore) {
	return {
		shipment_details: cloneShipmentDetailsForQuote(shipmentFlowStore?.shipmentDetails),
		packages: clonePackagesForQuote(shipmentFlowStore?.packages),
	}
}

/**
 * Formatta un importo in EUR usando il locale italiano e rimuove gli spazi
 * tra simbolo e valore: viene usato per la pillola "prezzo live" sotto i CTA.
 */
export function formatLivePrice(amount) {
	return new Intl.NumberFormat("it-IT", {
		style: "currency",
		currency: "EUR",
		minimumFractionDigits: 2,
		maximumFractionDigits: 2,
	}).format(Number(amount) || 0).replace(/\s/g, "")
}

/**
 * Restituisce true se almeno uno dei campi obbligatori del package
 * (peso + 3 dimensioni) e' vuoto: usato come pre-check prima delle API.
 */
export function packageMissingMeasurements(pack) {
	return !pack?.weight || !pack?.first_size || !pack?.second_size || !pack?.third_size
}
