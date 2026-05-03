<?php

namespace App\Services\Cart;

use App\Models\Package;
use App\Services\PriceEngineService;

/**
 * Pricing puntuale dei pacchi del carrello.
 * Calcoli sono in centesimi per evitare arrotondamenti float.
 */
class CartItemPricingService
{
    public function __construct(private readonly PriceEngineService $priceEngine) {}

    public function euroToCents(float|int|null $euro): int
    {
        return (int) round(($euro ?? 0) * 100);
    }

    public function unitPrice(int $totalPriceCents, int $quantity): int
    {
        return $quantity > 0 ? (int) round($totalPriceCents / $quantity) : $totalPriceCents;
    }

    public function mergeQuantity(int $existingPriceCents, int $existingQty, int $addedQty, ?int $newUnitPriceCents = null): array
    {
        $unitPrice = $newUnitPriceCents ?? $this->unitPrice($existingPriceCents, $existingQty);
        $totalQty = $existingQty + $addedQty;

        return ['quantity' => $totalQty, 'single_price' => $unitPrice * $totalQty];
    }

    public function pricePackageData(
        array $packageData,
        array $originAddress = [],
        array $destinationAddress = [],
        array $fallback = [],
    ): array {
        $payload = $fallback;
        foreach ($packageData as $key => $value) {
            if ($value !== null) {
                $payload[$key] = $value;
            }
        }
        $quantity = min(999, max(1, (int) ($payload['quantity'] ?? 1)));

        $weight = max(0, (float) preg_replace('/[^0-9.]/', '', (string) ($payload['weight'] ?? '0')));
        $firstSize = max(0, (float) preg_replace('/[^0-9.]/', '', (string) ($payload['first_size'] ?? '0')));
        $secondSize = max(0, (float) preg_replace('/[^0-9.]/', '', (string) ($payload['second_size'] ?? '0')));
        $thirdSize = max(0, (float) preg_replace('/[^0-9.]/', '', (string) ($payload['third_size'] ?? '0')));

        $volumeM3 = ($firstSize / 100) * ($secondSize / 100) * ($thirdSize / 100);

        $weightPriceCents = $weight > 0 ? $this->priceEngine->calculateBandPriceCents('weight', $weight) : 0;
        $volumePriceCents = $volumeM3 > 0 ? $this->priceEngine->calculateBandPriceCents('volume', $volumeM3) : 0;

        $capSupplementCents = $this->priceEngine->calculateCapSupplementCents(
            $originAddress['postal_code'] ?? null,
            $destinationAddress['postal_code'] ?? null,
        );

        $unitPriceCents = max($weightPriceCents, $volumePriceCents) + $capSupplementCents;

        return array_merge($payload, [
            'quantity' => $quantity,
            'weight_price' => round($weightPriceCents / 100, 2),
            'volume_price' => round($volumePriceCents / 100, 2),
            'single_price' => $unitPriceCents * $quantity,
            'unit_price_cents' => $unitPriceCents,
        ]);
    }

    public function pricePackageModel(Package $package, ?int $quantity = null): array
    {
        $origin = $package->originAddress?->toArray() ?? [];
        $destination = $package->destinationAddress?->toArray() ?? [];

        return $this->pricePackageData([
            'package_type' => $package->package_type,
            'quantity' => $quantity ?? (int) $package->quantity,
            'weight' => $package->weight,
            'first_size' => $package->first_size,
            'second_size' => $package->second_size,
            'third_size' => $package->third_size,
        ], $origin, $destination);
    }

    public function normalizePackagePricing($packages): int
    {
        $updated = 0;

        foreach ($packages as $package) {
            $priced = $this->pricePackageModel($package);

            if (
                (int) $package->quantity !== (int) $priced['quantity']
                || (float) $package->weight_price !== (float) $priced['weight_price']
                || (float) $package->volume_price !== (float) $priced['volume_price']
                || (int) $package->single_price !== (int) $priced['single_price']
            ) {
                $package->update([
                    'quantity' => $priced['quantity'],
                    'weight_price' => $priced['weight_price'],
                    'volume_price' => $priced['volume_price'],
                    'single_price' => $priced['single_price'],
                ]);
                $updated++;
            }
        }

        return $updated;
    }
}
