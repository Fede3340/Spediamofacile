# Legal Go-Live Checklist — SpediamoFacile

**Scopo:** elenco dei blocker legali (GDPR + business Italia) che devono essere chiusi prima del `GO-LIVE`. Tutti i punti con `[INSERIRE_*]` nel codice devono essere sostituiti con dati reali del Titolare del trattamento.

Riferimenti normativi:
- DPR 633/72 art. 35 (obbligo indicazione P.IVA nelle comunicazioni commerciali)
- GDPR Art. 30 (Registro dei Trattamenti)
- Codice Privacy italiano: d.lgs 196/2003 integrato dal d.lgs 101/2018
- Provvedimento Garante 10/06/2021 (Cookie Law)

---

## Sprint 0 — Blocker assoluti (GO-LIVE bloccato finché tutti "done")

### Dati societari (app.config.ts + backend)
- [ ] **P.IVA reale** inserita in `nuxt-spedizionefacile-master/app.config.ts` → `legal.vatNumber`
- [ ] **Codice Fiscale** (se diverso da P.IVA) in `legal.fiscalCode`
- [ ] **Sede legale reale** (via, CAP, città, provincia) in `legal.registeredOffice`
- [ ] **Numero REA** (iscrizione Camera di Commercio) in `legal.rea`
- [ ] **Capitale sociale i.v.** in `legal.shareCapital`
- [ ] **PEC** (obbligatoria per società) in `legal.pec`
- [ ] **Rappresentante legale** (nome + cognome) in `legal.legalRepresentative`

### Privacy / DPO
- [ ] **Email DPO** (se nominato) oppure email privacy istituzionale in `legal.dpoEmail`
- [ ] **Nome DPO** (se nominato) in `legal.dpoName`
- [ ] Valutazione formale se è **obbligatoria la nomina del DPO** ex Art. 37 GDPR
- [ ] Se DPO nominato: registrare nomina al Garante Privacy (facoltativo ma consigliato)

### Registro Trattamenti (`docs/GDPR_REGISTRO_TRATTAMENTI.md`)
- [ ] Sezione "Titolare del Trattamento" completata con dati reali
- [ ] Tutti 8 trattamenti documentati presenti e verificati:
  1. Registrazione utenti
  2. Ordini di spedizione
  3. Pagamenti Stripe
  4. Email transazionali
  5. Cookie essenziali
  6. Cookie analytics Plausible
  7. Google Analytics 4 (fallback consent)
  8. Partner BRT
- [ ] URL DPA BRT inserito (`[INSERIRE_URL_DPA_BRT]`)
- [ ] Firma DPA Stripe / BRT / Resend / Render / Backblaze / Sentry archiviata
- [ ] Retention documentata per ogni trattamento

### Pagine legali (pubbliche)
- [ ] **Privacy Policy** (`pages/privacy-policy.vue`): Titolare dinamico da `app.config.ts`, sub-processor elencati, tempo risposta 30 giorni citato
- [ ] **Cookie Policy** (`pages/cookie-policy.vue`): Plausible documentato, GA4 fallback documentato, durate cookie specifiche
- [ ] **Termini e Condizioni** (`pages/termini-condizioni.vue`): dati societari coerenti con app.config
- [ ] **Footer** (`components/Footer.vue`): P.IVA + REA + capitale + DPO email visibili

### Cookie banner
- [ ] Banner cookie mostrato al primo accesso (verifica running)
- [ ] Consenso opt-in granulare (analytics avanzati OFF di default)
- [ ] Pulsante "Gestisci cookie" nel footer funzionante
- [ ] GA4 caricato SOLO dopo consenso esplicito analytics

### Audit grep finale (no hardcoded placeholder nel codice pubblico)
- [ ] `grep -r "IT12345678901" nuxt-spedizionefacile-master/pages nuxt-spedizionefacile-master/components` → 0 hit (escluso `placeholder=""` form input)
- [ ] `grep -r "\[DA INSERIRE\]" docs/` → 0 hit
- [ ] `grep -r "\[INSERIRE_" nuxt-spedizionefacile-master/app.config.ts` → solo nei commenti/default; tutti i campi valorizzati con dati reali

---

## Sprint 0.1 — Documenti da produrre (non nel codice, responsabilità utente)

- [ ] **Valutazione impatto privacy (DPIA)** ex Art. 35 GDPR — valutare se necessaria
- [ ] **Nomina responsabili del trattamento** (Art. 28) firmate: BRT, Stripe, Resend, Render, Backblaze, Sentry, Plausible, Google (se GA4)
- [ ] **Informativa dipendenti** (se ci sono) separata dall'informativa clienti
- [ ] **Procedura data breach** (vedi `docs/GDPR_BREACH_NOTIFICATION_PLAN.md`) testata
- [ ] **Registro eventi di sicurezza** configurato (log 30gg)

---

## Sprint 0.2 — Obblighi business Italia

- [ ] Iscrizione Camera di Commercio / Registro Imprese completata
- [ ] PEC attiva e comunicata al Registro Imprese
- [ ] Conto corrente dedicato (se società di capitali)
- [ ] Comunicazione avvio attività (SCIA) se applicabile
- [ ] Adesione SDI (fatturazione elettronica)
- [ ] Se intermediazione spedizioni: verificare iscrizione all'**Albo Autotrasportatori conto terzi** o equivalente per intermediari (consulenza legale)
- [ ] Contratto di agenzia / partnership con BRT firmato

---

## Come validare "done"

Lo sviluppatore deve eseguire, prima di marcare la checklist complete:

```bash
# 1. Nessun placeholder legale residuo nei file pubblici
grep -rn "IT12345678901" nuxt-spedizionefacile-master/pages nuxt-spedizionefacile-master/components
grep -rn "\[INSERIRE_" nuxt-spedizionefacile-master/app.config.ts
grep -rn "\[DA INSERIRE\]" docs/

# 2. Footer mostra dati reali in preview
# 3. Privacy Policy mostra dati reali in preview
# 4. Cookie banner testa GA4 off/on con consenso
```

Output atteso: tutti i grep restituiscono 0 occorrenze rilevanti (le uniche eccezioni accettabili sono `placeholder=""` di input form e commenti esplicativi dei file di config).

---

## Owner

- Sviluppatore: predispone struttura, marker, template (fatto in Sprint 0)
- Titolare del trattamento: compila dati reali, firma DPA, nomina DPO
- DPO (se nominato): revisione finale Registro + Policy prima del go-live
