# API Contract — SpediamoFacile

Contratto endpoint REST esposti da Laravel. Tutte le rotte sono prefissate con `/api`.

> Base URL: `http://localhost:8000/api` (locale) · `https://api.spediamofacile.it/api` (prod)
>
> Auth: cookie-based Sanctum. Per rotte protette serve cookie `spediamofacile_session` + header `X-XSRF-TOKEN`.
>
> Content-Type: `application/json` salvo upload (`multipart/form-data`).
>
> Errori: schema Laravel standard `{ "message": "...", "errors": { "field": ["..."] } }` (HTTP 422 validation, 401 auth, 403 forbidden, 419 CSRF).

Indice:

- [Auth](#auth) · [Sessione preventivo](#sessione-preventivo) · [Locations](#locations) · [BRT PUDO](#brt-pudo) · [Tracking](#tracking)
- [Cart](#cart) · [Packages](#packages) · [Addresses](#addresses) · [Coupon](#coupon) · [Saved shipments](#saved-shipments)
- [Orders](#orders) · [Shipment execution](#shipment-execution) · [Refund](#refund)
- [Payments Stripe](#payments-stripe) · [Wallet](#wallet) · [Withdrawals](#withdrawals)
- [Referral](#referral) · [Notifications](#notifications) · [Pro Request](#pro-request) · [Contact](#contact)
- [Public content](#public-content) · [GDPR](#gdpr) · [Health](#health)
- [Admin](#admin)

---

## Auth

### `GET /user` (auth)
Utente corrente.
```json
200: { "id": 12, "email": "a@b.it", "role": "client", "email_verified_at": "..." }
```

### `POST /custom-login`
Body: `{ "email": "...", "password": "..." }` · Throttle 10/min · Restituisce user + setta session cookie.

### `POST /custom-register`
Body: `{ "first_name", "last_name", "email", "password", "password_confirmation", "accept_terms": true }` · Throttle 5/min · Invia email verifica.

### `POST /logout` (auth)
Revoca token Sanctum + invalida sessione.

### `POST /forgot-password`
Body: `{ "email": "..." }` · Throttle 5/min · Invia link reset.

### `POST /update-password`
Body: `{ "token", "email", "password", "password_confirmation" }` · Throttle 5/min.

### `GET /verify-email/{id}`
Link firmato da email. Marca `email_verified_at`. Throttle sign-based.

### `POST /resend-verification-email` · `POST /verify-code`
Throttle 5/min.

### `GET /auth/providers`
Response: `{ "google": true, "facebook": false, "apple": true }` — provider OAuth configurati.

### `GET /auth/{provider}/redirect` · `GET /auth/{provider}/callback`
Provider: `google` | `facebook` | `apple`. Redirect top-level al provider; callback crea/aggiorna user + sessione.

### `POST /auth/confirm-password` (auth)
Ri-conferma password per operazioni sensibili.

### `GET /users/{user}` · `PUT /users/{user}` (auth)
Self-read/update profilo.

---

## Sessione preventivo

### `GET /session`
Contesto preventivo guest (cookie-based).

### `POST /session/first-step`
Body: `{ "weight": 5.2, "length": 40, "width": 30, "height": 20, "origin": {...}, "destination": {...} }` · Persist step 1 -> calcolo prezzo.

### `POST /session/second-step`
Body: `{ "services": [1, 3], "insurance": true }` · Persist step 2.

---

## Locations

### `GET /locations/search?q=<query>` (throttle 30/min)
Autocomplete comune/CAP/provincia. Response: `[{ "city": "Milano", "cap": "20100", "province": "MI" }]`.

### `GET /locations/by-cap?cap=20100` · `GET /locations/by-city?name=Milano`
Lookup singoli.

---

## BRT PUDO

### `GET /brt/pudo/search?cap=20100&limit=20`
Punti pickup/delivery BRT vicini.

### `GET /brt/pudo/nearby?lat=45.46&lng=9.19`
Geolocalizzato.

### `GET /brt/pudo/{pudoId}`
Dettaglio singolo PUDO.

---

## Tracking

### `GET /tracking/search?code=...` (throttle 15/min)
Tracking pubblico (senza login).

### `GET /brt/tracking/{order}` (auth)
Tracking ordine registrato.

### `GET /brt/label/{order}` (auth)
Download etichetta PDF BRT.

### `POST /brt/create-shipment` (auth, throttle 5/min)
Body: `{ "order_id": 42 }` · Invio richiesta spedizione a BRT.

### `POST /brt/confirm-shipment` (auth, throttle 5/min)
Conferma dopo verifica.

### `POST /brt/delete-shipment` (auth + admin)
Annullamento spedizione BRT.

---

## Cart

### `GET /guest-cart` · `POST /guest-cart` · `PUT /guest-cart/{id}` · `DELETE /guest-cart/{id}`
Carrello guest (session-based).

### `DELETE /empty-guest-cart`
Svuota.

### `GET /cart` (auth)
Lista + totali. Prezzi in centesimi.

### `POST /cart` (auth)
Body: package item (weight, dim, addresses, service_ids...).

### `GET /cart/{cart}` · `PUT /cart/{cart}` · `DELETE /cart/{cart}` (auth)
CRUD singolo item.

### `PATCH /cart/{id}/quantity` (auth)
Body: `{ "quantity": 3 }`.

### `POST /cart/merge` (auth)
Merge item identici dopo login guest->user.

### `DELETE /empty-cart` (auth)
Svuota.

---

## Packages

### `GET|POST|PUT|DELETE /packages` (auth)
apiResource CRUD Package.

---

## Addresses

### `GET|POST|PUT|DELETE /addresses` (auth)
Indirizzi associati a pacco.

### `GET|POST|PUT|DELETE /user-addresses` (auth)
Rubrica personale utente.

---

## Coupon

### `POST /calculate-coupon` (auth, throttle 5/min)
Body: `{ "code": "SCONTO10", "cart_total_cents": 5000 }` · Response: `{ "discount_cents": 500, "final_cents": 4500, "type": "percent" }`.

---

## Saved shipments

### `GET /saved-shipments` · `POST /saved-shipments` (throttle 5/min) · `PUT /saved-shipments/{id}` · `DELETE /saved-shipments/{id}` (auth)

### `POST /saved-shipments/add-to-cart` (auth)
Duplica spedizione salvata nel carrello corrente.

---

## Orders

### `GET /orders` (auth)
Paginated lista ordini utente. Query: `?page=1&per_page=10&status=paid`.

### `GET /orders/{order}` (auth)
Dettaglio (owner o admin).

### `POST /orders/{order}/cancel` (auth, throttle 3/min)
Annulla se stato lo consente.

### `GET /orders/{order}/invoice` (auth)
Download PDF fattura.

### `GET /orders/{order}/refund-eligibility` (auth, throttle 5/min)
Check se rimborsabile.

### `POST /orders/{order}/add-package` (auth, throttle 10/min)
Aggiungi pacco a ordine esistente.

### `POST /create-direct-order` (auth, throttle 5/min)
Crea ordine senza passare dal carrello (admin/partner).

---

## Shipment execution

### `GET /orders/{order}/execution` (auth)
Stato esecuzione (pickup, bordero, docs).

### `POST /orders/{order}/pickup` (auth, throttle 5/min)
Richiedi pickup BRT.

### `POST /orders/{order}/bordero` (auth, throttle 5/min)
Genera bordero.

### `GET /orders/{order}/bordero/download` (auth)
Download PDF.

### `POST /orders/{order}/send-documents` (auth, throttle 5/min)
Invia etichetta + bordero via email.

---

## Payments Stripe

### `POST /stripe/create-payment-intent` (auth, cart required, throttle 10/min)
Body: `{ "payment_method_id": "pm_...", "save_card": true }` · Response: `{ "client_secret": "pi_..._secret_..." }`.

### `POST /stripe/create-payment` (auth)
Wrapper per flussi senza 3DS.

### `POST /stripe/create-order` (auth, cart)
Crea order pending prima del pagamento.

### `POST /stripe/order-paid` (auth, cart)
Conferma finale client dopo `confirmPayment`.

### `POST /stripe/existing-order-payment-intent` · `POST /stripe/existing-order-payment` · `POST /stripe/existing-order-paid` (auth, throttle 10/min)
Pagamento di un ordine gia' creato (retry failed).

### `POST /stripe/mark-order-completed` (auth, throttle 10/min)
Mark status completed (post-webhook fallback).

### `POST /stripe/create-setup-intent` (auth, throttle 10/min)
Aggiungi carta senza pagamento.

### `GET /stripe/payment-methods` (auth)
Lista carte salvate utente.

### `GET /stripe/default-payment-method` · `POST /stripe/set-default-payment-method` · `POST /stripe/change-default-payment-method` (auth, throttle 10/min)

### `DELETE /stripe/delete-card` (auth, throttle 10/min)
Body: `{ "payment_method_id": "pm_..." }`.

### `GET /stripe/connect` · `GET /stripe/callback` · `GET /stripe/create-account` (auth)
Stripe Connect onboarding Partner Pro.

### `GET /settings/stripe` (auth) · `POST /settings/stripe` (admin)
Config Stripe dinamica (chiavi test/live).

### `POST /stripe/webhook` (public, signed)
Webhook Stripe. Verifica firma `Stripe-Signature` con `STRIPE_WEBHOOK_SECRET`.

### `POST /webhooks/brt/tracking` (public, HMAC + IP whitelist)
Webhook BRT firmato con `BRT_WEBHOOK_SECRET`. **Nota**: rotta in `routes/web.php`, NO prefix `/api` (URL produzione: `https://spediamofacile.it/webhooks/brt/tracking`).

---

## Wallet

### `GET /wallet/balance` (auth)
Response: `{ "balance": 120.50, "commission_balance": 10.00, "currency": "EUR" }`.

### `GET /wallet/movements` (auth)
Lista movimenti portafoglio.

### `POST /wallet/top-up` (auth, throttle 5/min)
Body: `{ "amount": 50.00, "payment_method_id": "pm_...", "idempotency_key": "..." }` · Ricarica via Stripe.

### `POST /wallet/pay` (auth, throttle 10/min)
Body:

```json
{
  "amount": 26.90,
  "reference": "order-42",
  "description": "Pagamento spedizione"
}
```

Paga un ordine con saldo portafoglio creando **solo** il movimento debit confermato.

Note:
- il formato canonico del riferimento e' `order-{id}`
- per compatibilita' backend sono ancora accettati riferimenti legacy numerici grezzi (`"42"`), ma vengono normalizzati a `order-42`
- l'importo deve coincidere con il totale ordine, altrimenti la request viene rifiutata con `422`
- questo endpoint **non** completa da solo l'ordine

Step 2 obbligatorio dopo il successo di `/wallet/pay`:

### `POST /stripe/mark-order-completed` (auth, throttle 10/min)
Body:

```json
{
  "order_id": 42,
  "payment_type": "wallet",
  "ext_id": "wallet-991"
}
```

Il valore `ext_id` va costruito usando l'id del movimento debit restituito da `/wallet/pay`.
Il backend accetta solo il formato canonico `wallet-{movementId}` e verifica che il movimento:

- appartenga allo stesso utente dell'ordine
- sia `confirmed`
- abbia `source = wallet`
- punti allo stesso `reference = order-{id}`
- abbia importo identico al totale ordine

Retry / idempotency:

- ripetere la stessa chiamata con lo stesso `ext_id` e' sicuro
- il backend deve sempre riconciliare lo stesso ordine con la stessa transazione, senza crearne una nuova
- per `wallet`, un retry puo' riattivare i side-effect post-pagamento solo se il primo tentativo ha scritto ordine + transazione ma si e' fermato prima di dispatchare `OrderPaid`
- se l'ordine e' gia' avanzato oltre `completed` (`processing`, `label_generated`, `in_transit`), il retry non deve rilanciare `OrderPaid` una seconda volta
- per `bonifico`, il retry non deve duplicare gli effetti di messa in attesa bonifico

---

## Withdrawals

### `GET /withdrawals` (auth)
Storico prelievi Partner Pro.

### `POST /withdrawals` (auth, throttle 3/min)
Body: `{ "amount_cents": 20000, "iban": "IT..." }` · Richiedi prelievo (pending admin approval).

---

## Referral

### `GET /referral/my-code` (auth)
Codice invito utente.

### `POST /referral/validate` (auth, throttle 10/min)
Body: `{ "code": "ABC123" }`.

### `POST /referral/apply` (auth, throttle 5/min)
Applica codice all'ordine corrente.

### `POST /referral/store` (auth, throttle 5/min)
Persist referral al signup.

### `GET /referral/my-discount` · `GET /referral/earnings` (auth)

---

## Notifications

### `GET /notifications` (auth)
Lista paginata.

### `GET /notifications/unread-count` (auth)

### `PATCH /notifications/{notification}/read` · `PATCH /notifications/read-all` (auth)

### `GET /notifications/preferences` · `PUT /notifications/preferences` (auth)
Body: `{ "email": true, "push": false, "in_app": true }`.

---

## Pro Request

### `POST /pro-request` (auth, throttle 5/min)
Body: `{ "vat_number", "company_name", "reason" }`.

### `GET /pro-request/status` (auth)
`{ "status": "pending" | "approved" | "rejected" }`.

---

## Contact

### `POST /contact` (public, throttle 5/min)
Body: `{ "name", "email", "subject", "message" }` · Captcha-protected.

### `POST /support-tickets` (auth, throttle 10/min)
Ticket registrati (utenti loggati).

---

## Public content

### `GET /public/blog` · `GET /public/blog/{slug}`
Lista + dettaglio articoli blog.

### `GET /public/guides` · `GET /public/guides/{slug}`

### `GET /public/services` · `GET /public/services/{slug}`

### `GET /public/price-bands`
Fasce prezzo pubbliche (calcolatore preventivo).

### `GET /public/homepage-image`
Immagine hero homepage.

---

## GDPR

### `DELETE /user/account` (auth)
Cancellazione account + purge dati (soft-delete 30gg poi hard).

### `GET /user/data-export` (auth)
Download JSON dati utente (art. 20 GDPR).

### `POST /cookie-consent` (public, throttle 10/min)
Body: `{ "analytics": true, "marketing": false }`.

---

## Health

### `GET /health` (public, throttle 30/min)
Readiness: `{ "status": "ok", "checks": { "db": "ok", "redis": "ok" } }`.

### `GET /health/live`
Liveness: `{ "status": "ok" }`.

---

## Admin

Tutte le rotte sotto `/admin/*` richiedono `auth:sanctum` + middleware `CheckAdmin`.

### Dashboard
- `GET /admin/dashboard` — KPI (ordini oggi, revenue, utenti)

### Ordini e spedizioni
- `GET /admin/orders` — lista filtri
- `PATCH /admin/orders/{order}/status` — body `{ "status": "..." }`
- `PATCH /admin/orders/{order}/pudo` — aggiorna PUDO scelto
- `POST /admin/orders/{order}/regenerate-label`
- `GET /admin/shipments`

### Portafoglio e prelievi
- `GET /admin/wallet/overview`
- `GET /admin/wallet/users/{user}/movements`
- `GET /admin/withdrawals` — paginata
- `POST /admin/withdrawals/{withdrawal}/approve`
- `POST /admin/withdrawals/{withdrawal}/reject`

### Referral
- `GET /admin/referrals` — statistiche

### Partner Pro
- `GET /admin/pro-requests`
- `PATCH /admin/pro-requests/{id}/approve` · `PATCH /admin/pro-requests/{id}/reject`

### Utenti
- `GET /admin/users`
- `PATCH /admin/users/{user}/approve`
- `PATCH /admin/users/{user}/role` — body `{ "role": "admin|client|pro" }`
- `PATCH /admin/users/{user}/user-type`
- `DELETE /admin/users/{user}`

### Contenuti
- `GET /admin/contact-messages` · `PATCH /admin/contact-messages/{id}/read`
- `GET /admin/settings` · `POST /admin/settings`

### Articoli (blog/guide/servizi)
- `GET|POST /admin/articles`
- `GET|PUT|DELETE /admin/articles/{article}`
- `POST /admin/articles/{article}/upload-image` (throttle 30/min)

### Fasce prezzo
- `GET /admin/price-bands` · `PUT /admin/price-bands` (bulk) · `POST /admin/price-bands/seed`

### Promozioni / Homepage
- `GET /admin/promo-settings` · `POST /admin/promo-settings`
- `POST /admin/promo-settings/upload-image` (throttle 30/min)
- `POST /admin/homepage-image` · `GET /admin/homepage-image`

### Coupon
- `GET|POST /admin/coupons` · `PUT|DELETE /admin/coupons/{coupon}`

### BRT (admin)
- `POST /admin/brt/test-create` — test sandbox

---

## Convenzioni generali

**Rate limiting**: header `X-RateLimit-Limit` + `X-RateLimit-Remaining`, 429 on exceed.

**CSRF**: endpoint non-safe (POST/PUT/PATCH/DELETE) richiedono header `X-XSRF-TOKEN` letto dal cookie `XSRF-TOKEN`. `useSanctumClient` lo gestisce automatico.

**Paginazione**: default `per_page=15`, max 100. Response `{ data: [], meta: { current_page, last_page, total } }`.

**Webhook idempotenza**: tabelle `stripe_webhook_events` / `brt_webhook_events` salvano `event_id`; duplicati restituiscono 200 senza riprocessare.

**Money**: tutti i campi `*_cents` sono interi in centesimi EUR. Frontend divide per 100.

Per contesto architetturale: [`ARCHITECTURE.md`](./ARCHITECTURE.md). Per schema backend: [`BACKEND_STRUCTURE.md`](./BACKEND_STRUCTURE.md).
