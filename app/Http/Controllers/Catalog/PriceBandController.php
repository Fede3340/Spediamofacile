<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkUpdatePriceBandsRequest;
use App\Http\Requests\PromoImageUploadRequest;
use App\Http\Requests\SavePromoSettingsRequest;
use App\Services\Pricing\PriceBandService;
use App\Services\Security\ImageSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PriceBandController extends Controller
{
    public function __construct(
        private readonly PriceBandService $priceBandService,
    ) {}

    // Lista tutte le bande raggruppate per tipo
    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->priceBandService->buildIndexPayload()]);
    }

    // Inizializza le fasce di prezzo con i valori di default (seeder via API)
    public function seed(): JsonResponse
    {
        $this->priceBandService->runSeed();

        return response()->json([
            'success' => true,
            'message' => 'Fasce di prezzo inizializzate con successo.',
        ]);
    }

    // Aggiornamento massivo delle bande di prezzo (compatibilità legacy "bands" + path moderno)
    public function bulkUpdate(Request $request): JsonResponse
    {
        if ($request->has('bands') && ! $request->has('weight') && ! $request->has('volume')) {
            $legacy = $request->validate([
                'bands' => 'required|array|min:1',
                'bands.*.id' => 'required',
                'bands.*.base_price' => 'required|integer|min:0',
                'bands.*.discount_price' => 'nullable|integer|min:1',
                'bands.*.show_discount' => 'sometimes|boolean',
            ]);
            $payload = $this->priceBandService->normalizeLegacyBands($legacy['bands']);
            $hasEurope = false;
        } else {
            $payload = app(BulkUpdatePriceBandsRequest::class)->validated();
            $hasEurope = $request->filled('europe');
        }

        return response()->json([
            'success' => true,
            'message' => 'Fasce di prezzo aggiornate con successo.',
            'data' => $this->priceBandService->applyBulkUpdate($payload, $hasEurope),
        ]);
    }

    // Legge le impostazioni promozionali
    public function getPromoSettings(): JsonResponse
    {
        return response()->json(['data' => $this->priceBandService->loadPromoSettings()]);
    }

    // Salva le impostazioni promozionali
    public function savePromoSettings(SavePromoSettingsRequest $request): JsonResponse
    {
        $this->priceBandService->savePromoSettings($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Impostazioni promozionali salvate con successo.',
        ]);
    }

    // Upload immagine promozionale (Sprint 6.7 security hardening: PromoImageUploadRequest + ImageSanitizer).
    public function uploadPromoImage(PromoImageUploadRequest $request, ImageSanitizer $sanitizer): JsonResponse
    {
        $url = $this->priceBandService->storePromoImage($request->file('image'), $sanitizer);

        return response()->json([
            'success' => true,
            'image_url' => $url,
        ]);
    }
}
