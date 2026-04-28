<?php

namespace App\Services\Brt;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PudoService
{
    private PudoPointMapper $mapper;

    public function __construct(
        private readonly BrtConfig $config,
        ?PudoPointMapper $mapper = null,
    ) {
        $this->mapper = $mapper ?? new PudoPointMapper();
    }

    public function getPudoByAddress(string $address, string $zipCode, string $city, string $countryCode = 'ITA', int $maxResults = 50): array
    {
        $address = trim($address);
        $zipCode = preg_replace('/\D/', '', (string) $zipCode);
        $city = trim($city);
        $maxResults = max(1, min($maxResults, 50));
        $strategyUsed = [];
        $combinedPoints = [];
        $fallbackUsed = false;
        $geocodedSeed = null;

        $merge = function (array $points) use (&$combinedPoints, $maxResults): void {
            if (!empty($points)) $combinedPoints = $this->mapper->mergePudoPoints($combinedPoints, $points, $maxResults);
        };

        // Pass 1: city + ZIP
        if ($city !== '' && $zipCode !== '') {
            $strategyUsed[] = 'city_zip';
            $r = $this->queryPudoByAddressNoFallback($address, $zipCode, $city, $countryCode, $maxResults);
            if (!empty($r['pudo'])) $merge($r['pudo']);
        }

        // Pass 2: city with alternative ZIPs
        if (count($combinedPoints) < $maxResults && $city !== '') {
            $altZips = $this->mapper->resolveAlternativeZipsForCity($city, $zipCode);
            if (!empty($altZips)) {
                $strategyUsed[] = 'city_alt_zip';
                foreach ($altZips as $altZip) {
                    if (count($combinedPoints) >= $maxResults) break;
                    $r = $this->queryPudoByAddressNoFallback($address, $altZip, $city, $countryCode, $maxResults);
                    if (!empty($r['pudo'])) $merge($r['pudo']);
                }
            }
        }

        // Pass 2b: city only
        if (count($combinedPoints) < $maxResults && $city !== '') {
            $strategyUsed[] = 'city_only';
            $r = $this->queryPudoByAddressNoFallback($address, '', $city, $countryCode, $maxResults);
            if (!empty($r['pudo'])) $merge($r['pudo']);
        }

        // Pass 3: ZIP only
        if (count($combinedPoints) < $maxResults && $zipCode !== '') {
            $strategyUsed[] = 'zip_only';
            $r = $this->queryPudoByAddressNoFallback($address, $zipCode, '', $countryCode, $maxResults);
            if (!empty($r['pudo'])) $merge($r['pudo']);
        }

        // Pass 4: nearby from geocoded coordinates
        if (count($combinedPoints) < $maxResults) {
            $geocodedSeed = $this->geocodeInputToCoordinates($address, $city, $zipCode);
            if ($geocodedSeed) {
                $strategyUsed[] = 'nearby_geo_input';
                $nr = $this->getPudoByCoordinates((float) $geocodedSeed['latitude'], (float) $geocodedSeed['longitude'], $maxResults);
                if (!empty($nr['pudo'])) {
                    $merge($nr['pudo']);
                    if (!empty($nr['fallback'])) $fallbackUsed = true;
                }
            }
        }

        // Pass 5: geographic grid around seed
        if (count($combinedPoints) < min($maxResults, 30) && is_array($geocodedSeed) && isset($geocodedSeed['latitude'], $geocodedSeed['longitude'])) {
            $strategyUsed[] = 'nearby_geo_grid';
            foreach ($this->mapper->buildGeoGridSearchPoints((float) $geocodedSeed['latitude'], (float) $geocodedSeed['longitude']) as $gridPoint) {
                if (count($combinedPoints) >= $maxResults) break;
                $gr = $this->getPudoByCoordinates((float) $gridPoint['latitude'], (float) $gridPoint['longitude'], min($maxResults, 30));
                if (!empty($gr['pudo'])) {
                    $merge($gr['pudo']);
                    if (!empty($gr['fallback'])) $fallbackUsed = true;
                }
            }
        }

        // Fallback: local database
        if (empty($combinedPoints)) {
            $fbResult = $this->mapper->getPudoFromDatabase($city, $zipCode, $maxResults);
            if (!empty($fbResult['pudo'])) {
                $merge($fbResult['pudo']);
                $fallbackUsed = true;
                $strategyUsed[] = 'fallback_db';
            }
        }

        $combinedPoints = $this->mapper->filterBrtOnly($combinedPoints);
        $combinedPoints = $this->mapper->sortPudoByDistance($combinedPoints);
        if (count($combinedPoints) > $maxResults) {
            $combinedPoints = array_slice($combinedPoints, 0, $maxResults);
        }

        $meta = [
            'strategy_used' => array_values(array_unique($strategyUsed)),
            'search_passes' => count(array_unique($strategyUsed)),
            'coverage_km' => 80,
            'returned_count' => count($combinedPoints),
            'requested_count' => $maxResults,
            'fallback' => $fallbackUsed,
            'provider' => 'BRT',
        ];

        if (empty($combinedPoints)) {
            return ['success' => false, 'error' => 'Nessun punto PUDO trovato per i dati inseriti.', 'pudo' => [], 'fallback' => $fallbackUsed, 'meta' => $meta];
        }

        return ['success' => true, 'pudo' => $combinedPoints, 'fallback' => $fallbackUsed, 'meta' => $meta];
    }

    public function getPudoByCoordinates(float $latitude, float $longitude, int $maxResults = 50): array
    {
        $maxResults = max(1, min($maxResults, 50));

        try {
            $response = $this->config->pudoClient()
                ->get($this->config->pudoApiUrl . '/pudo/v1/open/pickup/get-pudo-by-lat-lng', [
                    'latitude' => $latitude, 'longitude' => $longitude,
                    'max_pudo_number' => $maxResults, 'maxDistanceSearch' => 50000,
                ]);

            if (!$response->successful()) {
                Log::warning('BRT PUDO coordinates API error', ['status' => $response->status(), 'lat' => $latitude, 'lng' => $longitude]);
                return $this->mapper->getPudoFromDatabaseByCoordinates($latitude, $longitude, $maxResults);
            }

            $pudoList = $response->json()['pudo'] ?? [];
            if (empty($pudoList)) {
                Log::info('BRT PUDO coordinates API returned no results', ['lat' => $latitude, 'lng' => $longitude]);
                return $this->mapper->getPudoFromDatabaseByCoordinates($latitude, $longitude, $maxResults);
            }

            return [
                'success' => true,
                'pudo' => array_map(fn($p) => $this->mapper->mapBrtPudoPoint($p), $pudoList),
                'fallback' => false,
                'meta' => ['strategy_used' => ['nearby_geo'], 'returned_count' => count($pudoList), 'requested_count' => $maxResults, 'fallback' => false, 'provider' => 'BRT'],
            ];
        } catch (\Exception $e) {
            Log::error('BRT PUDO coordinates exception', ['error' => $e->getMessage(), 'lat' => $latitude, 'lng' => $longitude]);
            return $this->mapper->getPudoFromDatabaseByCoordinates($latitude, $longitude, $maxResults);
        }
    }

    public function getPudoDetails(string $pudoId): array
    {
        try {
            $response = $this->config->pudoClient()
                ->get($this->config->pudoApiUrl . '/pudo/v1/open/pickup/get-pudo-details', ['pudoId' => $pudoId]);
            if (!$response->successful()) return ['success' => false, 'error' => 'Errore PUDO details API'];
            return ['success' => true, 'pudo' => $response->json()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function queryPudoByAddressNoFallback(string $address, string $zipCode, string $city, string $countryCode, int $maxResults): array
    {
        try {
            $response = $this->config->pudoClient()
                ->get($this->config->pudoApiUrl . '/pudo/v1/open/pickup/get-pudo-by-address', [
                    'address' => $address, 'zipCode' => $zipCode, 'city' => $city,
                    'countryCode' => $countryCode, 'max_pudo_number' => max(1, min($maxResults, 50)),
                    'maxDistanceSearch' => 80000,
                ]);

            if (!$response->successful()) {
                Log::warning('BRT PUDO API error (no fallback pass)', ['status' => $response->status(), 'city' => $city, 'zip' => $zipCode]);
                return ['success' => false, 'pudo' => []];
            }

            $pudoList = $response->json()['pudo'] ?? [];
            if (empty($pudoList)) return ['success' => true, 'pudo' => []];

            return ['success' => true, 'pudo' => array_map(fn($item) => $this->mapper->mapBrtPudoPoint($item), $pudoList)];
        } catch (\Exception $e) {
            Log::warning('BRT PUDO API exception (no fallback pass)', ['error' => $e->getMessage(), 'city' => $city, 'zip' => $zipCode]);
            return ['success' => false, 'pudo' => []];
        }
    }

    private function geocodeInputToCoordinates(string $address, string $city, string $zipCode): ?array
    {
        try {
            $parts = array_values(array_filter([trim($address), preg_replace('/\D/', '', (string) $zipCode), trim($city), 'Italia'], fn($v) => (string) $v !== ''));
            if (empty($parts)) return null;

            $query = implode(', ', $parts);
            $cacheKey = 'nominatim_' . md5($query);

            return Cache::remember($cacheKey, now()->addHours(24), function () use ($query) {
                // Nominatim ToS: max 1 request per second
                sleep(1);

                $response = Http::timeout(8)->acceptJson()
                    ->withHeaders(['User-Agent' => 'SpedizioneFacile/1.0 (info@spediamofacile.it)'])
                    ->get('https://nominatim.openstreetmap.org/search', ['format' => 'jsonv2', 'limit' => 1, 'q' => $query]);

                if (!$response->successful()) return null;
                $first = is_array($response->json()) ? ($response->json()[0] ?? null) : null;
                if (!$first || !isset($first['lat'], $first['lon']) || !is_numeric($first['lat']) || !is_numeric($first['lon'])) return null;

                return ['latitude' => (float) $first['lat'], 'longitude' => (float) $first['lon']];
            });
        } catch (\Exception $e) {
            Log::debug('PUDO geocode input failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
