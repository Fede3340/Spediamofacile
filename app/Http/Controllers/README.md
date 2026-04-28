# Controllers

Controller HTTP raggruppati per dominio. Naming: `<Verbo><Risorsa>Controller.php`.

## Domini

- `Auth/` — login, register, password reset, verifica email, OAuth Google
- `Catalog/` — pricing, supplements, promo, services, location lookup
- `Cart/` — carrello utente + guest, validazione articoli
- `Checkout/` — Stripe (PaymentIntent, webhook), bonifico, refund, wallet payment
- `Shipping/` — BRT (etichette, tracking, PUDO), session funnel
- `Account/` — profilo, address, fatture, referral, withdrawals, GDPR
- `Order/` — dettaglio ordine cliente
- `Admin/` — console admin (utenti, ordini, prezzi, contenuti, audit)
- `Communication/` — contact form

I controller di pagamento/spedizione (StripeWebhook, StripeCheckout, BrtController) sono **intoccabili** senza test verdi (vedi `CLAUDE.md`).
