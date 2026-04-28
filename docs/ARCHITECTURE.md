# Architecture — SpediamoFacile

Panoramica ad alto livello del sistema: tech stack, flussi dati, autenticazione, webhook, queue e observability.

> Target: dev nuovo che vuole capire come si tiene in piedi tutto in 20 minuti.

## Stack tecnologico

**Frontend** (`apps/web/`)
- Nuxt 4.1 (Vue 3.5) SPA + SSR ibrido
- Pinia 3 — store unico attivo `shipmentFlowStore` (state funnel preventivo). Altri composables di stato (`useAuth`, `useCart`, `usePayment`, `useFunnel`, ecc.) sono in fase di migrazione progressiva a store Pinia dedicati.
- Nuxt UI 4 (componenti), Tailwind CSS 4, CSS route-specific in `assets/css/`
- `nuxt-auth-sanctum` per cookie-based auth
- Plugin `20.auth-401-handler.client.js` riapre l'overlay login automaticamente su 401/419

**Backend** (`apps/api/`)
- Laravel 11 (PHP 8.3+)
- Sanctum 4 (SPA cookie-auth + API tokens)
- PostgreSQL 15 (Eloquent ORM)
- Redis 7 (cache, queue, session)
- Stripe SDK PHP 18, moneyphp/money per prezzi
- Queue worker standard (`php artisan queue:work --queue=default,emails,webhooks`). Horizon **NON e' installato** (composer.json non lo include): per dashboard monitoring code in produzione si valuta `composer require laravel/horizon` come item di backlog post-MVP.
- Sentry PHP SDK supportato via `class_exists` guard in `AppServiceProvider` (DSN da configurare prima del go-live)

**Infra**
- Caddy (reverse proxy + HTTPS automatico)
- Render.com (hosting prod: web + worker + cron + Redis + Postgres)

## Flusso utente — Diagramma ASCII

```
 +--------+                +-------+                +---------+                +------------+
 |        | --- HTTPS ---> |       | ---- HTTP ---> |         | --- SQL/TCP -> |            |
 | Client |   richiesta    | Caddy |   reverse      | Laravel |   query        | PostgreSQL |
 |  (Nuxt |                | proxy |   proxy        |   API   |                |            |
 |   SPA) | <--- JSON ---- |       | <------------- | (Sanctum|                +------------+
 |        |                +-------+   cookie SPA   |  guard) |                
 +--------+                                         |         | --- commands->  +----------+
                                                    |         |                 |          |
                                                    |         | <-- events ---- |  Redis   |
                                                    |         |                 | (queue + |
                                                    |         |                 |  cache)  |
                                                    +---------+                 +----------+
                                                         |
                                                         v
                                            +-----------------------+
                                            |   API esterne         |
                                            |  - Stripe (pagamenti) |
                                            |  - BRT REST (PUDO,    |
                                            |     spedizioni)       |
                                            |  - Postmark / SES     |
                                            |    (email)            |
                                            |  - OAuth Google/FB/   |
                                            |    Apple              |
                                            +-----------------------+
```

## Auth flow — Sanctum SPA cookie

1. Browser visita `http://localhost:8787` (Nuxt).
2. Plugin `10.bootstrap.client.js` esegue `GET /sanctum/csrf-cookie` -> riceve cookie `XSRF-TOKEN` + `laravel_session`.
3. Form login `POST /api/custom-login` con email/password e header `X-XSRF-TOKEN`.
4. Laravel valida, avvia sessione, setta cookie `spediamofacile_session` (SameSite=Lax, HttpOnly, Secure in prod).
5. Richieste successive protette (`middleware('auth:sanctum')`) leggono il cookie -> `$request->user()`.
6. Il composable `useSanctumAuth().user` chiama `GET /api/user` al boot -> popola stato auth. Il cookie UI `sf_auth_ui` (SSR-friendly snapshot) è gestito da `composables/useAuth.js` + `plugins/00.auth-ui-seed.js`.
7. Logout: `POST /api/logout` -> revoca token, invalida sessione, pulisce cookie UI.

OAuth social (solo Google attivo, Facebook/Apple archiviati 2026-04): redirect -> provider callback -> `GET /api/auth/google/callback` -> crea/aggiorna user -> stessa sessione Sanctum.

## Flusso preventivo -> pagamento -> BRT

```
 [Home] --(calcolo prezzo)--> [Preventivo step 1]
   |                                 |
   |                                 v
   |                          POST /api/session/first-step
   |                          (peso, dimensioni, origine, dest)
   |                                 |
   |                                 v
   |                          [Step 2: Servizi extra]
   |                          POST /api/session/second-step
   |                                 |
   |                                 v
   |                          [Step 3: Indirizzi + PUDO]
   |                          GET /api/brt/pudo/search
   |                                 |
   |                                 v
   |                          [Step 4: Riepilogo + carrello]
   |                          POST /api/cart (persist)
   |                                 |
   |                                 v
   |                          [Step 5: Checkout]
   |                          POST /api/stripe/create-payment-intent
   |                                 |
   |                                 v
   |                          Stripe Elements -> confirmPayment
   |                                 |
   |                                 v
   |                          Webhook: payment_intent.succeeded
   |                                 |
   |                                 v
   |                          OrderCreationService -> crea Order in DB
   |                                 |
   |                                 v
   |                          Dispatch Job: CreateBrtShipmentJob
   |                                 |
   |                                 v
   |                          BrtService -> POST api.brt.it/shipment
   |                                 |
   |                                 v
   |                          Order.brt_tracking_number salvato
   |                          Email conferma via Postmark
```

Tutti i prezzi sono in centesimi (MyMoney/moneyphp). `formatPrice` divide per 100 lato Nuxt.

## Webhook flow

### Stripe
- Endpoint: `POST /api/stripe/webhook`
- Firma: header `Stripe-Signature`, verificata con `STRIPE_WEBHOOK_SECRET`.
- Eventi gestiti:
  - `payment_intent.succeeded` -> finalizza ordine, crea spedizione BRT
  - `payment_intent.payment_failed` -> notifica utente
  - `charge.refunded` -> aggiorna `Order.refund_status`
- Idempotenza: tabella `stripe_webhook_events` salva `event.id`; duplicati rifiutati.

### BRT
- Endpoint: `POST /webhooks/brt/tracking` (rotta in `routes/web.php`, NO prefix `/api`)
- Firma: HMAC SHA256 con `BRT_WEBHOOK_SECRET`.
- Eventi:
  - tracking updates -> salvati in `brt_webhook_events` + push notifica user
- Stesso pattern idempotenza.

## Queue + cron

**Queue (Redis)**:
- `default` — job generici (retry 3x, backoff esponenziale)
- `emails` — Postmark dispatch async
- `webhooks` — retry webhook falliti (BRT timeout, Stripe)

Avvio worker locale: `php artisan queue:work --queue=webhooks,emails,default`

**Horizon**: NON installato. Quando si vorra' una dashboard di monitoring code, eseguire `composer require laravel/horizon && php artisan horizon:install` e proteggere la rotta `/horizon` con `auth:sanctum + CheckAdmin` (vedi `app/Providers/HorizonServiceProvider.php` da creare). Per ora il monitoring code passa da Sentry breadcrumbs + Render logs.

**Cron (scheduler)**: `php artisan schedule:run` ogni minuto (Render cron) esegue:
- `orders:cleanup-abandoned` (ogni 15 min)
- `coupons:expire` (giornaliero)
- `wallet:reconcile` (notturno)
- `backups:database` (giornaliero, S3)

## Observability

```
 [Laravel]                           [Nuxt]
    |                                   |
    | sentry-php                        | @sentry/vue
    v                                   v
 +--------------------------------------------+
 |              Sentry project                |
 | - issues (error grouping)                  |
 | - performance (transactions trace)         |
 | - alerts -> Slack #alerts-prod             |
 +--------------------------------------------+

 [Nuxt client]
    |
    | web-vitals + Plausible
    v
 +-----------------+      +--------------+
 | Plausible cloud |      | Dashboard    |
 | (pageview, CWV) | ---> | Render + own |
 +-----------------+      +--------------+

 [Laravel logs]
    |
    v
 stderr -> Render log drain -> Logtail
```

- **Sentry**: capture error + tracing (sample 10% prod, 100% staging).
- **Plausible**: analytics privacy-friendly, cookieless.
- **Web Vitals**: LCP/INP/CLS inviati a `/api/metrics/vitals` (tabella aggregata).
- **Health endpoint**: `GET /api/health` (DB+Redis check), `GET /api/health/live` (ping app).

## Ambienti

| Env       | URL                        | Branch       | Deploy         |
|-----------|----------------------------|--------------|----------------|
| Local     | localhost:8787             | feature/*    | manual         |
| Staging   | staging.spediamofacile.it  | develop      | auto push      |
| Prod      | spediamofacile.it          | main         | manual + tag   |

Variabili env: vedi `apps/api/.env.example` e `apps/web/.env.example`. Segreti prod vivono in Render dashboard (cifrati, non in repo).

## Riferimenti

- [`FRONTEND_STRUCTURE.md`](./FRONTEND_STRUCTURE.md) — tour Nuxt
- [`BACKEND_STRUCTURE.md`](./BACKEND_STRUCTURE.md) — tour Laravel
- [`API_CONTRACT.md`](./API_CONTRACT.md) — contratto endpoint
- [`DEPLOY.md`](../DEPLOY.md) — pipeline prod
- [`DEBUGGING.md`](./DEBUGGING.md) — troubleshooting
