# SpediamoFacile Backend (Laravel)

Questa cartella contiene il backend Laravel vivo di SpediamoFacile.

Qui vivono i boundary core di dominio:

- creazione ordine
- pagamento e finalizzazione ordine
- wallet
- coupon/referral
- integrazione BRT
- tracking, documenti e spedizioni
- account/admin API

## Da dove partire

Questo file e' una landing locale, non la source of truth completa.

I tre riferimenti canonici da aprire per primi sono:

- [../docs/QUICKSTART.md](../docs/QUICKSTART.md)
- [../docs/BACKEND_STRUCTURE.md](../docs/BACKEND_STRUCTURE.md)
- [../docs/API_CONTRACT.md](../docs/API_CONTRACT.md)

Per una mappa rapida delle route modulari backend:

- [routes/api/README.md](routes/api/README.md)

## Setup rapido

```bash
cd apps/api
composer install
php artisan migrate
php artisan serve --port=8000
```

## Test

```bash
php artisan test
```

## Regole importanti

- controller sottili, service chiari
- nessuna nuova logica business sparsa senza ownership esplicita
- il backend deve leggere l'ordine canonico persistito
- se una feature tocca denaro, ordine o BRT, deve avere test dedicati

La documentazione vera vive in `../docs/`: questo file orienta, non duplica.
