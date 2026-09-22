<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\TestCase;

class DrugBidAwardDeleteResultGroupContractTest extends TestCase
{
    public function test_tbmt_row_exposes_guarded_group_delete(): void
    {
        $root = dirname(__DIR__, 2);
        $service = file_get_contents($root.'/Services/DrugBidAwardService.php');
        $component = file_get_contents($root.'/Livewire/DrugBidAward/Index.php');
        $view = file_get_contents($root.'/resources/views/livewire/drug-bid-award/index.blade.php');

        $this->assertStringContainsString('function deleteResultGroup(int $representativeId): int', $service);
        $this->assertStringContainsString("where('bidding_notice_code', \$representative->bidding_notice_code)", $service);
        $this->assertStringContainsString('function deleteResultGroup(DrugBidAwardService $service, int $representativeId): void', $component);
        $this->assertStringContainsString("can('delete_pharma')", $view);
        $this->assertStringContainsString('wire:confirm=', $view);
        $this->assertStringContainsString('Xóa TBMT', $view);
    }
}
