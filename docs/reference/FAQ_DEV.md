# FAQ Dev — SpediamoFacile

Domande ricorrenti durante lo sviluppo. Se manca la tua, apri issue con label `faq`.

---

## Setup e primo avvio

### Perché `npm install` alla root del monorepo?
Installa i hook Husky + tooling di linting condiviso. Non serve per runtime: frontend e backend hanno `npm install` / `composer install` propri. Vedi `CONTRIBUTING.md`.

### Quale porta usa il frontend in dev?
Nuxt dev gira su **8787** dal worktree `lavoro`. Backend Laravel su 8000. La porta 8787 è hardcoded in alcuni script QA, non cambiarla senza aggiornarli tutti.

### Il setup deve funzionare anche su Windows?
Sì. Usa Git Bash (C:/Program Files/Git/bin/bash.exe) quando PowerShell dà 8009001d. Gli script assumono Bash POSIX: `mv`, `find`, ecc.

---

## Autenticazione

### `401 Unauthorized` su rotta protetta
Checklist:
1. Hai ottenuto il cookie CSRF con `GET /sanctum/csrf-cookie`? (Il composable `useSanctumClient` lo fa automaticamente.)
2. Il dominio frontend è in `SANCTUM_STATEFUL_DOMAINS`?
3. `CORS_ALLOWED_ORIGINS` contiene l'origin esatto (con scheme)?
4. In produzione, `SESSION_SECURE_COOKIE=true` e `SESSION_DOMAIN=.spediamofacile.it`?

Vedi `docs/adr/001-sanctum-spa-auth.md` per il razionale.

### `419 Page Expired`
Mismatch CSRF. Non stai usando `useSanctumClient()` oppure stai facendo `$fetch` raw senza header `X-XSRF-TOKEN`.

---

## Prezzi e pagamenti

### `formatPrice()` stampa importi 100x troppo grandi
Il backend salva in **cents**. Dividi per 100 prima di formattare. Vedi `docs/adr/002-moneyphp-cents.md`.

### Parse di stringa formattata (`"20,00 €"`) non funziona
Il locale IT usa NBSP (`\u00A0`) tra numero e simbolo. `.trim()` NON lo rimuove: usa `value.replace(/[€\s\u00A0]/g, '').replace(',', '.')` prima del parseFloat.

### Stripe webhook non marca l'ordine come `completed`
1. `STRIPE_WEBHOOK_SECRET` corrisponde a quello dell'endpoint Stripe?
2. Endpoint test su Stripe dashboard ritorna 200?
3. La coda webhook è attiva? (`php artisan queue:work --queue=webhooks`)

---

## BRT

### `PUDO API timeout` o lista punti vuota
Attiva il fallback locale: `php artisan db:seed --class=PudoPointSeeder` popola ~45 punti italiani. Il sistema lo usa automaticamente se l'API BRT PUDO è down. Vedi `PUDO_FALLBACK_SETUP.md`.

### Etichetta BRT non viene generata
1. `BRT_CLIENT_ID` / `BRT_PASSWORD` settate?
2. `BRT_ENV=sandbox` in dev, `production` in live?
3. Listener `GenerateBrtLabel` registrato in `EventServiceProvider`?
4. Coda attiva? (le label si generano async post-paid)

### Webhook BRT tracking non riceve
`BRT_WEBHOOK_ALLOWED_IPS` include gli IP push di BRT? `BRT_WEBHOOK_SECRET` identico fra portale BRT e Render env?

---

## Database e sessioni

### `Session not found` dopo login
Cookie `spediamofacile_session` non viene settato. Problema CORS o `SESSION_DOMAIN` sbagliato. Per dev locale lascia vuoto (usa default browser).

### Perché non SQLite in produzione?
Limite connessioni + corruption rate + impossibilità scaling orizzontale. Produzione usa PostgreSQL managed su Render. Vedi `docs/GOLIVE_CHECKLIST.md` sezione Database.

---

## Cart e checkout

### Duplicazione pacchi nel carrello
Il backend deduplica confrontando: `package_type + weight + dimensions + origin/dest city + postal_code + name + address`. Se due righe coincidono su tutti questi, vengono unite incrementando la quantità.

### Guest vs user cart
- Guest: session-based via `GuestCartController`
- Auth: tabella pivot `cart_user`
Entrambi memorizzano prezzi in cents. Al login avviene il merge automatico del guest cart nel user cart.

---

## Testing

### Playwright test falliscono dopo modifica UI
Aggiorna i baseline visuali con `npx playwright test --update-snapshots`. Vedi `VISUAL_REGRESSION.md`.

### Come runno un singolo test E2E?
```bash
cd nuxt-spedizionefacile-master
npx playwright test tests/e2e/preventivo.spec.ts --headed
```

---

## Git workflow

### Posso amendare un commit?
No, preferisci nuovi commit. `--amend` è usato solo su richiesta esplicita dell'utente. Vedi `CONTRIBUTING.md`.

### Worktree che uso per cosa?
- `main`: lavoro "ufficiale" per feature branch
- `worktree-lavoro`: sandbox per sperimentazione UX (porta 8787)

Non committare in `worktree-lavoro` senza merge deliberato nel main.

---

## Dove guardo se...

| Problema | File di partenza |
|---|---|
| Non riesco a clonare / avviare | `QUICKSTART.md` |
| Non so dove sta una feature | `ARCHITECTURE.md` + `FRONTEND_STRUCTURE.md` / `BACKEND_STRUCTURE.md` |
| Errore runtime non diagnosticato | `DEBUGGING.md` |
| Serve endpoint API | `API_CONTRACT.md` |
| Domanda di design / colori | `DESIGN_SYSTEM.md` |
| Deploy va giù | `DEPLOY.md` + `GOLIVE_CHECKLIST.md` rollback plan |
| Domanda legale / GDPR | `GDPR_COMPLETO.md` + `LEGAL_GOLIVE_CHECKLIST.md` |
| Perché è stato deciso così | `docs/adr/` |
