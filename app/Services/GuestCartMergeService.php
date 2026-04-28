<?php

namespace App\Services;

use App\Models\Package;
use App\Models\PackageAddress;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * GuestCartMergeService - Transfers guest session cart into authenticated user's DB cart.
 *
 * Extracted from CustomLoginController::mergeGuestCartIntoUserCart() and ::createPackage().
 * Called after login/verification to preserve packages added before authentication.
 */
class GuestCartMergeService
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    /**
     * Merge guest cart packages into the authenticated user's database cart.
     *
     * Does nothing if the session has no cart data. Failures are logged
     * but never block the login flow.
     */
    public function merge(array $guestPackages, User $user): void
    {
        if (empty($guestPackages)) {
            return;
        }

        DB::transaction(function () use ($guestPackages, $user) {
            $dbPackages = $this->createPackagesFromSessionData($guestPackages, $user);

            foreach ($dbPackages as $package) {
                DB::table('cart_user')->insert([
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                    'created_at' => now(),
                ]);
            }

            $cartPackageIds = DB::table('cart_user')
                ->where('user_id', $user->id)
                ->pluck('package_id');

            $mergedPackages = Package::with(['originAddress', 'destinationAddress', 'service'])
                ->whereIn('id', $cartPackageIds)
                ->get();

            $this->cartService->mergeIdenticalPackages($mergedPackages, $user->id);

            $cartPackageIds = DB::table('cart_user')
                ->where('user_id', $user->id)
                ->pluck('package_id');

            $mergedPackages = Package::with(['originAddress', 'destinationAddress', 'service'])
                ->whereIn('id', $cartPackageIds)
                ->get();

            $this->cartService->normalizePackagePricing($mergedPackages);
        });
    }

    /**
     * Create Package models from raw session cart data.
     *
     * @return Package[]
     */
    public function createPackagesFromSessionData(array $packages, User $user): array
    {
        $createdPackages = [];

        foreach ($packages as $package) {
            $pricedPackage = $this->cartService->pricePackageData(
                $package,
                $package['origin_address'] ?? [],
                $package['destination_address'] ?? [],
            );

            $origin = PackageAddress::create($pricedPackage['origin_address'] ?? $package['origin_address']);
            $destination = PackageAddress::create($pricedPackage['destination_address'] ?? $package['destination_address']);
            $services = Service::create($pricedPackage['services'] ?? $package['services']);

            $createdPackages[] = Package::create([
                'package_type' => $pricedPackage['package_type'],
                'quantity' => $pricedPackage['quantity'],
                'weight' => $pricedPackage['weight'],
                'first_size' => $pricedPackage['first_size'],
                'second_size' => $pricedPackage['second_size'],
                'third_size' => $pricedPackage['third_size'],
                'weight_price' => $pricedPackage['weight_price'] ?? null,
                'volume_price' => $pricedPackage['volume_price'] ?? null,
                'single_price' => $pricedPackage['single_price'] ?? null,
                'origin_address_id' => $origin->id,
                'destination_address_id' => $destination->id,
                'service_id' => $services->id,
                'user_id' => $user->id,
            ]);
        }

        return $createdPackages;
    }
}
