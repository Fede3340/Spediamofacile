# Security policy — SpedizioneFacile backend

Documento di riferimento per le policy di sicurezza applicate al backend
Laravel. Aggiornare a ogni sprint di security hardening.

## Anti-enumeration email (Sprint 6.4)

### Minaccia

Un attaccante che invia richieste a endpoint auth pubblici puo' distinguere
email registrate da email inesistenti osservando:

1. **Risposta HTTP**: status code, messaggio, struttura JSON diversi tra i
   due casi ("email gia' in uso" vs "inviata email di reset").
2. **Tempi di risposta (timing attack)**: il ramo "utente esiste" esegue
   Hash::make / Hash::check / query DB in piu' rispetto al ramo "utente
   non esiste", generando un delta misurabile dall'esterno.

Impatto: l'attaccante costruisce una lista di email valide per credential
stuffing, phishing mirato o brute force password (CVSS 5.3 Medium).

### Endpoint coperti

| Endpoint                   | Controller                            | Policy                                                             |
|----------------------------|---------------------------------------|--------------------------------------------------------------------|
| `POST /api/forgot-password`| `PasswordResetRequestController`      | Messaggio generico + timing jitter 200-300 ms + log selettivo      |
| `POST /api/custom-register`| `CustomRegisterController`            | Messaggio identico per email nuova e duplicata, nessuna mail reale |
| `POST /api/custom-login`   | `LoginController`                     | Dummy `Hash::check` se utente manca + errore generico 422          |

### Pattern di risposta

- **Forgot password**: sempre HTTP 200 con
  `"Se l'email è registrata riceverai un link di reset entro pochi minuti."`
  — formulazione condizionale raccomandata da OWASP, adottata da Stripe,
  Auth0 e Google.

- **Register email duplicata**: HTTP 201 identico alla registrazione nuova,
  `"Registrazione completata! Inserisci il codice di verifica..."`. L'utente
  legittimo non ricevera' mai il codice (non viene creato ne' inviato), quindi
  non puo' completare il secondo flow — ma l'attaccante non puo' distinguere
  questo caso da una registrazione nuova senza accedere alla casella email.

- **Login credenziali non valide**: HTTP 422 `ValidationException` con
  `"Le credenziali non sono corrette."` per entrambi i campi `email` e
  `password`, identico tra "utente non esiste" e "password errata".

### Timing normalization

- **Forgot password**: `PasswordResetRequestController::normalizeResponseTime`
  attende fino a ~200-300 ms dall'inizio della richiesta (jitter casuale via
  `random_int`). Sia il ramo "utente esiste" (costo reale ~150-250 ms per
  `Hash::make` + INSERT + `Mail::send`) che il ramo "utente non esiste" (costo
  reale ~1 ms) terminano nella stessa finestra.

- **Login**: `LoginController::timingSafeDummyHash()` cache-a in memoria un
  bcrypt hash generato alla prima invocazione con il cost configurato
  dall'env. Se l'utente non esiste, viene comunque eseguito `Hash::check`
  contro il dummy, eguagliando il costo del ramo "utente esiste + password
  errata". Il dummy deve condividere lo stesso cost degli hash reali,
  altrimenti reintroduce il signal.

### Logging di audit

Il log del backend NON deve mai creare un oracolo parallelo: non loggare
i tentativi verso email non registrate (sarebbe un canale laterale identico
al timing). Loggiamo solo:

- `Log::info('Password reset email dispatched.', …)` — solo per invii reali.
- `Log::info('Tentativo di registrazione con email duplicata.', …)` — solo
  per duplicati confermati, utile per rilevare credential stuffing. Il log
  include IP ed email, ma non viene esposto via API pubblica.

### Test di regressione

`tests/Feature/Auth/EmailEnumerationSecurityTest.php`:

- `test_forgot_password_same_response_for_existing_and_missing_email`
- `test_forgot_password_same_timing_diff_less_than_50ms`
- `test_register_duplicate_email_no_enumeration`
- `test_login_missing_user_same_response_as_wrong_password`
- `test_login_missing_user_same_timing_as_wrong_password`

Soglia timing: diff medio < 50 ms su 4 campioni. Molto al di sotto della
latenza di rete tipica, quindi non misurabile dall'esterno.

### Rate limiting (gia' presente, mantenuto)

- `/api/forgot-password` → `throttle:5,1`
- `/api/custom-register` → `throttle:5,1`
- `/api/custom-login` → `throttle:10,1`

Il throttle agisce da secondo strato di difesa: l'attaccante non puo' scalare
l'enumeration oltre 5-10 tentativi/minuto/IP, anche se le policy precedenti
fallissero.

### Riferimenti

- OWASP Authentication Cheatsheet, sezione "Authentication responses":
  https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html#authentication-responses
- Stripe Docs — "Sign-up without revealing existing accounts"
- Auth0 Docs — "Account enumeration best practices"
