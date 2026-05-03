<?php

namespace App\Services\Cart;

use App\Models\Package;
use Illuminate\Support\Facades\DB;

/**
 * Merge di pacchi identici nel carrello (stesso package_type, dimensioni, indirizzi, servizio).
 * Calcola la merge key + applica la fusione preservando i pacchi referenziati da
 * saved_shipments / package_order (storia ordini).
 */
class CartMergeService
{
    public function __construct(
        private readonly CartItemPricingService $pricing,
        private readonly CartSignatureService $signature,
    ) {}

    public function buildMergeKey(Package $pkg): string
    {
        $o = $pkg->originAddress;
        $d = $pkg->destinationAddress;
        $s = $pkg->service;
        $normalize = fn (?string $value) => mb_strtolower(trim($value ?? ''), 'UTF-8');

        return implode('|', [
            $normalize($pkg->package_type),
            (string) $pkg->weight, (string) $pkg->first_size, (string) $pkg->second_size, (string) $pkg->third_size,
            $o ? $normalize($o->name).'|'.$normalize($o->address).'|'.$normalize($o->city).'|'.$normalize($o->postal_code) : 'no-origin',
            $d ? $normalize($d->name).'|'.$normalize($d->address).'|'.$normalize($d->city).'|'.$normalize($d->postal_code) : 'no-dest',
            $s ? $normalize($s->service_type) : 'nessuno',
            $s ? $this->signature->buildFromService($s) : 'no-service-data',
        ]);
    }

    public function mergeIdenticalPackages($packages, int $userId): int
    {
        $cartPackageIds = DB::table('cart_user')
            ->where('user_id', $userId)
            ->whereIn('package_id', collect($packages)->pluck('id')->all())
            ->pluck('package_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $packages = collect($packages)
            ->filter(fn ($pkg) => $cartPackageIds->contains((int) $pkg->id))
            ->values();

        if ($packages->count() < 2) {
            return 0;
        }

        $protectedPackageIds = DB::table('saved_shipments')
            ->whereIn('package_id', $cartPackageIds->all())
            ->pluck('package_id')
            ->merge(
                DB::table('package_order')
                    ->whereIn('package_id', $cartPackageIds->all())
                    ->pluck('package_id')
            )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();
        $protectedLookup = array_fill_keys($protectedPackageIds, true);

        $groups = [];
        foreach ($packages as $pkg) {
            $groups[$this->buildMergeKey($pkg)][] = $pkg;
        }

        $merged = 0;
        foreach ($groups as $groupPackages) {
            if (count($groupPackages) < 2) {
                continue;
            }

            usort($groupPackages, function (Package $left, Package $right) use ($protectedLookup) {
                $leftProtected = isset($protectedLookup[(int) $left->id]) ? 1 : 0;
                $rightProtected = isset($protectedLookup[(int) $right->id]) ? 1 : 0;

                if ($leftProtected !== $rightProtected) {
                    return $rightProtected <=> $leftProtected;
                }

                return $left->id <=> $right->id;
            });

            $master = $groupPackages[0];
            $masterQty = (int) $master->quantity;
            $groupMerged = 0;

            for ($i = 1; $i < count($groupPackages); $i++) {
                $dup = $groupPackages[$i];

                if (isset($protectedLookup[(int) $dup->id])) {
                    continue;
                }

                $masterQty += (int) $dup->quantity;
                DB::table('cart_user')->where('user_id', $userId)->where('package_id', $dup->id)->delete();
                $dup->delete();
                $merged++;
                $groupMerged++;
            }

            if ($groupMerged === 0) {
                continue;
            }

            $pricedMaster = $this->pricing->pricePackageModel($master, $masterQty);

            $master->update([
                'quantity' => $pricedMaster['quantity'],
                'weight_price' => $pricedMaster['weight_price'],
                'volume_price' => $pricedMaster['volume_price'],
                'single_price' => $pricedMaster['single_price'],
            ]);
        }

        return $merged;
    }
}
