# Go-live Checklist — spediamofacile.it

Checklist di cutover finale. Ogni voce va spuntata dal responsabile indicato.
Consolida GOLIVE_CHECKLIST + DEPLOY_SECURITY_CHECKLIST in un unico documento operativo.

> Per compliance legale pre-prod vedi anche `LEGAL_GOLIVE_CHECKLIST.md` (blocker GDPR/business).
> Per policy di sicurezza implementative vedi `SECURITY.md`.
> Per compliance GDPR completa vedi `GDPR_COMPLETO.md`.

---

## 1. Segreti e credenziali (Ops)

- [ ] Stripe live keys (`STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_CLIENT_ID`) in Render env — **MAI in repo**
- [ ] `STRIPE_WEBHOOK_SECRET` in Render env, match con endpoint `https://spediamofacile.it/api/stripe/webhook`
- [ ] Stripe webhook endpoint registrato + test delivery verde
- [ ] BRT production credentials (`BRT_CLIENT_ID`, `BRT_PASSWORD`, `BRT_PUDO_TOKEN`) in Render env
- [ ] BRT webhook URL `https://spediamofacile.it/webhooks/brt/tracking` registrato su portale BRT (no prefix `/api` — rotta in `routes/web.php`)
- [ ] BRT IP whitelist (Render egress → BRT firewall; BRT push IPs → `BRT_WEBHOOK_ALLOWED_IPS`)
- [ ] `BRT_WEBHOOK_SECRET` identico fra portale BRT e Render env
- [ ] Mail provider Resend: `RESEND_KEY` in Render env, dominio `spediamofacileaccount.com` verificato (SPF+DKIM+DMARC verdi)
- [ ] Sentry DSN production (`SENTRY_LARAVEL_DSN`) + release SHA popolato da CI
- [ ] `APP_KEY` generato in produzione (`php artisan key:generate --show`) e settato UNA volta

## 2. Database

- [ ] DB managed in produzione (PostgreSQL su Render — **NO SQLite**)
- [ ] Encryption at rest attivo
- [ ] SSL/TLS sulle connessioni DB
- [ ] Backup automatici giornalieri (retention minima 30gg)
- [ ] IP whitelisting / private networking per accesso DB
- [ ] Test restore del backup almeno una volta

## 3. Infrastruttura (DevOps)

- [ ] DNS `spediamofacile.it` + `www.spediamofacile.it` → Render prod (A/CNAME verificati via `dig`)
- [ ] SSL Let's Encrypt attivo (auto-rinnovo Render, scadenza > 60gg)
- [ ] `SANCTUM_STATEFUL_DOMAINS=spediamofacile.it,www.spediamofacile.it`
- [ ] `CORS_ALLOWED_ORIGINS=https://spediamofacile.it,https://www.spediamofacile.it`
- [ ] Sessione cookie `SESSION_SECURE_COOKIE=true`, `SESSION_DOMAIN=.spediamofacile.it`
- [ ] Redis attivo (queue + cache), connettività verificata da task artisan
- [ ] MySQL/PostgreSQL backup giornaliero attivo (Render managed DB) + test restore almeno una volta
- [ ] `php artisan config:cache && route:cache && view:cache` nello script di deploy

## 4. Headers e sicurezza applicativa

- [x] CSP headers (nuxt-security + `SecurityHeaders.php`)
- [x] HSTS (backend su HTTPS)
- [x] X-Content-Type-Options, X-Frame-Options, Referrer-Policy
- [x] Rate limiting su tutti gli endpoint pubblici
- [ ] CORS ristretto al dominio reale (no wildcard)
- [ ] Nessuna chiave / secret in codice (scan repo con `gitleaks` verde)

## 5. GDPR & Legal (vedi `LEGAL_GOLIVE_CHECKLIST.md` per dettaglio)

- [x] Cookie consent con reject + gestisci cookie nel footer
- [x] Privacy checkbox in registrazione
- [x] Data export endpoint attivo
- [x] Delete account endpoint attivo
- [ ] Registro trattamenti compilato con dati reali Titolare (vedi `GDPR_COMPLETO.md` — tutti i `[INSERIRE_*]` risolti)
- [ ] DPIA firmata (se applicabile)
- [ ] Piano breach notification 72h collaudato (vedi `GDPR_COMPLETO.md` Parte B)

## 6. Monitoring (SRE)

- [ ] UptimeRobot check su `https://spediamofacile.it/api/health` ogni 1 min
- [ ] Sentry alert rule: error rate > 1% / 5min → Slack ops
- [ ] Log aggregator (Render logs oppure papertrail) attivo
- [ ] Dashboard Stripe Events: bookmark in browser ops
- [ ] Queue worker attivo (Render worker / supervisor)
- [ ] Cron scheduler attivo (`php artisan schedule:run`)

## 7. Smoke test cutover (QA)

- [ ] Signup nuovo utente → email di benvenuto ricevuta via Resend
- [ ] Login + ordine test €1 → paga con carta reale → ordine `completed`, etichetta BRT generata
- [ ] Webhook Stripe `payment_intent.succeeded` arriva, ordine aggiornato
- [ ] Webhook BRT `SHIPMENT_CREATED` arriva, `Order.status = in_transit`
- [ ] Rimborso Stripe dashboard → `charge.refunded` handled, ordine `refunded`
- [ ] Reset password flow → email reset arriva, nuovo login OK
- [ ] Partner Pro onboarding Stripe Connect → `account.updated` webhook → `stripe_charges_enabled=true`

## 8. Rollback plan

| Scenario | Azione | RTO |
|---|---|---|
| Stripe live 5xx persistente | Render env: `STRIPE_SECRET=sk_test_*`, ridistribuire | < 2 min |
| BRT production auth fail | Render env: `BRT_ENV=sandbox` + credentials sandbox | < 2 min |
| App down / deploy rotto | Render dashboard → Rollback al deploy precedente | < 1 min |
| Spike errori Sentry > 5% | Maintenance mode: `php artisan down --secret="..."` | < 30 sec |

## 9. Post-cutover (24h)

- [ ] Presidio umano attivo su Sentry + Stripe dashboard
- [ ] Check `stripe_webhook_events` vs Stripe dashboard counts ogni 2h (match atteso)
- [ ] Check `brt_webhook_events` vs portale BRT tracking (spot check 10 parcelId)
- [ ] Nessuna email di errore critical dal logger Laravel
- [ ] Review queue failed jobs (`php artisan queue:failed`) — deve essere vuota o con retry pianificato
