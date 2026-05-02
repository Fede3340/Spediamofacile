<?php

namespace App\Http\Controllers\Shipping;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddSavedShipmentToCartRequest;
use App\Http\Requests\PackageStoreRequest;
use App\Http\Requests\UpdateSavedShipmentRequest;
use App\Http\Resources\PackageResource;
use App\Models\Package;
use App\Services\Shipping\SavedShipmentService;
use Illuminate\Http\Request;

class SavedShipmentController extends Controller
{
    public function __construct(private readonly SavedShipmentService $service) {}

    public function index(Request $request)
    {
        $packages = $this->service->listForUser(auth()->id());

        return PackageResource::collection($packages)
            ->additional(['meta' => ['empty' => $packages->isEmpty(), 'count' => $packages->count()]]);
    }

    public function store(PackageStoreRequest $request)
    {
        $userId = auth()->id();
        $data = $request->validated();

        if ($this->service->findDuplicate($userId, $data)) {
            return response()->json(['message' => 'Spedizione già configurata. Modifica almeno un dato per salvarla come nuova configurazione.'], 422);
        }

        return PackageResource::collection($this->service->create($userId, $data));
    }

    public function update(UpdateSavedShipmentRequest $request, $id)
    {
        $userId = auth()->id();

        if ($guard = $this->guardMutation($userId, $id)) {
            return $guard;
        }

        $package = Package::where('id', $id)->where('user_id', $userId)->firstOrFail();

        return new PackageResource($this->service->updatePackage($package, $request->validated()));
    }

    public function destroy($id)
    {
        $userId = auth()->id();

        if ($guard = $this->guardMutation($userId, $id)) {
            return $guard;
        }

        $this->service->delete($userId, $id);

        return response()->json(['message' => 'Spedizione rimossa']);
    }

    public function addToCart(AddSavedShipmentToCartRequest $request)
    {
        $copied = $this->service->copyToCart(auth()->id(), $request->package_ids);

        return response()->json(['message' => 'Spedizioni aggiunte al carrello', 'moved' => $copied]);
    }

    private function guardMutation(int $userId, int|string $packageId)
    {
        if (! $this->service->isSaved($userId, $packageId)) {
            return response()->json(['message' => 'Pacco non trovato nelle spedizioni salvate'], 404);
        }
        if ($this->service->isInOrder($packageId)) {
            return response()->json(['message' => 'Spedizione già associata a un ordine'], 409);
        }

        return null;
    }
}
