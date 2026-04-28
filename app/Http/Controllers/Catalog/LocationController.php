<?php
namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;

use App\Models\Location;
use Illuminate\Http\Request;
use App\Utils\CustomResponse;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\LocationResource;
use Symfony\Component\HttpFoundation\Response;

class LocationController extends Controller
{
    /**
     * Paesi serviti da SpediamoFacile secondo il listino commerciale BRT.
     * La ricerca localita' (DB + fallback Photon) accetta SOLO questi paesi.
     * Qualsiasi altra nazione (USA, Giappone, ecc.) viene scartata.
     */
    private const ALLOWED_COUNTRIES = [
        'IT', 'AT', 'BE', 'BG', 'CH', 'CZ', 'DE', 'DK', 'EE', 'ES',
        'FI', 'FR', 'GB', 'GR', 'HR', 'HU', 'LT', 'LU', 'LV', 'NL',
        'PL', 'PT', 'RO', 'SE', 'SI', 'SK',
    ];

    private const COUNTRY_NAMES = [
        'IT' => 'Italia',
        'AT' => 'Austria',
        'BE' => 'Belgio',
        'BG' => 'Bulgaria',
        'HR' => 'Croazia',
        'DK' => 'Danimarca',
        'EE' => 'Estonia',
        'FI' => 'Finlandia',
        'FR' => 'Francia',
        'DE' => 'Germania',
        'GR' => 'Grecia',
        'LU' => 'Lussemburgo',
        'NL' => 'Olanda',
        'PL' => 'Polonia',
        'PT' => 'Portogallo',
        'CZ' => 'Repubblica Ceca',
        'RO' => 'Romania',
        'SK' => 'Slovacchia',
        'SI' => 'Slovenia',
        'ES' => 'Spagna',
        'SE' => 'Svezia',
        'HU' => 'Ungheria',
        'LV' => 'Lettonia',
        'LT' => 'Lituania',
    ];

    private function normalizeCountryFilter(Request $request): ?string
    {
        $countryCode = strtoupper(trim((string) $request->input('country', '')));
        return strlen($countryCode) === 2 ? $countryCode : null;
    }

    private function applyCountryFilter($query, ?string $countryCode)
    {
        if (! $countryCode) {
            return $query;
        }

        if ($countryCode === 'IT') {
            return $query->where(function ($countryQuery) {
                $countryQuery->where('country_code', 'IT')->orWhereNull('country_code');
            });
        }

        return $query->where('country_code', $countryCode);
    }

    // Salva la citta' selezionata dall'utente nella sessione
    // La "sessione" e' una memoria temporanea che dura finche' l'utente naviga sul sito
    public function postLocation(Request $request) {

        Session::put('city', $request->city);

        return CustomResponse::setSuccessResponse('Tutto ok', Response::HTTP_OK);
    }

    // Recupera i dati della citta' salvata in sessione
    // Cerca nel database il CAP, il nome della citta' e la provincia corrispondenti
    public function getLocations() {

        $city = Session::get('city');

        $result = Location::where('place_name', $city)
            ->select('postal_code', 'place_name', 'province', 'country_code')
            ->first();

        return response()->json($result ? $this->withCountryMetadata($result) : null);
    }

    /**
     * Cerca localita' per nome della citta' o per codice postale (CAP).
     * L'utente inizia a scrivere e questa funzione restituisce i suggerimenti.
     * Richiede almeno 2 caratteri per iniziare la ricerca (per evitare troppe risposte).
     * Restituisce massimo 20 risultati.
     */
    /**
     * Alias italiani delle citta' estere piu' cercate.
     * Il DB contiene i nomi originali (Paris, London, Athens...) ma
     * gli utenti italiani cercano in italiano (Parigi, Londra, Atene...).
     * Se la query matcha un alias, la traduciamo nel nome ufficiale.
     */
    private const CITY_ALIASES_IT = [
        'parigi' => 'Paris',
        'londra' => 'London',
        'atene' => 'Athens',
        'bruxelles' => 'Bruxelles',
        'vienna' => 'Wien',
        'monaco di baviera' => 'München',
        'monaco' => 'München',
        'berlino' => 'Berlin',
        'francoforte' => 'Frankfurt am Main',
        'amburgo' => 'Hamburg',
        'colonia' => 'Köln',
        'stoccarda' => 'Stuttgart',
        'dusseldorf' => 'Düsseldorf',
        'norimberga' => 'Nürnberg',
        'barcellona' => 'Barcelona',
        'madrid' => 'Madrid',
        'siviglia' => 'Sevilla',
        'valencia' => 'Valencia',
        'saragozza' => 'Zaragoza',
        'malaga' => 'Málaga',
        'lisbona' => 'Lisboa',
        'porto' => 'Porto',
        'varsavia' => 'Warszawa',
        'cracovia' => 'Kraków',
        'danzica' => 'Gdańsk',
        'breslavia' => 'Wrocław',
        'poznan' => 'Poznań',
        'praga' => 'Praha',
        'brno' => 'Brno',
        'budapest' => 'Budapest',
        'bucarest' => 'București',
        'zagabria' => 'Zagreb',
        'lubiana' => 'Ljubljana',
        'amsterdam' => 'Amsterdam',
        'rotterdam' => 'Rotterdam',
        'aja' => 'Den Haag',
        'utrecht' => 'Utrecht',
        'anversa' => 'Antwerpen',
        'gand' => 'Gent',
        'bruges' => 'Brugge',
        'liegi' => 'Liège',
        'salisburgo' => 'Salzburg',
        'graz' => 'Graz',
        'innsbruck' => 'Innsbruck',
        // Nuovi paesi importati (UK, Scandinavia, Baltici, Svizzera, Lussemburgo)
        'londra' => 'London',
        'edimburgo' => 'Edinburgh',
        'glasgow' => 'Glasgow',
        'manchester' => 'Manchester',
        'liverpool' => 'Liverpool',
        'birmingham' => 'Birmingham',
        'copenaghen' => 'København',
        'aarhus' => 'Aarhus',
        'stoccolma' => 'Stockholm',
        'goteborg' => 'Göteborg',
        'helsinki' => 'Helsinki',
        'tampere' => 'Tampere',
        'riga' => 'Riga',
        'vilnius' => 'Vilnius',
        'tallinn' => 'Tallinn',
        'lussemburgo' => 'Luxembourg',
        'zurigo' => 'Zürich',
        'ginevra' => 'Genève',
        'basilea' => 'Basel',
        'berna' => 'Bern',
        'losanna' => 'Lausanne',
        'bratislava' => 'Bratislava',
        'sofia' => 'Sofia',
        'plovdiv' => 'Plovdiv',
        // Grecia (non nel DB ma teniamo alias per futuro)
        'atene' => 'Athens',
        'salonicco' => 'Thessaloniki',
    ];

    public function search(Request $request)
    {
        $query = trim((string) $request->input('q', ''));
        $limit = (int) $request->input('limit', 120);
        $limit = max(20, min($limit, 500));
        $countryCode = $this->normalizeCountryFilter($request);

        // Se l'utente ha scritto meno di 2 caratteri, non cerchiamo nulla
        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        // Se l'utente scrive in italiano una citta' estera nota, traduci nel nome DB.
        $queryNormalized = mb_strtolower(trim($query));
        if (isset(self::CITY_ALIASES_IT[$queryNormalized])) {
            $query = self::CITY_ALIASES_IT[$queryNormalized];
        } else {
            $matched = false;
            // 1. Prefisso parziale: "parig" -> matcha "parigi" -> "Paris"
            foreach (self::CITY_ALIASES_IT as $italianName => $originalName) {
                if (str_starts_with($italianName, $queryNormalized) && mb_strlen($queryNormalized) >= 3) {
                    $query = $originalName;
                    $matched = true;
                    break;
                }
            }
            // 2. Fuzzy match (tolleranza typo): "ahtene" -> "atene" -> "Athens"
            // Attivo solo per query di 4+ caratteri per evitare falsi positivi.
            if (! $matched && mb_strlen($queryNormalized) >= 4) {
                $bestMatch = null;
                $bestDistance = PHP_INT_MAX;
                foreach (self::CITY_ALIASES_IT as $italianName => $originalName) {
                    $lenDiff = abs(mb_strlen($italianName) - mb_strlen($queryNormalized));
                    if ($lenDiff > 2) continue;
                    $distance = levenshtein($queryNormalized, $italianName);
                    // Max 2 errori di digitazione, preferisce match con distanza minore
                    if ($distance <= 2 && $distance < $bestDistance) {
                        $bestDistance = $distance;
                        $bestMatch = $originalName;
                    }
                }
                if ($bestMatch) {
                    $query = $bestMatch;
                }
            }
        }

        // Ricerca CAP: prefisso numerico (es. 001 -> 00100, 00118, ...)
        if (preg_match('/^\d+$/', $query)) {
            $results = $this->applyCountryFilter(
                Location::where('postal_code', 'LIKE', $query . '%'),
                $countryCode
            )
                ->select('postal_code', 'place_name', 'province', 'country_code')
                ->orderBy('postal_code')
                ->orderBy('place_name')
                ->limit($limit)
                ->get();

            return response()->json($this->withCountryMetadata($results));
        }

        $queryLower = mb_strtolower($query);

        // Ricerca citta': priorita' a match esatto, poi inizio parola, poi prefisso.
        $results = $this->applyCountryFilter(Location::query(), $countryCode)
            ->select('postal_code', 'place_name', 'province', 'country_code')
            ->where(function ($q) use ($queryLower) {
                $q->whereRaw('LOWER(place_name) = ?', [$queryLower])
                    ->orWhereRaw('LOWER(place_name) LIKE ?', [$queryLower . ' %'])
                    ->orWhereRaw('LOWER(place_name) LIKE ?', ['% ' . $queryLower . ' %'])
                    ->orWhereRaw('LOWER(place_name) LIKE ?', [$queryLower . '%']);
            })
            ->orderByRaw(
                "CASE
                    WHEN LOWER(place_name) = ? THEN 0
                    WHEN LOWER(place_name) LIKE ? THEN 1
                    WHEN LOWER(place_name) LIKE ? THEN 2
                    ELSE 3
                END",
                [$queryLower, $queryLower . ' %', $queryLower . '%']
            )
            ->orderBy('place_name')
            ->orderBy('postal_code')
            ->limit($limit)
            ->get();

        // Fallback live su Photon (OpenStreetMap) quando il DB locale non ha dati.
        // Copertura: qualsiasi paese del mondo, dati sempre aggiornati da OSM.
        // Esempi d'uso: Grecia (non nel DB), Irlanda, Norvegia, citta' minori.
        if ($results->isEmpty()) {
            $fallback = $this->searchPhotonFallback($query, $countryCode);
            if (!empty($fallback)) {
                return response()->json($this->withCountryMetadata(collect($fallback)));
            }
        }

        return response()->json($this->withCountryMetadata($results));
    }

    /**
     * Fallback live su Photon (OpenStreetMap) quando il DB locale non ha dati.
     * - Gratuito, nessuna API key
     * - Aggiornato continuamente da OSM (dati piu' freschi di GeoNames)
     * - Copre ogni paese del mondo
     * - Cache 24h per evitare chiamate ripetute sulla stessa query
     * - Timeout 4s per non bloccare la UX se il servizio e' lento/offline
     */
    private function searchPhotonFallback(string $query, ?string $countryCode = null): array
    {
        $cacheKey = 'loc:photon:' . md5(mb_strtolower(trim($query)) . '|' . ($countryCode ?? ''));

        return Cache::remember($cacheKey, 86400, function () use ($query, $countryCode) {
            try {
                // verify=false su dev Windows dove manca il CA bundle cURL.
                // In produzione Linux/Docker la verify SSL e' attiva di default.
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

                    // Scarta paesi fuori dalla whitelist commerciale (no USA, JP, ecc).
                    if (! in_array($cc, self::ALLOWED_COUNTRIES, true)) {
                        continue;
                    }

                    // Se e' stato selezionato un paese specifico, filtra solo quello
                    if ($countryCode && $cc !== strtoupper($countryCode)) {
                        continue;
                    }

                    $postcode = trim((string) ($props['postcode'] ?? ''));
                    $placeName = trim((string) ($props['name'] ?? $props['city'] ?? ''));

                    // Scarta risultati senza CAP o senza nome citta'
                    if (! $postcode || ! $placeName) {
                        continue;
                    }

                    // Tieni solo tipologie rilevanti (citta', paesi, comuni)
                    $osmKey = $props['osm_key'] ?? '';
                    $osmValue = $props['osm_value'] ?? '';
                    $accepted = ($osmKey === 'place' && in_array($osmValue, ['city', 'town', 'village', 'municipality', 'suburb', 'hamlet'], true))
                        || ($osmKey === 'boundary' && $osmValue === 'administrative');
                    if (! $accepted) {
                        continue;
                    }

                    // Deduplica per postcode+citta'
                    $dedupKey = $postcode . '|' . mb_strtolower($placeName);
                    if (isset($seen[$dedupKey])) {
                        continue;
                    }
                    $seen[$dedupKey] = true;

                    // Converti in stdClass per compatibilita' con withCountryMetadata()
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

    /**
     * Cerca localita' per codice postale (CAP) esatto.
     * A differenza della funzione "search", questa cerca una corrispondenza esatta del CAP.
     * Utile quando si conosce gia' il CAP e si vogliono trovare le citta' corrispondenti
     * (un CAP puo' corrispondere a piu' citta').
     */
    public function byCap(Request $request)
    {
        $cap = trim((string) $request->input('cap', ''));
        $countryCode = $this->normalizeCountryFilter($request);

        if (empty($cap)) {
            return response()->json([]);
        }

        // Cerchiamo tutte le localita' con questo CAP esatto
        $results = $this->applyCountryFilter(
            Location::where('postal_code', $cap),
            $countryCode
        )
            ->select('postal_code', 'place_name', 'province', 'country_code')
            ->get();

        return response()->json($this->withCountryMetadata($results));
    }

    /**
     * Cerca localita' per nome citta' (priorita' match esatto).
     * Se trova la citta' esatta, restituisce tutti i CAP di quella citta'.
     * Se non trova match esatto, usa fallback per prefisso (city%).
     */
    public function byCity(Request $request)
    {
        $city = trim((string) $request->input('city', ''));
        $limit = (int) $request->input('limit', 500);
        $limit = max(20, min($limit, 1000));
        $countryCode = $this->normalizeCountryFilter($request);

        if (mb_strlen($city) < 2) {
            return response()->json([]);
        }

        $cityLower = mb_strtolower($city);

        $exact = $this->applyCountryFilter(Location::query(), $countryCode)
            ->select('postal_code', 'place_name', 'province', 'country_code')
            ->whereRaw('LOWER(place_name) = ?', [$cityLower])
            ->distinct()
            ->orderBy('postal_code')
            ->get();

        if ($exact->isNotEmpty()) {
            return response()->json($this->withCountryMetadata($exact));
        }

        $prefix = $this->applyCountryFilter(Location::query(), $countryCode)
            ->select('postal_code', 'place_name', 'province', 'country_code')
            ->whereRaw('LOWER(place_name) LIKE ?', [$cityLower . '%'])
            ->distinct()
            ->orderBy('place_name')
            ->orderBy('postal_code')
            ->limit($limit)
            ->get();

        return response()->json($this->withCountryMetadata($prefix));
    }

    private function withCountryMetadata($results)
    {
        if ($results instanceof \Illuminate\Support\Collection) {
            return $results->map(fn ($location) => $this->withCountryMetadata($location))->values();
        }

        if (!$results) {
            return $results;
        }

        $countryCode = strtoupper(trim((string) ($results->country_code ?? 'IT')));
        $results->country_code = $countryCode;
        $results->country_name = self::COUNTRY_NAMES[$countryCode] ?? $countryCode;

        return $results;
    }
}
