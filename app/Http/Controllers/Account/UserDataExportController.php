<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\Gdpr\GdprService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * GET /api/me/export-data — Art. 20 GDPR (portabilita' dati).
 *
 * Endpoint canonico, alias di /api/user/data-export (legacy).
 * A differenza del legacy:
 *   - risponde come download (Content-Disposition: attachment, filename JSON)
 *   - registra una riga in audit_logs (azione gdpr.export.download)
 *   - emette stream per file potenzialmente grandi
 *
 * Riusa la business logic di GdprService::exportUserData(): qui solo wrapping
 * di trasporto + tracciatura.
 */
class UserDataExportController extends Controller
{
    public function __invoke(Request $request, GdprService $gdpr): StreamedResponse
    {
        $user = $request->user();
        $payload = $gdpr->exportUserData($user);
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = 'spediamofacile-export-'.$user->id.'-'.now()->format('Y-m-d').'.json';
        $size = strlen($json);

        AuditLogService::log(
            'gdpr.export.download',
            $user,
            ['size_bytes' => $size, 'filename' => $filename],
        );

        return new StreamedResponse(function () use ($json) {
            echo $json;
        }, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => (string) $size,
            'Cache-Control' => 'no-store',
        ]);
    }
}
