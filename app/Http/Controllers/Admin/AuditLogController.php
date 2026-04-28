<?php

/**
 * AdminController: AuditLogController
 *
 * F14 (audit BRT 2026-04-18) — endpoint admin per consultazione registro
 * attivita' (lettura + export CSV).
 *
 * ROTTE (registrate in routes/api/admin.php sotto auth:sanctum + CheckAdmin):
 *   GET  /api/admin/audit-logs           — lista paginata con filtri
 *   GET  /api/admin/audit-logs/{id}      — dettaglio singola riga
 *   GET  /api/admin/audit-logs/export    — export CSV del filtro corrente
 *   GET  /api/admin/audit-logs/actions   — lista distinct delle azioni (per filtro UI)
 *
 * SICUREZZA:
 *   - protetto da CheckAdmin (gia' applicato a livello di group)
 *   - rate limit standard Laravel (vedi bootstrap/app.php)
 *   - export CSV cap 10k righe per evitare DoS
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\AuditLog;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    private const EXPORT_MAX_ROWS = 10000;

    /**
     * Lista paginata con filtri.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->buildQuery($request);

        $perPage = (int) min(max((int) $request->input('per_page', 50), 1), 100);

        $page = $query->with('user:id,name,surname,email')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($page);
    }

    /**
     * Dettaglio singola riga.
     */
    public function show(AuditLog $auditLog): JsonResponse
    {
        $auditLog->load('user:id,name,surname,email');
        return response()->json($auditLog);
    }

    /**
     * Lista distinct delle azioni presenti, per popolamento dropdown filtro.
     */
    public function actions(): JsonResponse
    {
        $actions = AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return response()->json(['actions' => $actions]);
    }

    /**
     * Export CSV del filtro corrente. Capped a EXPORT_MAX_ROWS per evitare DoS.
     */
    public function export(Request $request): StreamedResponse
    {
        $admin = $request->user();
        AuditLogService::log('admin.audit_log.export', null, [
            'filters' => $request->only(['action', 'user_id', 'target_type', 'date_from', 'date_to', 'ip']),
        ], ['user' => $admin]);

        $query = $this->buildQuery($request)
            ->with('user:id,name,surname,email')
            ->orderByDesc('created_at')
            ->limit(self::EXPORT_MAX_ROWS);

        $filename = 'audit-log-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            // BOM UTF-8 cosi' Excel apre i caratteri accentati correttamente
            fwrite($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['id', 'created_at', 'action', 'actor_type', 'user_id', 'user_email', 'target_type', 'target_id', 'ip', 'user_agent', 'context']);

            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->id,
                        optional($r->created_at)->toIso8601String(),
                        $r->action,
                        $r->actor_type,
                        $r->user_id,
                        optional($r->user)->email,
                        $r->target_type,
                        $r->target_id,
                        $r->ip,
                        $r->user_agent,
                        $r->context ? json_encode($r->context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Costruisce la query base applicando i filtri.
     */
    private function buildQuery(Request $request)
    {
        return AuditLog::query()
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->input('action')))
            ->when($request->filled('action_like'), fn ($q) => $q->where('action', 'like', '%' . $request->input('action_like') . '%'))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', (int) $request->input('user_id')))
            ->when($request->filled('actor_type'), fn ($q) => $q->where('actor_type', $request->input('actor_type')))
            ->when($request->filled('target_type'), fn ($q) => $q->where('target_type', $request->input('target_type')))
            ->when($request->filled('target_id'), fn ($q) => $q->where('target_id', (int) $request->input('target_id')))
            ->when($request->filled('ip'), fn ($q) => $q->where('ip', $request->input('ip')))
            ->when($request->filled('date_from'), fn ($q) => $q->where('created_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->where('created_at', '<=', $request->input('date_to')));
    }
}
