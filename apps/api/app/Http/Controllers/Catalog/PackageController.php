<?php
namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;

use App\Cart\MyMoney;
use App\Models\PackageAddress;
use App\Models\Package;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\PackageResource;
use App\Http\Requests\PackageStoreRequest;
class PackageController extends Controller
{
    // Mostra la lista di tutti i pacchi dell'utente attualmente loggato
    public function index(Request $request) {

        $packages = Package::where('user_id', auth()->id())->get();

        return PackageResource::collection($packages);

    }

    /* Le funzioni seguenti sono state commentate perche' la logica del carrello
       e' stata spostata in altri controller (CartController, GuestCartController).
       Sono state lasciate come riferimento per capire come funzionava prima. */

    /* public function subtotal($packages) {

        $subtotal = $packages->sum(function($package) {
            return (int) $package->single_price * $package->quantity;
        });

        return new MyMoney($subtotal);
    }

    public function total($packages) {
        $sixEuro = new MyMoney(600); // 600 centesimi = 6€
        return $this->subtotal($packages)->add($sixEuro);
    }

    protected function meta($packages) {
        return [
            'empty' => $packages->isEmpty(),
            'subtotal' => $this->subtotal($packages)->formatted(),
            'total' => $this->total($packages)->formatted()
        ];
    } */

    /* public function show(Package $package) {

        return new PackageResource($package);
    } */

    /* public function store(PackageStoreRequest $request) {

        $data = $request->validated();

        $outPackages = DB::transaction(function() use ($data) {
            $origin = PackageAddress::create($data['origin_address']);
            $destination = PackageAddress::create($data['destination_address']);

            $services = Service::create($data['services']);

            $authId = auth()->id();
            $packages = [];

            foreach ($data['packages'] as $packageData) {
                $packages[] = Package::create([
                    'package_type' => $packageData['package_type'],
                    'quantity' => $packageData['quantity'],
                    'weight' => $packageData['weight'],
                    'first_size' => $packageData['first_size'],
                    'second_size' => $packageData['second_size'],
                    'third_size' => $packageData['third_size'],
                    'weight_price' => $packageData['weight_price'] ?? null,
                    'volume_price' => $packageData['volume_price'] ?? null,
                    'single_price' => $packageData['single_price'] * 100 ?? null,
                    'origin_address_id' => $origin->id,
                    'destination_address_id' => $destination->id,
                    'service_id' => $services->id,
                    'user_id' => $authId,
                ]);
            }

            return $packages;
        });

        return PackageResource::collection($outPackages);
    } */

    /* public function emptyCart() {
        Package::where('user_id', auth()->id())->delete();

        return response()->json(['message' => 'Carrello svuotato']);
    } */

}
