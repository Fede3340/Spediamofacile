<?php

namespace App\Services\Brt;

use App\Models\Location;
use App\Models\PudoPoint;
use Illuminate\Support\Facades\Log;

class PudoPointMapper
{
    public function mapBrtPudoPoint(array $point): array
    {
        return [
            'pudo_id' => $point['pudoId'] ?? '',
            'carrier_pudo_id' => $point['carrierPudoId'] ?? '',
            'name' => $point['pointName'] ?? '',
            'address' => $point['fullAddress'] ?? trim(($point['street'] ?? '') . ' ' . ($point['streetNumber'] ?? '')),
            'city' => $point['town'] ?? '',
            'zip_code' => $point['zipCode'] ?? '',
            'province' => $point['state'] ?? '',
            'country' => $point['country'] ?? 'ITA',
            'latitude' => $point['latitude'] ?? null,
            'longitude' => $point['longitude'] ?? null,
            'distance_meters' => isset($point['distanceFromPoint']) ? (int) round((float) $point['distanceFromPoint']) : null,
            'enabled' => $point['enabled'] ?? true,
            'opening_hours' => $point['hours'] ?? [],
            'localization_hint' => $point['localizationHint'] ?? '',
            'provider' => 'BRT',
        ];
    }

    public function mapDbPoint(array $p): array
    {
        return [
            'pudo_id' => $p['id'],
            'carrier_pudo_id' => $p['id'],
            'name' => $p['name'],
            'address' => $p['address'],
            'city' => $p['city'],
            'zip_code' => $p['zip_code'],
            'province' => $p['province'],
            'country' => $p['country'],
            'latitude' => $p['latitude'],
            'longitude' => $p['longitude'],
            'distance_meters' => $p['distance'] ? (int)($p['distance'] * 1000) : null,
            'enabled' => true,
            'opening_hours' => $p['opening_hours'] ?? [],
            'localization_hint' => '',
            'provider' => 'BRT',
        ];
    }

    public function mergePudoPoints(array $base, array $incoming, int $maxResults): array
    {
        $combined = array_merge($base, $incoming);
        $deduped = $this->dedupePudoPoints($combined);
        $sorted = $this->sortPudoByDistance($deduped);
        return array_slice($sorted, 0, max(1, min($maxResults, 50)));
    }

    public function sortPudoByDistance(array $points): array
    {
        usort($points, function ($a, $b) {
            $aD = isset($a['distance_meters']) && is_numeric($a['distance_meters']) ? (float) $a['distance_meters'] : INF;
            $bD = isset($b['distance_meters']) && is_numeric($b['distance_meters']) ? (float) $b['distance_meters'] : INF;
            return $aD === $bD ? strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')) : $aD <=> $bD;
        });
        return $points;
    }

    public function filterBrtOnly(array $points): array
    {
        $filtered = array_filter($points, function ($point) {
            $provider = strtoupper(trim((string) ($point['provider'] ?? 'BRT')));
            return $provider === '' || $provider === 'BRT';
        });
        return array_values(array_map(function ($point) {
            $point['provider'] = 'BRT';
            return $point;
        }, $filtered));
    }

    public function resolveAlternativeZipsForCity(string $city, string $currentZip = ''): array
    {
        try {
            $normalizedCity = mb_strtoupper(trim($city), 'UTF-8');
            if ($normalizedCity === '') return [];

            $zips = Location::query()
                ->whereRaw('UPPER(place_name) = ?', [$normalizedCity])
                ->pluck('postal_code')
                ->map(fn($zip) => preg_replace('/\D/', '', (string) $zip))
                ->filter()->unique()->values()->toArray();

            if (empty($zips)) {
                $zips = Location::query()
                    ->whereRaw('UPPER(place_name) LIKE ?', [$normalizedCity . '%'])
                    ->limit(100)
                    ->pluck('postal_code')
                    ->map(fn($zip) => preg_replace('/\D/', '', (string) $zip))
                    ->filter()->unique()->values()->toArray();
            }

            $currentZip = preg_replace('/\D/', '', (string) $currentZip);
            if ($currentZip !== '') {
                $zips = array_values(array_filter($zips, fn($zip) => $zip !== $currentZip));
            }

            return array_slice($zips, 0, 8);
        } catch (\Exception $e) {
            Log::warning('PUDO alternative ZIP resolution failed', ['city' => $city, 'error' => $e->getMessage()]);
            return [];
        }
    }

    public function getPudoFromDatabase(string $city, string $zipCode, int $maxResults): array
    {
        try {
            $points = PudoPoint::searchByLocation($city, $zipCode, $maxResults);
            Log::info('PUDO fallback database search', ['city' => $city, 'zip' => $zipCode, 'results' => count($points)]);

            return [
                'success' => true,
                'pudo' => array_map(fn($p) => $this->mapDbPoint($p), $points),
                'fallback' => true,
                'meta' => ['strategy_used' => ['fallback_db'], 'returned_count' => count($points), 'requested_count' => $maxResults, 'fallback' => true, 'provider' => 'BRT'],
            ];
        } catch (\Exception $e) {
            Log::error('PUDO fallback database error', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Nessun punto PUDO disponibile al momento.', 'pudo' => []];
        }
    }

    public function getPudoFromDatabaseByCoordinates(float $latitude, float $longitude, int $maxResults): array
    {
        try {
            $points = PudoPoint::searchByCoordinates($latitude, $longitude, $maxResults);
            Log::info('PUDO fallback database search by coordinates', ['lat' => $latitude, 'lng' => $longitude, 'results' => count($points)]);

            return [
                'success' => true,
                'pudo' => array_map(fn($p) => $this->mapDbPoint($p), $points),
                'fallback' => true,
                'meta' => ['strategy_used' => ['fallback_db_coordinates'], 'returned_count' => count($points), 'requested_count' => $maxResults, 'fallback' => true, 'provider' => 'BRT'],
            ];
        } catch (\Exception $e) {
            Log::error('PUDO fallback database error (coordinates)', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Nessun punto PUDO disponibile al momento.', 'pudo' => []];
        }
    }

    public function buildGeoGridSearchPoints(float $latitude, float $longitude): array
    {
        $latKmFactor = 110.574;
        $lngKmFactor = max(111.320 * cos(deg2rad($latitude)), 30.0);
        $distancesKm = [40, 75];
        $directions = [[1,0],[-1,0],[0,1],[0,-1],[1,1],[1,-1],[-1,1],[-1,-1]];

        $points = [];
        foreach ($distancesKm as $distanceKm) {
            foreach ($directions as [$latDir, $lngDir]) {
                if ($distanceKm >= 75 && abs($latDir) + abs($lngDir) === 2) continue;
                $candidateLat = $latitude + ($distanceKm / $latKmFactor) * $latDir;
                $candidateLng = $longitude + ($distanceKm / $lngKmFactor) * $lngDir;
                $key = sprintf('%.5f|%.5f', $candidateLat, $candidateLng);
                $points[$key] = ['latitude' => $candidateLat, 'longitude' => $candidateLng];
            }
        }
        return array_values($points);
    }

    private function dedupePudoPoints(array $points): array
    {
        $map = [];
        foreach ($points as $point) {
            $key = (string) ($point['pudo_id'] ?? '');
            if ($key === '') {
                $lat = isset($point['latitude']) && is_numeric($point['latitude']) ? number_format((float) $point['latitude'], 6, '.', '') : 'na';
                $lng = isset($point['longitude']) && is_numeric($point['longitude']) ? number_format((float) $point['longitude'], 6, '.', '') : 'na';
                $key = sprintf('%s|%s|%s|%s|%s|%s', strtolower((string) ($point['name'] ?? '')), strtolower((string) ($point['address'] ?? '')), strtolower((string) ($point['zip_code'] ?? '')), strtolower((string) ($point['city'] ?? '')), $lat, $lng);
            }

            if (!isset($map[$key])) {
                $map[$key] = $point;
                continue;
            }

            $currentD = isset($map[$key]['distance_meters']) && is_numeric($map[$key]['distance_meters']) ? (float) $map[$key]['distance_meters'] : INF;
            $nextD = isset($point['distance_meters']) && is_numeric($point['distance_meters']) ? (float) $point['distance_meters'] : INF;
            if ($nextD < $currentD) $map[$key] = $point;
        }
        return array_values($map);
    }
}
