<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\TestCase;

class MuasamcongDrugAwardSyncScopeContractTest extends TestCase
{
    public function test_kqlcnt_sync_is_scoped_to_company_contractor_code(): void
    {
        $service = file_get_contents(dirname(__DIR__, 2).'/Integrations/Muasamcong/MuasamcongDrugAwardSyncService.php');

        $this->assertStringContainsString("private const COMPANY_CONTRACTOR_CODE = 'vn0314492345';", $service);
        $this->assertStringContainsString("LOWER(TRIM(contractor_code)) = ?", $service);
        $this->assertStringContainsString("[self::COMPANY_CONTRACTOR_CODE]", $service);
        $this->assertStringContainsString("\$companyItems()->whereKey('>', \$lastId)->exists()", $service);
        $this->assertStringNotContainsString("KqlcntAwardItem::query()->whereKey('>', \$lastId)->exists()", $service);
    }

    public function test_kqlcnt_sync_skips_tbmt_already_present_in_pharma(): void
    {
        $service = file_get_contents(dirname(__DIR__, 2).'/Integrations/Muasamcong/MuasamcongDrugAwardSyncService.php');

        $this->assertStringContainsString("trim((string) \$projection->notifyNo)", $service);
        $this->assertStringContainsString("where('bidding_notice_code', \$tbmt)->exists()", $service);
        $this->assertStringContainsString("if (\$tbmt !== ''", $service);
        $this->assertStringContainsString('continue;', $service);
    }

}
