<?php

/**
 * ROTTE CONSOLE (console.php)
 *
 * Questo file definisce i comandi che possono essere eseguiti da terminale
 * (riga di comando) e i task pianificati che vengono eseguiti automaticamente.
 *
 * I task pianificati funzionano come una sveglia: ad orari prestabiliti,
 * Laravel esegue automaticamente delle operazioni di manutenzione.
 */

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Comando "inspire": mostra una citazione motivazionale (gia' incluso in Laravel)
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Task pianificato: ogni giorno alle 3:00 di notte, pulisce gli ordini vuoti
// (ordini senza pacchi validi che non servono piu')
Schedule::command('orders:cleanup')->dailyAt('03:00');

// Task pianificato: ogni ora, sincronizza lo stato tracking degli ordini BRT
// (aggiorna ordini in_transit e processing interrogando le API BRT)
Schedule::command('orders:sync-tracking')->hourly();

// Task pianificato: ogni giorno alle 4:00, pulisce gli eventi webhook Stripe
// processati piu' di 7 giorni fa (tabella idempotenza, non serve tenerli a lungo)
Schedule::call(function () {
    \App\Models\StripeWebhookEvent::pruneOlderThan(7);
})->dailyAt('04:00');

// F15 — Email abbandono carrello: ogni 6 ore seleziona utenti con carrello
// abbandonato da almeno 24h e invia reminder con link di ripresa.
Schedule::command('carts:send-abandoned-reminders')->everySixHours();
