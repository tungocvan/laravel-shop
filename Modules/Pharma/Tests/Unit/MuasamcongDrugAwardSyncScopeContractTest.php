<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\TestCase;

class MuasamcongDrugAwardSyncScopeContractTest extends TestCase
{
    public function test_kqlcnt_sync_is_scoped_to_company_contractor_code(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Integrations/Muasamcong/MuasamcongDrugAwardSyncService.php'));

        $this->assertStringContainsString("private const COMPANY_CONTRACTOR_CODE = 'vn0314492345';", $service);
        $this->assertStringContainsString("LOWER(TRIM(contractor_code)) = ?", $service);
        $this->assertStringContainsString("[self::COMPANY_CONTRACTOR_CODE]", $service);
        $this->assertStringContainsString("\$companyItems()->whereKey('>', \$lastId)->exists()", $service);
        $this->assertStringNotContainsString("KqlcntAwardItem::query()->whereKey('>', \$lastId)->exists()", $service);
    }
}
