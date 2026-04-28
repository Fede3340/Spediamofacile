# Routes API Map

Questa cartella divide le API backend per dominio.

Usala come mappa rapida:

- `auth.php` -> login, registrazione, sessione, profilo auth
- `public.php` -> quote, location, tracking, contenuti pubblici
- `cart.php` -> carrello, colli, merge guest/auth
- `orders.php` -> ordini cliente, dettaglio ordine, post-pagamento
- `payments.php` -> Stripe, wallet, payment completion
- `shipment.php` -> fulfillment, BRT, documenti, tracking operativo
- `admin.php` -> dashboard admin, ordini, spedizioni, utenti, configurazione
- `community.php` -> referral, Partner Pro, richieste community
- `claims.php` -> area reclami legacy o non core launch
- `invoices.php` -> fatture e documenti fiscali

Regola pratica:

- route module = confine HTTP per dominio
- controller = entrypoint del modulo
- service = owner del business

Per capire chi possiede davvero il business dietro queste route:

- [../../README.md](../../README.md)
- [../../../docs/BACKEND_STRUCTURE.md](../../../docs/BACKEND_STRUCTURE.md)
- [../../../docs/API_CONTRACT.md](../../../docs/API_CONTRACT.md)
