<?php

namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;
use App\Http\Requests\PackageStoreRequest;
use App\Http\Requests\UpdateCartItemQuantityRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\PackageResource;
use App\Models\Package;
use App\Services\Cart\CartItemService;

class CartItemController extends Controller
{
    public function __construct(
        private readonly CartItemService $service,
    ) {}

    public function show($id)
    {
        $userId = auth()->id();

        if (! $this->service->isInCart($userId, $id)) {
            return response()->json(['message' => 'Pacco non trovato nel carrello'], 404);
        }

        $package = Package::with(['originAddress', 'destinationAddress', 'service'])
            ->where('id', $id)->firstOrFail();

        return new PackageResource($package);
    }

    public function store(PackageStoreRequest $request)
    {
        $packages = $this->service->storePackages($request->validated(), auth()->id());

        return PackageResource::collection($packages);
    }

    public function update(UpdateCartItemRequest $request, $id)
    {
        $userId = auth()->id();

        if (! $request->filled('pudo.pudo_id') && is_array($request->input('selected_pudo'))) {
            $request->merge(['pudo' => $request->input('selected_pudo')]);
        }

        if (! $this->service->isInCart($userId, $id)) {
            return response()->json(['message' => 'Pacco non trovato nel carrello'], 404);
        }

        $package = Package::where('id', $id)->where('user_id', $userId)->firstOrFail();

        return new PackageResource($this->service->updatePackage($package, $request->validated()));
    }

    public function updateQuantity(UpdateCartItemQuantityRequest $request, $id)
    {
        $userId = auth()->id();

        if (! $this->service->isInCart($userId, $id)) {
            return response()->json(['message' => 'Pacco non trovato nel carrello'], 404);
        }

        if ($this->service->isInOrder($id)) {
            return response()->json(['message' => 'Pacco già associato a un ordine'], 409);
        }

        $package = Package::with(['originAddress', 'destinationAddress'])
            ->where('id', $id)->where('user_id', $userId)->firstOrFail();

        $result = $this->service->updateQuantity($package, (int) $request->quantity);

        return response()->json(['message' => 'Quantita aggiornata', ...$result]);
    }

    public function destroy($id)
    {
        $userId = auth()->id();

        if (! $this->service->isInCart($userId, $id)) {
            return response()->json(['message' => 'Pacco non trovato nel carrello'], 404);
        }

        if ($this->service->isInOrder($id)) {
            return response()->json(['message' => 'Pacco già associato a un ordine'], 409);
        }

        $this->service->destroy($userId, $id);

        return response()->json(['message' => 'Spedizione rimossa dal carrello']);
    }
}
