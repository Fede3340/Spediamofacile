<?php

namespace Tests\Unit\Services\Brt;

use App\Services\Brt\BrtPayloadBuilder;
use Tests\TestCase;

class BrtPayloadBuilderTest extends TestCase
{
    public function test_sanitize_create_data_removes_unsupported_sender_override_fields(): void
    {
        $builder = new BrtPayloadBuilder();

        $sanitized = $builder->sanitizeCreateData([
            'createData' => [
                'senderCustomerCode' => 1020108,
                'senderCompanyName' => 'Mittente Demo',
                'senderAddress' => 'Via Roma 10',
                'senderZIPCode' => '00118',
                'senderCity' => 'ROMA',
                'senderProvinceAbbreviation' => 'RM',
                'senderCountryAbbreviationISOAlpha2' => 'IT',
                'senderContactName' => 'Mario Rossi',
                'senderTelephone' => '3331234567',
                'senderEMail' => 'mario@example.test',
                'consigneeCompanyName' => 'Cliente Demo',
                'consigneeAddress' => 'Via Milano 22',
                'consigneeZIPCode' => '20121',
                'consigneeCity' => 'MILANO',
                'consigneeProvinceAbbreviation' => 'MI',
                'isAlertRequired' => 1,
                'isCODMandatory' => 0,
            ],
        ]);

        $createData = $sanitized['createData'];

        $this->assertSame(1020108, $createData['senderCustomerCode']);
        $this->assertSame('Cliente Demo', $createData['consigneeCompanyName']);
        $this->assertSame(1, $createData['isAlertRequired']);
        $this->assertSame(0, $createData['isCODMandatory']);
        $this->assertArrayNotHasKey('senderCompanyName', $createData);
        $this->assertArrayNotHasKey('senderAddress', $createData);
        $this->assertArrayNotHasKey('senderZIPCode', $createData);
        $this->assertArrayNotHasKey('senderCity', $createData);
        $this->assertArrayNotHasKey('senderProvinceAbbreviation', $createData);
        $this->assertArrayNotHasKey('senderCountryAbbreviationISOAlpha2', $createData);
        $this->assertArrayNotHasKey('senderContactName', $createData);
        $this->assertArrayNotHasKey('senderTelephone', $createData);
        $this->assertArrayNotHasKey('senderEMail', $createData);
    }
}
