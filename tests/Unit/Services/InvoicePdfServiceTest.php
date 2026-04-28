<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\Package;
use App\Models\PackageAddress;
use App\Models\Service;
use App\Models\User;
use App\Services\InvoicePdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePdfServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoicePdfService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InvoicePdfService();
    }

    public function test_genera_pdf_valido_per_ordine_semplice(): void
    {
        $order = $this->createOrderWithPackage(['subtotal' => 1220]);

        $pdf = $this->service->generate($order);

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringEndsWith('%%EOF', $pdf);
    }

    public function test_pdf_include_numero_ordine_formattato(): void
    {
        $order = $this->createOrderWithPackage(['subtotal' => 1000]);

        $pdf = $this->service->generate($order);

        // Numero ordine: SF-000001 (padding a 6 cifre)
        $this->assertStringContainsString('SF-' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT), $pdf);
    }

    public function test_pdf_calcola_scorporo_iva_22_percento(): void
    {
        // subtotal 1220 cent = 12.20 EUR -> imponibile 10.00 EUR, IVA 2.20 EUR
        $order = $this->createOrderWithPackage(['subtotal' => 1220]);

        $pdf = $this->service->generate($order);

        // Dentro lo stream PDF le parentesi sono escapate come \( e \)
        $this->assertStringContainsString('IVA \(22%\)', $pdf);
        $this->assertStringContainsString('TOTALE', $pdf);
        $this->assertStringContainsString('12,20 EUR', $pdf);
    }

    public function test_pdf_include_dati_cliente_da_billing_data(): void
    {
        $order = $this->createOrderWithPackage([
            'subtotal' => 1000,
            'billing_data' => [
                'type' => 'azienda',
                'name' => 'Acme S.r.l.',
                'vat_number' => '12345678901',
                'fiscal_code' => 'RSSMRA80A01H501Z',
            ],
        ]);

        $pdf = $this->service->generate($order);

        $this->assertStringContainsString('Acme S.r.l.', $pdf);
        $this->assertStringContainsString('12345678901', $pdf);
    }

    public function test_pdf_include_metodo_pagamento_tradotto(): void
    {
        $order = $this->createOrderWithPackage([
            'subtotal' => 500,
            'payment_method' => 'wallet',
        ]);

        $pdf = $this->service->generate($order);

        $this->assertStringContainsString('Portafoglio virtuale', $pdf);
    }

    private function createOrderWithPackage(array $orderAttributes = []): Order
    {
        $user = User::factory()->create();
        $origin = PackageAddress::factory()->create(['type' => 'Partenza']);
        $destination = PackageAddress::factory()->create(['type' => 'Destinazione']);
        $service = Service::create(['service_type' => 'Nessuno', 'date' => '', 'time' => '']);

        $order = Order::factory()->create(array_merge([
            'user_id' => $user->id,
        ], $orderAttributes));

        $package = Package::factory()->create([
            'user_id' => $user->id,
            'origin_address_id' => $origin->id,
            'destination_address_id' => $destination->id,
            'service_id' => $service->id,
            'single_price' => 1000,
        ]);

        Order::attachPackage($order->id, $package->id, 1);
        $order->refresh();

        return $order;
    }
}
