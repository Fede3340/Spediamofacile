<?php
namespace App\Models;

use App\Cart\MyMoney;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * @property-read User|null $user
 * @property-read Collection<int, Package> $packages
 * @property-read Collection<int, Transaction> $transactions
 */
class Order extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Campi compilabili dall'esterno.
     * Sono i dati che possono essere inseriti o modificati quando si crea/aggiorna un ordine.
     */
    protected $fillable = [
        'status',                        // Stato dell'ordine (vedi costanti sotto)
        'user_id',                       // ID dell'utente che ha fatto l'ordine
        'subtotal',                      // Totale dell'ordine in centesimi
        'client_submission_id',          // ID submission del client per retry/idempotenza
        'pricing_signature',             // Firma del preventivo accettato
        'pricing_snapshot_version',      // Versione dello snapshot prezzi
        'pricing_snapshot',              // Snapshot prezzi/servizi accettato
        'brt_parcel_id',                 // ID del pacco nel sistema BRT (corriere)
        'brt_numeric_sender_reference',  // Riferimento numerico del mittente per BRT
        'brt_tracking_url',              // Link per seguire la spedizione sul sito BRT
        'brt_pudo_id',                   // ID del punto di ritiro/consegna BRT (se scelto)
        'is_cod',                        // Se true, il pagamento e' in contrassegno (paga il destinatario)
        'cod_amount',                    // Importo da incassare in contrassegno
        'cod_payment_type',              // Tipo pagamento contrassegno BRT: BM, CC, AS
        'cod_incasso_type',              // Modalita incasso destinatario: contanti|assegno (audit F01)
        'insurance_amount_cents',        // Valore dichiarato assicurazione in centesimi (audit F02)
        'brt_error',                     // Eventuale errore nella generazione etichetta BRT
        'brt_tracking_number',           // Numero di tracking BRT (parcelNumberFrom)
        'brt_parcel_number_to',          // Ultimo numero collo (parcelNumberTo) per multi-collo
        'brt_departure_depot',           // Codice deposito BRT di partenza
        'brt_arrival_terminal',          // Codice terminale BRT di arrivo
        'brt_arrival_depot',             // Codice deposito BRT di arrivo
        'brt_delivery_zone',             // Zona di consegna BRT
        'brt_series_number',             // Numero di serie BRT
        'brt_service_type',              // Tipo di servizio BRT (codice API)
        'brt_all_labels',                // JSON etichette individuali per multi-collo
        'brt_raw_response',              // Risposta JSON completa da BRT (per debug)
        // Campi rimborso
        'refund_status',                 // Stato del rimborso (pending, completed, failed, none)
        'refund_amount',                 // Importo rimborsato in centesimi
        'refund_method',                 // Metodo di rimborso (stripe, wallet)
        'refund_reason',                 // Motivo del rimborso
        'refunded_at',                   // Data e ora del rimborso completato
        'cancellation_fee',              // Commissione di annullamento in centesimi (200 = 2 EUR)
        'billing_data',                  // Snapshot dati di fatturazione scelti al checkout
        'brt_last_tracking_check',       // Ultima volta che il tracking è stato sincronizzato
        // Campi fatturazione elettronica SDI
        'sdi_status',                    // pending | sent | accepted | rejected | archived
        'sdi_xml_path',                  // Path XML FatturaPA in storage privato
        'sdi_transmission_id',           // ID trasmissione provider
        'sdi_invoice_number',            // Numero progressivo fattura (es. "2026/00012")
        'sdi_sent_at',                   // Timestamp invio al provider
        'sdi_accepted_at',               // Timestamp ricevuta accettazione SDI
        'sdi_rejected_at',               // Timestamp scarto/rifiuto SDI
        'sdi_last_error',                // Ultimo messaggio di errore SDI
        // Campi ritiro programmato (F04 — audit BRT 2026-04-18)
        'pickup_date',                   // Data ritiro programmata (modificabile finché non ritirato)
        // Campi bonifico bancario (F05 — audit BRT 2026-04-18)
        'bank_transfer_confirmed_at',    // Quando admin ha confermato la ricezione del bonifico
        'bank_transfer_reference',       // Riferimento bonifico (CRO/altro) inserito da admin
        'bank_transfer_confirmed_by',    // User ID admin che ha confermato
        // SICUREZZA: i seguenti campi NON sono in $fillable (assegnare con $order->campo = valore):
        // - payment_method: metodo di pagamento (stripe, wallet) — impostato solo dal server
        // - stripe_payment_intent_id: ID PaymentIntent Stripe — impostato solo dal server
    ];

    // Converte automaticamente i campi nei tipi corretti
    protected $casts = [
        'subtotal' => 'integer',             // Centesimi: 890 = 8,90 EUR
        'is_cod' => 'boolean',
        'cod_amount' => 'integer',           // Centesimi contrassegno
        'insurance_amount_cents' => 'integer', // Centesimi valore assicurato (audit F02)
        'refund_amount' => 'integer',        // Centesimi rimborsati
        'cancellation_fee' => 'integer',     // Commissione annullamento in centesimi
        'pricing_snapshot' => 'array',
        'pricing_snapshot_version' => 'integer',
        'brt_all_labels' => 'array',         // Etichette individuali multi-collo (JSON)
        'brt_raw_response' => 'array',       // Converte JSON in array PHP automaticamente
        'billing_data' => 'array',
        'refunded_at' => 'datetime',
        'pickup_requested_at' => 'datetime',
        'documents_sent_customer_at' => 'datetime',
        'documents_sent_admin_at' => 'datetime',
        'brt_last_tracking_check' => 'datetime',
        'sdi_sent_at' => 'datetime',
        'sdi_accepted_at' => 'datetime',
        'sdi_rejected_at' => 'datetime',
        'pickup_date' => 'date',
        'bank_transfer_confirmed_at' => 'datetime',
    ];

    /* ===== COSTANTI STATI SDI ===== */

    const SDI_PENDING = 'pending';     // XML generato, non ancora inviato

    const SDI_SENT = 'sent';           // Inviato al provider, in attesa ricevuta

    const SDI_ACCEPTED = 'accepted';   // Consegnata al destinatario (RC/MC)

    const SDI_REJECTED = 'rejected';   // Scartata dallo SDI o mancata consegna

    const SDI_ARCHIVED = 'archived';   // Spostata in conservazione sostitutiva

    protected $hidden = [
        'brt_label_base64',
        'brt_raw_response',
        'bordero_document_base64',
        'stripe_payment_intent_id',
        'pricing_signature',
        'pricing_snapshot',
    ];

    // Questi sono gli stati possibili di un ordine:
    const PENDING = 'pending';                // In attesa - l'utente non ha ancora pagato

    const PROCESSING = 'processing';          // In lavorazione - il pagamento e' stato ricevuto

    const PAID = 'paid';                      // Pagato - pagamento confermato (legacy/translation)

    const PAYMENT_FAILED = 'payment_failed';  // Pagamento fallito - qualcosa e' andato storto col pagamento

    const IN_TRANSIT = 'in_transit';          // In transito - pacco ritirato dal corriere, spedizione in corso

    const COMPLETED = 'completed';            // Completato - la spedizione e' stata conclusa

    const DELIVERED = 'delivered';            // Consegnato - il pacco e' stato consegnato

    const IN_GIACENZA = 'in_giacenza';        // In giacenza - il pacco e' in giacenza presso il corriere

    const LABEL_GENERATED = 'label_generated'; // Etichetta generata - etichetta BRT creata ma pacco non ancora ritirato

    const OUT_FOR_DELIVERY = 'out_for_delivery'; // In consegna - pacco in consegna ultimo miglio

    const RETURNED = 'returned';              // Reso - pacco restituito al mittente

    const REFUSED = 'refused';                // Rifiutato - pacco rifiutato dal destinatario

    const CANCELLED = 'cancelled';            // Annullato - l'ordine e' stato annullato dall'utente

    const REFUNDED = 'refunded';              // Rimborsato - il rimborso e' stato completato

    const AWAITING_BANK_TRANSFER = 'awaiting_bank_transfer'; // In attesa di bonifico bancario (F05)

    /**
     * Traduce lo stato dell'ordine dall'inglese all'italiano.
     * Viene usato per mostrare lo stato in modo comprensibile all'utente.
     */
    public function getStatus(string $status): string
    {
        $data = [
            'pending' => 'In attesa',
            'processing' => 'In lavorazione',
            'completed' => 'Completato',
            'payment_failed' => 'Fallito',
            'paid' => 'Pagato',
            'cancelled' => 'Annullato',
            'refunded' => 'Rimborsato',
            'label_generated' => 'Etichetta generata',
            'in_transit' => 'In transito',
            'out_for_delivery' => 'In consegna',
            'delivered' => 'Consegnato',
            'in_giacenza' => 'In giacenza',
            'returned' => 'Reso',
            'refused' => 'Rifiutato',
            'awaiting_bank_transfer' => 'In attesa di bonifico',
        ];

        return $data[$status] ?? $status;
    }

    public function rawStatus(): string
    {
        return (string) $this->getRawOriginal('status');
    }

    public function isAwaitingPayment(): bool
    {
        return in_array($this->rawStatus(), [
            self::PENDING,
            self::PAYMENT_FAILED,
        ], true);
    }

    public function isPostPaymentState(): bool
    {
        return in_array($this->rawStatus(), [
            self::PROCESSING,
            self::COMPLETED,
            self::LABEL_GENERATED,
            self::IN_TRANSIT,
            self::OUT_FOR_DELIVERY,
            self::DELIVERED,
            self::IN_GIACENZA,
            self::RETURNED,
            self::REFUSED,
            self::REFUNDED,
        ], true);
    }

    public function hasSuccessfulTransactionForExternalId(?string $externalId): bool
    {
        if (! filled($externalId)) {
            return false;
        }

        return $this->transactions()
            ->where('ext_id', $externalId)
            ->where('status', 'succeeded')
            ->exists();
    }

    /**
     * Azione automatica: quando viene creato un nuovo ordine,
     * il suo stato iniziale e' sempre "pending" (in attesa di pagamento).
     */
    protected static function booted()
    {
        static::creating(function ($order) {
            if (empty($order->status)) {
                $order->status = self::PENDING;
            }
        });
    }

    /* ===== SCOPES — Query predefinite per stati comuni ===== */

    public function scopePending($query)
    {
        return $query->where('status', self::PENDING);
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', self::PROCESSING);
    }

    public function scopeInTransit($query)
    {
        return $query->where('status', self::IN_TRANSIT);
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', self::DELIVERED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::CANCELLED);
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', self::REFUNDED);
    }

    public function scopeAwaitingPayment($query)
    {
        return $query->whereIn('status', [self::PENDING, self::PAYMENT_FAILED]);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::PROCESSING, self::LABEL_GENERATED, self::IN_TRANSIT,
            self::OUT_FOR_DELIVERY, self::IN_GIACENZA,
        ]);
    }

    /**
     * Quando leggi il subtotale dell'ordine, viene automaticamente
     * convertito in un oggetto MyMoney che gestisce la formattazione
     * dei prezzi (es. da centesimi a euro con virgola).
     */
    public function getSubtotalAttribute($subtotal)
    {
        return new MyMoney($subtotal);
    }

    /**
     * Contesto sconto persistito nello snapshot prezzi dell'ordine.
     *
     * Resta null quando l'ordine non ha coupon/referral applicati in modo canonico.
     */
    public function discountContext(): ?array
    {
        $snapshot = $this->getAttribute('pricing_snapshot');

        if (! is_array($snapshot)) {
            return null;
        }

        $discountContext = $snapshot['discount_context'] ?? null;

        return is_array($discountContext) ? $discountContext : null;
    }

    public function grossSubtotalCents(): int
    {
        return (int) ($this->getRawOriginal('subtotal') ?? $this->getAttributes()['subtotal'] ?? 0);
    }

    public function discountAmountCents(): int
    {
        $discountAmount = $this->discountContext()['discount_amount'] ?? null;

        if (! is_numeric($discountAmount)) {
            return 0;
        }

        return max(0, (int) round(((float) $discountAmount) * 100));
    }

    public function payableTotalCents(): int
    {
        $grossSubtotal = $this->grossSubtotalCents();
        $discountAmount = $this->discountAmountCents();
        $candidate = $this->discountContext()['final_total_raw'] ?? null;

        if (! is_numeric($candidate)) {
            return max(0, $grossSubtotal - $discountAmount);
        }

        $candidateCents = max(0, (int) round(((float) $candidate) * 100));

        if ($candidateCents === 0 && $discountAmount > 0 && $discountAmount < $grossSubtotal) {
            return $grossSubtotal - $discountAmount;
        }

        if ($candidateCents === 0 && $discountAmount === 0) {
            return $grossSubtotal;
        }

        if ($candidateCents > $grossSubtotal) {
            return $grossSubtotal;
        }

        return $candidateCents;
    }

    public function payableTotal(): MyMoney
    {
        return new MyMoney($this->payableTotalCents());
    }

    // Relazione: ogni ordine appartiene a UN utente
    // Cioe' ogni ordine e' stato fatto da una persona specifica
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relazione: un ordine ha MOLTE transazioni di pagamento
    // Esempio: un tentativo fallito e poi uno riuscito
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // Relazione: un ordine contiene MOLTI pacchi
    // La relazione passa per la tabella "package_order" (tabella ponte)
    // che collega ordini e pacchi (relazione molti-a-molti)
    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'package_order')
            ->withPivot('quantity');
    }

    /**
     * Collega un pacco all'ordine tramite la tabella pivot package_order.
     */
    public static function attachPackage(int $orderId, int $packageId, int $quantity = 1): void
    {
        DB::table('package_order')->updateOrInsert(
            [
                'order_id' => $orderId,
                'package_id' => $packageId,
            ],
            [
                'quantity' => $quantity,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }
}
