<?php
namespace App\Services\Brt;

use App\Models\Location;
use Illuminate\Support\Facades\Log;

class AddressNormalizer
{
    /**
     * Normalizza un indirizzo per il sistema di routing BRT.
     *
     * @param object $address L'oggetto indirizzo con city, postal_code, province
     * @return array Array con chiavi: city, postal_code, province (normalizzati per BRT)
     */
    public function normalizeAddressForBrt(object $address): array
    {
        $city = trim($address->city ?? '');
        $postalCode = trim($address->postal_code ?? '');
        $province = trim($address->province ?? '');

        // 1. Normalizza il CAP: solo cifre, zero-padded a 5 caratteri
        $postalCode = preg_replace('/[^0-9]/', '', $postalCode);
        $postalCode = str_pad($postalCode, 5, '0', STR_PAD_LEFT);

        // 2. Normalizza la provincia (sigla a 2 lettere)
        $province = $this->provinceToAbbreviation($province);

        // 3. Normalizza la citta': maiuscolo e abbreviazioni espanse
        $city = $this->normalizeCityName($city);

        // 4. Prova a trovare la citta' corretta dal database locations usando il CAP
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('locations')) {
                $city = $this->resolveCityFromLocations($city, $postalCode, $province);
            }
        } catch (\Exception $e) {
            Log::debug('BRT normalizeAddress: locations table not available', ['error' => $e->getMessage()]);
        }

        return [
            'city' => $city,
            'postal_code' => $postalCode,
            'province' => $province,
        ];
    }

    /**
     * Converte il nome del paese nel codice ISO 3166-1 Alpha-2.
     */
    public function countryToIso2(string $country): string
    {
        $map = [
            // Europa
            'italia' => 'IT', 'italy' => 'IT',
            'francia' => 'FR', 'france' => 'FR',
            'germania' => 'DE', 'germany' => 'DE', 'deutschland' => 'DE',
            'spagna' => 'ES', 'spain' => 'ES', 'españa' => 'ES',
            'regno unito' => 'GB', 'united kingdom' => 'GB', 'great britain' => 'GB',
            'svizzera' => 'CH', 'switzerland' => 'CH',
            'austria' => 'AT',
            'belgio' => 'BE', 'belgium' => 'BE',
            'olanda' => 'NL', 'paesi bassi' => 'NL', 'netherlands' => 'NL',
            'portogallo' => 'PT', 'portugal' => 'PT',
            'polonia' => 'PL', 'poland' => 'PL',
            'grecia' => 'GR', 'greece' => 'GR',
            'irlanda' => 'IE', 'ireland' => 'IE',
            'danimarca' => 'DK', 'denmark' => 'DK',
            'svezia' => 'SE', 'sweden' => 'SE',
            'norvegia' => 'NO', 'norway' => 'NO',
            'finlandia' => 'FI', 'finland' => 'FI',
            'lussemburgo' => 'LU', 'luxembourg' => 'LU',
            'romania' => 'RO',
            'ungheria' => 'HU', 'hungary' => 'HU',
            'repubblica ceca' => 'CZ', 'czech republic' => 'CZ', 'czechia' => 'CZ',
            'slovacchia' => 'SK', 'slovakia' => 'SK',
            'slovenia' => 'SI',
            'croazia' => 'HR', 'croatia' => 'HR',
            'bulgaria' => 'BG',
            'estonia' => 'EE',
            'lettonia' => 'LV', 'latvia' => 'LV',
            'lituania' => 'LT', 'lithuania' => 'LT',
            'malta' => 'MT',
            'cipro' => 'CY', 'cyprus' => 'CY',
            'islanda' => 'IS', 'iceland' => 'IS',
            'liechtenstein' => 'LI',
            'monaco' => 'MC',
            'san marino' => 'SM',
            'città del vaticano' => 'VA', 'vatican' => 'VA',
            'andorra' => 'AD',
            'serbia' => 'RS',
            'montenegro' => 'ME',
            'bosnia erzegovina' => 'BA', 'bosnia and herzegovina' => 'BA', 'bosnia' => 'BA',
            'macedonia del nord' => 'MK', 'north macedonia' => 'MK',
            'albania' => 'AL',
            'moldavia' => 'MD', 'moldova' => 'MD',
            'ucraina' => 'UA', 'ukraine' => 'UA',
            'bielorussia' => 'BY', 'belarus' => 'BY',
            'russia' => 'RU',
            'turchia' => 'TR', 'turkey' => 'TR', 'türkiye' => 'TR',
            // Americhe
            'stati uniti' => 'US', 'united states' => 'US', 'usa' => 'US',
            'canada' => 'CA',
            'messico' => 'MX', 'mexico' => 'MX',
            'brasile' => 'BR', 'brazil' => 'BR', 'brasil' => 'BR',
            'argentina' => 'AR',
            'cile' => 'CL', 'chile' => 'CL',
            'colombia' => 'CO',
            'perù' => 'PE', 'peru' => 'PE',
            'venezuela' => 'VE',
            'ecuador' => 'EC',
            'uruguay' => 'UY',
            'paraguay' => 'PY',
            'bolivia' => 'BO',
            'costa rica' => 'CR',
            'panama' => 'PA',
            'cuba' => 'CU',
            'repubblica dominicana' => 'DO', 'dominican republic' => 'DO',
            'porto rico' => 'PR', 'puerto rico' => 'PR',
            // Asia
            'giappone' => 'JP', 'japan' => 'JP',
            'cina' => 'CN', 'china' => 'CN',
            'india' => 'IN',
            'corea del sud' => 'KR', 'south korea' => 'KR',
            'corea del nord' => 'KP', 'north korea' => 'KP',
            'thailandia' => 'TH', 'thailand' => 'TH',
            'vietnam' => 'VN',
            'indonesia' => 'ID',
            'malesia' => 'MY', 'malaysia' => 'MY',
            'filippine' => 'PH', 'philippines' => 'PH',
            'singapore' => 'SG',
            'taiwan' => 'TW',
            'hong kong' => 'HK',
            'israele' => 'IL', 'israel' => 'IL',
            'emirati arabi uniti' => 'AE', 'united arab emirates' => 'AE', 'uae' => 'AE',
            'arabia saudita' => 'SA', 'saudi arabia' => 'SA',
            'qatar' => 'QA',
            'kuwait' => 'KW',
            'pakistan' => 'PK',
            'bangladesh' => 'BD',
            'sri lanka' => 'LK',
            // Oceania
            'australia' => 'AU',
            'nuova zelanda' => 'NZ', 'new zealand' => 'NZ',
            // Africa
            'sudafrica' => 'ZA', 'south africa' => 'ZA',
            'marocco' => 'MA', 'morocco' => 'MA',
            'tunisia' => 'TN',
            'egitto' => 'EG', 'egypt' => 'EG',
            'nigeria' => 'NG',
            'kenya' => 'KE',
            'algeria' => 'DZ',
            'libia' => 'LY', 'libya' => 'LY',
            'ghana' => 'GH',
            'senegal' => 'SN',
            'etiopia' => 'ET', 'ethiopia' => 'ET',
        ];

        $lower = strtolower(trim($country));

        // Se il valore e' gia' un codice ISO a 2 lettere, restituiscilo direttamente
        if (strlen($country) === 2) {
            return strtoupper($country);
        }

        if (isset($map[$lower])) {
            return $map[$lower];
        }

        // Per valori vuoti o null, fallback a IT (paese di default)
        if (empty(trim($country))) {
            return 'IT';
        }

        // Paese non riconosciuto: log warning e restituisci il valore in maiuscolo
        // (potrebbe essere gia' un codice ISO valido in formato diverso)
        Log::warning('BRT countryToIso2: paese non mappato, uso valore raw', [
            'country_input' => $country,
            'returned' => strtoupper(trim($country)),
        ]);

        return strtoupper(trim($country));
    }

    /**
     * Normalizza il nome della citta' per BRT (maiuscolo + abbreviazioni espanse).
     */
    private function normalizeCityName(string $city): string
    {
        $city = mb_strtoupper(trim($city), 'UTF-8');

        $abbreviations = [
            '/\bSS\.\s*/u' => 'SANTISSIMO ',
            '/\bS\.S\.\s*/u' => 'SANTISSIMO ',
            '/\bS\.\s*/u' => 'SAN ',
            '/\bSTA\.\s*/u' => 'SANTA ',
            '/\bSTO\.\s*/u' => 'SANTO ',
            '/\bV\.LE\s*/u' => 'VIALE ',
            '/\bP\.ZZA\s*/u' => 'PIAZZA ',
            '/\bC\.SO\s*/u' => 'CORSO ',
            '/\bF\.LLI\s*/u' => 'FRATELLI ',
            '/\bMTE\.\s*/u' => 'MONTE ',
        ];

        foreach ($abbreviations as $pattern => $replacement) {
            $city = preg_replace($pattern, $replacement, $city);
        }

        return preg_replace('/\s+/', ' ', trim($city));
    }

    /**
     * Risolve il nome citta' corretto dal database locations.
     */
    private function resolveCityFromLocations(string $normalizedCity, string $postalCode, string $province): string
    {
        if (empty($postalCode) || $postalCode === '00000') {
            return $normalizedCity;
        }

        try {
            // 1. Corrispondenza esatta: CAP + nome citta'
            $exactMatch = Location::where('postal_code', $postalCode)
                ->whereRaw('UPPER(place_name) = ?', [$normalizedCity])
                ->first();

            if ($exactMatch) {
                return mb_strtoupper($exactMatch->place_name, 'UTF-8');
            }

            // 2. Cerca tutte le citta' con questo CAP
            $citiesByZip = Location::where('postal_code', $postalCode)->get();

            if ($citiesByZip->isEmpty()) {
                Log::warning('BRT address normalization: ZIP code not found in locations database', [
                    'postal_code' => $postalCode,
                    'city' => $normalizedCity,
                ]);
                return $normalizedCity;
            }

            // Se c'e' un solo risultato per questo CAP, usa quello
            if ($citiesByZip->count() === 1) {
                $resolved = mb_strtoupper($citiesByZip->first()->place_name, 'UTF-8');
                if ($resolved !== $normalizedCity) {
                    Log::info('BRT address normalization: resolved city from ZIP', [
                        'original_city' => $normalizedCity,
                        'resolved_city' => $resolved,
                        'postal_code' => $postalCode,
                    ]);
                }
                return $resolved;
            }

            // 3. Match parziale
            foreach ($citiesByZip as $location) {
                $dbCity = mb_strtoupper($location->place_name, 'UTF-8');
                if (str_contains($dbCity, $normalizedCity) || str_contains($normalizedCity, $dbCity)) {
                    Log::info('BRT address normalization: partial match found', [
                        'original_city' => $normalizedCity,
                        'resolved_city' => $dbCity,
                        'postal_code' => $postalCode,
                    ]);
                    return $dbCity;
                }
            }

            // 4. Match per provincia
            if (!empty($province)) {
                $provinceMatch = $citiesByZip->first(function ($loc) use ($province) {
                    return mb_strtoupper($loc->province ?? '', 'UTF-8') === $province;
                });
                if ($provinceMatch) {
                    $resolved = mb_strtoupper($provinceMatch->place_name, 'UTF-8');
                    Log::info('BRT address normalization: resolved city from ZIP + province', [
                        'original_city' => $normalizedCity,
                        'resolved_city' => $resolved,
                        'postal_code' => $postalCode,
                        'province' => $province,
                    ]);
                    return $resolved;
                }
            }

            // 5. Nessuna corrispondenza
            Log::warning('BRT address normalization: no matching city found for ZIP', [
                'postal_code' => $postalCode,
                'city' => $normalizedCity,
                'available_cities' => $citiesByZip->pluck('place_name')->toArray(),
            ]);
            return $normalizedCity;

        } catch (\Exception $e) {
            Log::warning('BRT address normalization exception', [
                'error' => $e->getMessage(),
                'city' => $normalizedCity,
                'postal_code' => $postalCode,
            ]);
            return $normalizedCity;
        }
    }

    /**
     * Converte la provincia italiana nella sigla a 2 lettere.
     */
    private function provinceToAbbreviation(string $province): string
    {
        $province = trim($province);

        if (strlen($province) === 2) {
            return strtoupper($province);
        }

        $map = [
            'agrigento' => 'AG', 'alessandria' => 'AL', 'ancona' => 'AN', 'aosta' => 'AO',
            'arezzo' => 'AR', 'ascoli piceno' => 'AP', 'asti' => 'AT', 'avellino' => 'AV',
            'bari' => 'BA', 'barletta-andria-trani' => 'BT', 'belluno' => 'BL', 'benevento' => 'BN',
            'bergamo' => 'BG', 'biella' => 'BI', 'bologna' => 'BO', 'bolzano' => 'BZ',
            'brescia' => 'BS', 'brindisi' => 'BR', 'cagliari' => 'CA', 'caltanissetta' => 'CL',
            'campobasso' => 'CB', 'carbonia-iglesias' => 'CI', 'caserta' => 'CE', 'catania' => 'CT',
            'catanzaro' => 'CZ', 'chieti' => 'CH', 'como' => 'CO', 'cosenza' => 'CS',
            'cremona' => 'CR', 'crotone' => 'KR', 'cuneo' => 'CN', 'enna' => 'EN',
            'fermo' => 'FM', 'ferrara' => 'FE', 'firenze' => 'FI', 'foggia' => 'FG',
            'forlì-cesena' => 'FC', 'forli-cesena' => 'FC', 'frosinone' => 'FR', 'genova' => 'GE',
            'gorizia' => 'GO', 'grosseto' => 'GR', 'imperia' => 'IM', 'isernia' => 'IS',
            'la spezia' => 'SP', 'l\'aquila' => 'AQ', 'laquila' => 'AQ', 'latina' => 'LT',
            'lecce' => 'LE', 'lecco' => 'LC', 'livorno' => 'LI', 'lodi' => 'LO',
            'lucca' => 'LU', 'macerata' => 'MC', 'mantova' => 'MN', 'massa-carrara' => 'MS',
            'massa carrara' => 'MS', 'matera' => 'MT', 'medio campidano' => 'VS',
            'messina' => 'ME', 'milano' => 'MI', 'modena' => 'MO', 'monza e brianza' => 'MB',
            'monza' => 'MB', 'napoli' => 'NA', 'novara' => 'NO', 'nuoro' => 'NU',
            'ogliastra' => 'OG', 'olbia-tempio' => 'OT', 'oristano' => 'OR', 'padova' => 'PD',
            'palermo' => 'PA', 'parma' => 'PR', 'pavia' => 'PV', 'perugia' => 'PG',
            'pesaro e urbino' => 'PU', 'pesaro-urbino' => 'PU', 'pescara' => 'PE',
            'piacenza' => 'PC', 'pisa' => 'PI', 'pistoia' => 'PT', 'pordenone' => 'PN',
            'potenza' => 'PZ', 'prato' => 'PO', 'ragusa' => 'RG', 'ravenna' => 'RA',
            'reggio calabria' => 'RC', 'reggio emilia' => 'RE', 'rieti' => 'RI', 'rimini' => 'RN',
            'roma' => 'RM', 'rovigo' => 'RO', 'salerno' => 'SA', 'sassari' => 'SS',
            'savona' => 'SV', 'siena' => 'SI', 'siracusa' => 'SR', 'sondrio' => 'SO',
            'sud sardegna' => 'SU', 'taranto' => 'TA', 'teramo' => 'TE', 'terni' => 'TR',
            'torino' => 'TO', 'trapani' => 'TP', 'trento' => 'TN', 'treviso' => 'TV',
            'trieste' => 'TS', 'udine' => 'UD', 'varese' => 'VA', 'venezia' => 'VE',
            'verbano-cusio-ossola' => 'VB', 'verbania' => 'VB', 'vercelli' => 'VC',
            'verona' => 'VR', 'vibo valentia' => 'VV', 'vicenza' => 'VI', 'viterbo' => 'VT',
        ];

        $lower = strtolower(trim($province));

        return $map[$lower] ?? strtoupper($province);
    }
}
