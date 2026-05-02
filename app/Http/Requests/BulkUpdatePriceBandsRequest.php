<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione bulk update bande prezzo (admin).
 * Copre weight, volume, extra_rules, supplements e blocco europe.
 */
class BulkUpdatePriceBandsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return array_merge(
            $this->bandRules('weight'),
            $this->bandRules('volume'),
            $this->extraRulesRules(),
            $this->supplementRules(),
            $this->europeRules(),
            [
                'service_pricing' => 'nullable|array',
                'automatic_supplements' => 'nullable|array',
                'operational_fees' => 'nullable|array',
            ],
        );
    }

    private function bandRules(string $key): array
    {
        return [
            $key => 'required|array|min:1',
            "{$key}.*.min_value" => 'required|numeric|min:0',
            "{$key}.*.max_value" => 'required|numeric|min:0',
            "{$key}.*.base_price" => 'required|integer|min:0',
            "{$key}.*.discount_price" => 'nullable|integer|min:1',
            "{$key}.*.show_discount" => 'nullable|boolean',
            "{$key}.*.sort_order" => 'nullable|integer|min:1',
            "{$key}.*.id" => 'nullable',
        ];
    }

    private function extraRulesRules(): array
    {
        return [
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
        ];
    }

    private function supplementRules(): array
    {
        return [
            'supplements' => 'nullable|array',
            'supplements.*.id' => 'nullable',
            'supplements.*.prefix' => 'required|string',
            'supplements.*.amount_cents' => 'required|integer|min:0',
            'supplements.*.apply_to' => 'required|in:origin,destination,both',
            'supplements.*.enabled' => 'nullable|boolean',
        ];
    }

    private function europeRules(): array
    {
        return [
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
        ];
    }
}
