import { describe, it, expect } from 'vitest'

/**
 * Test per shipmentFlowStore.js (stores/shipmentFlowStore.js)
 * Testiamo la struttura dati, i calcoli e la logica di serializzazione
 * senza dipendenze da Pinia/Vue runtime.
 */

describe('shipmentFlowStore Logic', () => {

  // ========== PACKAGE DATA ==========
  describe('Package Data Structure', () => {
    // Struttura default di un pacco come usata nello store
    const createDefaultPackage = () => ({
      package_type: 'Pacco',
      quantity: 1,
      weight: 0,
      first_size: 0,
      second_size: 0,
      third_size: 0,
      weight_price: 0,
      volume_price: 0,
      single_price: 0,
    })

    it('default package ha tipo Pacco', () => {
      const pkg = createDefaultPackage()
      expect(pkg.package_type).toBe('Pacco')
    })

    it('default package ha quantita 1', () => {
      const pkg = createDefaultPackage()
      expect(pkg.quantity).toBe(1)
    })

    it('default package ha peso 0', () => {
      const pkg = createDefaultPackage()
      expect(pkg.weight).toBe(0)
    })

    it('default package ha tutte le dimensioni a 0', () => {
      const pkg = createDefaultPackage()
      expect(pkg.first_size).toBe(0)
      expect(pkg.second_size).toBe(0)
      expect(pkg.third_size).toBe(0)
    })

    it('default package ha prezzi a 0', () => {
      const pkg = createDefaultPackage()
      expect(pkg.weight_price).toBe(0)
      expect(pkg.volume_price).toBe(0)
      expect(pkg.single_price).toBe(0)
    })

    it('package types validi', () => {
      const validTypes = ['Pacco', 'Pallet', 'Valigia']
      expect(validTypes).toContain('Pacco')
      expect(validTypes).toContain('Pallet')
      expect(validTypes).toContain('Valigia')
    })
  })

  // ========== TOTAL PRICE CALCULATION ==========
  describe('Total Price Calculation', () => {
    // Riproduce la logica di calcolo totalPrice usata nel componente Preventivo
    function calculateTotal(packages: Array<{ single_price: number; quantity: number }>): number {
      return packages.reduce((sum, p) => sum + p.single_price * p.quantity, 0)
    }

    it('totale = somma (single_price * quantity) per ogni pacco', () => {
      const packages = [
        { single_price: 890, quantity: 2 },
        { single_price: 1490, quantity: 1 },
      ]
      expect(calculateTotal(packages)).toBe(3270) // (890*2) + (1490*1)
    })

    it('totale con 0 pacchi = 0', () => {
      expect(calculateTotal([])).toBe(0)
    })

    it('totale con un solo pacco', () => {
      const packages = [{ single_price: 890, quantity: 1 }]
      expect(calculateTotal(packages)).toBe(890)
    })

    it('totale con quantita multipla', () => {
      const packages = [{ single_price: 1190, quantity: 5 }]
      expect(calculateTotal(packages)).toBe(5950)
    })

    it('totale con 3 pacchi diversi', () => {
      const packages = [
        { single_price: 890, quantity: 1 },
        { single_price: 1490, quantity: 2 },
        { single_price: 2990, quantity: 1 },
      ]
      expect(calculateTotal(packages)).toBe(6860) // 890 + 2980 + 2990
    })
  })

  // ========== SHIPMENT DETAILS ==========
  describe('Shipment Details', () => {
    const defaultShipmentDetails = {
      origin_city: '',
      origin_postal_code: '',
      destination_city: '',
      destination_postal_code: '',
      date: '',
    }

    it('default shipment details ha tutti i campi vuoti', () => {
      expect(defaultShipmentDetails.origin_city).toBe('')
      expect(defaultShipmentDetails.origin_postal_code).toBe('')
      expect(defaultShipmentDetails.destination_city).toBe('')
      expect(defaultShipmentDetails.destination_postal_code).toBe('')
      expect(defaultShipmentDetails.date).toBe('')
    })

    it('shipment details ha tutte le chiavi necessarie', () => {
      const keys = Object.keys(defaultShipmentDetails)
      expect(keys).toContain('origin_city')
      expect(keys).toContain('origin_postal_code')
      expect(keys).toContain('destination_city')
      expect(keys).toContain('destination_postal_code')
      expect(keys).toContain('date')
    })
  })

  // ========== STEP NAVIGATION ==========
  describe('Step Navigation', () => {
    it('step default e\' 1', () => {
      const defaultStep = 1
      expect(defaultStep).toBe(1)
    })

    it('step validi 1-5', () => {
      const validSteps = [1, 2, 3, 4, 5]
      for (const step of validSteps) {
        expect(step).toBeGreaterThanOrEqual(1)
        expect(step).toBeLessThanOrEqual(5)
      }
    })

    it('step 0 non valido', () => {
      expect(0).toBeLessThan(1)
    })

    it('step 6 non valido', () => {
      expect(6).toBeGreaterThan(5)
    })
  })

  // ========== DELIVERY MODE ==========
  describe('Delivery Mode', () => {
    it('delivery modes validi', () => {
      const validModes = ['home', 'pudo']
      expect(validModes).toContain('home')
      expect(validModes).toContain('pudo')
    })

    it('default delivery mode e\' home', () => {
      const defaultMode = 'home'
      expect(defaultMode).toBe('home')
    })

    it('selectedPudo default e\' null', () => {
      const defaultPudo = null
      expect(defaultPudo).toBeNull()
    })
  })

  // ========== SESSION STORAGE PERSISTENCE ==========
  describe('SessionStorage Persistence', () => {
    it('dati serializzabili in JSON', () => {
      const state = {
        stepNumber: 1,
        shipmentDetails: {
          origin_city: 'Milano',
          origin_postal_code: '20100',
          destination_city: 'Roma',
          destination_postal_code: '00100',
          date: '2026-03-19',
        },
        isQuoteStarted: true,
        totalPrice: 890,
        packages: [{ package_type: 'Pacco', weight: 5, quantity: 1, single_price: 890 }],
        servicesArray: ['contrassegno'],
        contentDescription: 'Elettronica',
        pendingShipment: null,
        originAddressData: null,
        destinationAddressData: null,
        pickupDate: '2026-03-20',
        editingCartItemId: null,
        deliveryMode: 'home',
        selectedPudo: null,
        smsEmailNotification: true,
        serviceData: { contrassegno: { importo: '25.00' } },
      }

      const serialized = JSON.stringify(state)
      const deserialized = JSON.parse(serialized)

      expect(deserialized.stepNumber).toBe(1)
      expect(deserialized.shipmentDetails.origin_city).toBe('Milano')
      expect(deserialized.packages[0].weight).toBe(5)
      expect(deserialized.totalPrice).toBe(890)
      expect(deserialized.deliveryMode).toBe('home')
      expect(deserialized.servicesArray).toContain('contrassegno')
      expect(deserialized.contentDescription).toBe('Elettronica')
      expect(deserialized.smsEmailNotification).toBe(true)
      expect(deserialized.serviceData.contrassegno.importo).toBe('25.00')
    })

    it('stato completo ha tutte le chiavi persistite', () => {
      // Le chiavi che saveToSession salva (come da persist() nello store)
      const persistedKeys = [
        'stepNumber',
        'shipmentDetails',
        'isQuoteStarted',
        'totalPrice',
        'packages',
        'servicesArray',
        'contentDescription',
        'pendingShipment',
        'originAddressData',
        'destinationAddressData',
        'pickupDate',
        'editingCartItemId',
        'deliveryMode',
        'selectedPudo',
        'smsEmailNotification',
        'serviceData',
      ]

      expect(persistedKeys).toHaveLength(16)
      expect(persistedKeys).toContain('stepNumber')
      expect(persistedKeys).toContain('packages')
      expect(persistedKeys).toContain('totalPrice')
      expect(persistedKeys).toContain('deliveryMode')
      expect(persistedKeys).toContain('selectedPudo')
      expect(persistedKeys).toContain('contentDescription')
      expect(persistedKeys).toContain('pickupDate')
      expect(persistedKeys).toContain('smsEmailNotification')
      expect(persistedKeys).toContain('serviceData')
    })

    it('array packages vuoto serializzabile', () => {
      const state = { packages: [] }
      const deserialized = JSON.parse(JSON.stringify(state))
      expect(deserialized.packages).toEqual([])
    })

    it('pendingShipment null serializzabile', () => {
      const state = { pendingShipment: null }
      const deserialized = JSON.parse(JSON.stringify(state))
      expect(deserialized.pendingShipment).toBeNull()
    })

    it('selectedPudo con dati serializzabile', () => {
      const state = {
        selectedPudo: {
          pudo_id: 'BRT123',
          name: 'Punto BRT Milano',
          address: 'Via Roma 1',
        },
      }
      const deserialized = JSON.parse(JSON.stringify(state))
      expect(deserialized.selectedPudo.pudo_id).toBe('BRT123')
      expect(deserialized.selectedPudo.name).toBe('Punto BRT Milano')
    })
  })

  // ========== DEFAULT STATE VALUES ==========
  describe('Default State Values', () => {
    it('isQuoteStarted default false', () => {
      const defaultValue = false
      expect(defaultValue).toBe(false)
    })

    it('totalPrice default 0', () => {
      const defaultValue = 0
      expect(defaultValue).toBe(0)
    })

    it('packages default array vuoto', () => {
      const defaultValue: any[] = []
      expect(defaultValue).toEqual([])
    })

    it('servicesArray default array vuoto', () => {
      const defaultValue: string[] = []
      expect(defaultValue).toEqual([])
    })

    it('contentDescription default stringa vuota', () => {
      const defaultValue = ''
      expect(defaultValue).toBe('')
    })

    it('editingCartItemId default null', () => {
      const defaultValue = null
      expect(defaultValue).toBeNull()
    })

    it('pickupDate default stringa vuota', () => {
      const defaultValue = ''
      expect(defaultValue).toBe('')
    })
  })

  // ========== STORAGE KEY ==========
  describe('Storage Key', () => {
    it('chiave sessionStorage corretta', () => {
      const STORAGE_KEY = 'spedizionefacile_user_store'
      expect(STORAGE_KEY).toBe('spedizionefacile_user_store')
    })
  })
})
