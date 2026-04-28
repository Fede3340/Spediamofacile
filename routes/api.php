<?php

/**
 * FILE: api.php (Rotte API — Loader)
 *
 * SCOPO:
 *   Punto di ingresso per tutte le rotte API. Carica i file modulari
 *   dalla directory routes/api/ per mantenere il codice organizzato.
 *
 * MODULI:
 *   - auth.php      → Autenticazione, OAuth, verifica email, password reset
 *   - shipment.php   → Sessione preventivo, localita', tracking, BRT PUDO
 *   - cart.php       → Carrello ospite e utente, pacchi
 *   - orders.php     → Ordini, rimborsi, esecuzione spedizione, BRT gestione
 *   - payments.php   → Stripe pagamenti/carte, portafoglio virtuale
 *   - community.php  → Referral, notifiche, prelievi, Partner Pro, contatti
 *   - admin.php      → Pannello amministrazione
 *   - public.php     → Contenuti pubblici (guide, servizi), GDPR
 *   - invoices.php   → Fatture PDF (M10 — InvoicePdfGenerator)
 *
 * COME FUNZIONA IL MIDDLEWARE "statefulApi":
 *   Configurato in bootstrap/app.php con $middleware->statefulApi().
 *   Quando una richiesta arriva da un dominio "stateful" (definito in SANCTUM_STATEFUL_DOMAINS
 *   nel file .env), Sanctum aggiunge automaticamente: EncryptCookies, StartSession,
 *   ValidateCsrfToken, AuthenticateSession.
 *
 * PERCHE' TUTTE LE ROTTE API SONO IN api.php (e non in web.php):
 *   Tutte le rotte usano lo stesso middleware stack "statefulApi" cosi' la sessione
 *   e' condivisa. In precedenza login/registrazione erano in web.php con middleware
 *   "web" diverso, causando errori "Unauthenticated" perche' le sessioni non combaciavano.
 */

$routeDir = __DIR__ . '/api';

require $routeDir . '/auth.php';
require $routeDir . '/shipment.php';
require $routeDir . '/cart.php';
require $routeDir . '/orders.php';
require $routeDir . '/payments.php';
require $routeDir . '/community.php';
require $routeDir . '/admin.php';
require $routeDir . '/public.php';
require $routeDir . '/invoices.php';
