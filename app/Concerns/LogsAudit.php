<?php

/**
 * TRAIT: LogsAudit
 *
 * F14 (audit BRT 2026-04-18) — Helper da agganciare ai controller (specie
 * Admin/*) per scrivere righe di audit con un solo metodo.
 *
 * USO:
 *   class UserManagementController extends Controller {
 *       use \App\Concerns\LogsAudit;
 *
 *       public function approveUser(User $user) {
 *           $user->update(['approved_at' => now()]);
 *           $this->audit('admin.user.approve', $user);
 *           return ...;
 *       }
 *   }
 */

namespace App\Concerns;

use App\Models\AuditLog;
use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;

trait LogsAudit
{
    protected function audit(string $action, ?Model $target = null, array $context = [], array $opts = []): ?AuditLog
    {
        return AuditLogService::log($action, $target, $context, $opts);
    }
}
