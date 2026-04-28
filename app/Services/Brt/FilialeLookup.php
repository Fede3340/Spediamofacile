<?php
namespace App\Services\Brt;

class FilialeLookup
{
    /** @var array|null Cache delle filiali caricate dal config */
    private static ?array $filiali = null;

    /**
     * Risolve la filiale BRT piu' vicina al CAP del mittente.
     *
     * @param string $cap CAP del mittente (5 cifre)
     * @return int|null Codice filiale BRT, o null se nessun match
     */
    public static function resolveFilialeByCap(string $cap): ?int
    {
        $cap = preg_replace('/[^0-9]/', '', $cap);
        $cap = str_pad($cap, 5, '0', STR_PAD_LEFT);

        if (empty($cap) || $cap === '00000') {
            return null;
        }

        $filiali = self::loadFiliali();
        if (empty($filiali)) {
            return null;
        }

        // 1. Match esatto: CAP completo (5 cifre)
        foreach ($filiali as $filiale) {
            if (($filiale['cap'] ?? '') === $cap) {
                return (int) $filiale['codice'];
            }
        }

        // 2. Match sulle prime 3 cifre (stessa sottozona postale)
        $prefix3 = substr($cap, 0, 3);
        $candidates3 = [];
        foreach ($filiali as $filiale) {
            if (substr($filiale['cap'] ?? '', 0, 3) === $prefix3) {
                $candidates3[] = $filiale;
            }
        }
        if (count($candidates3) === 1) {
            return (int) $candidates3[0]['codice'];
        }
        if (count($candidates3) > 1) {
            // Piu' candidati: scegli quello con il CAP piu' vicino numericamente
            return (int) self::closestByCap($candidates3, $cap)['codice'];
        }

        // 3. Match sulle prime 2 cifre (stessa provincia postale)
        $prefix2 = substr($cap, 0, 2);
        $candidates2 = [];
        foreach ($filiali as $filiale) {
            if (substr($filiale['cap'] ?? '', 0, 2) === $prefix2) {
                $candidates2[] = $filiale;
            }
        }
        if (! empty($candidates2)) {
            return (int) self::closestByCap($candidates2, $cap)['codice'];
        }

        // 4. Nessun match
        return null;
    }

    /**
     * Tra i candidati, restituisce la filiale con il CAP piu' vicino.
     */
    private static function closestByCap(array $candidates, string $cap): array
    {
        $capNum = (int) $cap;
        $best = $candidates[0];
        $bestDiff = abs((int) ($best['cap'] ?? '0') - $capNum);

        foreach ($candidates as $candidate) {
            $diff = abs((int) ($candidate['cap'] ?? '0') - $capNum);
            if ($diff < $bestDiff) {
                $best = $candidate;
                $bestDiff = $diff;
            }
        }

        return $best;
    }

    /**
     * Carica le filiali dal config (con cache statica).
     */
    private static function loadFiliali(): array
    {
        if (self::$filiali === null) {
            self::$filiali = config('brt_filiali.filiali', []);
        }
        return self::$filiali;
    }

    /**
     * Reset cache (utile nei test).
     */
    public static function resetCache(): void
    {
        self::$filiali = null;
    }
}
