<?php
namespace App\Http\Controllers\Shipping;

use App\Http\Controllers\Controller;

use App\Models\Order;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderExportController extends Controller
{
    /**
     * Numero massimo di righe per un singolo export.
     * Protezione contro estrazioni enormi e timeout.
     */
    public const MAX_ROWS = 1000;

    /**
     * Esporta gli ordini in CSV.
     * Ritorna StreamedResponse col Content-Type text/csv.
     */
    public function exportCsv(\App\Http\Requests\ExportOrdersCsvRequest $request): StreamedResponse
    {
        $data = $request->validated();

        $user = $request->user();
        abort_if($user === null, 401, 'Non autenticato.');

        // Costruzione query: l'admin vede tutti gli ordini, l'utente solo i propri.
        $query = Order::query()
            ->with([
                'packages.originAddress:id,name,city,postal_code',
                'packages.destinationAddress:id,name,city,postal_code',
                'packages.service:id,service_type',
            ])
            ->orderByDesc('created_at');

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        if (! empty($data['from'])) {
            $query->whereDate('created_at', '>=', $data['from']);
        }
        if (! empty($data['to'])) {
            $query->whereDate('created_at', '<=', $data['to']);
        }
        if (! empty($data['status'])) {
            $query->where('status', $data['status']);
        }

        $query->limit(self::MAX_ROWS);

        $fileName = 'ordini_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate',
            'Pragma'              => 'no-cache',
        ];

        return new StreamedResponse(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 per far aprire il file correttamente in Excel italiano.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'order_id',
                'data',
                'stato',
                'mittente',
                'destinatario',
                'peso',
                'servizio',
                'totale_eur',
                'tracking_brt',
            ], ';');

            $query->chunk(200, function ($chunk) use ($handle) {
                foreach ($chunk as $order) {
                    fputcsv($handle, $this->formatRow($order), ';');
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Converte un Order in una riga CSV.
     * Il totale e' convertito da centesimi a euro formato italiano.
     *
     * @return array<int,string>
     */
    protected function formatRow(Order $order): array
    {
        $firstPackage = $order->packages->first();

        $sender = $firstPackage?->originAddress
            ? sprintf('%s - %s (%s)',
                $firstPackage->originAddress->name ?? '',
                $firstPackage->originAddress->city ?? '',
                $firstPackage->originAddress->postal_code ?? '')
            : '';

        $recipient = $firstPackage?->destinationAddress
            ? sprintf('%s - %s (%s)',
                $firstPackage->destinationAddress->name ?? '',
                $firstPackage->destinationAddress->city ?? '',
                $firstPackage->destinationAddress->postal_code ?? '')
            : '';

        $weight = $firstPackage?->weight !== null
            ? str_replace('.', ',', (string) $firstPackage->weight)
            : '';

        $service = $firstPackage?->service?->service_type ?? '';

        // Conversione centesimi -> euro formato italiano (es. 1590 -> "15,90").
        $totalCents = (int) ($order->getRawOriginal('subtotal') ?? 0);
        $totalEur   = number_format($totalCents / 100, 2, ',', '.');

        return [
            (string) $order->id,
            $order->created_at?->format('Y-m-d H:i:s') ?? '',
            $order->getStatus((string) ($order->getRawOriginal('status') ?? '')),
            $sender,
            $recipient,
            $weight,
            $service,
            $totalEur,
            $order->brt_tracking_number ?? '',
        ];
    }
}
