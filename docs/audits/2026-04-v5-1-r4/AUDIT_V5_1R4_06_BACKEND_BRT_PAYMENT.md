# Audit V5.1R4 - Backend, BRT e payment

Data: 2026-04-29

## Giudizio

Il backend Laravel e piu solido del frontend sul piano sintattico. `php -l` sui file PHP reali non ha rilevato errori e `composer validate` e valido.

Il backend ha pero alcuni rischi di boundary business e alcune incoerenze operative.

## Punti forti

- Route API modulari.
- Stripe webhook con firma e idempotenza.
- Wallet e referral hanno servizi dedicati.
- Order/payment/BRT hanno test presenti.
- Schema dump documentato in `database/migrations/README.md`.

## Rischi critici

### BRT webhook fail-open

File: `app/Http/Controllers/Shipping/BrtWebhookController.php`

Problema: se in produzione mancano secret/IP allowlist, il webhook viene accettato.

Rischio:

- tracking falsificabile
- stato spedizione alterabile
- boundary esterno non affidabile

Fix:

- fail-closed in produzione
- log critical
- documentare env obbligatori

### BRT manual/admin authorization

File indicativi:

- `app/Http/Controllers/Shipping/BrtController.php`
- `app/Policies/OrderPolicy.php`

Problema da verificare: endpoint manuale/admin usa policy che potrebbe includere owner ordine. Serve decidere se alcune azioni BRT devono essere solo admin.

Fix:

- separare policy `manageShipment` owner-safe da `manageBrtExecution` admin-only
- testare con utente cliente e admin

### Refund con side effect esterni

Rischio: chiamate Stripe/BRT dentro transazioni DB possono creare casi di parziale successo.

Fix:

- valutare outbox/saga o riconciliazione
- idempotency key stabili
- audit log per retry

### Schema dump

File: `database/migrations/README.md`

Schema dump e valido come scelta tecnica Laravel, ma e rischioso se non spiegato nel quickstart vero.

Fix:

- documentare chiaramente `migrate:fresh`
- aggiungere regola per nuove migration
- test fresh install

## Payment scope

Osservato:

- card/Stripe presente
- bonifico presente
- wallet presente
- PayPal citato in alcuni punti, ma FAQ dice che non e gestito

Decisione necessaria:

- PayPal launch: implementare flusso completo
- PayPal post-launch: rimuovere o marcare riferimenti come legacy

## Priorita backend

1. BRT webhook fail-closed.
2. Confermare autorizzazioni BRT admin/cliente.
3. Riallineare PayPal scope.
4. Rendere CI backend reale.
5. Solo dopo refactor di controller grandi.

