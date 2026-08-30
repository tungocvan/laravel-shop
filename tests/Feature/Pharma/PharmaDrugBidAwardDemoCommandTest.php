<?php

namespace Tests\Feature\Pharma;

use Tests\TestCase;

class PharmaDrugBidAwardDemoCommandTest extends TestCase
{
    public function test_demo_command_is_local_only_and_scoped_to_demo_records(): void
    {
        $command = file_get_contents(base_path('Modules/Pharma/Console/Commands/ResetDrugBidAwardDemoCommand.php'));

        $this->assertStringContainsString("protected \$signature = 'reset:pharma-drug-bid-award-demo';", $command);
        $this->assertStringContainsString("app()->environment('local')", $command);
        $this->assertStringContainsString("private const DEMO_PREFIX = 'DEMO-PHARMA-';", $command);
        $this->assertStringContainsString("where('bidding_notice_code', 'like', self::DEMO_PREFIX.'%')->delete()", $command);
        $this->assertStringContainsString("where('registration_number', 'like', self::DEMO_PREFIX.'%')->delete()", $command);
        $this->assertStringNotContainsString('truncate()', $command);
        $this->assertStringNotContainsString('migrate:fresh', $command);
    }

    public function test_demo_dataset_covers_manual_source_linked_unmatched_and_pagination_scenarios(): void
    {
        $command = file_get_contents(base_path('Modules/Pharma/Console/Commands/ResetDrugBidAwardDemoCommand.php'));

        $this->assertStringContainsString('for ($i = 1; $i <= 24; $i++)', $command);
        $this->assertStringContainsString('for ($i = 25; $i <= 30; $i++)', $command);
        $this->assertStringContainsString('$linked = $i % 4 !== 0;', $command);
        $this->assertStringContainsString('$linked = $i % 2 === 1;', $command);
        $this->assertStringContainsString('DrugBidAward::SOURCE_MUASAMCONG', $command);
        $this->assertStringContainsString('projectFromSource(new DrugBidAwardSourceData(', $command);
        $this->assertStringContainsString('3 trang khi chọn 10 bản ghi/trang', $command);
    }
}
