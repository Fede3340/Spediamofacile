<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class PackageStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $pudo = $this->input('pudo');
        $selectedPudo = $this->input('selected_pudo');
        if ((empty($pudo) || ! is_array($pudo)) && is_array($selectedPudo) && ! empty($selectedPudo)) {
            $this->merge([
                'pudo' => $selectedPudo,
            ]);
        }

        $services = $this->input('services', []);
        if (is_array($services) && isset($services['serviceData']) && is_array($services['serviceData'])) {
            $services['service_data'] = $services['service_data'] ?? $services['serviceData'];
            unset($services['serviceData']);
        }

        if (is_array($services)) {
            $services['service_data'] = is_array($services['service_data'] ?? null) ? $services['service_data'] : [];
            $pickupRequest = $services['service_data']['pickup_request'] ?? [];
            if (! is_array($pickupRequest)) {
                $pickupRequest = [];
            }

            $pickupDate = trim((string) ($services['date'] ?? $pickupRequest['date'] ?? $this->input('pickup_date', '')));
            $pickupTime = trim((string) ($services['time'] ?? $pickupRequest['time_slot'] ?? '09:00-18:00'));

            if ($pickupDate !== '' || ! empty($pickupRequest)) {
                $services['service_data']['pickup_request'] = [
                    'enabled' => (bool) ($pickupRequest['enabled'] ?? ($pickupDate !== '')),
                    'date' => $this->normalizePickupRequestDate($pickupDate),
                    'time_slot' => $pickupTime !== '' ? $pickupTime : '09:00-18:00',
                    'notes' => trim((string) ($pickupRequest['notes'] ?? '')),
                ];
                $services['date'] = $pickupDate;
                $services['time'] = $services['service_data']['pickup_request']['time_slot'];
            }
        }

        $packages = collect($this->input('packages', []))
            ->map(function ($package) {
                if (! is_array($package)) {
                    return $package;
                }

                return Arr::except($package, [
                    'weight_price',
                    'volume_price',
                    'single_price',
                    'single_price_cents',
                    'pricing_signature',
                    'pricing_snapshot',
                    'pricing_snapshot_version',
                ]);
            })
            ->all();

        $this->merge([
            'services' => $services,
            'packages' => $packages,
            'pricing_signature' => null,
            'pricing_snapshot' => null,
            'pricing_snapshot_version' => null,
        ]);
    }

    public function rules(): array
    {
        return [
            /* Indirizzo di partenza - da dove parte il pacco */
            'origin_address.type' => 'required|string|max:50',
            'origin_address.name' => 'required|string|max:200',
            'origin_address.additional_information' => 'nullable|string|max:500',
            'origin_address.address' => 'required|string|max:300',
            'origin_address.number_type' => 'required|string|max:50',
            'origin_address.address_number' => 'required|string|max:20',
            'origin_address.intercom_code' => 'nullable|string|max:50',
            'origin_address.country' => 'required|string|max:100',
            'origin_address.city' => 'required|string|max:200',
            'origin_address.postal_code' => 'required|string|max:10',
            'origin_address.province' => 'required|string|max:10',
            'origin_address.telephone_number' => 'required|string|max:20',
            'origin_address.email' => 'nullable|string|max:200',

            /* Indirizzo di destinazione - dove deve arrivare il pacco */
            'destination_address.type' => 'required|string|max:50',
            'destination_address.name' => 'required|string|max:200',
            'destination_address.additional_information' => 'nullable|string|max:500',
            'destination_address.address' => 'required|string|max:300',
            'destination_address.number_type' => 'required|string|max:50',
            'destination_address.address_number' => 'required|string|max:20',
            'destination_address.intercom_code' => 'nullable|string|max:50',
            'destination_address.country' => 'required|string|max:100',
            'destination_address.city' => 'required|string|max:200',
            'destination_address.postal_code' => 'required|string|max:10',
            'destination_address.province' => 'required|string|max:10',
            'destination_address.telephone_number' => 'required|string|max:20',
            'destination_address.email' => 'nullable|string|max:200',

            /* Servizi opzionali (tipo di spedizione, data e orario di ritiro) */
            'services.service_type' => 'nullable|string|max:500',
            'services.date' => 'nullable|string|max:20',
            'services.time' => 'nullable|string|max:20',
            'services.service_data' => 'nullable|array',
            'services.sms_email_notification' => 'nullable|boolean',
            'sms_email_notification' => 'nullable|boolean',

            /* Pacchi - almeno 1 pacco, massimo 50 */
            'packages' => 'required|array|min:1|max:50',
            'packages.*.package_type' => 'required|string|max:50',           // Tipo (busta, scatola...)
            'packages.*.quantity' => 'required|integer|min:1|max:999',       // Quantita' (da 1 a 999)
            'packages.*.weight' => 'required|numeric|min:0.1|max:9999',     // Peso minimo 0.1 kg
            'packages.*.first_size' => 'required|numeric|min:1|max:9999',   // Lunghezza minimo 1 cm
            'packages.*.second_size' => 'required|numeric|min:1|max:9999',  // Larghezza minimo 1 cm
            'packages.*.third_size' => 'required|numeric|min:1|max:9999',   // Altezza minimo 1 cm
            /* Descrizione del contenuto del pacco (opzionale) */
            'content_description' => 'nullable|string|max:255',
            'billing_data' => 'nullable|array',
            'client_submission_id' => 'nullable|string|max:255',
            'discount_context' => 'nullable|array',

            /* PUDO - Punto di ritiro BRT (opzionale) */
            /* delivery_mode: 'home' = domicilio, 'pudo' = ritiro in punto BRT convenzionato */
            'delivery_mode' => 'nullable|string|in:home,pudo',
            /* pudo: oggetto con i dati del punto BRT selezionato (pudo_id, name, address, ecc.) */
            'pudo' => 'nullable|array',
            'pudo.pudo_id' => 'nullable|string|max:100',
            'pudo.name' => 'nullable|string|max:300',
            'pudo.address' => 'nullable|string|max:300',
            'pudo.city' => 'nullable|string|max:200',
            'pudo.zip_code' => 'nullable|string|max:10',

        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $deliveryMode = (string) $this->input(
                'delivery_mode',
                data_get($this->input('services', []), 'service_data.delivery_mode', 'home')
            );

            if ($deliveryMode !== 'pudo') {
                return;
            }

            $pudoId = trim((string) data_get($this->input('pudo', []), 'pudo_id', ''));
            if ($pudoId === '') {
                $validator->errors()->add('pudo.pudo_id', 'Seleziona un punto BRT valido.');
            }
        });
    }

    private function normalizePickupRequestDate(string $pickupDate): string
    {
        $pickupDate = trim($pickupDate);
        if ($pickupDate === '') {
            return '';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $pickupDate)) {
            return $pickupDate;
        }

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $pickupDate, $matches)) {
            return sprintf('%s-%02d-%02d', $matches[3], (int) $matches[2], (int) $matches[1]);
        }

        return $pickupDate;
    }
}
