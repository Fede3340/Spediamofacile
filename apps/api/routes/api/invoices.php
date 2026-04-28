<?php

/**
 * ROTTE FATTURE PDF (M10 — InvoicePdfGenerator) + LISTA FATTURE UTENTE
 *
 * Espone:
 *   - utente proprietario dell'ordine     → GET /api/orders/{order}/invoice.pdf
 *   - amministratore (override owner)     → GET /api/admin/orders/{order}/invoice.pdf
 *   - lista fatture dell'utente loggato   → GET /api/invoices
 *
 * SICUREZZA:
 *   - Tutte le rotte richiedono auth:sanctum (sessione SPA).
 *   - Owner check per i PDF dentro InvoicePdfController::show().
 *   - Route admin protette anche da middleware CheckAdmin.
 *   - Rate limit: 10/min sui PDF (anti scraping), 30/min sulla lista.
 *
 * NB: questo file e' caricato da routes/api.php tramite require.
 */

use App\Http\Controllers\Account\InvoiceListController;
use App\Http\Controllers\Account\InvoicePdfController;
use App\Http\Middleware\CheckAdmin;
use Illuminate\Support\Facades\Route;

// ── Endpoint utente: fattura del proprio ordine ───────────────────────────────
// L'owner check (user_id == order->user_id) e' nel controller, non come gate
// perche' anche l'admin puo' accedere a questa rotta come scorciatoia.
Route::middleware(['auth:sanctum', 'throttle:10,1'])
    ->get('orders/{order}/invoice.pdf', [InvoicePdfController::class, 'show'])
    ->where('order', '[0-9]+');

// ── Endpoint admin: fattura di qualunque ordine ───────────────────────────────
// CheckAdmin garantisce ruolo admin; route prefix esplicito per coerenza con admin.php.
Route::middleware(['auth:sanctum', CheckAdmin::class, 'throttle:10,1'])
    ->get('admin/orders/{order}/invoice.pdf', [InvoicePdfController::class, 'adminShow'])
    ->where('order', '[0-9]+');

// ── Endpoint utente: lista fatture (ordini con evidenza SDI o richiesta fattura) ──
// Rate limit piu' permissivo (30/min): e' una rotta di lettura paginata usata
// dalla pagina /account/fatture, nessun costo esterno da proteggere.
Route::middleware(['auth:sanctum', 'throttle:30,1'])
    ->get('invoices', [InvoiceListController::class, 'index']);
