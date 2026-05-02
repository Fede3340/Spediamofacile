<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserAddressStoreRequest;
use App\Http\Resources\UserAddressResource;
use App\Models\UserAddress;
use App\Support\CustomResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class UserAddressController extends Controller
{
    private function normalizeAddressText(?string $value): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', (string) $value)));
    }

    private function normalizeAddressCountry(?string $value): string
    {
        $normalized = $this->normalizeAddressText($value);

        return in_array($normalized, ['italia', 'it'], true) ? 'it' : $normalized;
    }

    private function normalizeAddressPhone(?string $value): string
    {
        return preg_replace('/\s+/', '', (string) $value);
    }

    private function normalizeAddressEmail(?string $value): string
    {
        return strtolower(trim((string) $value));
    }

    private function normalizeAddressPostalCode(array $data): string
    {
        $country = $this->normalizeAddressCountry($data['country'] ?? 'Italia');
        $postalCode = (string) ($data['postal_code'] ?? '');

        if ($country === 'it') {
            return preg_replace('/[^0-9]/', '', $postalCode);
        }

        return trim(strtoupper(preg_replace('/[^A-Z0-9-\s]/i', '', $postalCode)));
    }

    private function getAddressSignature(array $data): string
    {
        return json_encode([
            'name' => $this->normalizeAddressText($data['name'] ?? ''),
            'additional_information' => $this->normalizeAddressText($data['additional_information'] ?? ''),
            'address' => $this->normalizeAddressText($data['address'] ?? ''),
            'address_number' => $this->normalizeAddressText($data['address_number'] ?? ''),
            'intercom_code' => $this->normalizeAddressText($data['intercom_code'] ?? ''),
            'country' => $this->normalizeAddressCountry($data['country'] ?? 'Italia'),
            'city' => $this->normalizeAddressText($data['city'] ?? ''),
            'postal_code' => $this->normalizeAddressPostalCode($data),
            'province' => $this->normalizeAddressText($data['province'] ?? ''),
            'telephone_number' => $this->normalizeAddressPhone($data['telephone_number'] ?? ''),
            'email' => $this->normalizeAddressEmail($data['email'] ?? ''),
        ]);
    }

    private function hasDuplicateAddress($user, array $data): bool
    {
        $candidateSignature = $this->getAddressSignature($data);

        return $user->addresses()
            ->get([
                'name',
                'additional_information',
                'address',
                'address_number',
                'intercom_code',
                'country',
                'city',
                'postal_code',
                'province',
                'telephone_number',
                'email',
            ])
            ->contains(function (UserAddress $address) use ($candidateSignature) {
                return $this->getAddressSignature($address->toArray()) === $candidateSignature;
            });
    }

    // Mostra la lista di tutti gli indirizzi dell'utente
    // Li ordina mettendo prima quello predefinito (default)
    public function index(Request $request)
    {

        $addresses = auth()->user()->addresses()->orderBy('default', 'desc')->get();

        return UserAddressResource::collection($addresses);
    }

    // Mostra i dettagli di un singolo indirizzo
    public function show(UserAddress $user_address)
    {

        return new UserAddressResource($user_address);
    }

    // Crea un nuovo indirizzo nella rubrica dell'utente
    // L'utente puo' avere al massimo 5 indirizzi salvati
    public function store(UserAddressStoreRequest $request)
    {
        $user = auth()->user();

        // Controlliamo che l'utente non abbia gia' raggiunto il limite di 5 indirizzi
        if ($user->addresses()->count() >= 5) {
            $errorMsg = 'Hai raggiunto il numero massimo di indirizzi';

            return CustomResponse::setFailResponse($errorMsg, Response::HTTP_NOT_ACCEPTABLE);
        }

        // Prendiamo solo i campi che ci servono dalla richiesta
        $data = $request->only(
            [
                'type',
                'name',
                'additional_information',
                'address',
                'number_type',
                'address_number',
                'intercom_code',
                'country',
                'city',
                'postal_code',
                'province',
                'telephone_number',
                'email',
                'default',
            ]
        );

        // Se la provincia non e' stata fornita ma c'e' il nome della provincia, usiamo quello
        if (empty($data['province']) && $request->has('province_name')) {
            $data['province'] = $request->input('province_name');
        }

        // Impostiamo valori predefiniti per i campi che il frontend potrebbe non inviare
        $data['type'] = $data['type'] ?? 'shipping';        // Tipo: spedizione (default)
        $data['country'] = $data['country'] ?? 'IT';        // Paese: Italia (default)
        $data['number_type'] = $data['number_type'] ?? 'civico';  // Tipo numero: civico (default)
        $data['address_number'] = $data['address_number'] ?? '';   // Numero: vuoto se non specificato

        if ($this->hasDuplicateAddress($user, $data)) {
            return CustomResponse::setFailResponse(
                'Questo indirizzo è già presente tra gli indirizzi salvati.',
                Response::HTTP_CONFLICT
            );
        }

        // Creiamo l'indirizzo e lo colleghiamo all'utente
        $user_address = new UserAddress($data);
        $user->addresses()->save($user_address);

        return new UserAddressResource($user_address);
    }

    // Modifica un indirizzo esistente dell'utente
    // Puo' aggiornare tutti i campi oppure solo impostare l'indirizzo come predefinito
    public function update(Request $request, UserAddress $user_address)
    {

        // Controllo di sicurezza: verifica che l'utente abbia il permesso di modificare questo indirizzo
        // (ogni utente puo' modificare solo i PROPRI indirizzi)
        Gate::authorize('update', $user_address);

        // Se la richiesta contiene solo il campo "default", impostiamo l'indirizzo come predefinito
        if ($request->has('default')) {
            $user_address->update(['default' => true]);
        } else {
            // Se l'utente sta modificando gli altri campi dell'indirizzo
            $validated = $request->validate([
                'name' => 'required|string',
                'additional_information' => 'nullable|string',
                'address' => 'required|string',
                'number_type' => 'required|string',
                'address_number' => 'required|string',
                'intercom_code' => 'nullable|string',
                'country' => 'required|string',
                'city' => 'required|string',
                'postal_code' => 'required|string',
                'province' => 'required|string',
                'telephone_number' => 'required|string',
                'email' => 'nullable|string',
                'default' => 'nullable',
            ]);

            // Aggiorniamo solo i campi che sono effettivamente cambiati
            // (per evitare di fare scritture inutili nel database)
            $updateData = [];
            foreach ($validated as $key => $value) {
                if ($value !== $user_address->$key) {
                    $updateData[$key] = $value;
                }
            }

            if (! empty($updateData)) {
                $user_address->update($updateData);
            }
        }

        return CustomResponse::setSuccessResponse('Modifica effettuata con successo', Response::HTTP_OK);

    }

    // Elimina un indirizzo dalla rubrica dell'utente
    public function destroy(UserAddress $user_address)
    {

        // Controllo di sicurezza: verifica che l'utente abbia il permesso di eliminare questo indirizzo
        Gate::authorize('delete', $user_address);
        $user_address->delete();
    }
}
