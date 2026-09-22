<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\TestCase;

class DrugAwardProjectionIdentityContractTest extends TestCase
{
    public function test_muasamcong_kqlcnt_items_keep_source_identity_instead_of_collapsing_by_medicine_fallback(): void
    {
        $root = dirname(__DIR__, 2);
        $projection = file_get_contents($root.'/Services/DrugAwardProjectionService.php');

        $this->assertStringContainsString("\$source->sourceSystem === DrugBidAward::SOURCE_MUASAMCONG", $projection);
        $this->assertStringContainsString("\$source->sourceRecordType === 'kqlcnt_award_item'", $projection);
        $this->assertStringContainsString("trim(\$source->sourceRecordKey) !== ''", $projection);
        $this->assertStringContainsString("'source',", $projection);
        $this->assertStringContainsString("trim(\$source->sourceRecordKey)", $projection);
    }
}
