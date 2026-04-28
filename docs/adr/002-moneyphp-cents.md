# ADR 002 — Prezzi in cents con moneyphp/money

Data: 2026-04-18
Status: Accepted

## Contesto
Un marketplace di spedizioni opera su cifre piccole ma con arrotondamenti ricorrenti (IVA, sconti, margini partner, wallet, split Stripe Connect). L'uso di `float` PHP introduce errori cumulativi (classico `0.1 + 0.2 !== 0.3`). Opzioni valutate:
- float con arrotondamento manuale a 2 decimali
- `decimal` DB + `number_format` PHP
- `moneyphp/money` -> integer cents + `Money` VO

## Decisione
Tutti gli importi sono memorizzati e manipolati come **interi in centesimi** (EUR). Il VO `MyMoney` (wrapper su `moneyphp/money`) è l'unico punto di creazione, somma, moltiplicazione, formattazione.

## Motivazioni
- Zero errori di floating point: operazioni sempre su integer.
- Stripe API usa nativamente cents -> nessuna conversione a runtime.
- `IntlMoneyFormatter` produce output corretto per locale italiano: `20,00 €` con NBSP (`\u00A0`).
- Immutabilità del VO -> nessun bug da `$total += $qty * $price` distratto.

## Conseguenze
- Convenzione irrevocabile: **backend stores cents, frontend divides by 100 for display**.
- Colonne DB di prezzo: `INT UNSIGNED` NOT NULL (mai DECIMAL).
- Parsing di stringhe formattate richiede regex `/[€\s\u00A0]/g` (il `\u00A0` NBSP NON viene rimosso da `.trim()`).
- Tutti i `formatPrice()` di frontend DEVONO dividere per 100 prima di formattare.
- PR review: segnalare qualunque `float` sui prezzi come bug bloccante.

## Riferimenti
- `apps/api/app/Support/MyMoney.php`
- `apps/web/utils/formatPrice.js`
- Memoria utente: «Backend stores single_price in cents (multiply by 100); frontend formatPrice must divide by 100 before display».
