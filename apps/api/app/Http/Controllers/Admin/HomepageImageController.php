<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Http\Requests\HomepageImageUploadRequest;
use App\Models\Setting;
use App\Services\Security\ImageSanitizer;
use Illuminate\Http\JsonResponse;

class HomepageImageController extends Controller
{
    public function __construct(private readonly ImageSanitizer $sanitizer)
    {
    }

    // Upload immagine homepage
    public function uploadHomepageImage(HomepageImageUploadRequest $request): JsonResponse
    {
        if (
            !$request->hasFile('image') &&
            !$request->filled('config') &&
            !$request->has('desktop') &&
            !$request->has('mobile')
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Nessuna modifica da salvare.',
            ], 422);
        }

        $storedImageUrl = Setting::get('homepage_image_url', '');
        $currentConfig = $this->parseHomepageImageConfig(
            Setting::get('homepage_image_config', ''),
            $storedImageUrl ?: null
        );

        if ($request->hasFile('image')) {
            // Sprint 6.7: sanitize + hash filename + strip EXIF
            $path = $this->sanitizer->sanitizeAndStore(
                $request->file('image'),
                'homepage',
                'public'
            );
            $currentConfig['image_url'] = '/storage/' . $path;
        }

        if ($request->filled('config')) {
            $rawConfig = $request->input('config');
            $decodedConfig = is_string($rawConfig) ? json_decode($rawConfig, true) : $rawConfig;

            if (!is_array($decodedConfig)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configurazione hero non valida.',
                ], 422);
            }

            if (array_key_exists('desktop', $decodedConfig)) {
                $currentConfig['desktop'] = $this->normalizeHomepageViewport(
                    is_array($decodedConfig['desktop']) ? $decodedConfig['desktop'] : [],
                    $currentConfig['desktop']
                );
            }

            if (array_key_exists('mobile', $decodedConfig)) {
                $currentConfig['mobile'] = $this->normalizeHomepageViewport(
                    is_array($decodedConfig['mobile']) ? $decodedConfig['mobile'] : [],
                    $currentConfig['mobile']
                );
            }
        }

        if ($request->has('desktop')) {
            $currentConfig['desktop'] = $this->normalizeHomepageViewport(
                is_array($request->input('desktop')) ? $request->input('desktop') : [],
                $currentConfig['desktop']
            );
        }

        if ($request->has('mobile')) {
            $currentConfig['mobile'] = $this->normalizeHomepageViewport(
                is_array($request->input('mobile')) ? $request->input('mobile') : [],
                $currentConfig['mobile']
            );
        }

        $currentConfig['updated_at'] = now()->toIso8601String();

        Setting::set('homepage_image_url', $currentConfig['image_url']);
        Setting::set('homepage_image_config', json_encode($currentConfig, JSON_UNESCAPED_SLASHES));

        return response()->json([
            'success' => true,
            'image_url' => $currentConfig['image_url'],
            'desktop' => $currentConfig['desktop'],
            'mobile' => $currentConfig['mobile'],
            'updated_at' => $currentConfig['updated_at'],
            'config' => $currentConfig,
            'message' => 'Immagine homepage aggiornata con successo.',
        ]);
    }

    // Recupera l'immagine homepage corrente
    public function getHomepageImage(): JsonResponse
    {
        $url = Setting::get('homepage_image_url', '');
        $config = $this->parseHomepageImageConfig(
            Setting::get('homepage_image_config', ''),
            $url ?: null
        );

        if ($url && $config['image_url'] !== $url) {
            $config['image_url'] = $url;
        }

        return response()->json([
            'image_url' => $config['image_url'],
            'desktop' => $config['desktop'],
            'mobile' => $config['mobile'],
            'updated_at' => $config['updated_at'],
            'config' => $config,
        ]);
    }

    private function defaultHomepageImageConfig(?string $imageUrl = null): array
    {
        return [
            'image_url' => $imageUrl ?: null,
            'desktop' => [
                'mode' => 'fill',
                'zoom' => 1.0,
                'x' => 0.0,
                'y' => 0.0,
            ],
            'mobile' => [
                'mode' => 'fill',
                'zoom' => 1.0,
                'x' => 0.0,
                'y' => 0.0,
            ],
            'updated_at' => now()->toIso8601String(),
        ];
    }

    private function normalizeHomepageViewport(array $input, array $fallback): array
    {
        $mode = in_array($input['mode'] ?? null, ['fill', 'fit', 'crop'], true)
            ? $input['mode']
            : ($fallback['mode'] ?? 'fill');

        $zoomRaw = is_numeric($input['zoom'] ?? null) ? (float) $input['zoom'] : (float) ($fallback['zoom'] ?? 1.0);
        $zoom = max(0.5, min(4.0, $zoomRaw));

        $xRaw = is_numeric($input['x'] ?? null) ? (float) $input['x'] : (float) ($fallback['x'] ?? 0.0);
        $yRaw = is_numeric($input['y'] ?? null) ? (float) $input['y'] : (float) ($fallback['y'] ?? 0.0);

        return [
            'mode' => $mode,
            'zoom' => round(max(0.5, min(4.0, $zoom)), 4),
            'x' => round(max(-500, min(500, $xRaw)), 2),
            'y' => round(max(-500, min(500, $yRaw)), 2),
        ];
    }

    private function parseHomepageImageConfig(?string $rawConfig, ?string $fallbackImageUrl = null): array
    {
        $base = $this->defaultHomepageImageConfig($fallbackImageUrl);

        if (!is_string($rawConfig) || trim($rawConfig) === '') {
            return $base;
        }

        $decoded = json_decode($rawConfig, true);
        if (!is_array($decoded)) {
            return $base;
        }

        return [
            'image_url' => is_string($decoded['image_url'] ?? null) && trim($decoded['image_url']) !== ''
                ? $decoded['image_url']
                : $base['image_url'],
            'desktop' => $this->normalizeHomepageViewport(
                is_array($decoded['desktop'] ?? null) ? $decoded['desktop'] : [],
                $base['desktop']
            ),
            'mobile' => $this->normalizeHomepageViewport(
                is_array($decoded['mobile'] ?? null) ? $decoded['mobile'] : [],
                $base['mobile']
            ),
            'updated_at' => is_string($decoded['updated_at'] ?? null) && trim($decoded['updated_at']) !== ''
                ? $decoded['updated_at']
                : $base['updated_at'],
        ];
    }
}
