# GDPR — Registro Trattamenti + Piano Data Breach

Documento unico di compliance GDPR per SpediamoFacile. Consolida il Registro dei Trattamenti (Art. 30) e il Piano di Notifica Data Breach (Artt. 33-34).

**Riferimento normativo:** Regolamento UE 2016/679 (GDPR) + d.lgs 196/2003 integrato dal d.lgs 101/2018 (Codice Privacy italiano).

> **LEGAL-TODO (go-live blocker):** i dati contrassegnati `[INSERIRE_*]` devono essere compilati dal Titolare prima della messa in produzione. Vedi `docs/LEGAL_GOLIVE_CHECKLIST.md`.

Ultima revisione struttura documento: 18 aprile 2026.

---

# PARTE A — Registro dei Trattamenti (Art. 30 GDPR)

## 1. Titolare del Trattamento

| Campo | Valore |
|---|---|
| Ragione sociale | SpediamoFacile S.r.l. |
| Sede legale | `[INSERIRE_SEDE_LEGALE]` (es. Via Roma 1, 00100 Roma (RM)) |
| P.IVA | `[INSERIRE_PIVA]` (es. IT01234567890) |
| Codice Fiscale | `[INSERIRE_CF]` (se diverso da P.IVA) |
| REA | `[INSERIRE_REA]` (es. RM-1234567) |
| Capitale sociale i.v. | `[INSERIRE_CAPITALE]` |
| PEC | `[INSERIRE_PEC]` |
| Rappresentante legale | `[INSERIRE_RAPPRESENTANTE_LEGALE]` (nome, cognome) |
| Email supporto | info@spediamofacile.it |
| Email DPO / Privacy | privacy@spediamofacile.it |
| DPO (se nominato) | `[INSERIRE_NOME_DPO]` — email: dpo@spediamofacile.it |

> **Nota DPO:** la nomina del DPO (Data Protection Officer) è obbligatoria nei casi previsti dall'Art. 37 GDPR. Per PMI che trattano dati di spedizione (non sensibili su larga scala) la nomina è facoltativa ma consigliata. Finché non nominato, il contatto privacy istituzionale è `privacy@spediamofacile.it`.

---

## 2. Trattamenti documentati

### 2.1 Registrazione utenti

- **Dati trattati:** email, nome, cognome, password (hash bcrypt), tipo account (`privato` / `commerciante`), telefono (cifrato AES-256)
- **Base giuridica:** Art. 6.1.a GDPR (consenso esplicito via checkbox privacy all'iscrizione)
- **Finalità:** creazione e gestione account utente, autenticazione
- **Conservazione:** finché account attivo. Su richiesta cancellazione: anonimizzazione immediata + retention di 10 anni dei soli dati fiscali obbligatori
- **Destinatari:** nessuno (uso interno)

### 2.2 Ordini di spedizione

- **Dati trattati:** indirizzi ritiro/destinazione, contatti destinatario (nome, telefono, email), dettagli pacco (peso, dimensioni, contenuto dichiarato), note consegna
- **Base giuridica:** Art. 6.1.b GDPR (esecuzione contratto)
- **Finalità:** prenotazione, gestione, tracking spedizione
- **Conservazione:** 10 anni (obbligo fiscale Art. 2220 c.c.)
- **Destinatari:** BRT S.p.A. (corriere — sub-processor logistica)

### 2.3 Pagamenti Stripe

- **Dati trattati:** customer_id, payment_intent_id, metodo di pagamento (masked), importi. **Dati carta NON conservati sui nostri server** (PCI-DSS delegato a Stripe).
- **Base giuridica:** Art. 6.1.b GDPR (esecuzione contratto)
- **Finalità:** elaborazione pagamenti, gestione rimborsi
- **Conservazione:** 10 anni (obbligo fiscale)
- **Destinatari:** Stripe Payments Europe Ltd. (PCI-DSS Level 1)
- **DPA:** https://stripe.com/privacy#dpa
- **Trasferimento extra-UE:** Standard Contractual Clauses (SCC)

### 2.4 Email transazionali

- **Dati trattati:** email destinatario, contenuto (conferma ordine, tracking, reset password, notifiche stato)
- **Base giuridica:** Art. 6.1.b GDPR (esecuzione contratto) per le transazionali; Art. 6.1.a (consenso) per newsletter
- **Finalità:** comunicazioni di servizio
- **Conservazione:** log invio 30 giorni (Resend), contenuto email non persistito sui nostri server
- **Destinatari:** Resend (API-based, no SMTP) — sub-processor AWS SES
- **DPA:** https://resend.com/legal/dpa

### 2.5 Cookie essenziali (tecnici)

- **Dati trattati:** session ID, CSRF token, preferenza cookie-consent
- **Base giuridica:** Art. 6.1.f GDPR (legittimo interesse — necessari al funzionamento del sito); esenti da consenso ex Cookie Law (Provv. Garante 2021)
- **Finalità:** autenticazione, sicurezza, preferenze interfaccia
- **Conservazione:** session 120 minuti; consent cookie 12 mesi
- **Destinatari:** nessuno

### 2.6 Cookie analytics (Plausible — cookie-less)

- **Dati trattati:** aggregato anonimo pagine visitate, referrer, user-agent, nessun cookie persistente
- **Base giuridica:** Art. 6.1.f GDPR (legittimo interesse) + esenzione consenso per analytics pseudonimizzate ex Provv. Garante 10/06/2021
- **Finalità:** statistiche aggregate di utilizzo
- **Conservazione:** 14 mesi
- **Destinatari:** Plausible Analytics (hosting UE, GDPR-compliant by design)

### 2.7 Google Analytics 4 (fallback — solo se consenso esplicito)

- **Dati trattati:** client_id, eventi navigazione, IP anonimizzato (IP-anonymization ON)
- **Base giuridica:** Art. 6.1.a GDPR (consenso esplicito via banner cookie — opt-in)
- **Finalità:** misurazione audience dettagliata (solo con consenso)
- **Conservazione:** 14 mesi (impostazione GA4)
- **Destinatari:** Google Ireland Ltd. (sub-processor Google LLC, USA)
- **DPA:** https://business.safety.google/adsprocessorterms/
- **Trasferimento extra-UE:** EU-US Data Privacy Framework + SCC
- **Nota:** attivo **solo** se utente accetta cookie analytics nel banner. Default: OFF.

### 2.8 Partner BRT (sub-processor logistica)

- **Dati trattati:** tutti i dati della spedizione (2.2) comunicati via API per l'esecuzione del trasporto
- **Base giuridica:** Art. 6.1.b GDPR (esecuzione contratto)
- **Finalità:** ritiro, trasporto, consegna pacchi
- **Conservazione:** secondo policy BRT (riferimento DPA sottoscritto)
- **Destinatari:** BRT S.p.A. (Italia, responsabile del trattamento ex Art. 28)
- **DPA:** `[INSERIRE_URL_DPA_BRT]` (contratto di nomina a responsabile sottoscritto)

---

## 3. Sub-processor & DPA

| Fornitore | Servizio | DPA | Paese |
|---|---|---|---|
| Stripe Payments Europe | Pagamenti | https://stripe.com/privacy#dpa | IE/US (SCC) |
| BRT S.p.A. | Logistica | `[INSERIRE_URL_DPA_BRT]` | IT |
| Resend | Email transazionali | https://resend.com/legal/dpa | US (SCC, sub: AWS SES) |
| Render | Hosting applicazione | https://render.com/legal/dpa | US (SCC) |
| Backblaze B2 | Backup DB (S3-compatible) | https://www.backblaze.com/b2/docs/gdpr.html | US/EU |
| Sentry | Error monitoring | https://sentry.io/legal/dpa/ | US (SCC) |
| Plausible Analytics | Analytics cookie-less | https://plausible.io/dpa | EU (DE) |
| Google Ireland | GA4 (fallback consent) | https://business.safety.google/adsprocessorterms/ | IE (sub US, DPF) |

---

## 4. Retention (tempi di conservazione)

| Categoria dati | Retention |
|---|---|
| Account utente | Indefinito, fino a richiesta cancellazione (anonimizzazione post-delete) |
| Ordini / fatture | 10 anni (obbligo fiscale Art. 2220 c.c.) |
| Log server applicazione | 30 giorni |
| Session cookie | 120 minuti |
| Cookie consent | 12 mesi |
| Analytics (Plausible / GA4) | 14 mesi |
| Backup DB (Backblaze lifecycle) | 30 giorni |
| Log invio email (Resend) | 30 giorni |

---

## 5. Misure di Sicurezza (Art. 32 GDPR)

- Password: hash bcrypt (cost factor 12)
- Telefono utente: cifratura AES-256 (Laravel `Crypt`)
- Stripe secret key: cifratura AES-256 a riposo
- HTTPS obbligatorio sitewide (HSTS)
- CSP headers (Content-Security-Policy)
- Rate limiting su tutti gli endpoint pubblici
- Soft delete + anonimizzazione alla cancellazione
- Backup cifrati con retention 30 giorni
- Accesso al DB limitato via VPN / Render private networking
- Log accessi amministrativi
- Aggiornamenti di sicurezza periodici delle dipendenze

---

## 6. Diritti degli Interessati (Artt. 15-22 GDPR)

Tutti implementati via `GdprController` + UI account:

| Diritto | Articolo | Endpoint | Tempo di risposta |
|---|---|---|---|
| Accesso | Art. 15 | `GET /api/user/data-export` | entro 30 giorni (Art. 12.3) |
| Rettifica | Art. 16 | `PUT /api/users/{id}` | entro 30 giorni |
| Cancellazione (oblio) | Art. 17 | `DELETE /api/user/account` | entro 30 giorni |
| Limitazione | Art. 18 | richiesta via email privacy@ | entro 30 giorni |
| Portabilità | Art. 20 | `GET /api/user/data-export` (formato JSON) | entro 30 giorni |
| Opposizione | Art. 21 | richiesta via email privacy@ + cookie banner | entro 30 giorni |
| Revoca consenso cookie | Art. 7.3 | bottone "Gestisci cookie" nel footer | immediato |
| Reclamo al Garante | — | https://www.garanteprivacy.it/ | — |

---

# PARTE B — Piano di Notifica Data Breach (Artt. 33-34 GDPR)

## Procedura

### Fase 1 — Rilevamento (0-4 ore)

1. Identificare la natura della violazione
2. Valutare i dati coinvolti (tipo, quantità, soggetti)
3. Contenere la violazione (blocco accesso, reset password)

### Fase 2 — Valutazione (4-24 ore)

1. Determinare il rischio per i diritti degli interessati
2. Documentare la violazione nel registro interno

### Fase 3 — Notifica al Garante (entro 72 ore)

1. Se rischio per diritti e libertà: notifica al Garante Privacy
2. Contenuto: natura violazione, dati coinvolti, conseguenze, misure adottate
3. Contatto: protocollo@gpdp.it

### Fase 4 — Notifica agli interessati (senza ritardo)

1. Se rischio elevato: comunicazione diretta agli utenti interessati
2. Via email: descrizione violazione + azioni consigliate
3. Via sito: banner informativo se necessario

---

## Registro Violazioni

Ogni violazione viene registrata con: data, natura, dati coinvolti, conseguenze, misure adottate, notifiche effettuate.

Termini GDPR:
- Notifica al Garante: entro **72 ore** dalla conoscenza del breach (Art. 33)
- Notifica agli interessati: senza ingiustificato ritardo se rischio elevato (Art. 34)

---

# PARTE C — Revisione del Registro

Il presente registro va rivisto:
- Ad ogni modifica dei trattamenti (nuovi fornitori, nuove finalità)
- Ad ogni cambio di sub-processor
- Con cadenza annuale obbligatoria
- A seguito di incidenti di sicurezza

Responsabile revisione: Titolare / DPO (se nominato).
