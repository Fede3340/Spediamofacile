<?php

namespace Database\Seeders;

use App\Models\PriceBand;
use Illuminate\Database\Seeder;

class PriceBandSeeder extends Seeder
{
    public function run(): void
    {
        $bands = [
            // Weight bands (kg)
            ['type' => 'weight', 'min_value' => 0,    'max_value' => 2,    'base_price' => 890,  'sort_order' => 1],
            ['type' => 'weight', 'min_value' => 2,    'max_value' => 5,    'base_price' => 1190, 'sort_order' => 2],
            ['type' => 'weight', 'min_value' => 5,    'max_value' => 10,   'base_price' => 1490, 'sort_order' => 3],
            ['type' => 'weight', 'min_value' => 10,   'max_value' => 25,   'base_price' => 1990, 'sort_order' => 4],
            ['type' => 'weight', 'min_value' => 25,   'max_value' => 50,   'base_price' => 2990, 'sort_order' => 5],
            ['type' => 'weight', 'min_value' => 50,   'max_value' => 75,   'base_price' => 3990, 'sort_order' => 6],
            ['type' => 'weight', 'min_value' => 75,   'max_value' => 100,  'base_price' => 4990, 'sort_order' => 7],
            // Volume bands (m³)
            ['type' => 'volume', 'min_value' => 0,     'max_value' => 0.010, 'base_price' => 890,  'sort_order' => 1],
            ['type' => 'volume', 'min_value' => 0.010, 'max_value' => 0.020, 'base_price' => 1190, 'sort_order' => 2],
            ['type' => 'volume', 'min_value' => 0.020, 'max_value' => 0.040, 'base_price' => 1490, 'sort_order' => 3],
            ['type' => 'volume', 'min_value' => 0.040, 'max_value' => 0.100, 'base_price' => 1990, 'sort_order' => 4],
            ['type' => 'volume', 'min_value' => 0.100, 'max_value' => 0.200, 'base_price' => 2990, 'sort_order' => 5],
            ['type' => 'volume', 'min_value' => 0.200, 'max_value' => 0.300, 'base_price' => 3990, 'sort_order' => 6],
            ['type' => 'volume', 'min_value' => 0.300, 'max_value' => 0.400, 'base_price' => 4990, 'sort_order' => 7],
        ];

        foreach ($bands as $band) {
            PriceBand::updateOrCreate(
                ['type' => $band['type'], 'min_value' => $band['min_value'], 'max_value' => $band['max_value']],
                ['base_price' => $band['base_price'], 'sort_order' => $band['sort_order']]
            );
        }
    }
}
