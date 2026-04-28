# Services

Logica di business riusabile, **niente HTTP**. I controller orchestrano, i Service eseguono.

## Service critici (vedi `CLAUDE.md` § File critici)

- `StripePaymentService` — client Stripe + idempotency-key
- `OrderCreationService` — Carrello → Order con `pricing_snapshot`
- `WalletOrderPaymentService` / `WalletOrderLinkService` — lock DB su saldo wallet
- `OrderBrtTrackingLifecycleService` / `OrderBrtTrackingReadService` / `OrderBrtTrackingLookupService` — gestione stati BRT
- `Invoice/InvoicePdfGenerator` + `InvoicePdfService` — fatture XML/PDF SDI

## Convenzioni

- Naming: `<Risorsa><Azione>Service.php` (es. `OrderCreationService`).
- DI esplicita nel constructor (no facades dentro service).
- Tipo ritorno esplicito (`?Order`, `array`, `void`).
