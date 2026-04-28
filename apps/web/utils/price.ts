/**
 * Formattazione prezzi centralizzata — SpedizioneFacile
 *
 * Convenzione: il backend salva i prezzi in CENTESIMI (integer).
 * Il frontend mostra in formato italiano: "8,90 €"
 */

/**
 * Centesimi → stringa italiana con simbolo
 * @param {number} cents - es. 890
 * @returns {string} - es. "8,90 €"
 */
export const formatPrice = (cents) => {
  if (!cents && cents !== 0) return '0,00 €'
  const num = Number(cents) / 100
  return num.toFixed(2).replace('.', ',') + ' \u20AC'
}

/**
 * Euro (numero) → stringa italiana senza simbolo
 * @param {number} euros - es. 8.9
 * @returns {string} - es. "8,90"
 */
export const formatEuro = (euros) => {
  if (!euros && euros !== 0) return '0,00'
  return Number(euros).toFixed(2).replace('.', ',')
}

/**
 * Euro (stringa o numero) → centesimi (integer)
 * @param {string|number} euro - es. "8,90" o 8.9
 * @returns {number|null}
 */
export const euroToCents = (euro) => {
  if (euro == null || euro === '') return null
  const cleaned = String(euro).replace(/[\u20AC\u00A0\s]/g, '').replace(',', '.')
  const num = parseFloat(cleaned)
  return isNaN(num) ? null : Math.round(num * 100)
}

/**
 * Euro (numero) → centesimi (integer)
 * @param {number} euros - es. 8.9
 * @returns {number}
 */
export const toCents = (euros) => Math.round(Number(euros) * 100)

/**
 * Centesimi → euro (numero)
 * @param {number} cents - es. 890
 * @returns {number}
 */
export const toEuros = (cents) => Number(cents) / 100

/**
 * Centesimi → stringa formattata Intl (it-IT, EUR)
 * @param {number} cents - es. 890
 * @returns {string} - es. "8,90 €"
 */
export const formatPriceIntl = (cents) => {
  return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(cents / 100)
}

/**
 * Variante "safe" di formatPriceIntl: ritorna "—" per valori non finiti.
 * Usata nelle pagine ordini/collo per gestire lo stato loading/missing.
 * @param {number|null|undefined} cents
 * @returns {string}
 */
export const formatPriceSafe = (cents) => {
  const value = Number(cents)
  if (!Number.isFinite(value)) return '—'
  return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(value / 100)
}

/**
 * Parse input utente (formato italiano/misto) → centesimi
 * Supporta: "12,50", "12.50", "1.234,56", "12,50 EUR", "€12,50"
 * @param {string} raw
 * @returns {number|null}
 */
export const parsePrice = (raw) => {
  if (!raw) return null
  let s = String(raw).replace(/[\u20AC\u00A0\s]/g, '').replace(/EUR/gi, '')

  if (s.includes(',') && s.includes('.')) {
    if (s.lastIndexOf(',') > s.lastIndexOf('.')) {
      s = s.replace(/\./g, '').replace(',', '.')
    } else {
      s = s.replace(/,/g, '')
    }
  } else {
    s = s.replace(',', '.')
  }

  const num = parseFloat(s)
  return isNaN(num) ? null : Math.round(num * 100)
}
