# Components

Componenti Vue raggruppati per area. Configurato `pathPrefix: false` — accessibili col loro nome file (es. `<ServizioGrid>`, non `<ServiziServizioGrid>`).

## Aree

- `auth/` — modal e form login/register/recovery (vedi `autenticazione.css`)
- `shipment/` — funnel preventivo (4 step, progress, modali admin gate)
- `admin/` — console admin (drawer, tabelle, charts)
- `account/` — area cliente (profilo, ordini, fatture, wallet)
- `pudo/` — mappa Leaflet + lista punti BRT
- `cart/`, `checkout/` — carrello + step pagamento
- `layout/` — Navbar, Footer, PublicPageHeader
- `sf/` — primitive UI condivise (button, modal, skeleton, confirm)

## Convenzioni

- `<script setup>` plain (no `lang="ts"`).
- Props in forma runtime: `defineProps({ name: { type, default } })`.
- Niente CSS scoped per classi shared cross-area (vedi `CLAUDE.md` § CSS architecture).
