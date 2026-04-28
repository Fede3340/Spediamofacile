<?php

namespace Tests\Unit\Services;

use App\Cart\MyMoney;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    private CartService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CartService();
    }

    public function test_euro_to_cents_converte_correttamente(): void
    {
        $this->assertSame(890, $this->service->euroToCents(8.90));
        $this->assertSame(2000, $this->service->euroToCents(20));
        $this->assertSame(0, $this->service->euroToCents(null));
    }

    public function test_unit_price_calcola_prezzo_unitario_da_totale_e_quantita(): void
    {
        $this->assertSame(500, $this->service->unitPrice(1000, 2));
        // Quantita zero: ritorna il totale senza dividere (evita divisione per zero).
        $this->assertSame(1000, $this->service->unitPrice(1000, 0));
    }

    public function test_merge_quantity_somma_quantita_mantenendo_prezzo_unitario(): void
    {
        // esistente: 2 pezzi a 500 cent = 1000 cent totali, aggiungiamo 3 pezzi
        $result = $this->service->mergeQuantity(1000, 2, 3);

        $this->assertSame(5, $result['quantity']);
        $this->assertSame(2500, $result['single_price']); // 500 * 5
    }

    public function test_same_package_dimensions_rileva_duplicati(): void
    {
        $a = ['package_type' => 'Pacco', 'weight' => '5', 'first_size' => '30', 'second_size' => '20', 'third_size' => '10'];
        $b = ['package_type' => 'Pacco', 'weight' => '5', 'first_size' => '30', 'second_size' => '20', 'third_size' => '10'];
        $c = ['package_type' => 'Pacco', 'weight' => '6', 'first_size' => '30', 'second_size' => '20', 'third_size' => '10'];

        $this->assertTrue($this->service->samePackageDimensions($a, $b));
        $this->assertFalse($this->service->samePackageDimensions($a, $c));
    }

    public function test_same_address_rileva_indirizzi_identici(): void
    {
        $origin = ['city' => 'Roma', 'postal_code' => '00100', 'name' => 'Mario', 'address' => 'Via Roma 1'];
        $same = ['city' => 'Roma', 'postal_code' => '00100', 'name' => 'Mario', 'address' => 'Via Roma 1'];
        $diff = ['city' => 'Milano', 'postal_code' => '00100', 'name' => 'Mario', 'address' => 'Via Roma 1'];

        $this->assertTrue($this->service->sameAddress($origin, $same));
        $this->assertFalse($this->service->sameAddress($origin, $diff));
    }

    public function test_subtotal_from_array_ritorna_mymoney_in_centesimi(): void
    {
        $packages = [
            ['single_price' => 1000, 'services' => []],
            ['single_price' => 2500, 'services' => []],
        ];

        $result = $this->service->subtotalFromArray($packages);

        $this->assertInstanceOf(MyMoney::class, $result);
        // 1000 + 2500 = 3500 cent (nessun surcharge con services vuoti)
        $this->assertSame('3500', $result->amount());
    }

    public function test_normalize_lowercases_e_trimma_stringhe(): void
    {
        $this->assertSame('mario rossi', $this->service->normalize('  Mario Rossi  '));
        $this->assertSame('', $this->service->normalize(null));
    }

    public function test_normalize_service_data_imposta_valori_default(): void
    {
        $normalized = $this->service->normalizeServiceData([]);

        $this->assertSame('Nessuno', $normalized['service_type']);
        $this->assertSame('', $normalized['date']);
        $this->assertSame('', $normalized['time']);
        $this->assertIsArray($normalized['service_data']);
    }

    public function test_normalize_service_data_converte_data_italiana(): void
    {
        $normalized = $this->service->normalizeServiceData([
            'service_type' => 'Ritiro',
            'date' => '15/04/2026',
            'time' => '09:00-13:00',
        ]);

        $this->assertSame('2026-04-15', $normalized['service_data']['pickup_request']['date']);
        $this->assertSame('09:00-13:00', $normalized['service_data']['pickup_request']['time_slot']);
    }
}
