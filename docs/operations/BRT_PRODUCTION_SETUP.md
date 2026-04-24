# BRT Production Setup — Go-live Procedure

Attivazione credentials BRT live per spediamofacile.it. Il sito opera come intermediario BRT (vedi `project_brt_business`): tutte le spedizioni passano dall'account BRT Business aziendale.

> Nota: sandbox e production BRT usano URL e credentials differenti. Il flag `BRT_ENV=production` consumato dai service in `app/Services/Brt/` determina base URL + strict mode (niente mock, niente fallback permissivi).

## 1. Portale BRT production

- URL sandbox: `https://sandbox.brt.it/...`
- URL production: `https://api.brt.it/rest/v1/`

Richiedere al referente BRT Business:
- `BRT_CLIENT_ID` (ID cliente production, diverso da sandbox)
- `BRT_PASSWORD` (password API production)
- `BRT_PUDO_TOKEN` (token separato per API Punti PUDO)
- Filiale di partenza (`BRT_DEPARTURE_DEPOT`, codice numerico).

## 2. Env vars production (Render)

Configurare su Render → Environment Variables — NON nel repo:

```
BRT_ENV=production
BRT_API_BASE_URL=https://api.brt.it/rest/v1/
BRT_API_URL=https://api.brt.it/rest/v1/shipments
BRT_PUDO_API_URL=https://api.brt.it
BRT_CLIENT_ID=<da portale BRT>
BRT_PASSWORD=<da portale BRT>
BRT_PUDO_TOKEN=<da portale BRT>
BRT_DEPARTURE_DEPOT=<codice numerico filiale>
BRT_VERIFY_SSL=true
BRT_WEBHOOK_SECRET=<generato sotto, step 3>
BRT_WEBHOOK_ALLOWED_IPS=<IP BRT push, forniti dal referente>
```

## 3. Webhook tracking — registrazione lato BRT

1. Portale BRT production → sezione Webhook Push Tracking.
2. URL: `https://spediamofacile.it/webhooks/brt/tracking` (rotta in `routes/web.php`, NO prefix `/api`)
3. Metodo: POST, Content-Type: `application/json`.
4. Generare secret HMAC locale:
   ```bash
   openssl rand -hex 32
   ```
   Settare lo stesso valore su BRT portale (header `X-Brt-Signature`) e su env var `BRT_WEBHOOK_SECRET`.
5. `BrtWebhookController::verifyRequestAuthenticity` verifica HMAC-SHA256 sul body grezzo (gia' implementato, Sprint 6.8).

## 4. IP whitelist — Render egress + BRT firewall

- Render docs → Egress IPs: https://render.com/docs/static-outbound-ip-addresses (sezione "Static Outbound IPs" del servizio).
- Comunicare gli IP di egress al referente BRT per il whitelist lato firewall BRT (chiamate da backend Laravel a API BRT).
- In senso opposto: BRT fornisce gli IP sorgente dei loro webhook push → popolare `BRT_WEBHOOK_ALLOWED_IPS` (CSV).

## 5. Test pre-cutover

1. **Dry-run create shipment**: usare un ordine reale in staging con `BRT_ENV=sandbox`, verificare che `POST /shipments` ritorni `parcelId` + label PDF.
2. **Ripetere in production**: switch `BRT_ENV=production`, test con ordine interno del team (no cliente).
3. **Webhook**: forzare uno stato tracking dal portale BRT → verificare log Laravel `BRT webhook received` + transizione `Order.status`.
4. Sentry: zero errori su `BrtService`, `TrackingService`, `BrtWebhookController`.

## 6. Sandbox vs live — flag runtime

- `BRT_ENV=sandbox` → base URL sandbox, logging verbose, validazioni rilassate.
- `BRT_ENV=production` → strict mode: tutti i campi obbligatori BRT validati, niente mock response, errori HTTP propagati come exception.
- Il flag e' letto in `app/Services/Brt/*` all'istanziazione del service.

## 7. Rollback

- Se BRT production fallisce (auth errors, API 5xx persistenti):
  - Render env vars → `BRT_ENV=sandbox` + credentials sandbox.
  - Manual Deploy → ripristino in < 2 min.
  - Comunicare ai clienti eventuali ritardi sulle etichette generate (coda job rieseguibile).
