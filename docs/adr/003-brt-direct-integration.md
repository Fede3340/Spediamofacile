# ADR 003 — Integrazione BRT diretta (no aggregator)

Data: 2026-04-18
Status: Accepted

## Contesto
SpediamoFacile opera come **intermediario diretto** di BRT Corriere Espresso: l'account BRT Business aziendale è l'unico backend per tutte le spedizioni clienti. Opzioni valutate:
- Aggregator multi-corriere (ShipStation, Packlink, Shippypro)
- API BRT dirette + white-label
- Rivenditore BRT con login utente sul portale

## Decisione
Integrazione diretta con API REST BRT (`https://api.brt.it/rest/v1/`) usando credentials Business dedicate. Il sito espone una UI brandizzata ma tutte le etichette, bordero, tracking passano dall'account aziendale.

## Motivazioni
- Controllo completo su servizi, pricing, contratti -> margine gestito direttamente.
- Zero fees aggregator (tipico 5-10% su ogni shipping).
- SLA e supporto diretti col referente BRT Business.
- Possibilità di negoziare pricing custom e servizi aggiuntivi (PUDO, ritiro a domicilio, cash on delivery).

## Conseguenze
- TUTTE le spedizioni utente vanno sull'account BRT Business -> controllo centrale via API (no login separato).
- Serve sistema di normalizzazione indirizzi lato backend (BRT pretende dati strict-format: vedi `PERCHE-BRT-NORMALIZZAZIONE.md`).
- Credentials BRT (`BRT_CLIENT_ID`, `BRT_PASSWORD`, `BRT_PUDO_TOKEN`) vivono solo in env, mai nel repo.
- `BRT_ENV=production` attiva strict mode (no mock, no fallback permissivi).
- Serve fallback PUDO locale (45+ punti italiani) per resilienza quando l'API BRT PUDO è down (vedi `PUDO_FALLBACK_SETUP.md`).
- Webhook BRT tracking -> endpoint `/webhooks/brt/tracking` (rotta in `routes/web.php`, NO prefix `/api`) con IP whitelist + secret HMAC-SHA256.

## Riferimenti
- `apps/api/app/Services/Brt/`
- `docs/BRT_PRODUCTION_SETUP.md` (post-move)
- `docs/GOLIVE_CHECKLIST.md` (sezione BRT)
- Business context: «Site is BRT intermediary, must control ALL business account options via API».
