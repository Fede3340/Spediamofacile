# ADR 001 — Autenticazione Sanctum SPA cookie-based

Data: 2026-04-18
Status: Accepted

## Contesto
Serve autenticazione per SPA Nuxt (frontend separato) che parla con backend Laravel. Le opzioni considerate:
- Sanctum SPA cookie-based con XSRF
- Sanctum token-based (Bearer)
- JWT custom
- OAuth2 / Passport

## Decisione
Sanctum SPA cookie-based con cookie `spediamofacile_session` + header `X-XSRF-TOKEN` per protezione CSRF.

## Motivazioni
- Cookie HTTPOnly Secure: nessuna esposizione XSS del token in `localStorage` / `sessionStorage`.
- Stesso dominio apex (`spediamofacile.it` / `api.spediamofacile.it`) -> SameSite=Lax basta, meno complessità.
- Modulo `nuxt-auth-sanctum` gestisce refresh csrf + interceptor pronti.
- Minor superficie di attacco vs JWT (no revoca lato server su token leak).

## Conseguenze
- Tutte le rotte protette richiedono cookie + header XSRF -> necessari i middleware `EnsureFrontendRequestsAreStateful`.
- CORS configurato con `supports_credentials: true` e origin esplicito (nessun `*`).
- Frontend DEVE usare `useSanctumClient()` (composable del modulo) per auto-gestione CSRF, NON `$fetch` raw.
- Session storage Redis in produzione (scalabilità orizzontale).

## Riferimenti
- `apps/api/config/sanctum.php`
- `apps/web/nuxt.config.ts` (sanctum block)
- `docs/API_CONTRACT.md` (sezione Auth)
