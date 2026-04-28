<?php

/**
 * MODEL: AuditLog
 *
 * F14 (audit BRT 2026-04-18) — Riga di registro attivita' utenti/admin.
 *
 * USO TIPICO (NON usare direttamente, preferire AuditLogService):
 *   AuditLogService::log('order.refund', $order, ['amount_cents' => 1500]);
 *
 * REGOLE:
 *   - Mai update/delete da codice applicativo: i record sono immutabili.
 *   - Retention: 24 mesi (job di cleanup futuro). GDPR Art. 5(1)(e).
 *   - PII: NON loggare password/secret/PAN. Loggare ID + delta non-sensibili.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'actor_type',
        'action',
        'target_type',
        'target_id',
        'ip',
        'user_agent',
        'context',
        'created_at',
    ];

    protected $casts = [
        'context' => AsArrayObject::class,
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
