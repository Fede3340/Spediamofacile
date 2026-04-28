import { describe, it, expect } from 'vitest'
import {
  deriveShipmentFlowState,
  resolveShipmentFlowState,
  pickMostAdvancedShipmentFlowState,
  getShipmentFlowStage,
  canAccessShipmentFlowRoute,
  SHIPMENT_FLOW_ROUTES,
} from '~/utils/shipment'

const makeQuoteData = () => ({
  shipment_details: { origin_city: 'Milano', destination_city: 'Roma' },
  packages: [{
    package_type: 'Pacco',
    weight: 5,
    quantity: 1,
    first_size: 20,
    second_size: 20,
    third_size: 20,
  }],
})

const makeServicesData = () => ({
  ...makeQuoteData(),
  content_description: 'Libri',
  services: { date: '2026-04-10' },
})

const makeAddressesData = () => ({
  ...makeServicesData(),
  origin_address: {
    name: 'Mario Rossi',
    address: 'Via Roma 1',
    city: 'Milano',
    postal_code: '20100',
  },
  destination_address: {
    name: 'Luigi Verdi',
    address: 'Via Napoli 2',
    city: 'Roma',
    postal_code: '00100',
  },
})

describe('deriveShipmentFlowState', () => {
  it('dati vuoti → nessuno stato ready', () => {
    const state = deriveShipmentFlowState({})
    expect(state.quote_ready).toBe(false)
    expect(state.services_ready).toBe(false)
    expect(state.addresses_ready).toBe(false)
    expect(state.summary_ready).toBe(false)
    expect(state.last_valid_route).toBe(SHIPMENT_FLOW_ROUTES.packages)
  })

  it('preventivo completo → quote_ready', () => {
    const state = deriveShipmentFlowState(makeQuoteData())
    expect(state.quote_ready).toBe(true)
    expect(state.services_ready).toBe(false)
    expect(state.last_valid_route).toBe(SHIPMENT_FLOW_ROUTES.services)
  })

  it('servizi completi → services_ready', () => {
    const state = deriveShipmentFlowState(makeServicesData())
    expect(state.services_ready).toBe(true)
    expect(state.addresses_ready).toBe(false)
    expect(state.last_valid_route).toBe(SHIPMENT_FLOW_ROUTES.addresses)
  })

  it('indirizzi completi → summary_ready', () => {
    const state = deriveShipmentFlowState(makeAddressesData())
    expect(state.addresses_ready).toBe(true)
    expect(state.summary_ready).toBe(true)
    expect(state.last_valid_route).toBe(SHIPMENT_FLOW_ROUTES.summary)
  })

  it('indirizzo incompleto (manca city) → addresses_ready false', () => {
    const data = makeAddressesData()
    data.destination_address.city = ''
    const state = deriveShipmentFlowState(data)
    expect(state.addresses_ready).toBe(false)
    expect(state.summary_ready).toBe(false)
  })
})

describe('resolveShipmentFlowState', () => {
  it('senza flow_state esplicito usa derivazione', () => {
    const data = makeQuoteData()
    const state = resolveShipmentFlowState(data)
    expect(state.quote_ready).toBe(true)
  })

  it('con flow_state esplicito lo usa', () => {
    const data = {
      ...makeQuoteData(),
      flow_state: {
        quote_ready: true,
        services_ready: true,
        addresses_ready: false,
        summary_ready: false,
        last_valid_route: '/la-tua-spedizione/2?step=ritiro',
      },
    }
    const state = resolveShipmentFlowState(data)
    expect(state.services_ready).toBe(true)
    expect(state.addresses_ready).toBe(false)
  })
})

describe('pickMostAdvancedShipmentFlowState', () => {
  it('sceglie lo stato piu avanzato', () => {
    const quoteState = deriveShipmentFlowState(makeQuoteData())
    const addressState = deriveShipmentFlowState(makeAddressesData())
    const result = pickMostAdvancedShipmentFlowState(quoteState, addressState)
    expect(result.summary_ready).toBe(true)
  })

  it('gestisce null/undefined', () => {
    const quoteState = deriveShipmentFlowState(makeQuoteData())
    const result = pickMostAdvancedShipmentFlowState(null, undefined, quoteState)
    expect(result.quote_ready).toBe(true)
  })
})

describe('getShipmentFlowStage', () => {
  it('riconosce la route preventivo', () => {
    expect(getShipmentFlowStage({ path: '/preventivo', query: {} })).toBe('quote')
  })

  it('riconosce la route servizi', () => {
    expect(getShipmentFlowStage({ path: '/la-tua-spedizione/2', query: {} })).toBe('services')
  })

  it('riconosce la route indirizzi (step=ritiro)', () => {
    expect(getShipmentFlowStage({ path: '/la-tua-spedizione/2', query: { step: 'ritiro' } })).toBe('addresses')
  })

  it('trampolino /riepilogo viene mappato sul payment step', () => {
    expect(getShipmentFlowStage({ path: '/riepilogo', query: {} })).toBe('payment')
  })

  it('alias storico step=conferma viene mappato su payment', () => {
    expect(getShipmentFlowStage({ path: '/la-tua-spedizione/2', query: { step: 'conferma' } })).toBe('payment')
  })

  it('riconosce la route pagamento', () => {
    expect(getShipmentFlowStage({ path: '/la-tua-spedizione/2', query: { step: 'pagamento' } })).toBe('payment')
  })
})

describe('canAccessShipmentFlowRoute', () => {
  it('chiunque puo accedere a /preventivo', () => {
    const emptyState = deriveShipmentFlowState({})
    expect(canAccessShipmentFlowRoute({ path: '/preventivo', query: {} }, emptyState)).toBe(true)
  })

  it('senza quote_ready non puo accedere a servizi', () => {
    const emptyState = deriveShipmentFlowState({})
    expect(canAccessShipmentFlowRoute({ path: '/la-tua-spedizione/2', query: {} }, emptyState)).toBe(false)
  })

  it('con quote_ready puo accedere a servizi', () => {
    const quoteState = deriveShipmentFlowState(makeQuoteData())
    expect(canAccessShipmentFlowRoute({ path: '/la-tua-spedizione/2', query: {} }, quoteState)).toBe(true)
  })

  it('con addresses_ready puo accedere al pagamento (ex summary)', () => {
    const fullState = deriveShipmentFlowState(makeAddressesData())
    expect(canAccessShipmentFlowRoute({ path: '/la-tua-spedizione/2', query: { step: 'pagamento' } }, fullState)).toBe(true)
  })
})
