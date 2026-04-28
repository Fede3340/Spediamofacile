/**
 * TYPES — Interfacce TypeScript principali
 *
 * Riflettono la struttura REALE dei dati dal backend Laravel e dal frontend Nuxt.
 *
 * CONVENZIONE PREZZI:
 *   - I campi *_cents (es. single_price, subtotal_cents) sono in centesimi di euro (intero).
 *   - I campi senza _cents (es. subtotal, total) sono stringhe formattate "20,00€" o float euro.
 *   - Per la visualizzazione: dividere per 100.
 *   - Per inviare al backend: inviare in euro (diviso per 100).
 *
 * DOVE SI USANO:
 *   - composables/ — hint di tipo per le strutture dati ritornate dalle API
 *   - stores/shipmentFlowStore.js — tipi di packages, shipmentDetails, pendingShipment
 *   - pages/ e components/ — accesso tipizzato ai dati di ordini, carrello, utente
 */

// ---------------------------------------------------------------------------
// INDIRIZZO
// ---------------------------------------------------------------------------

/**
 * Indirizzo fisico: sia mittente (origin_address) che destinatario (destination_address).
 * Usato in: CartItem, Order package, SavedShipment.
 */
export interface Address {
  type?: 'Partenza' | 'Destinazione' | string
  name: string
  surname?: string
  additional_information?: string
  address: string
  number_type?: 'Numero Civico' | 'SNC' | string
  address_number?: string
  intercom_code?: string
  country?: string
  city: string
  postal_code: string
  province: string
  telephone_number?: string
  email?: string
}

// ---------------------------------------------------------------------------
// UTENTE
// ---------------------------------------------------------------------------

/**
 * Utente autenticato — dati restituiti da /api/user e /api/custom-login.
 * Rispecchia il modello Laravel User.
 */
export interface User {
  id: number
  name: string
  surname: string
  email: string
  role: 'Admin' | 'Cliente' | string
  user_type?: 'privato' | 'azienda' | string
  telephone_number?: string
  prefix?: string
  referred_by?: string
  email_verified_at?: string | null
  created_at?: string
  updated_at?: string
  /** Saldo portafoglio in centesimi */
  wallet_balance?: number
}

// ---------------------------------------------------------------------------
// SERVIZI
// ---------------------------------------------------------------------------

/**
 * Servizio aggiuntivo di spedizione (es. Contrassegno, Assicurazione, Senza Etichetta).
 * Usato sia in fase di configurazione (step 2) che in CartItem/Order.
 */
export interface Service {
  key: string
  label: string
  /** Costo del servizio in centesimi */
  price_cents: number
  active: boolean
  /** Dati specifici del servizio (es. importo contrassegno, valore assicurato) */
  data?: Record<string, unknown>
}

/**
 * Struttura servizi allegata a ogni collo (come salvato nel DB e nella sessione).
 * service_type: stringa CSV dei servizi attivi (es. "Contrassegno,Assicurazione").
 */
export interface PackageServices {
  service_type?: string
  date?: string
  time?: string
  /** Dati aggiuntivi per servizi specifici: contrassegno, assicurazione, ecc. */
  serviceData?: {
    contrassegno_amount?: number
    assicurazione_value?: number
    delivery_mode?: 'home' | 'pudo'
    pudo?: PudoPoint | null
    sms_email_notification?: boolean
    [key: string]: unknown
  }
  /** Notifiche SMS/email per aggiornamenti stato */
  sms_email_notification?: boolean
}

// ---------------------------------------------------------------------------
// PUNTO PUDO (Consegna presso punto BRT)
// ---------------------------------------------------------------------------

/**
 * Punto di ritiro/consegna BRT (PUDO = Pick Up Drop Off).
 * Selezionato durante lo step 2 del flusso spedizione.
 */
export interface PudoPoint {
  pudo_id: string
  name: string
  address: string
  city: string
  postal_code?: string
  province?: string
  country?: string
  latitude?: number
  longitude?: number
  opening_hours?: string
}

// ---------------------------------------------------------------------------
// COLLO / PACKAGE (unità base della spedizione)
// ---------------------------------------------------------------------------

/**
 * Singolo collo nel flusso preventivo (shipmentFlowStore.packages[]).
 * I prezzi sono in euro (float) durante il calcolo lato client,
 * poi convertiti in centesimi prima di inviare al backend.
 */
export interface Package {
  /** Identificatore univoco del collo (assegnato dal backend dopo salvataggio) */
  id?: number
  package_type: 'Pacco' | 'Pallet' | 'Valigia' | 'Busta' | string
  quantity: number
  weight: number | string
  /** Prima dimensione in cm */
  first_size: number | string
  /** Seconda dimensione in cm */
  second_size: number | string
  /** Terza dimensione in cm */
  third_size: number | string
  /** Prezzo da peso (euro, calcolato lato client) */
  weight_price?: number | null
  /** Prezzo da volume (euro, calcolato lato client) */
  volume_price?: number | null
  /**
   * Prezzo unitario del collo.
   * - In shipmentFlowStore / flusso nuovo: euro (es. 15.50).
   * - In CartItem da DB / edit: centesimi (es. 1550).
   * Usare isEditFromCart per distinguere.
   */
  single_price?: number
  content_description?: string
}

// ---------------------------------------------------------------------------
// SPEDIZIONE (ShipmentDetails — dati di partenza/arrivo del preventivo)
// ---------------------------------------------------------------------------

/**
 * Dettagli geografici della spedizione (step 1 — preventivo).
 * Corrisponde a shipmentFlowStore.shipmentDetails.
 */
export interface ShipmentDetails {
  origin_city: string
  origin_postal_code: string
  origin_country_code: string
  origin_country: string
  destination_city: string
  destination_postal_code: string
  destination_country_code: string
  destination_country: string
  /** Data di ritiro desiderata (ISO string o stringa vuota) */
  date: string
}

/**
 * Spedizione completa pronta per il riepilogo/checkout.
 * Corrisponde a shipmentFlowStore.pendingShipment.
 */
export interface PendingShipment {
  packages: Package[]
  origin_address: Partial<Address>
  destination_address: Partial<Address>
  services: PackageServices
  delivery_mode?: 'home' | 'pudo'
  selected_pudo?: PudoPoint | null
  sms_email_notification?: boolean
}

// ---------------------------------------------------------------------------
// PREVENTIVO / PRICE QUOTE
// ---------------------------------------------------------------------------

/**
 * Risposta del backend al calcolo preventivo (/api/session/first-step).
 * Tutti i prezzi sono in euro (float).
 */
export interface PriceQuote {
  /** Prezzo base (somma colli) in euro */
  base_price: number
  /** Totale servizi aggiuntivi in euro */
  services_total: number
  /** Totale finale in euro */
  total_price: number
  /** Identificativo step corrente */
  step: number
  shipment_details?: ShipmentDetails
  packages?: Package[]
}

// ---------------------------------------------------------------------------
// ELEMENTO CARRELLO (CartItem)
// ---------------------------------------------------------------------------

/**
 * Elemento del carrello come restituito da /api/cart o /api/guest-cart.
 * Tutti i prezzi sono in centesimi (single_price è intero in centesimi).
 */
export interface CartItem {
  id: number
  package_type: string
  quantity: number
  weight: number
  first_size: number
  second_size: number
  third_size: number
  /** Prezzo totale del collo in centesimi (include tutti i colli della quantità) */
  single_price: number
  weight_price?: number
  volume_price?: number
  content_description?: string
  origin_address: Address
  destination_address: Address
  services: PackageServices
  delivery_mode?: 'home' | 'pudo'
  selected_pudo?: PudoPoint | null
  sms_email_notification?: boolean
  created_at?: string
  updated_at?: string
}

/**
 * Risposta completa del carrello (data + meta).
 * cart.value da useCart().
 */
export interface CartResponse {
  data: CartItem[]
  meta: {
    /** Totale formattato (es. "45,90€" con non-breaking space) */
    total: string | number
    address_groups: AddressGroup[]
  }
}

/**
 * Gruppo di colli con stesso indirizzo (per UI raggruppamento nel carrello).
 */
export interface AddressGroup {
  package_ids: number[]
  count: number
  origin_city?: string
  destination_city?: string
}

// ---------------------------------------------------------------------------
// ORDINE
// ---------------------------------------------------------------------------

/**
 * Stato di un ordine (come arriva dal backend — stringa italiana).
 */
export type OrderStatus =
  | 'In attesa'
  | 'In lavorazione'
  | 'Etichetta generata'
  | 'Completato'
  | 'Fallito'
  | 'Pagato'
  | 'Annullato'
  | 'Rimborsato'
  | 'In transito'
  | 'In consegna'
  | 'Consegnato'
  | 'In giacenza'
  | 'Reso'
  | 'Rifiutato'

/**
 * Stato ordine raw (slug inglese, usato internamente).
 */
export type OrderStatusRaw =
  | 'pending'
  | 'processing'
  | 'label_generated'
  | 'completed'
  | 'payment_failed'
  | 'paid'
  | 'cancelled'
  | 'refunded'
  | 'in_transit'
  | 'out_for_delivery'
  | 'delivered'
  | 'in_giacenza'
  | 'returned'
  | 'refused'

/**
 * Metodo di pagamento.
 */
export type PaymentMethod = 'stripe' | 'wallet' | 'bonifico' | string

/**
 * Ordine completo come restituito da /api/orders/{id}.
 * I prezzi dei colli sono in centesimi; subtotal è stringa formattata.
 */
export interface Order {
  id: number
  user_id?: number
  /** Stato leggibile in italiano */
  status: OrderStatus
  /** Stato raw in inglese (per logica UI) */
  raw_status?: OrderStatusRaw
  /** Totale formattato (es. "45,90 EUR" o "45,90€") */
  subtotal?: string
  /** Totale in centesimi */
  subtotal_cents?: number
  payment_method?: PaymentMethod
  /** Stripe payment intent ID */
  stripe_payment_intent_id?: string
  /** L'ordine può essere annullato dall'utente */
  cancellable?: boolean
  packages: CartItem[]
  /** Dati fatturazione (se richiesta fattura) */
  billing?: BillingData | null
  /** Coupon applicato */
  coupon_code?: string | null
  /** Sconto coupon percentuale */
  coupon_discount?: number | null
  created_at?: string
  updated_at?: string
}

// ---------------------------------------------------------------------------
// FATTURAZIONE
// ---------------------------------------------------------------------------

/**
 * Dati per la fatturazione (checkout — sezione "Fattura").
 */
export interface BillingData {
  type: 'ricevuta' | 'fattura'
  subject_type?: 'privato' | 'azienda'
  nome_completo?: string
  ragione_sociale?: string
  p_iva?: string
  codice_fiscale?: string
  indirizzo?: string
  city?: string
  province?: string
  postal_code?: string
  pec?: string
  codice_sdi?: string
}

// ---------------------------------------------------------------------------
// LOCATION SEARCH — risultati API /api/locations/*
// ---------------------------------------------------------------------------

/**
 * Risultato di una ricerca località (backend /api/locations/*).
 */
export interface LocationSearchResult {
  place_name: string
  postal_code: string
  country_code?: string
  country_name?: string
  province_code?: string
  province_name?: string
  latitude?: number
  longitude?: number
}

// ---------------------------------------------------------------------------
// NOMINATIM — OpenStreetMap API (https://nominatim.openstreetmap.org)
// ---------------------------------------------------------------------------

/** Risposta search Nominatim (format=jsonv2). */
export interface NominatimSearchResult {
  lat: string
  lon: string
  display_name?: string
  place_id?: number
  osm_id?: number
  osm_type?: string
  class?: string
  type?: string
  importance?: number
}

/** Sezione address di una risposta reverse Nominatim. */
export interface NominatimAddress {
  road?: string
  pedestrian?: string
  path?: string
  house_number?: string
  city?: string
  town?: string
  village?: string
  municipality?: string
  postcode?: string
  country?: string
  country_code?: string
}

/** Risposta reverse Nominatim (format=jsonv2&addressdetails=1). */
export interface NominatimReverseResult {
  display_name?: string
  address?: NominatimAddress
}

// ---------------------------------------------------------------------------
// BRT PUDO — Punti di ritiro BRT (wrapper del nostro backend)
// ---------------------------------------------------------------------------

/** Punto PUDO normalizzato dal composable. */
export interface BrtPudoNormalized {
  pudo_id: string
  carrier_pudo_id: string
  ui_key: string
  provider: string
  name: string
  address: string
  city: string
  zip_code: string
  province: string
  country: string
  latitude: number | null
  longitude: number | null
  distance_meters: number | null
  enabled: boolean
  opening_hours: string | null
  localization_hint: string
}

/** Metadati restituiti dalle API di ricerca BRT. */
export interface BrtPudoMeta {
  strategy_used?: string[]
  returned_count?: number
  requested_count?: number
  provider?: string
  fallback?: boolean
}

/**
 * Risposta generica delle API BRT PUDO.
 * /api/brt/pudo/search, /api/brt/pudo/nearby, /api/brt/pudo/{id}.
 */
export interface BrtPudoResponse {
  success?: boolean
  error?: string
  pudo?: unknown[]
  data?: {
    pudo?: unknown[]
    meta?: BrtPudoMeta
  }
  meta?: BrtPudoMeta
}

// ---------------------------------------------------------------------------
// PINIA STORE — shipmentFlowStore
// ---------------------------------------------------------------------------

/**
 * Forma dello stato del shipmentFlowStore (stores/shipmentFlowStore.js).
 * Usato per riferimento e futuri refactor TypeScript.
 */
export interface ShipmentFlowStoreState {
  stepNumber: number
  isQuoteStarted: boolean
  shipmentDetails: ShipmentDetails
  packages: Package[]
  /** Prezzo totale in euro (somma di tutti i colli calcolata lato client) */
  totalPrice: number
  /** Chiavi dei servizi selezionati (es. ["contrassegno"]) */
  servicesArray: string[]
  contentDescription: string
  pendingShipment: PendingShipment | null
  originAddressData: Partial<Address> | null
  destinationAddressData: Partial<Address> | null
  pickupDate: string
  editingCartItemId: number | string | null
  deliveryMode: 'home' | 'pudo'
  selectedPudo: PudoPoint | null
  smsEmailNotification: boolean
  serviceData: Record<string, unknown>
}

// ---------------------------------------------------------------------------
// ADMIN — Pannello admin (tipi condivisi usati dai composable useAdmin*)
// ---------------------------------------------------------------------------

/** Messaggio di feedback mostrato dal pannello admin. */
export interface AdminActionMessage {
  type: 'success' | 'error'
  text: string
}

/** Configurazione UI per un singolo stato (colori Tailwind + icona Iconify). */
export interface AdminStatusConfigEntry {
  label: string
  bg: string
  text: string
  icon?: string
}

export type AdminStatusConfig = Record<string, AdminStatusConfigEntry>

/** Risposta paginata generica delle API admin (list ordini/utenti/spedizioni). */
export interface AdminPaginatedResponse<T = unknown> {
  data: T[]
  meta?: {
    current_page?: number
    last_page?: number
    per_page?: number
    total?: number
  }
}

/** Opzione di stato selezionabile nei filtri admin (UI <select>). */
export interface AdminStatusOption {
  value: string
  label: string
}

/** Dati payload per cambio ruolo utente dal pannello admin. */
export interface AdminRoleChangeData {
  user_id: number | string
  role: string
}

// ---------------------------------------------------------------------------
// AUTH — Form e stato auth (registrazione / login / password reset)
// ---------------------------------------------------------------------------

export interface AuthCredentials {
  email: string
  password: string
  remember?: boolean
}

export interface AuthRegisterForm extends AuthCredentials {
  name: string
  surname: string
  telephone_number?: string
  prefix?: string
  user_type?: 'privato' | 'azienda' | string
  referred_by?: string | null
}

export interface AuthPasswordChecks {
  length: boolean
  uppercase: boolean
  lowercase: boolean
  number: boolean
  special: boolean
}

export type AuthErrorDictionary = Record<string, string[] | string>

export interface AuthResendMessage {
  type: 'success' | 'error' | 'info'
  text: string
}

export interface AuthTabItem {
  key: string
  label: string
  to?: string
}

// ---------------------------------------------------------------------------
// PRICE BANDS — Tipi condivisi con i composable admin/pubblici sui listini
// ---------------------------------------------------------------------------

/** Singola banda di prezzo (peso o volume). */
export interface PriceBand {
  id: string
  type: 'weight' | 'volume'
  min_value: number
  max_value: number
  base_price: number
  discount_price: number | null
  show_discount: boolean
  sort_order: number
}

/** Riga di una "ladder" di incremento extra. */
export interface LadderRow {
  from_step: number
  to_step: number | null
  increment_cents: number
}

/** Regole extra oltre l'ultima banda (peso/volume). */
export interface ExtraRules {
  enabled: boolean
  weight_start: number
  weight_step: number
  volume_start: number
  volume_step: number
  increment_cents: number
  increment_mode: string
  weight_increment_ladder: LadderRow[]
  volume_increment_ladder: LadderRow[]
  base_price_cents_mode: 'last_band_effective' | 'manual' | string
  base_price_cents_manual: number | null
  weight_resolution: number
  volume_resolution: number
}

/** Regola supplemento CAP (origine/destinazione). */
export interface SupplementRule {
  id: string
  prefix: string
  amount_cents: number
  apply_to: 'origin' | 'destination' | 'both' | string
  enabled: boolean
}

/** Impostazioni promo sitewide. */
export interface PromoSettings {
  active: boolean
  label_text: string
  label_color: string
  label_image: string | null
  show_badges: boolean
  description: string
}

/** Tariffa Europa per paese specifico. */
export interface EuropeRate {
  country_code: string
  country_name: string
  price_cents: number | null
  quote_required: boolean
}

/** Banda peso per il pricing Europa. */
export interface EuropePricingBand {
  id: string
  label: string
  max_weight_kg: number
  max_volume_m3: number
  volumetric_factor: number
  rates: EuropePricingRate[]
}

/** Alias storico per EuropeRate (retrocompat). */
export type EuropePricingRate = EuropeRate

/** Configurazione completa Europa. */
export interface EuropePricing {
  enabled: boolean
  scope: string
  origin_country_code: string
  max_packages: number
  max_quantity_per_package: number
  supported_country_codes: string[]
  bands: EuropePricingBand[]
  version: string | null
}

/** Livello tariffa per regole a scaglioni di peso. */
export interface PricingRuleTier {
  up_to_kg: number | null
  price_cents: number
}

/**
 * Regola di pricing "keyed" (service_pricing, automatic_supplements, operational_fees).
 * Tutti i campi sono opzionali perché lo schema varia per chiave specifica.
 */
export interface PricingRule {
  label?: string
  description?: string
  pricing_type?: string
  enabled?: boolean
  application?: string
  note?: string
  price_cents?: number | null
  min_fee_cents?: number | null
  percentage_rate?: number | null
  threshold_amount_eur?: number | null
  max_weight_kg?: number | null
  threshold_cm?: number | null
  longest_side_threshold_cm?: number | null
  girth_threshold_cm?: number | null
  min_longest_side_cm?: number | null
  max_secondary_side_cm?: number | null
  province_codes?: string[]
  country_codes?: string[]
  keyword_list?: string[]
  flag_keys?: string[]
  delivery_modes?: string[]
  tiers?: PricingRuleTier[]
}

/** Gruppo di regole keyed (es. { contrassegno: PricingRule, assicurazione: PricingRule }). */
export type PricingRuleGroup = Record<string, PricingRule>
