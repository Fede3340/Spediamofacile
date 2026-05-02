<?php

namespace App\Http\Controllers\Gdpr;

use App\Http\Controllers\Controller;
use App\Http\Requests\CookieConsentRequest;
use App\Services\Gdpr\GdprService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GdprController extends Controller
{
    public function __construct(private readonly GdprService $gdpr) {}

    /** DELETE /api/user/account — Art. 17 GDPR (diritto all'oblio). */
    public function deleteAccount(Request $request): JsonResponse
    {
        $this->gdpr->deleteAccount($request->user(), $request->ip());

        return response()->json([
            'message' => 'Account eliminato con successo. I tuoi dati personali sono stati rimossi.',
        ]);
    }

    /** GET /api/user/data-export — Art. 20 GDPR (portabilita' dati). */
    public function dataExport(Request $request): JsonResponse
    {
        return response()->json($this->gdpr->exportUserData($request->user()));
    }

    /** POST /api/cookie-consent — Art. 7 GDPR (prova del consenso). */
    public function cookieConsent(CookieConsentRequest $request): JsonResponse
    {
        $this->gdpr->recordCookieConsent(
            $request->validated(),
            $request->user()?->id,
            $request->ip(),
            $request->userAgent(),
        );

        return response()->json(['ok' => true]);
    }
}
