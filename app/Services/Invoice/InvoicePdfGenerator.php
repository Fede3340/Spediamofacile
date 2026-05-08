<?php

namespace App\Services\Invoice;

use App\Models\InvoiceArchive;
use App\Models\Order;
use App\Services\InvoicePdfService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class InvoicePdfGenerator
{
    public function __construct(
        // Servizio raw PDF (gia' esistente in app/Services/InvoicePdfService.php)
        // Usato come fallback quando dompdf NON e' installato.
        private readonly InvoicePdfService $rawPdfService,
    ) {}

    /**
     * Genera (o recupera se gia' esistente) il PDF fattura per l'ordine.
     *
     * @param  Order  $order  Ordine da fatturare. Deve avere subtotal > 0.
     * @return string Path relativo al PDF su Storage (es. "invoices/2026/04/INV-2026-00042.pdf").
     *
     * @throws RuntimeException se subtotal mancante o errore di scrittura su disk.
     */
    public function generate(Order $order): string
    {
        // Carico relazioni minime per il template (eager-loading per evitare N+1).
        $order->loadMissing([
            'user',
            'packages.originAddress',
            'packages.destinationAddress',
            'packages.service',
        ]);

        if (empty($order->getRawOriginal('subtotal'))) {
            throw new RuntimeException("Order #{$order->id}: subtotal mancante o zero, impossibile generare fattura.");
        }

        // Idempotenza: se l'ordine ha gia' un numero fattura E il file esiste, ritornalo.
        $disk = $this->disk();
        if (! empty($order->sdi_invoice_number)) {
            $existingPath = $this->buildPathFromNumber($order->sdi_invoice_number, $order->created_at ?? now());
            if ($disk->exists($existingPath)) {
                return $existingPath;
            }
        }

        // Assegna numero progressivo annuale (transazione + lockForUpdate).
        $issueDate = CarbonImmutable::now();
        $invoiceNumber = $order->sdi_invoice_number ?: $this->reserveInvoiceNumber($issueDate->year);

        // Calcoli fiscali: scorporo IVA (prezzi includono IVA), bollo virtuale.
        $vatRate = (float) config('billing.iva.aliquota', 22);
        $grossTotalCents = $order->grossSubtotalCents();
        $discountCents = $order->discountAmountCents();
        $totalCents = $order->payableTotalCents();
        $imponibileCents = (int) round($totalCents / (1 + $vatRate / 100));
        $ivaCents = $totalCents - $imponibileCents;

        $stampDuty = $this->computeStampDuty($totalCents / 100);

        // Preparo i dati per il template.
        $viewData = [
            'order' => $order,
            'invoice' => [
                'number' => $invoiceNumber,
                'issue_date' => $issueDate,
                'due_date' => $issueDate->addDays((int) config('billing.pagamento.termini_giorni', 0)),
                'order_number' => 'SF-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
            ],
            'cedente' => config('billing.cedente'),
            'pagamento' => config('billing.pagamento'),
            'bollo' => $stampDuty, // ['applicabile' => bool, 'importo' => float, 'nota' => string]
            'totals' => [
                'gross_total_cents' => $grossTotalCents,
                'discount_cents' => $discountCents,
                'imponibile_cents' => $imponibileCents,
                'iva_cents' => $ivaCents,
                'iva_rate' => $vatRate,
                'totale_cents' => $totalCents,
            ],
            // Comodi alias usati dal template legacy resources/views/pdf/invoice.blade.php
            'orderNumber' => 'SF-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
            'subtotalCents' => $totalCents,
            'grossSubtotalCents' => $grossTotalCents,
            'discountCents' => $discountCents,
            'imponibileCents' => $imponibileCents,
            'ivaCents' => $ivaCents,
        ];

        // Genera il binario PDF (dompdf se disponibile, altrimenti raw).
        $pdfBinary = $this->renderPdfBinary($viewData, $order);

        // Persisti su disk: invoices/{year}/{month}/{invoiceNumber}.pdf.
        $relativePath = $this->buildPathFromNumber($invoiceNumber, $issueDate);
        $disk->put($relativePath, $pdfBinary);

        // Salva il numero sull'ordine (campo sdi_invoice_number gia' presente).
        if ($order->sdi_invoice_number !== $invoiceNumber) {
            $order->forceFill(['sdi_invoice_number' => $invoiceNumber])->saveQuietly();
        }

        // Inserisci record in invoice_archive (conservazione decennale).
        $this->archive($order, $invoiceNumber, $issueDate, $relativePath, $pdfBinary);

        return $relativePath;
    }

    /**
     * Restituisce il filesystem (disco Storage) dove salvare i PDF.
     */
    public function disk(): Filesystem
    {
        return Storage::disk((string) config('billing.storage.disk', 'local'));
    }

    /**
     * Costruisce il path relativo del PDF a partire dal numero fattura e dalla data.
     * Es: "invoices/2026/04/INV-2026-00042.pdf"
     */
    public function buildPathFromNumber(string $invoiceNumber, \DateTimeInterface $date): string
    {
        $base = (string) config('billing.storage.base_path', 'invoices');
        $year = $date->format('Y');
        $month = $date->format('m');
        $filename = $invoiceNumber.'.pdf';

        return "{$base}/{$year}/{$month}/{$filename}";
    }

    /**
     * Riserva atomicamente il prossimo numero fattura per l'anno indicato.
     * Schema risultante: "{prefix}-{YYYY}-{NNNNN}", es. "INV-2026-00042".
     *
     * Concorrenza: usa transazione + lockForUpdate sulla riga (prefix, year)
     * della tabella invoice_counters per evitare duplicati in caso di richieste
     * parallele.
     */
    public function reserveInvoiceNumber(int $year): string
    {
        $prefix = (string) config('billing.numerazione.prefix', 'INV');
        $padding = (int) config('billing.numerazione.padding', 5);

        // Reset annuale: se disabilitato, accumuliamo tutto sull'anno 0 (sentinel).
        if (! (bool) config('billing.numerazione.reset_yearly', true)) {
            $year = 0;
        }

        return DB::transaction(function () use ($prefix, $year, $padding) {
            // Inseriamo riga vuota se non esiste (idempotente grazie alla unique).
            DB::table('invoice_counters')->updateOrInsert(
                ['prefix' => $prefix, 'year' => $year],
                ['updated_at' => now()],
            );

            // Lock + lettura del valore corrente.
            $row = DB::table('invoice_counters')
                ->where('prefix', $prefix)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            $next = (int) ($row->last_number ?? 0) + 1;

            DB::table('invoice_counters')
                ->where('prefix', $prefix)
                ->where('year', $year)
                ->update([
                    'last_number' => $next,
                    'updated_at' => now(),
                ]);

            return sprintf(
                '%s-%04d-%0'.$padding.'d',
                $prefix,
                $year > 0 ? $year : date('Y'),
                $next,
            );
        });
    }

    /**
     * Calcola se l'imposta di bollo virtuale e' applicabile e con quale importo.
     * D.P.R. 642/1972: bollo 2,00 EUR per documenti > 77,47 EUR esenti IVA / fuori campo IVA.
     * Per fatture IVA22% standard la nota e' informativa (non addebitata in tabella).
     */
    public function computeStampDuty(float $totalEur): array
    {
        $threshold = (float) config('billing.bollo_virtuale.soglia_eur', 77.47);
        $amount = (float) config('billing.bollo_virtuale.importo_eur', 2.00);
        $showNote = (bool) config('billing.bollo_virtuale.mostra_nota', true);

        $applicable = $totalEur > $threshold;

        return [
            'applicabile' => $applicable,
            'importo' => $applicable ? $amount : 0.0,
            'nota' => ($applicable && $showNote)
                ? "Imposta di bollo assolta in modo virtuale ai sensi del D.M. 17/06/2014 (importo {$amount} EUR)."
                : null,
        ];
    }

    /**
     * Renderizza il PDF: dompdf se disponibile, altrimenti raw via InvoicePdfService.
     */
    private function renderPdfBinary(array $viewData, Order $order): string
    {
        // Tenta dompdf via facade (registrata con auto-discovery se package installato).
        // barryvdh/laravel-dompdf e' opzionale: usiamo riferimenti via stringa per
        // evitare che PHPStan tenti di analizzare la classe quando assente.
        $dompdfFacade = '\\Barryvdh\\DomPDF\\Facade\\Pdf';
        if (class_exists($dompdfFacade)) {
            try {
                $pdf = $dompdfFacade::loadView('invoices.pdf', $viewData)
                    ->setPaper('a4', 'portrait')
                    ->setOptions([
                        'isRemoteEnabled' => false,
                        'isHtml5ParserEnabled' => true,
                        'defaultFont' => 'DejaVu Sans',
                    ]);

                return $pdf->output();
            } catch (\Throwable $e) {
                // Non blocchiamo l'utente: log + fallback raw.
                Log::warning('InvoicePdfGenerator: dompdf failed, falling back to raw renderer', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback: usa il servizio raw esistente (PHP puro, niente dipendenze).
        return $this->rawPdfService->generate($order);
    }

    /**
     * Inserisce il record di conservazione decennale (DM 17/06/2014).
     * Idempotente: se esiste gia' un record con stesso invoice_number, aggiorna il path.
     */
    private function archive(
        Order $order,
        string $invoiceNumber,
        CarbonImmutable $issueDate,
        string $relativePath,
        string $pdfBinary,
    ): void {
        $hash = hash('sha256', $pdfBinary);
        $size = strlen($pdfBinary);
        // Retention: 10 anni dalla data fattura (DM 17/06/2014).
        $retainUntil = $issueDate->addYears(10)->toDateString();

        InvoiceArchive::updateOrCreate(
            [
                'invoice_number' => $invoiceNumber,
                'document_type' => InvoiceArchive::TYPE_RICEVUTA,
            ],
            [
                'order_id' => $order->id,
                'file_path' => $relativePath,
                'mime_type' => 'application/pdf',
                'sha256_hash' => $hash,
                'size_bytes' => $size,
                'invoice_date' => $issueDate->toDateString(),
                'archive_status' => InvoiceArchive::STATUS_ARCHIVED,
                'provider' => null, // archivio locale
                'retain_until' => $retainUntil,
                'metadata' => [
                    'order_id' => $order->id,
                    'subtotal_cents' => (int) $order->getRawOriginal('subtotal'),
                    'cedente_piva' => config('billing.cedente.partita_iva'),
                    'regime_fiscale' => config('billing.regime_fiscale'),
                ],
            ],
        );
    }
}
