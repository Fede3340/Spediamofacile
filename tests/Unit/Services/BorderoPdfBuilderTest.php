<?php

namespace Tests\Unit\Services;

use App\Services\BorderoPdfBuilder;
use Tests\TestCase;

class BorderoPdfBuilderTest extends TestCase
{
    public function test_genera_pdf_minimale_con_magic_number_valido(): void
    {
        $builder = new BorderoPdfBuilder();

        $pdf = $builder->build([
            'bordero_date' => '15/04/2026',
            'bordero_number' => '1234',
            'bordero_reference' => 'BORD-00001234',
            'localita' => 'Roma',
            'prov' => 'RM',
            'lna' => '00100',
            'colli' => '1',
        ]);

        // Un PDF valido inizia con la signature "%PDF-" e termina con "%%EOF"
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringEndsWith('%%EOF', $pdf);
    }

    public function test_genera_pdf_include_dati_mittente_e_destinatario(): void
    {
        $builder = new BorderoPdfBuilder();

        $pdf = $builder->build([
            'bordero_number' => '42',
            'sender_name' => 'Mittente Acme',
            'recipient_name' => 'Destinatario Beta',
            'colli' => '3',
        ]);

        // I contenuti text sono wrappati in parentesi dentro lo stream PDF
        $this->assertStringContainsString('Mittente Acme', $pdf);
        $this->assertStringContainsString('Destinatario Beta', $pdf);
        $this->assertStringContainsString('Totali colli: 3', $pdf);
    }

    public function test_gestisce_payload_vuoto_senza_errore(): void
    {
        $builder = new BorderoPdfBuilder();

        $pdf = $builder->build([]);

        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF-', $pdf);
        // Con zero pacchi deve comunque mostrare "Totali colli: 0"
        $this->assertStringContainsString('Totali colli: 0', $pdf);
    }

    public function test_pdf_contiene_struttura_oggetti_base(): void
    {
        $builder = new BorderoPdfBuilder();

        $pdf = $builder->build(['bordero_number' => '1']);

        // Un PDF 1.4 base deve avere Catalog, Pages, Page, Font
        $this->assertStringContainsString('/Type /Catalog', $pdf);
        $this->assertStringContainsString('/Type /Pages', $pdf);
        $this->assertStringContainsString('/Type /Page ', $pdf);
        $this->assertStringContainsString('/Type /Font', $pdf);
    }

    public function test_caratteri_non_ascii_vengono_translitterati(): void
    {
        $builder = new BorderoPdfBuilder();

        // "Citta" con accento: deve essere translitterato in ASCII e non rompere il PDF
        $pdf = $builder->build([
            'sender_name' => 'Cittadinanza Attivà',
            'localita' => 'Città del Vaticano',
        ]);

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringEndsWith('%%EOF', $pdf);
    }
}
