<?php
namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShipmentStatusUpdateMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Order $order;
    public string $oldStatus;
    public string $newStatus;

    public function __construct(Order $order, string $oldStatus, string $newStatus)
    {
        $this->order = $order;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    /**
     * Configura l'oggetto dell'email.
     * Usa un soggetto specifico per ogni stato rilevante, con fallback generico.
     */
    public function envelope(): Envelope
    {
        $statusSubjects = [
            'label_generated'  => 'Il tuo pacco e\' stato preparato',
            'in_transit'       => 'Il tuo pacco e\' in viaggio',
            'out_for_delivery' => 'Il tuo pacco e\' in consegna',
            'delivered'        => 'Il tuo pacco e\' stato consegnato',
            'in_giacenza'      => 'Il tuo pacco e\' in giacenza',
            'returned'         => 'Il tuo pacco e\' stato restituito',
            'refused'          => 'Il tuo pacco e\' stato rifiutato',
            'cancelled'        => 'Ordine annullato',
            'refunded'         => 'Rimborso elaborato',
        ];

        $subject = isset($statusSubjects[$this->newStatus])
            ? $statusSubjects[$this->newStatus] . ' - SpediamoFacile'
            : 'Aggiornamento spedizione #' . $this->order->id . ' - SpediamoFacile';

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Aggiunge header List-Unsubscribe per conformita' GDPR.
     */
    public function headers(): \Illuminate\Mail\Mailables\Headers
    {
        $unsubscribeUrl = config('app.frontend_url') . '/account/notifiche?unsubscribe=1';

        return new \Illuminate\Mail\Mailables\Headers(
            text: [
                'List-Unsubscribe' => '<' . $unsubscribeUrl . '>',
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            ],
        );
    }

    /**
     * Definisce il contenuto dell'email usando il template "shipment-status".
     * Passa le traduzioni degli stati in italiano alla vista.
     */
    public function content(): Content
    {
        $this->order->loadMissing([
            'packages.originAddress',
            'packages.destinationAddress',
            'user',
        ]);

        $statusLabels = [
            'pending' => 'In attesa',
            'processing' => 'In lavorazione',
            'label_generated' => 'Etichetta generata',
            'completed' => 'Completato',
            'payment_failed' => 'Pagamento fallito',
            'paid' => 'Pagato',
            'cancelled' => 'Annullato',
            'refunded' => 'Rimborsato',
            'in_transit' => 'In transito',
            'out_for_delivery' => 'In consegna',
            'delivered' => 'Consegnato',
            'in_giacenza' => 'In giacenza',
            'returned' => 'Reso al mittente',
            'refused' => 'Rifiutato',
        ];

        return new Content(
            view: 'emails.shipment-status',
            with: [
                'oldStatusLabel' => $statusLabels[$this->oldStatus] ?? $this->oldStatus,
                'newStatusLabel' => $statusLabels[$this->newStatus] ?? $this->newStatus,
            ],
        );
    }

    /**
     * Nessun allegato per l'email di aggiornamento stato.
     */
    public function attachments(): array
    {
        return [];
    }
}
