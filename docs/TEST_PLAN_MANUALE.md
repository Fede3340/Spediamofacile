# Test Plan Manuale — SpediamoFacile V5.1R4

Lista degli scenari da testare manualmente in preview.
Per ciascuno: esito, screenshot, eventuali note.

## Prerequisiti avvio

```bash
# Terminale 1 — Backend Laravel
cd laravel-spedizionefacile-main
php artisan serve  # porta 8000

# Terminale 2 — Frontend Nuxt
cd nuxt-spedizionefacile-master
npx nuxi dev --host 127.0.0.1 --port 8787

# Terminale 3 — Queue worker (opzionale ma consigliato per email/BRT)
cd laravel-spedizionefacile-main
php artisan queue:work
```

URL preview: `http://127.0.0.1:8787`
Admin test: `admin@spediamofacile.it` / `Admin2026!`
Stripe test card 3DS: `4000002500003155`

---

## Fase 1 — Smoke test routes (10 min)

```bash
# Script di smoke (da eseguire con backend + frontend running)
for r in / /carrello /preventivo /contatti /la-tua-spedizione/2 /servizi /faq /chi-siamo /traccia-spedizione /accedi /registrati /guide; do
  curl -s -o /dev/null -w "$r: %{http_code}\n" "http://127.0.0.1:8787$r"
done
```

Tutte devono rispondere `200` o `302` (redirect auth). Se vedi `500` annota quale.

---

## Fase 2 — Funnel ordine (30 min)

### 2.1 Home → Funnel (happy path)
- [ ] Apri `http://127.0.0.1:8787/`
- [ ] Il preventivo rapido è visibile sotto la hero
- [ ] Compila: origine Milano 20100, destinazione Roma 00100, peso 3 kg
- [ ] Premi "Calcola preventivo" → il funnel si apre su step Colli

### 2.2 Multi-collo
- [ ] Nel funnel aggiungi 3 colli con dimensioni diverse
- [ ] Prosegui a Servizi → selezionane almeno 1
- [ ] Prosegui a Indirizzi → compila mittente + destinatario
- [ ] Prosegui a Pagamento → il totale è visibile

### 2.3 PUDO
- [ ] Torna a Indirizzi
- [ ] Cambia destinatario da "domicilio" a "Punto BRT" (PUDO)
- [ ] Seleziona un punto dalla mappa
- [ ] Prosegui al pagamento

### 2.4 Contrassegno + Extra services
- [ ] Torna a Servizi
- [ ] Attiva "Contrassegno" con valore 50€
- [ ] Attiva altri extra (assicurazione, ecc.)
- [ ] Verifica che il totale aggiornato riflette gli extra

### 2.5 **FAQ HOME** — bug fix verificare
- [ ] Scrolla in fondo a home fino alle FAQ
- [ ] Click su FAQ #1 → si apre risposta
- [ ] Click su FAQ #2 → **la FAQ #1 resta aperta** (prima si chiudeva)
- [ ] Click di nuovo su FAQ #1 → si chiude
- [ ] Click su FAQ #2 → resta aperta

✅ Atteso: più FAQ possono stare aperte contemporaneamente.

---

## Fase 3 — Pagamenti (60 min)

### 3.1 Pagamento carta — test happy path
- [ ] Login cliente
- [ ] Crea ordine funnel completo fino a pagamento
- [ ] Scegli "Carta"
- [ ] Inserisci Stripe test card `4242 4242 4242 4242`, scadenza futura, CVV 123
- [ ] Accetta termini e conferma pagamento
- [ ] Verifica: ordine creato + sei in `/account/spedizioni` con l'ordine visibile
- [ ] Verifica: etichetta BRT generata (se Laravel queue worker attivo)

### 3.2 **Pagamento carta 3DS — bug fix verificare**
- [ ] Login cliente
- [ ] Crea ordine funnel
- [ ] Scegli "Carta"
- [ ] Usa test card 3DS `4000 0025 0000 3155`
- [ ] Al 3DS challenge, **aspetta 20 secondi prima di confermare** (simula sessione scaduta)
- [ ] Completa il 3DS
- [ ] Verifica: il pagamento completa senza disconnect
- [ ] Se disconnetti: al re-login trovi l'ordine in `/account/spedizioni`

✅ Atteso: il fix payment draft persistence mantiene l'ordine recuperabile anche in caso di disconnect.

### 3.3 Pagamento bonifico
- [ ] Scegli "Bonifico"
- [ ] Conferma ordine
- [ ] Verifica: email IBAN arriva (check queue worker log)
- [ ] Ordine visibile in "Spedizioni in attesa di bonifico"

### 3.4 Pagamento wallet
- [ ] Admin: carica saldo wallet di un utente test
- [ ] Utente: login + crea ordine + paga con wallet
- [ ] Verifica: saldo scala del totale ordine
- [ ] Verifica: ordine marcato pagato

### 3.5 PayPal (se configurato)
- [ ] Verificare se il metodo PayPal è visibile nel checkout
- [ ] Se stub → marcare per archiviazione post-launch

---

## Fase 4 — Coupon e referral (30 min)

### 4.1 Coupon cliente
- [ ] Admin: crea coupon `TEST10` sconto 10%
- [ ] Cliente: nel checkout applica `TEST10`
- [ ] Totale aggiornato di -10%
- [ ] Completa pagamento → sconto applicato nell'ordine

### 4.2 Referral Partner Pro
- [ ] Admin: crea utente Partner Pro con codice referral `PRO123`
- [ ] Cliente: nel checkout applica `PRO123`
- [ ] Sconto applicato al cliente
- [ ] Dopo pagamento: Partner Pro vede commissione accreditata in `/account/portafoglio`

---

## Fase 5 — Wallet (15 min)

### 5.1 Top-up wallet
- [ ] Cliente: `/account/portafoglio` → Top-up 20€
- [ ] Paga con carta Stripe
- [ ] Saldo aggiornato +20€
- [ ] Movimento registrato

### 5.2 Bonus vs referral prelevabile
- [ ] Verifica che bonus top-up NON sia prelevabile
- [ ] Verifica che referral reward SIA prelevabile
- [ ] Prova richiesta prelievo

---

## Fase 6 — Account cliente e admin (20 min)

### 6.1 Cliente
- [ ] Login + vedere profilo, ordini, spedizioni, carte, portafoglio, fatture, indirizzi, assistenza
- [ ] Aggiungere indirizzo
- [ ] Modificare profilo
- [ ] Cambio password

### 6.2 Admin
- [ ] Login admin
- [ ] Dashboard KPI → numeri visibili
- [ ] `/account/amministrazione/ordini` → lista ordini
- [ ] `/account/amministrazione/spedizioni` → lista spedizioni
- [ ] `/account/amministrazione/utenti` → lista utenti
- [ ] `/account/amministrazione/prezzi` → prezzi per zona
- [ ] Dettaglio ordine admin

---

## Fase 7 — Regressioni visive (30 min)

Con strumenti dev open (Chrome DevTools):

### 7.1 **Cookie banner** — bug fix verificare
- [ ] Home → banner appare in fondo
- [ ] Account → banner appare identico (stessa posizione, stessa grafica)
- [ ] Funnel → banner appare identico
- [ ] Admin → banner appare identico
- [ ] Click "Personalizza" → si apre preferences panel
- [ ] Salva preferenze → banner si chiude

✅ Atteso: stesso aspetto sitewide (prima cambiava visualizzazione tra home/account/funnel).

### 7.2 Breadcrumb
- [ ] `/contatti` → breadcrumb "Home / Contatti" visibile con spaziatura coerente
- [ ] `/servizi` → stesso pattern
- [ ] `/guide` → stesso pattern

### 7.3 Shell account/admin
- [ ] `/account/profilo` → sidebar sinistra + content a destra
- [ ] `/account/amministrazione/ordini` → sidebar con link admin

### 7.4 Route legacy
- [ ] `/preventivo` → redirect a home o pagina legacy funzionante
- [ ] `/checkout` → redirect a funnel step `pagamento`

### 7.5 Mobile
- [ ] Home su viewport 375px (iPhone SE)
- [ ] Funnel su mobile
- [ ] Menu hamburger navigabile

### 7.6 Nessun layout rotto
- [ ] Scroll home → zero artefatti
- [ ] Scroll funnel → animazioni fluide
- [ ] Hover bottoni → transizioni stabili

### 7.7 Nessun mismatch tra locale e preview condivisa
- [ ] Se hai tunnel trycloudflare attivo → confronta stesse pagine
- [ ] Screenshot side-by-side

---

## Fase 8 — Post-pagamento BRT (20 min)

### 8.1 Etichetta
- [ ] Dopo pagamento, apri ordine in `/account/spedizioni/:id`
- [ ] Scarica etichetta BRT (PDF)
- [ ] Verifica dati mittente/destinatario

### 8.2 Documenti
- [ ] Scarica fattura / ricevuta
- [ ] Scarica borderò di consegna

### 8.3 Tracking
- [ ] Pagina `/traccia-spedizione` → inserisci numero tracking dell'ordine
- [ ] Verifica info visibili

---

## Errori noti da NON segnalare come nuovi bug

- **BRT -68 "Unrecognized field"**: bypassato con payload whitelist. Fix reale post-launch con sandbox BRT.
- **`pages/la-tua-spedizione/[step].vue` 1263 LOC**: split rinviato post-launch (alto rischio regressione funnel).
- **PayPal stub**: se non funzionante, marcare per archiviazione post-launch.

---

## Template esito test

Copia questo template per ogni sezione:

```
### Scenario: [nome]
- Data: 2026-04-__
- Esito: ✅ OK / 🟡 Parziale / 🔴 Bloccante
- Screenshot: _LOG/test-manual/[nome].png
- Note: [cosa hai osservato]
```

Salva tutti gli screenshot in `_LOG/test-manual-2026-04-24/`.
