<?php
namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;

use App\Cart\MyMoney;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class InvoiceListController extends Controller
{
    /**
     * GET /api/invoices
     *
     * Ritorna le fatture dell'utente autenticato, ordinate dalla piu' recente.
     * Paginata 20 per pagina via query param ?page=N.
     *
     * Un ordine e' considerato "con fattura" se soddisfa almeno una condizione:
     *   1) sdi_invoice_number IS NOT NULL   (numero fattura SDI assegnato)
     *   2) sdi_sent_at IS NOT NULL          (trasmissione gia' avvenuta)
     *   3) billing_data->type = 'fattura'   (richiesta esplicita di fattura)
     *      oppure pricing_snapshot->billing_type = 'fattura' (snapshot accettato)
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        abort_unless($user !== null, 401, 'Utente non autenticato.');

        $query = Order::query()
            ->where('user_id', (int) $user->id)
            ->where(function ($q): void {
                $q->whereNotNull('sdi_invoice_number')
                    ->orWhereNotNull('sdi_sent_at')
                    // JSON path: billing_data->>type = 'fattura'
                    ->orWhere(function ($inner): void {
                        $inner->whereRaw("json_extract(billing_data, '$.type') = ?", ['fattura'])
                            ->orWhereRaw("json_extract(pricing_snapshot, '$.billing_type') = ?", ['fattura']);
                    });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $orders = $query->paginate(20);

        return InvoiceListItemResource::collection($orders);
    }
}

/**
 * Resource interna: trasforma un Order in un "item fattura" per il frontend.
 *
 * Nota stilistica: vive nello stesso file perche' e' un dettaglio implementativo
 * del controller (progetto ha convenzione Resource separata solo quando la
 * risorsa viene riusata in piu' controller — qui non e' il caso).
 *
 * @mixin \App\Models\Order
 */
class InvoiceListItemResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var Order $order */
        $order = $this->resource;

        // Dati economici: subtotal e' gia' MyMoney via accessor del model.
        // Passiamo da MyMoney::amount() per ottenere i centesimi originali.
        $amountCents = (int) $order->subtotal->amount();
        $amountFormatted = (new MyMoney($amountCents))->formatted();

        // Data emissione: se l'invio SDI e' avvenuto usiamo sdi_sent_at (momento
        // fiscalmente rilevante), altrimenti ripieghiamo su created_at dell'ordine.
        $issuedAt = $order->sdi_sent_at instanceof Carbon
            ? $order->sdi_sent_at
            : $order->created_at;

        return [
            // id: coincide con l'order id. E' volutamente duplicato in `id` e
            // `order_id` per permettere al frontend di trattarli come entita'
            // logicamente distinte (potremmo avere piu' fatture per ordine in futuro).
            'id' => (int) $order->id,
            'order_id' => (int) $order->id,
            'invoice_number' => $order->sdi_invoice_number,
            'issued_at' => $issuedAt?->toIso8601String(),
            'amount_cents' => $amountCents,
            'amount_eur_formatted' => $amountFormatted,
            'sdi_status' => $this->deriveSdiStatus($order),
            'sdi_sent_at' => $order->sdi_sent_at?->toIso8601String(),
            'download_url' => '/api/orders/' . (int) $order->id . '/invoice.pdf',
        ];
    }

    /**
     * Deriva lo stato SDI.
     *
     * - Se il DB ha gia' un valore per sdi_status lo rispettiamo.
     * - Altrimenti lo deriviamo dai timestamp:
     *     nessun invio, nessun numero           → null   (non ancora una fattura)
     *     numero presente ma sdi_sent_at null   → "pending"
     *     sdi_sent_at valorizzato e accepted_at → "accepted"
     *     sdi_sent_at valorizzato e rejected_at → "rejected"
     *     sdi_sent_at valorizzato, nessun esito → "sent"
     */
    private function deriveSdiStatus(Order $order): ?string
    {
        $raw = $order->sdi_status;
        if (filled($raw)) {
            return (string) $raw;
        }

        $sent = $order->sdi_sent_at !== null;
        $hasNumber = filled($order->sdi_invoice_number);

        // Nessuna evidenza SDI → non e' ancora una fattura fiscale.
        if (! $sent && ! $hasNumber) {
            return null;
        }

        // Numero assegnato ma non ancora inviato al provider: pending.
        if (! $sent) {
            return 'pending';
        }

        // Da qui in poi l'ordine e' stato inviato: distinguiamo l'esito.
        if ($order->sdi_accepted_at !== null) {
            return 'accepted';
        }

        if ($order->sdi_rejected_at !== null) {
            return 'rejected';
        }

        return 'sent';
    }
}
