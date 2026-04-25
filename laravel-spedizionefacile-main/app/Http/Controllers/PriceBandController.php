<?php
/**
 * FILE: PriceBandController.php
 * SCOPO: Gestisce le fasce di prezzo (peso e volume) dal pannello admin.
 *        Include anche la gestione delle impostazioni promozionali.
 *
 * COSA ENTRA:
 *   - Request con array di bande per bulkUpdate
 *   - Request con impostazioni promo per savePromoSettings
 *
 * COSA ESCE:
 *   - JSON con bande raggruppate per tipo (index)
 *   - JSON con success per aggiornamento massivo (bulkUpdate)
 *   - JSON con impostazioni promo (getPromoSettings/savePromoSettings)
 *
 * VINCOLI:
 *   - I prezzi base e scontati sono in CENTESIMI nel DB (890 = 8,90 EUR)
 *   - La cache 'public_price_bands' viene invalidata dopo ogni modifica
 *   - Le impostazioni promo sono salvate nella tabella settings (modello chiave-valore)
 *   - L'immagine promo ha limite 2MB, l'immagine homepage 5MB
 *
 * ERRORI TIPICI:
 *   - 422: ID banda non esistente, prezzo negativo, immagine troppo grande
 *
 * PUNTI DI MODIFICA SICURI:
 *   - Per aggiungere una fascia: usare il seed o crearla da DB; il frontend si adatta automaticamente
 *   - Per aggiungere un campo promo: aggiungerlo in getPromoSettings() e savePromoSettings()
 *
 * COLLEGAMENTI:
 *   - PublicPriceBandController.php — endpoint pubblico che legge le fasce con cache
 *   - SessionController.php — findBandPrice() legge le fasce per calcolare i preventivi
 *   - pages/account/amministrazione/prezzi.vue — pannello admin prezzi
 */

namespace App\Http\Controllers;

use App\Models\PriceBand;
use App\Models\Setting;
use App\Services\EuropePriceEngineService;
use App\Services\PriceEngineService;
use App\Services\ShipmentServicePricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PriceBandController extends Controller
{
    public function __construct(
        private readonly PriceEngineService $priceEngine,
        private readonly EuropePriceEngineService $europePriceEngine,
        private readonly ShipmentServicePricingService $shipmentServicePricing,
    )
    {
    }

    // Lista tutte le bande raggruppate per tipo
    public function index(): JsonResponse
    {
        $config = $this->priceEngine->getPricingConfig();

        return response()->json([
            'data' => [
                'weight' => $config['weight'] ?? [],
                'volume' => $config['volume'] ?? [],
                'extra_rules' => $config['extra_rules'] ?? [],
                'supplements' => $config['supplements'] ?? [],
                'europe' => $this->europePriceEngine->getPricingConfig(),
                'service_pricing' => $this->shipmentServicePricing->getPricingConfig()['service_pricing'] ?? [],
                'automatic_supplements' => $this->shipmentServicePricing->getPricingConfig()['automatic_supplements'] ?? [],
                'operational_fees' => $this->shipmentServicePricing->getPricingConfig()['operational_fees'] ?? [],
                'version' => $config['version'] ?? null,
            ],
        ]);
    }

    // Inizializza le fasce di prezzo con i valori di default (seeder via API)
    public function seed(): JsonResponse
    {
        $seeder = new \Database\Seeders\PriceBandSeeder();
        $seeder->run();
        $this->priceEngine->clearPricingSettings();

        try { Cache::forget('public_price_bands'); } catch (\Exception $e) {}

        return response()->json([
            'success' => true,
            'message' => 'Fasce di prezzo inizializzate con successo.',
        ]);
    }

    // Aggiornamento massivo delle bande di prezzo
    public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            $payload = [];

            // Compatibilità legacy: body con array "bands" basato su ID DB
            if ($request->has('bands') && !$request->has('weight') && !$request->has('volume')) {
                $legacy = $request->validate([
                    'bands' => 'required|array|min:1',
                    'bands.*.id' => 'required',
                    'bands.*.base_price' => 'required|integer|min:0',
                    'bands.*.discount_price' => 'nullable|integer|min:1',
                    'bands.*.show_discount' => 'sometimes|boolean',
                ]);

                $current = $this->priceEngine->getPricingConfig();
                $map = [];
                foreach ($legacy['bands'] as $item) {
                    $map[(string) $item['id']] = $item;
                }

                $payload['weight'] = collect($current['weight'] ?? [])->map(function (array $band) use ($map) {
                    $id = (string) ($band['id'] ?? '');
                    if (isset($map[$id])) {
                        $band['base_price'] = (int) $map[$id]['base_price'];
                        $band['discount_price'] = $map[$id]['discount_price'] ?? null;
                        if (array_key_exists('show_discount', $map[$id])) {
                            $band['show_discount'] = (bool) $map[$id]['show_discount'];
                        }
                    }
                    return $band;
                })->values()->all();

                $payload['volume'] = collect($current['volume'] ?? [])->map(function (array $band) use ($map) {
                    $id = (string) ($band['id'] ?? '');
                    if (isset($map[$id])) {
                        $band['base_price'] = (int) $map[$id]['base_price'];
                        $band['discount_price'] = $map[$id]['discount_price'] ?? null;
                        if (array_key_exists('show_discount', $map[$id])) {
                            $band['show_discount'] = (bool) $map[$id]['show_discount'];
                        }
                    }
                    return $band;
                })->values()->all();

                $payload['extra_rules'] = $current['extra_rules'] ?? [];
                $payload['supplements'] = $current['supplements'] ?? [];
            } else {
                $payload = $request->validate([
                    'weight' => 'required|array|min:1',
                    'weight.*.min_value' => 'required|numeric|min:0',
                    'weight.*.max_value' => 'required|numeric|min:0',
                    'weight.*.base_price' => 'required|integer|min:0',
                    'weight.*.discount_price' => 'nullable|integer|min:1',
                    'weight.*.show_discount' => 'nullable|boolean',
                    'weight.*.sort_order' => 'nullable|integer|min:1',
                    'weight.*.id' => 'nullable',
                    'volume' => 'required|array|min:1',
                    'volume.*.min_value' => 'required|numeric|min:0',
                    'volume.*.max_value' => 'required|numeric|min:0',
                    'volume.*.base_price' => 'required|integer|min:0',
                    'volume.*.discount_price' => 'nullable|integer|min:1',
                    'volume.*.show_discount' => 'nullable|boolean',
                    'volume.*.sort_order' => 'nullable|integer|min:1',
                    'volume.*.id' => 'nullable',
                    'extra_rules' => 'required|array',
                    'extra_rules.enabled' => 'required|boolean',
                    'extra_rules.weight_start' => 'required|numeric|min:0',
                    'extra_rules.weight_step' => 'required|numeric|min:0.0001',
                    'extra_rules.volume_start' => 'required|numeric|min:0',
                    'extra_rules.volume_step' => 'required|numeric|min:0.0001',
                    'extra_rules.increment_cents' => 'required|integer|min:0',
                    'extra_rules.increment_mode' => 'nullable|in:flat',
                    'extra_rules.base_price_cents_mode' => 'required|in:last_band_effective,manual',
                    'extra_rules.base_price_cents_manual' => 'nullable|integer|min:1',
                    'extra_rules.weight_resolution' => 'required|numeric|min:0.0001',
                    'extra_rules.volume_resolution' => 'required|numeric|min:0.0001',
                    'supplements' => 'nullable|array',
                    'supplements.*.id' => 'nullable',
                    'supplements.*.prefix' => 'required|string',
                    'supplements.*.amount_cents' => 'required|integer|min:0',
                    'supplements.*.apply_to' => 'required|in:origin,destination,both',
                    'supplements.*.enabled' => 'nullable|boolean',
                    'europe' => 'nullable|array',
                    'europe.enabled' => 'nullable|boolean',
                    'europe.origin_country_code' => 'nullable|string|size:2',
                    'europe.max_packages' => 'nullable|integer|min:1|max:1',
                    'europe.max_quantity_per_package' => 'nullable|integer|min:1|max:1',
                    'europe.bands' => 'nullable|array|min:1',
                    'europe.bands.*.id' => 'required_with:europe.bands|string',
                    'europe.bands.*.label' => 'required_with:europe.bands|string',
                    'europe.bands.*.max_weight_kg' => 'required_with:europe.bands|numeric|min:0.001',
                    'europe.bands.*.max_volume_m3' => 'required_with:europe.bands|numeric|min:0.000001',
                    'europe.bands.*.volumetric_factor' => 'required_with:europe.bands|integer|min:1',
                    'europe.bands.*.rates' => 'required_with:europe.bands|array|min:1',
                    'europe.bands.*.rates.*.country_code' => 'required|string|size:2',
                    'europe.bands.*.rates.*.country_name' => 'required|string',
                    'europe.bands.*.rates.*.price_cents' => 'nullable|integer|min:1',
                    'europe.bands.*.rates.*.quote_required' => 'nullable|boolean',
                    'service_pricing' => 'nullable|array',
                    'automatic_supplements' => 'nullable|array',
                    'operational_fees' => 'nullable|array',
                ]);
            }

            $config = $this->priceEngine->savePricingConfig($payload);
            $europeConfig = $request->filled('europe')
                ? $this->europePriceEngine->savePricingConfig($payload['europe'])
                : $this->europePriceEngine->getPricingConfig();
            $shipmentPricingConfig = $this->shipmentServicePricing->savePricingConfig([
                'service_pricing' => $payload['service_pricing'] ?? [],
                'automatic_supplements' => $payload['automatic_supplements'] ?? [],
                'operational_fees' => $payload['operational_fees'] ?? [],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Configurazione prezzi non valida.',
                'errors' => $e->errors(),
            ], 422);
        }

        // Invalida la cache pubblica: i nuovi prezzi sono visibili subito
        try { Cache::forget('public_price_bands'); } catch (\Exception $e) {}

        return response()->json([
            'success' => true,
            'message' => 'Fasce di prezzo aggiornate con successo.',
            'data' => [
                'weight' => $config['weight'] ?? [],
                'volume' => $config['volume'] ?? [],
                'extra_rules' => $config['extra_rules'] ?? [],
                'supplements' => $config['supplements'] ?? [],
                'europe' => $europeConfig,
                'service_pricing' => $shipmentPricingConfig['service_pricing'] ?? [],
                'automatic_supplements' => $shipmentPricingConfig['automatic_supplements'] ?? [],
                'operational_fees' => $shipmentPricingConfig['operational_fees'] ?? [],
                'version' => $config['version'] ?? null,
            ],
        ]);
    }

    // Legge le impostazioni promozionali
    public function getPromoSettings(): JsonResponse
    {
        return response()->json([
            'data' => [
                'promo_active' => Setting::get('promo_active', 'false'),
                'promo_label_text' => Setting::get('promo_label_text', ''),
                'promo_label_color' => Setting::get('promo_label_color', '#E44203'),
                'promo_label_image' => Setting::get('promo_label_image'),
                'promo_show_badges' => Setting::get('promo_show_badges', 'true'),
                // Descrizione personalizzata dello sconto (es. "Sconto del 20% su tutte le spedizioni!")
                'promo_description' => Setting::get('promo_description', ''),
            ],
        ]);
    }

    // Salva le impostazioni promozionali
    public function savePromoSettings(\App\Http\Requests\SavePromoSettingsRequest $request): JsonResponse
    {
        $data = $request->validated();

        Setting::set('promo_active', $data['promo_active']);
        Setting::set('promo_label_text', $data['promo_label_text'] ?? '');
        Setting::set('promo_label_color', $data['promo_label_color'] ?? '#E44203');
        Setting::set('promo_show_badges', $data['promo_show_badges']);
        // Salva la descrizione dello sconto (es. "Sconto del 20% su tutte le spedizioni!")
        Setting::set('promo_description', $data['promo_description'] ?? '');

        // Invalida cache pubblica per mostrare subito le modifiche promo
        try { Cache::forget('public_price_bands'); } catch (\Exception $e) {}

        return response()->json([
            'success' => true,
            'message' => 'Impostazioni promozionali salvate con successo.',
        ]);
    }

    // Upload immagine promozionale.
    // Sprint 6.7 security hardening: PromoImageUploadRequest + ImageSanitizer.
    public function uploadPromoImage(\App\Http\Requests\PromoImageUploadRequest $request, \App\Services\Security\ImageSanitizer $sanitizer): JsonResponse
    {
        $path = $sanitizer->sanitizeAndStore(
            $request->file('image'),
            'promo',
            'public'
        );
        Setting::set('promo_label_image', '/storage/' . $path);

        // Invalida cache pubblica
        try { Cache::forget('public_price_bands'); } catch (\Exception $e) {}

        return response()->json([
            'success' => true,
            'image_url' => '/storage/' . $path,
        ]);
    }
}
