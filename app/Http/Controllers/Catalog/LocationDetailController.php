<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Services\Catalog\LocationLookupService;
use Illuminate\Http\Request;

/**
 * Lookup deterministico localita' per CAP o nome citta' esatto.
 * A differenza di LocationSearchController, qui l'utente conosce gia' il dato
 * e cerca corrispondenze puntuali (un CAP puo' avere piu' citta', e viceversa).
 */
class LocationDetailController extends Controller
{
    public function __construct(private readonly LocationLookupService $lookup) {}

    public function byCap(Request $request)
    {
        $cap = trim((string) $request->input('cap', ''));
        $countryCode = $this->lookup->normalizeCountryFilter($request->input('country'));

        if (empty($cap)) {
            return response()->json([]);
        }

        $results = $this->lookup->lookupByPostalCode($cap, $countryCode);

        return response()->json($this->lookup->withCountryMetadata($results));
    }

    public function byCity(Request $request)
    {
        $city = trim((string) $request->input('city', ''));
        $limit = max(20, min((int) $request->input('limit', 500), 1000));
        $countryCode = $this->lookup->normalizeCountryFilter($request->input('country'));

        if (mb_strlen($city) < 2) {
            return response()->json([]);
        }

        $results = $this->lookup->lookupByCityName($city, $countryCode, $limit);

        return response()->json($this->lookup->withCountryMetadata($results));
    }
}
