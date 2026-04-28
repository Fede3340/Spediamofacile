<?php

namespace App\Services\Catalog;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Fallback live Photon (OpenStreetMap) per ricerca località fuori dal DB locale.
 *
 * - Gratuito, nessuna API key
 * - Aggiornato continuamente da OSM
 * - Cache 24h per query+countryCode
 * - Timeout 4s
 *
 * Estratto da LocationController per ridurre il file principale a < 400 LOC
 * e isolare la logica esterna API.
 */
class PhotonLocationFallback
{
    /** @var string[] Paesi serviti commercialmente da SpediamoFacile (whitelist BRT). */
    public const ALLOWED_COUNTRIES = [
        'IT', 'AT', 'BE', 'BG', 'CH', 'CZ', 'DE', 'DK', 'EE', 'ES',
        'FI', 'FR', 'GB', 'GR', 'HR', 'HU', 'LT', 'LU', 'LV', 'NL',
        'PL', 'PT', 'RO', 'SE', 'SI', 'SK',
    ];

    /**
     * Cerca località su Photon e ritorna risultati normalizzati come stdClass
     * compatibili con il resto del LocationController.
     *
     * @return list<\stdClass> con campi postal_code, place_name, province, country_code
     */
    public function search(string $query, ?string $countryCode = null): array
    {
        $cacheKey = 'loc:photon:'.md5(mb_strtolower(trim($query)).'|'.($countryCode ?? ''));

        return Cache::remember($cacheKey, 86400, function () use ($query, $countryCode) {
            try {
                // verify=false su dev Windows dove manca il CA bundle cURL.
                // In produzione Linux/Docker la verify SSL è attiva di default.
                $verify = config('app.env') === 'production';
                $response = Http::timeout(4)
                    ->withOptions(['verify' => $verify])
                    ->get('https://photon.komoot.io/api/', [
                        'q' => $query,
                        'limit' => 20,
                        'lang' => 'en',
                    ]);

                if (! $response->ok()) {
                    return [];
                }

                $features = $response->json('features', []);
                $results = [];
                $seen = [];

                foreach ($features as $feature) {
                    $props = $feature['properties'] ?? [];
                    $cc = strtoupper((string) ($props['countrycode'] ?? ''));

                    if (! in_array($cc, self::ALLOWED_COUNTRIES, true)) {
                        continue;
                    }

                    if ($countryCode && $cc !== strtoupper($countryCode)) {
                        continue;
                    }

                    $postcode = trim((string) ($props['postcode'] ?? ''));
                    $placeName = trim((string) ($props['name'] ?? $props['city'] ?? ''));

                    if (! $postcode || ! $placeName) {
                        continue;
                    }

                    $osmKey = $props['osm_key'] ?? '';
                    $osmValue = $props['osm_value'] ?? '';
                    $accepted = ($osmKey === 'place' && in_array($osmValue, ['city', 'town', 'village', 'municipality', 'suburb', 'hamlet'], true))
                        || ($osmKey === 'boundary' && $osmValue === 'administrative');
                    if (! $accepted) {
                        continue;
                    }

                    $dedupKey = $postcode.'|'.mb_strtolower($placeName);
                    if (isset($seen[$dedupKey])) {
                        continue;
                    }
                    $seen[$dedupKey] = true;

                    $obj = new \stdClass();
                    $obj->postal_code = $postcode;
                    $obj->place_name = $placeName;
                    $obj->province = trim((string) ($props['state'] ?? $props['county'] ?? ''));
                    $obj->country_code = $cc;
                    $results[] = $obj;
                }

                return $results;
            } catch (\Throwable $e) {
                return [];
            }
        });
    }
}
