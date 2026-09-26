<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\TestCase;

class DrugBidAwardAllocationWorkspaceUiContractTest extends TestCase
{
    public function test_hospital_management_actions_live_on_product_workspace_only(): void
    {
        $root = dirname(__DIR__, 2);
        $products = file_get_contents($root.'/resources/views/pages/drug-bid-award/products.blade.php');
        $allocation = file_get_contents($root.'/resources/views/pages/drug-bid-award/allocations.blade.php');

        $this->assertStringContainsString('Quản lý bệnh viện', $products);
        $this->assertStringContainsString('+ Thêm bệnh viện', $products);
        $this->assertStringNotContainsString('Quản lý bệnh viện', $allocation);
        $this->assertStringNotContainsString('+ Thêm bệnh viện', $allocation);
    }

    public function test_distribution_setup_is_collapsible_three_column_workspace(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/livewire/drug-bid-award/product-workspace.blade.php');

        $this->assertStringContainsString('x-data="{ open:', $view);
        $this->assertStringContainsString('x-on:click="open = !open"', $view);
        $this->assertStringContainsString("x-text=\"open ? 'Ẩn thiết lập' : 'Hiển thị / Chỉnh sửa'\"", $view);
        $this->assertStringContainsString('x-show="open"', $view);
        $this->assertStringContainsString('xl:grid-cols-3', $view);
        $this->assertStringContainsString('Bước 1 · Phạm vi', $view);
        $this->assertStringContainsString('Bước 2 · Cơ sở nhận phân bổ', $view);
        $this->assertStringContainsString('Bước 3 · Kiểm tra trước khi lưu', $view);
    }
}
