# Database Migrations

> Schema database in **6 migration files** raggruppate per dominio funzionale.

## Struttura

| File | Tabelle | Dominio |
|---|---|---|
| `2026_05_01_000000_create_auth_tables.php` | users, password_reset_tokens, sessions, personal_access_tokens, audit_logs, cache, cache_locks, jobs, job_batches, failed_jobs | Auth + infrastruttura Laravel |
| `2026_05_01_000100_create_user_extensions_tables.php` | user_addresses, user_notification_preferences, user_notifications, push_subscriptions, cookie_consents, contact_messages, pro_requests, pro_api_keys | Estensioni utente |
| `2026_05_01_000200_create_catalog_tables.php` | locations, pudo_points, services, package_addresses, packages, articles | Catalogo + CMS |
| `2026_05_01_000300_create_orders_tables.php` | billing_addresses, cart_user, saved_shipments, orders, package_order, transactions, claims, claim_attachments, invoice_counters | Carrello + Ordini + Reclami |
| `2026_05_01_000400_create_payments_tables.php` | wallet_movements, withdrawal_requests, stripe_webhook_events, brt_webhook_events, invoice_archive | Pagamenti + idempotency webhook |
| `2026_05_01_000500_create_admin_tables.php` | coupons, coupon_user, referral_usages, price_bands, settings | Admin + commercial |

**Totale: 43 tabelle utente + 1 ledger Laravel `migrations` = 44 tabelle.**

## Perché 6 file e non 60+?

Ogni file rappresenta un **dominio funzionale completo** che può essere letto in ~15 minuti.
Un junior che apre `2026_05_01_000300_create_orders_tables.php` capisce subito tutto il dominio Ordini senza dover saltare tra 30 file di micro-modifiche.

Vantaggi vs. 60+ migration files atomiche:
- **Junior-friendly**: 1 file per dominio, leggibile in sequenza
- **Postgres parity**: Schema::create() è DB-agnostic (funziona SQLite + Postgres)
- **No schema:dump SQLite-specific**: niente `database/schema/sqlite-schema.sql` (era anti-pattern Postgres)

## Quando aggiungere una migration nuova

Per ogni cambio schema FUTURO:

```bash
php artisan make:migration add_xxx_to_yyy_table
# scrivi up()/down() usando Schema::table('yyy', ...)
php artisan migrate
```

**Quando hai 10+ migration incrementali su un dominio**, consolida nella migration di dominio
appropriata (es. `2026_05_01_000300_create_orders_tables.php` per cambi sulla tabella `orders`)
e cancella i file incrementali.

## Postgres parity

Le migration usano solo Laravel Schema builder (DB-agnostic). Per testare in locale:

```bash
docker compose up postgres -d
cp .env.local.postgres .env
php artisan migrate:fresh --seed
```

Schema identico a SQLite (verificato via `php artisan db:show`).

## Riferimenti

- Laravel docs: <https://laravel.com/docs/11.x/migrations>
- Per vedere DDL di una tabella: `php artisan db:show --table=users`
