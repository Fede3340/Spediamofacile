<?php

namespace App\Services\Account;

use App\Models\User;
use App\Models\UserAddress;

/**
 * SERVICE: Gestione indirizzi rubrica utente.
 *
 * Estrae normalizzazione, dedup e CRUD dal controller per mantenerlo thin.
 * Limite massimo: 5 indirizzi per utente.
 */
class UserAddressService
{
    public const MAX_ADDRESSES = 5;

    /**
     * @var string[] Campi confrontati per il dedup (signature normalizzata).
     */
    private const SIGNATURE_FIELDS = [
        'name', 'additional_information', 'address', 'address_number',
        'intercom_code', 'country', 'city', 'postal_code', 'province',
        'telephone_number', 'email',
    ];

    public function hasReachedLimit(User $user): bool
    {
        return $user->addresses()->count() >= self::MAX_ADDRESSES;
    }

    public function hasDuplicate(User $user, array $data): bool
    {
        $candidate = $this->signature($data);

        return $user->addresses()
            ->get(self::SIGNATURE_FIELDS)
            ->contains(fn ($a) => $this->signature($a->toArray()) === $candidate);
    }

    /**
     * Normalizza i default frontend e crea l'indirizzo legato all'utente.
     */
    public function create(User $user, array $data): UserAddress
    {
        $data['type'] = $data['type'] ?? 'shipping';
        $data['country'] = $data['country'] ?? 'IT';
        $data['number_type'] = $data['number_type'] ?? 'civico';
        $data['address_number'] = $data['address_number'] ?? '';

        $address = new UserAddress($data);
        $user->addresses()->save($address);

        return $address;
    }

    /**
     * Aggiorna solo i campi effettivamente cambiati per evitare scritture inutili.
     */
    public function update(UserAddress $address, array $validated): void
    {
        $changes = [];
        foreach ($validated as $key => $value) {
            if ($value !== $address->$key) {
                $changes[$key] = $value;
            }
        }

        if (! empty($changes)) {
            $address->update($changes);
        }
    }

    public function setAsDefault(UserAddress $address): void
    {
        $address->update(['default' => true]);
    }

    /**
     * Firma normalizzata usata per riconoscere indirizzi duplicati
     * indipendentemente da spazi, maiuscole o varianti di "Italia"/"IT".
     */
    private function signature(array $data): string
    {
        return json_encode([
            'name' => $this->text($data['name'] ?? ''),
            'additional_information' => $this->text($data['additional_information'] ?? ''),
            'address' => $this->text($data['address'] ?? ''),
            'address_number' => $this->text($data['address_number'] ?? ''),
            'intercom_code' => $this->text($data['intercom_code'] ?? ''),
            'country' => $this->country($data['country'] ?? 'Italia'),
            'city' => $this->text($data['city'] ?? ''),
            'postal_code' => $this->postalCode($data),
            'province' => $this->text($data['province'] ?? ''),
            'telephone_number' => preg_replace('/\s+/', '', (string) ($data['telephone_number'] ?? '')),
            'email' => strtolower(trim((string) ($data['email'] ?? ''))),
        ]);
    }

    private function text(?string $value): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', (string) $value)));
    }

    private function country(?string $value): string
    {
        $normalized = $this->text($value);

        return in_array($normalized, ['italia', 'it'], true) ? 'it' : $normalized;
    }

    private function postalCode(array $data): string
    {
        $country = $this->country($data['country'] ?? 'Italia');
        $postalCode = (string) ($data['postal_code'] ?? '');

        if ($country === 'it') {
            return preg_replace('/[^0-9]/', '', $postalCode);
        }

        return trim(strtoupper(preg_replace('/[^A-Z0-9-\s]/i', '', $postalCode)));
    }
}
