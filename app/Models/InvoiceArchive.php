<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property string $document_type
 * @property string $file_path
 * @property string $mime_type
 * @property string $sha256_hash
 * @property int $size_bytes
 * @property string|null $invoice_number
 * @property \Illuminate\Support\Carbon|null $invoice_date
 * @property string $archive_status
 * @property string|null $provider
 * @property string|null $provider_reference
 * @property \Illuminate\Support\Carbon|null $retain_until
 * @property array|null $metadata
 * @property-read Order|null $order
 */
class InvoiceArchive extends Model
{
    protected $table = 'invoice_archive';

    protected $fillable = [
        'order_id',
        'document_type',
        'file_path',
        'mime_type',
        'sha256_hash',
        'size_bytes',
        'invoice_number',
        'invoice_date',
        'archive_status',
        'provider',
        'provider_reference',
        'retain_until',
        'metadata',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'retain_until' => 'date',
        'size_bytes' => 'integer',
        'metadata' => 'array',
    ];

    public const TYPE_FATTURA_SDI = 'fattura_sdi';

    public const TYPE_RICEVUTA = 'ricevuta_cortesia';

    public const TYPE_NOTA_CREDITO = 'nota_credito';

    public const STATUS_PENDING = 'pending';

    public const STATUS_ARCHIVED = 'archived';

    public const STATUS_MIGRATED = 'migrated';

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
