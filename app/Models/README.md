# Models

Entita' Eloquent — 1 file = 1 tabella DB.

## Convenzioni

- Costanti enum per status (`Order::PENDING`, `Order::PROCESSING`).
- Cast espliciti in `$casts` (es. `'pricing_snapshot' => 'array'`).
- Relations come metodi (`hasMany`, `belongsTo`); niente query inline.
- Scopes per query ricorrenti (`scopePending`, `scopeAwaitingPayment`).

## Modelli principali

- `Order` — ordine cliente con stati spedizione (intoccabile §`CLAUDE.md`)
- `User` — utenti, ruoli (`Cliente`, `Pro`, `Admin`)
- `Cart` / `CartUser` — carrello + pivot articoli
- `Package` / `PackageAddress` — colli + indirizzi
- `WalletMovement` — movimenti portafoglio
- `ReferralCode` / `ReferralReward` — programma referral
