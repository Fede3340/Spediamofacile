<?php

/**
 * FILE: config/sentry.php
 * SCOPO: Configurazione Sentry per il backend Laravel SpediamoFacile.
 *
 * COSA FA:
 *   - Invia errori non gestiti al dashboard Sentry in tempo reale.
 *   - Tagga ogni errore con il rilascio (release) per collegarlo al deploy.
 *   - Campiona solo il 10% delle transazioni di performance per contenere i costi.
 *   - Sanitizza i dati personali (PII) prima di inviarli: conforme GDPR.
 *   - Ignora eccezioni "non interessanti" (validation 422, auth 401, not found 404).
 *
 * VARIABILI .ENV RICHIESTE:
 *   SENTRY_LARAVEL_DSN        — DSN progetto (se vuoto, Sentry rimane disattivato)
 *   SENTRY_RELEASE            — GIT SHA o versione deploy (es. "v1.2.3" o "abc1234")
 *   SENTRY_TRACES_SAMPLE_RATE — 0.0-1.0, default 0.1 (10% transazioni)
 *
 * RIFERIMENTI:
 *   https://docs.sentry.io/platforms/php/guides/laravel/configuration/options/
 */

/**
 * Funzione helper: maschera valori di chiavi sensibili in un array
 * ricorsivamente. Tiene la forma dell'array intatta per non rompere i
 * breadcrumb Sentry.
 *
 * NOTA: definita qui a livello di file config — Laravel carica il file ad
 * ogni richiesta ma solo una volta per processo PHP, quindi function_exists
 * evita redeclare se il file venisse re-richiesto in test.
 */
if (!function_exists('sf_sentry_scrub_sensitive')) {
    function sf_sentry_scrub_sensitive(array $data): array
    {
        $sensitiveKeys = [
            'password', 'password_confirmation', 'current_password',
            'token', 'api_key', 'secret', '_token', 'csrf_token',
            'stripe_account_id', 'stripe_secret', 'stripe_webhook_secret',
            'email', 'email_address', 'phone', 'telefono',
            'codice_fiscale', 'partita_iva', 'vat_number', 'fiscal_code',
            'card_number', 'card', 'cvv', 'cvc', 'iban',
            'authorization', 'cookie',
        ];

        foreach ($data as $key => $value) {
            $keyLower = strtolower((string) $key);
            if (in_array($keyLower, $sensitiveKeys, true)) {
                $data[$key] = '[FILTERED]';
                continue;
            }
            if (is_array($value)) {
                $data[$key] = sf_sentry_scrub_sensitive($value);
            }
        }

        return $data;
    }
}

return [

    // DSN progetto Sentry. Se vuoto/null, il pacchetto non invia nulla.
    // Piano go-live: popolato solo in produzione (sviluppo = silenzio).
    'dsn' => env('SENTRY_LARAVEL_DSN'),

    // Ambiente: "production", "staging", "local". Filtra nei dashboard Sentry.
    'environment' => env('APP_ENV', 'production'),

    // Release: permette di correlare un errore al commit/deploy che l'ha
    // introdotto. In deploy CI si passa il GIT SHA abbreviato.
    // Fallback a git HEAD locale se la variabile non e' impostata (utile in dev).
    'release' => env('SENTRY_RELEASE') ?: (trim((string) @shell_exec('git rev-parse --short HEAD 2>/dev/null')) ?: null),

    // Performance monitoring: 10% delle richieste vengono tracciate.
    // A 1.0 rischiamo esaurire la quota free tier e rallentare l'app.
    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.1),

    // Profiling CPU: disattivato per default (costoso e non essenziale).
    // Attivabile via env per debug mirati.
    'profiles_sample_rate' => (float) env('SENTRY_PROFILES_SAMPLE_RATE', 0.0),

    // GDPR: NON inviare PII automaticamente (email utente, IP, cookie).
    // Il middleware SentryContext aggiunge solo ID utente e ruolo.
    'send_default_pii' => false,

    // Breadcrumbs: traccia query SQL e job coda (utile per debug).
    'breadcrumbs' => [
        'logs' => true,
        'cache' => true,
        'livewire' => false,
        'sql_queries' => true,
        'sql_bindings' => false, // BINDING = potenziali PII, mai loggarli
        'queue_info' => true,
        'command_info' => true,
        'http_client_requests' => true,
        'notifications' => true,
    ],

    // Tracing dettagliato: cosa misurare nelle transazioni.
    'tracing' => [
        'queue_job_transactions' => env('SENTRY_TRACE_QUEUE_ENABLED', false),
        'queue_jobs' => true,
        'sql_queries' => true,
        'sql_origin' => true,
        'views' => true,
        'livewire' => false,
        'http_client_requests' => true,
        'redis_commands' => env('SENTRY_TRACE_REDIS_ENABLED', false),
        'redis_origin' => true,
        'cache' => true,
    ],

    // before_send: hook chiamato PRIMA dell'invio di ogni evento.
    // Qui sanitizziamo i dati sensibili per essere conformi GDPR.
    'before_send' => static function (\Sentry\Event $event): ?\Sentry\Event {
        // Rimuovi dai request payload i campi sensibili (password, token, Stripe).
        $request = $event->getRequest();
        if (!empty($request)) {
            $sanitized = $request;

            // Sanitize body data (POST, PUT).
            if (isset($sanitized['data']) && is_array($sanitized['data'])) {
                $sanitized['data'] = sf_sentry_scrub_sensitive($sanitized['data']);
            }

            // Sanitize query string.
            if (isset($sanitized['query_string']) && is_string($sanitized['query_string'])) {
                foreach (['token', 'api_key', 'password', 'secret'] as $key) {
                    $sanitized['query_string'] = preg_replace(
                        "/($key=)[^&]*/i",
                        '$1[FILTERED]',
                        $sanitized['query_string']
                    );
                }
            }

            // Sanitize headers (Authorization, Cookie).
            if (isset($sanitized['headers']) && is_array($sanitized['headers'])) {
                foreach (array_keys($sanitized['headers']) as $headerKey) {
                    $lower = strtolower($headerKey);
                    if (in_array($lower, ['authorization', 'cookie', 'x-csrf-token', 'x-xsrf-token'], true)) {
                        $sanitized['headers'][$headerKey] = '[FILTERED]';
                    }
                }
            }

            $event->setRequest($sanitized);
        }

        // Rimuovi email utente dal contesto user (manteniamo solo id + ruolo).
        $user = $event->getUser();
        if ($user !== null) {
            $user->setEmail(null);
            $user->setIpAddress(null);
        }

        return $event;
    },

    // Integrations: array vuoto = usa le default del pacchetto sentry-laravel.
    'integrations' => [],

    // Eccezioni ignorate: non finiscono in Sentry. Regola d'oro:
    //   - Validation: input utente sbagliato, NON e' un bug.
    //   - Authentication/Authorization: 401/403, gestiti dal frontend.
    //   - NotFoundHttp: 404, spesso bot/scanner.
    //   - HttpResponseException 4xx: redirect/abort volontari.
    // Un 500 VERO (DB down, null pointer) viene sempre inviato.
    'ignore_exceptions' => [
        \Illuminate\Validation\ValidationException::class,
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
        \Illuminate\Http\Exceptions\HttpResponseException::class,
        \Illuminate\Http\Exceptions\ThrottleRequestsException::class,
        \Illuminate\Routing\Exceptions\InvalidSignatureException::class,
    ],

    // Transazioni ignorate (health check, metrics endpoint).
    'ignore_transactions' => [
        '/up',
        '/sanctum/csrf-cookie',
        '/_test-sentry',
    ],

    // In-app paths: codice nostro vs vendor. Sentry mostra stack trace "in-app" diverso.
    'in_app_exclude' => [
        '/vendor',
        '/storage',
    ],
];
