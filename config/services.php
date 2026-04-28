<?php

/**
 * CONFIGURAZIONE SERVIZI ESTERNI (services.php)
 *
 * Questo file contiene le credenziali e le impostazioni per tutti i servizi
 * esterni usati dal sito. Le credenziali vere sono salvate nel file .env
 * (che NON viene mai condiviso pubblicamente per sicurezza).
 *
 * La funzione env('NOME', 'default') legge il valore dal file .env.
 * Se il valore non esiste nel .env, usa il valore predefinito.
 *
 * Servizi configurati:
 * - Postmark: servizio per inviare email
 * - SES (Amazon): altro servizio per inviare email
 * - Slack: per notifiche interne del team
 * - Google: per il login con Google (OAuth)
 * - Stripe: per i pagamenti con carta di credito
 * - BRT: per le spedizioni con il corriere BRT
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    // Postmark - servizio di invio email professionale
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    // Amazon SES (Simple Email Service) - altro servizio email
    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Slack - per inviare notifiche al team tramite bot
    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Google OAuth - permette agli utenti di registrarsi/loggarsi con il loro account Google
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),           // ID dell'app su Google
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),    // Chiave segreta dell'app Google
        'redirect' => env('GOOGLE_REDIRECT_URI'),          // URL dove Google rimanda dopo il login
    ],

    // -- ARCHIVIATO 2026-04-24-v2 -- Facebook OAuth rimosso (manteniamo solo Google).
    // -- ARCHIVIATO 2026-04-24-v2 -- File archiviato in _archive/cleanup-2026-04-24-v2/oauth-extra-providers/
    // -- ARCHIVIATO 2026-04-24-v2 -- 'facebook' => [
    // -- ARCHIVIATO 2026-04-24-v2 --     'client_id' => env('FACEBOOK_CLIENT_ID'),
    // -- ARCHIVIATO 2026-04-24-v2 --     'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
    // -- ARCHIVIATO 2026-04-24-v2 --     'redirect' => env('FACEBOOK_REDIRECT_URI'),
    // -- ARCHIVIATO 2026-04-24-v2 -- ],

    // -- ARCHIVIATO 2026-04-24-v2 -- Apple OAuth rimosso (manteniamo solo Google).
    // -- ARCHIVIATO 2026-04-24-v2 -- File archiviato in _archive/cleanup-2026-04-24-v2/oauth-extra-providers/
    // -- ARCHIVIATO 2026-04-24-v2 -- 'apple' => [
    // -- ARCHIVIATO 2026-04-24-v2 --     'client_id' => env('APPLE_CLIENT_ID'),
    // -- ARCHIVIATO 2026-04-24-v2 --     'client_secret' => env('APPLE_CLIENT_SECRET'),
    // -- ARCHIVIATO 2026-04-24-v2 --     'redirect' => env('APPLE_REDIRECT_URI'),
    // -- ARCHIVIATO 2026-04-24-v2 --     'team_id' => env('APPLE_TEAM_ID'),
    // -- ARCHIVIATO 2026-04-24-v2 --     'key_id' => env('APPLE_KEY_ID'),
    // -- ARCHIVIATO 2026-04-24-v2 --     'private_key' => env('APPLE_PRIVATE_KEY'),
    // -- ARCHIVIATO 2026-04-24-v2 -- ],

    // Stripe - sistema di pagamento con carta di credito
    'stripe' => [
        'key' => env('STRIPE_KEY'),                        // Chiave pubblica (visibile nel frontend)
        'secret' => env('STRIPE_SECRET'),                  // Chiave segreta (solo backend)
        'client_id' => env('STRIPE_CLIENT_ID'),            // ID client per Stripe Connect
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET')   // Segreto per verificare i webhook
    ],

    // Referral / coupon preview - percentuale sconto usata dal boundary preview condiviso
    'referral' => [
        'discount_percent' => env('REFERRAL_DISCOUNT_PERCENT', 5),
    ],

    // -- ARCHIVIATO 2026-04-20 -- SDI (Sistema di Interscambio) - fatturazione elettronica italiana
    // -- ARCHIVIATO 2026-04-20 -- Modulo archiviato in _archive/2026-04-20-features-rimosse/sdi-fatturazione/
    // -- ARCHIVIATO 2026-04-20 -- 'sdi' => [
    // -- ARCHIVIATO 2026-04-20 --     'provider' => env('SDI_PROVIDER', 'null'),
    // -- ARCHIVIATO 2026-04-20 --     'cedente' => [
    // -- ARCHIVIATO 2026-04-20 --         'company_name' => env('SDI_CEDENTE_COMPANY', 'SpediamoFacile S.r.l.'),
    // -- ARCHIVIATO 2026-04-20 --         'vat_number' => env('SDI_CEDENTE_VAT', '00000000000'),
    // -- ARCHIVIATO 2026-04-20 --         'fiscal_code' => env('SDI_CEDENTE_FISCAL_CODE'),
    // -- ARCHIVIATO 2026-04-20 --         'address' => env('SDI_CEDENTE_ADDRESS', 'Via Esempio 1'),
    // -- ARCHIVIATO 2026-04-20 --         'postal_code' => env('SDI_CEDENTE_CAP', '20100'),
    // -- ARCHIVIATO 2026-04-20 --         'city' => env('SDI_CEDENTE_CITY', 'Milano'),
    // -- ARCHIVIATO 2026-04-20 --         'province' => env('SDI_CEDENTE_PROVINCE', 'MI'),
    // -- ARCHIVIATO 2026-04-20 --         'country' => env('SDI_CEDENTE_COUNTRY', 'IT'),
    // -- ARCHIVIATO 2026-04-20 --         'regime_fiscale' => env('SDI_CEDENTE_REGIME', 'RF01'),
    // -- ARCHIVIATO 2026-04-20 --     ],
    // -- ARCHIVIATO 2026-04-20 --     'fic' => [
    // -- ARCHIVIATO 2026-04-20 --         'api_token' => env('FIC_API_TOKEN'),
    // -- ARCHIVIATO 2026-04-20 --         'company_id' => env('FIC_COMPANY_ID'),
    // -- ARCHIVIATO 2026-04-20 --     ],
    // -- ARCHIVIATO 2026-04-20 -- ],

    // SMS - provider pluggable (default: null = nessun invio reale, solo log).
    // Per attivare Twilio: SMS_DRIVER=twilio + TWILIO_ACCOUNT_SID + TWILIO_AUTH_TOKEN + TWILIO_FROM in .env
    // Numero di default normalizzato con prefisso +39 (Italia) se mancante.
    'sms' => [
        'driver' => env('SMS_DRIVER', 'null'),               // "null" oppure "twilio"
        'default_country_code' => env('SMS_DEFAULT_COUNTRY_CODE', '+39'),
        'twilio' => [
            'sid' => env('TWILIO_ACCOUNT_SID'),               // Account SID Twilio (ACxxx...)
            'token' => env('TWILIO_AUTH_TOKEN'),              // Auth Token Twilio
            'from' => env('TWILIO_FROM'),                     // Sender ID o numero E.164 acquistato
        ],
    ],

    // -- ARCHIVIATO 2026-04-20 -- PUSH - Web Push (VAPID) per notifiche su PWA installate.
    // -- ARCHIVIATO 2026-04-20 -- Modulo archiviato in _archive/2026-04-20-features-rimosse/pwa-push/
    // -- ARCHIVIATO 2026-04-20 -- 'push' => [
    // -- ARCHIVIATO 2026-04-20 --     'vapid' => [
    // -- ARCHIVIATO 2026-04-20 --         'subject' => env('VAPID_SUBJECT', 'mailto:supporto@spediamofacile.it'),
    // -- ARCHIVIATO 2026-04-20 --         'public_key' => env('VAPID_PUBLIC_KEY'),
    // -- ARCHIVIATO 2026-04-20 --         'private_key' => env('VAPID_PRIVATE_KEY'),
    // -- ARCHIVIATO 2026-04-20 --     ],
    // -- ARCHIVIATO 2026-04-20 --     'ttl' => (int) env('PUSH_TTL', 86400),
    // -- ARCHIVIATO 2026-04-20 -- ],

    // BRT - corriere per le spedizioni
    'brt' => [
        'client_id' => env('BRT_CLIENT_ID'),               // ID cliente BRT
        'password' => env('BRT_PASSWORD'),                  // Password API BRT
        'api_url' => env('BRT_API_URL', 'https://api.brt.it/rest/v1/shipments'), // URL delle API spedizioni
        'pudo_api_url' => env('BRT_PUDO_API_URL', 'https://api.brt.it'),         // URL API punti ritiro
        'pudo_token' => env('BRT_PUDO_TOKEN'),              // Token per le API dei punti ritiro
        'departure_depot' => env('BRT_DEPARTURE_DEPOT', 89), // Filiale BRT di partenza (default: 89 = Milano Bovisa)
        'verify_ssl' => env('BRT_VERIFY_SSL', true),          // Verifica SSL (disabilitare solo in sviluppo)
        'pickup_enabled' => env('BRT_PICKUP_ENABLED', false),  // Attiva solo se BRT ha abilitato l'endpoint ritiro sul contratto
        'pickup_endpoint' => env('BRT_PICKUP_ENDPOINT'),       // Endpoint ritiro esplicito; se vuoto non chiamiamo API non documentate
        // Webhook push tracking
        'webhook_secret' => env('BRT_WEBHOOK_SECRET'),             // Segreto HMAC-SHA256 per verifica firma (prioritario)
        'webhook_allowed_ips' => env('BRT_WEBHOOK_ALLOWED_IPS'),   // IP BRT separati da virgola (fallback se no secret)
    ],

];
