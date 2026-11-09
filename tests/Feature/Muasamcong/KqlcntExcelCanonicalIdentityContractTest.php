<?php

namespace Tests\Feature\Muasamcong;

use Modules\Muasamcong\Services\ContractorAwardPersistenceService;
use Tests\TestCase;

class KqlcntExcelCanonicalIdentityContractTest extends TestCase
{
    public function test_new_excel_awards_reuse_catalog_identity_and_fingerprint(): void
    {
        $source = file_get_contents(base_path('Modules/Muasamcong/Services/KqlcntAwardExcelImportService.php'));
        $this->assertNotFalse($source);
        $this->assertStringContainsString('app(ContractorAwardPersistenceService::class)', $source);
        $this->assertStringContainsString("$"."data['identity_key'] = $"."identity->identityKey($"."notice, $"."contractor, $"."data)", $source);
        $this->assertStringContainsString("$"."data['fingerprint'] = $"."identity->fingerprint($"."data)", $source);
    }

    public function test_identity_matches_existing_canonical_algorithm_and_is_stable(): void
    {
        $service = app(ContractorAwardPersistenceService::class);
        $row = [
            'lot_no' => '2', 'medicine_code' => 'ABC',
            'medicine_name' => 'Noradrenalin', 'active_ingredient' => 'Noradrenalin',
            'concentration' => '5mg/5ml', 'quantity' => '50000',
            'winning_price' => '47000',
        ];
        $expected = hash('sha256', 'IB2500361133|vn0314492345|2');
        $this->assertSame($expected, $service->identityKey('IB2500361133', 'vn0314492345', $row));
        $this->assertSame(64, strlen($service->fingerprint($row)));
        $this->assertSame($service->fingerprint($row), $service->fingerprint($row));
        $this->assertNotSame($expected, $service->identityKey('IB2500361132', 'vn0314492345', $row));
    }
}
