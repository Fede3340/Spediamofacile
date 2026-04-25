/**
 * HTML helpers — utility comuni per gestione stringhe HTML lato client.
 *
 * escapeHtml: escapa entita' HTML per prevenire XSS quando si concatena a stringhe.
 * Usato da: useFaqs (highlight match), PudoMap/MapPudo (popup leaflet).
 */

/**
 * Escapa caratteri HTML pericolosi (&, <, >, ", ').
 * Idempotente sui valori falsy → restituisce stringa vuota.
 */
export const escapeHtml = (value: unknown): string =>
	String(value ?? '')
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;')
		.replace(/'/g, '&#039;')
