/**
 * TYPES — barrel re-export per dominio.
 *
 * Splitting per dominio (agency-grade): ogni file espone i tipi di un'area
 * specifica. Questo file re-esporta tutto per i caller storici che importano
 * da '~/types'. Per nuovi caller, preferire l'import dal file specifico
 * (es. `import type { CartItem } from '~/types/cart'`).
 *
 * CONVENZIONE PREZZI:
 *   - Campi *_cents (single_price, subtotal_cents) = centesimi (intero).
 *   - Campi senza _cents (subtotal, total) = stringhe formattate "20,00€" o float euro.
 *   - Per visualizzare: dividere per 100. Per inviare al backend: euro (diviso per 100).
 */

export type * from './address'
export type * from './admin'
export type * from './auth'
export type * from './cart'
export type * from './location'
export type * from './order'
export type * from './pricing'
export type * from './pudo'
export type * from './shipment'
export type * from './shipmentAddressForm'
