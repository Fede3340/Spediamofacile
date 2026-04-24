# Stripe Live Setup — Go-live Procedure

Procedura step-by-step per attivare Stripe in modalita' LIVE su spediamofacile.it.

> Nota: NON committare MAI chiavi reali nel repo. Usare sempre le env vars del provider di hosting (Render, Railway, ecc.) — non `.env.production.example`.

## 1. Ottenere le chiavi live da Stripe

1. Login su https://dashboard.stripe.com
2. Toggle **View test data** → OFF (in alto a destra).
3. Navigare su **Developers → API keys**.
4. Copiare:
   - **Publishable key** → `STRIPE_KEY` (formato `pk_live_...`)
   - **Secret key** → `STRIPE_SECRET` (formato `sk_live_...`, mostrata una sola volta)
5. Se si usa Stripe Connect per i Partner Pro:
   - **Connect → Settings → Integration** → copiare il Client ID (`ca_...`) → `STRIPE_CLIENT_ID`.

## 2. Configurare le env vars su Render (production)

1. Render dashboard → selezionare il service Laravel backend.
2. **Environment → Environment Variables → Add**:
   ```
   STRIPE_KEY=pk_live_xxx
   STRIPE_SECRET=sk_live_xxx
   STRIPE_CLIENT_ID=ca_xxx
   STRIPE_WEBHOOK_SECRET=whsec_xxx   # compilato allo step 3
   ```
3. **Save, Manual Deploy → Clear build cache & deploy** (le env vars sono lette al boot PHP-FPM).

## 3. Registrare il webhook endpoint

1. Dashboard Stripe → **Developers → Webhooks → Add endpoint**.
2. Endpoint URL: `https://spediamofacile.it/api/stripe/webhook`
3. Eventi da sottoscrivere (match del `match()` in `StripeWebhookController::handle`):
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `account.updated`
   - `account.application.deauthorized`
   - `charge.refunded` (per rimborsi manuali da dashboard)
   - `customer.subscription.created`, `.updated`, `.deleted` (solo se subscription attive — attualmente non previste)
4. **Signing secret** (Reveal) → copiare `whsec_...` → env var `STRIPE_WEBHOOK_SECRET` su Render.
5. Trigger manuale: **Send test webhook → payment_intent.succeeded** → risposta deve essere `200 {received:true}`.

## 4. Checklist go-live test

- [ ] Payment intent test live da €1 creato tramite UI checkout reale
- [ ] Pagamento completato con carta reale del team → ordine passa a `completed`
- [ ] Rimborso di test emesso da dashboard Stripe → webhook `charge.refunded` arriva
- [ ] Stripe dashboard → Webhooks → Events → tutte le consegne verdi (200)
- [ ] Sentry Laravel → zero errori su `StripeWebhookController`
- [ ] `SELECT COUNT(*) FROM stripe_webhook_events WHERE created_at > NOW() - INTERVAL 1 HOUR` → match count Stripe dashboard

## 5. Monitoring post-cutover

- Stripe dashboard → **Developers → Events** — qualsiasi evento con status "Failed" richiede investigazione immediata.
- Sentry alert rule: trigger su `StripeWebhookController` error level → Slack ops.
- 24h prime ore: presidio umano su dashboard Stripe + Sentry.

## 6. Rollback

Se i webhook live falliscono in produzione:

1. Render env vars → sostituire `STRIPE_SECRET` e `STRIPE_WEBHOOK_SECRET` con i valori test (`sk_test_`, `whsec_` del test endpoint).
2. Manual Deploy → restart istantaneo (< 2 min).
3. Disabilitare l'endpoint live su dashboard Stripe per evitare duplicati quando si torna live.
