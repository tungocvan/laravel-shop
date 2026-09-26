<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\TestCase;

class DrugBidAwardAllocationEditContractTest extends TestCase
{
    public function test_edit_allocation_keeps_hospital_selector_editable(): void
    {
        $root = dirname(__DIR__, 2);
        $view = file_get_contents($root.'/resources/views/livewire/drug-bid-award/allocation-workspace.blade.php');

        $this->assertStringContainsString('wire:model="partnerId"', $view);
        $this->assertStringContainsString('Có thể đổi sang bệnh viện khác trong phạm vi phân bổ đã duyệt.', $view);
        $this->assertStringNotContainsString('<select disabled', $view);
        $this->assertStringNotContainsString('Bệnh viện đã chọn', $view);
    }

    public function test_allocation_service_allows_safe_hospital_reassignment_and_blocks_duplicates(): void
    {
        $service = file_get_contents(dirname(__DIR__, 2).'/Services/DrugBidAwardAllocationService.php');

        $this->assertStringNotContainsString('Không thể đổi bệnh viện của một phân bổ đã tồn tại.', $service);
        $this->assertStringContainsString("where('partner_id', \$partnerId)", $service);
        $this->assertStringContainsString("where('id', '!=', \$allocation->id)", $service);
        $this->assertStringContainsString('Bệnh viện đã có phân bổ cho sản phẩm trúng thầu này.', $service);
        $this->assertStringContainsString("'partner_id' => \$partnerId", $service);
    }
}
