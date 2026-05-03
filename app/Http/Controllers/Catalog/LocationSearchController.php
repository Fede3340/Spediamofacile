<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Services\Catalog\LocationLookupService;
use App\Services\Catalog\PhotonLocationFallback;
use Illuminate\Http\Request;

/**
 * Autocomplete localita': l'utente scrive in input, riceve suggerimenti.
 * Tollera typo (fuzzy match), traduce alias italiani citta' estere,
 * fallback a Photon (OSM) quando il DB locale non ha dati.
 */
class LocationSearchController extends Controller
{
    public function __construct(
        private readonly LocationLookupService $lookup,
        private readonly PhotonLocationFallback $photonFallback,
    ) {}

    public function search(Request $request)
    {
        $query = trim((string) $request->input('q', ''));
        $limit = max(20, min((int) $request->input('limit', 120), 500));
        $countryCode = $this->lookup->normalizeCountryFilter($request->input('country'));

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $query = $this->lookup->resolveCityAlias($query);

        if (preg_match('/^\d+$/', $query)) {
            $results = $this->lookup->searchByPostalPrefix($query, $countryCode, $limit);

            return response()->json($this->lookup->withCountryMetadata($results));
        }

        $results = $this->lookup->searchByCityName($query, $countryCode, $limit);

        // Fallback live su Photon (OpenStreetMap) quando il DB locale non ha dati.
        if ($results->isEmpty()) {
            $fallback = $this->photonFallback->search($query, $countryCode);
            if (! empty($fallback)) {
                return response()->json($this->lookup->withCountryMetadata(collect($fallback)));
            }
        }

        return response()->json($this->lookup->withCountryMetadata($results));
    }
}
