# Composables — INDEX

> Mappa rapida dei composable. Auto-imported da Nuxt 4 (no need di `import`).

## Auth & Sessione

- `useAuth` — login/logout/registrazione + utente corrente (Sanctum SPA). Usato da Navbar, modal login, guardie pagina.
- `useAuthOverlay` — orchestrazione modal di login/registrazione (apertura, validazione, switch login↔register).
- `useSession` — wrapper su `useSanctumClient` per leggere sessione preventivo persistita (fallback SSR-safe).

## Funnel preventivo (step 1: colli + indirizzi alto livello)

- `useFunnelValidation` — validazione cross-field step 1 (colli, dimensioni, paesi).
- `useFunnelAnalytics` — tracking eventi Plausible (auth, payment, funnel step). No-op se tracker offline.
- `useQuote` — orchestratore preventivo: pacchi + locazioni + pricing + persist sessione.
- `useQuoteForm` — gestione form pacchi (add/remove/edit).
- `useQuotePricing` — calcolo prezzo runtime (peso/volume/CAP supplement).
- `useQuoteResults` — riepilogo prezzi + suggerimenti UI.
- `useQuickQuoteLocations` — autocomplete localita' nel widget preventivo veloce home.
- `useQuickQuotePackages` — gestione colli nel widget preventivo veloce home.
- `useLocationSearch` — wrapper API `/api/locations/{search,by-cap,by-city}` (autocomplete + dedup + relevance).

## Funnel spedizione (step 2: servizi + indirizzi dettaglio)

- `useShipmentFormValidation` — validazione form completo (errori, focus management, summary).
- `useShipmentLocationAutocomplete` — autocomplete CAP/citta'/provincia per sezione mittente/destinatario.
- `useShipmentStepFlow` — stato globale step (current step, navigazione avanti/indietro).
- `useShipmentStepPageOrchestration` — orchestratore page level: sessione + auth + tracking + flow.
- `useShipmentStepPageState` — stato locale step page (loading, error, dirty).
- `useShipmentStepValidation` — composizione: form validation + location autocomplete + smart validator.
- `useShipmentStepAddresses` — sezione indirizzi (mittente/destinatario/PUDO).
- `useShipmentStepServices` — sezione servizi accessori (express, fragile, COD, assicurazione).
- `useShipmentStepServiceCards` — UI card per servizi.
- `useShipmentStepSummary` — riepilogo step prima del checkout.
- `useShipmentStepCartEdit` — edit pacchi nel carrello.
- `useShipmentStepSubmit` — submit form step → API.
- `useShipmentStepPaymentEntry` — entry-point pagamento step.
- `useShipmentVentaglioActions` — azioni "ventaglio" (apri/chiudi pannelli).
- `useShipmentExistingOrderSummary` — summary ordine esistente quando si paga separatamente.
- `useShipmentPaymentEvents` — eventi pagamento (succeded/failed/required_action).
- `useShipmentPaymentSummaryView` — view model summary pagamento.

## Carrello & Checkout

- `useCart` — orchestratore carrello (count, items, totale, add/remove/update).
- `useCarrello` — alias italiano usato in pagine (delega a `useCart`).
- `useCartFetch` — fetch carrello da API.
- `useCartPromoPreview` — preview applicazione coupon/promo.
- `useCheckoutBilling` — gestione dati fatturazione (privato/azienda, P.IVA, SDI, PEC).
- `useCheckoutOrderContext` — contesto ordine in checkout (payment_intent, idempotency).
- `useCheckoutPromoPreview` — preview promo/coupon nel form checkout.

## Pagamenti

- `usePayment` — orchestratore pagamenti (Stripe, wallet, bonifico) + dispatching dei sub-composable.
- `composables/payment/*` — sub-composable per ogni metodo (vedi quella cartella).
- `useWalletTopUp` — ricarica portafoglio virtuale.

## Ordini

- `useOrderDetail` — dettaglio singolo ordine (timeline, transactions, packages).
- `useOrdersList` — lista ordini account (paginata, filtrata).
- `useTrackingDetail` — pagina tracking pubblico /traccia.

## Account

- `useAccountDashboard` — KPI dashboard utente (spedizioni, saldi, ordini recenti).

## Admin

- `useAdmin` — guard ruolo admin.
- `useAdminPricing` — orchestratore pricing admin (delega a 3 sub).
- `useAdminPricingForm` — form per band prezzi (peso/volume).
- `useAdminPricingImport` — import CSV bande prezzi.
- `useAdminPricingList` — listing band prezzi attive.
- `useAdminSpedizioni` — admin pannello spedizioni.
- `useAdminUtenti` — admin pannello utenti.

## PUDO

- `usePudo` — orchestratore PUDO (lookup, mappa, selezione).
- `usePudoMap` — gestione mappa Leaflet (markers, eventi).
- `usePudoSearchApi` — wrapper API `/api/brt/pudo/*`.

## SEO & UI

- `useCanonical` — gestione `<link rel="canonical">` per route.
- `useBreadcrumbSchema` — JSON-LD breadcrumb (schema.org).
- `useSiteSchema` — JSON-LD organization/site (schema.org).
- `useContenutoHeader` — header sezione "Contenuto" (titoli pagina).
- `useShellRouteState` — stato route layout (transitions, loading).
- `useUiFeedback` — toast/alert globali.

## Form helpers

- `useAddressFormField` — composable singolo campo indirizzo (validation + format).
- `useSmartValidation` — smart validator core (errori, touched, validate).
- `usePriceBands` — caricamento band prezzi pubbliche.

## Sicurezza

- `useTurnstile` — Cloudflare Turnstile (CAPTCHA) widget.

---

## Linee guida composables

- **Nuovo composable solo se usato in 2+ posti** (singolo chiamante: inline).
- **Naming**: `useXxx` per composable Vue, `xxx` per utils puri (in `utils/`).
- **Auto-import**: tutti i composable in `composables/` sono auto-importati da Nuxt.
- **TypeScript**: usare tipi espliciti per gli args, ritorni inferiti dal compiler.
- **No global state**: usare Pinia store quando lo stato deve essere condiviso tra rotte.
- **SSR-safe**: ogni composable deve funzionare anche server-side (no `window`/`document` in setup).
