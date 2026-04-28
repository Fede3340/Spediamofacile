<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportLocations extends Command
{
    protected $signature = 'locations:import
        {--fresh : Truncate the table before importing}
        {--if-empty : Skip import when the selected countries already have rows}
        {--country=* : Country codes to import (default: all .txt files in database/)}';

    protected $description = 'Import postal codes from GeoNames .txt files into the locations table';

    public function handle(): int
    {
        if (! Schema::hasTable('locations')) {
            $this->error('The locations table does not exist. Run migrations first.');

            return self::FAILURE;
        }

        $countries = $this->option('country');
        $normalizedCountries = array_values(array_filter(array_map(
            static fn ($code) => strtoupper(trim((string) $code)),
            $countries
        )));

        if ($this->option('if-empty')) {
            $existingRows = empty($normalizedCountries)
                ? DB::table('locations')->count()
                : DB::table('locations')->whereIn('country_code', $normalizedCountries)->count();

            if ($existingRows > 0) {
                $scope = empty($normalizedCountries) ? 'all countries' : implode(', ', $normalizedCountries);
                $this->info("Locations already present for {$scope}: import skipped.");

                return self::SUCCESS;
            }
        }

        if ($this->option('fresh')) {
            DB::table('locations')->truncate();
            $this->info('Table truncated.');
        }

        // Directory file GeoNames: dataset offline tenuto FUORI dal repo (vedi docs/operations/DEPLOY.md).
        // Cerca prima in spedizionefacile-offline-data (sibling repo), poi _data/ (legacy), poi database/.
        $dataDirs = [
            base_path('../../spedizionefacile-offline-data/geonames-postalcodes'),
            base_path('../_data/geonames-postalcodes'),
            database_path(),
        ];
        $resolveFile = function (string $code) use ($dataDirs): ?string {
            foreach ($dataDirs as $dir) {
                $file = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . "{$code}.txt";
                if (file_exists($file)) return $file;
            }
            return null;
        };

        if (empty($normalizedCountries)) {
            $files = [];
            foreach ($dataDirs as $dir) {
                foreach (glob(rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . '*.txt') ?: [] as $f) {
                    $basename = basename($f, '.txt');
                    if (preg_match('/^[A-Z]{2}$/', $basename)) {
                        $files[] = $f;
                    }
                }
            }
            $files = array_values(array_unique($files));
        } else {
            $files = [];
            foreach ($normalizedCountries as $code) {
                $file = $resolveFile($code);
                if ($file) {
                    $files[] = $file;
                } else {
                    $this->warn("File not found for {$code} — skipping");
                }
            }
        }

        if (empty($files)) {
            $this->error('No GeoNames files found. Download them from https://download.geonames.org/export/zip/');

            return self::FAILURE;
        }

        sort($files);
        $grandTotal = 0;

        foreach ($files as $file) {
            $countryCode = strtoupper(basename($file, '.txt'));
            $total = $this->importFile($file, $countryCode);
            $grandTotal += $total;
            $this->info("  {$countryCode}: {$total} locations imported");
        }

        $this->newLine();
        $this->info("Total: {$grandTotal} locations imported from " . count($files) . ' countries.');

        // Show stats
        $totalRows = DB::table('locations')->count();
        $uniqueCaps = DB::table('locations')->distinct()->count('postal_code');
        $uniqueCities = DB::table('locations')->distinct()->count('place_name');
        $countriesCount = DB::table('locations')->distinct()->count('country_code');

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total rows', $totalRows],
                ['Unique postal codes', $uniqueCaps],
                ['Unique city names', $uniqueCities],
                ['Countries', $countriesCount],
            ]
        );

        return self::SUCCESS;
    }

    private function importFile(string $file, string $countryCode): int
    {
        $handle = fopen($file, 'r');
        if (! $handle) {
            $this->error("Cannot open file: {$file}");

            return 0;
        }

        // Rimuovi righe esistenti per questo paese (per evitare duplicati su re-import)
        DB::table('locations')->where('country_code', $countryCode)->delete();

        $batch = [];
        $total = 0;
        $batchSize = 500;

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = explode("\t", $line);
            // GeoNames format: country \t postal_code \t place_name \t admin1 \t admin1_code \t admin2 \t admin2_code \t ...
            if (count($parts) < 3) {
                continue;
            }

            // province: usa admin2_code se presente (IT=MI,RM), altrimenti admin1_code
            $province = '';
            if (isset($parts[6]) && $parts[6] !== '') {
                $province = $parts[6];
            } elseif (isset($parts[4]) && $parts[4] !== '') {
                $province = $parts[4];
            }

            $batch[] = [
                'postal_code' => $parts[1],
                'place_name' => $parts[2],
                'province' => $province,
                'country_code' => $countryCode,
            ];

            if (count($batch) >= $batchSize) {
                DB::table('locations')->insert($batch);
                $total += count($batch);
                $batch = [];
            }
        }

        if (! empty($batch)) {
            DB::table('locations')->insert($batch);
            $total += count($batch);
        }

        fclose($handle);

        return $total;
    }
}
