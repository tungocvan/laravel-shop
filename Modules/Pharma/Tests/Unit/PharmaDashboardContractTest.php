<?php

namespace Modules\Pharma\Tests\Unit;

use Tests\TestCase;

class PharmaDashboardContractTest extends TestCase
{
    public function test_dashboard_service_covers_operational_domains(): void
    {
        $service=file_get_contents(base_path('Modules/Pharma/Services/PharmaDashboardService.php'));

        foreach (['master_data','inventory','commercial','sales','attention','price_lists'] as $section) {
            $this->assertStringContainsString("'{$section}'", $service);
        }

        $this->assertStringContainsString("where('quantity_on_hand', '>', 0)", $service);
        $this->assertStringContainsString("'available_stock_value'", $service);
        $this->assertStringContainsString("'available_stock_lots'", $service);
        $this->assertStringContainsString("'unpriced_available_lots'", $service);
        $this->assertStringContainsString("manual_cost_price", $service);
        $this->assertStringContainsString("AVG(cost_price) as average_cost_price", $service);
        $this->assertStringContainsString("defaultWarehouse()", $service);
        $this->assertStringContainsString("whereDate('expiry_date', '<=', \$today->copy()->addDays(90))", $service);
        $this->assertStringContainsString("InventoryIssueDeferredSupply::PENDING", $service);
        $this->assertStringContainsString("InventoryIssueCommission::STATUS_UNRESOLVED", $service);
        $this->assertStringContainsString("DrugBidAwardMatch::REVIEW_PENDING", $service);
        $this->assertStringContainsString("DrugBidAwardMatch::REVIEW_STALE", $service);
        $this->assertStringContainsString("whereDoesntHave('canonicalMatch')", $service);
        $this->assertStringContainsString("where('is_current', true)", $service);
    }

    public function test_dashboard_is_an_operations_hub_with_actionable_workspaces(): void
    {
        $view=file_get_contents(base_path('Modules/Pharma/resources/views/pages/dashboard.blade.php'));

        $this->assertStringContainsString("@section('admin_container','full')", $view);
        $this->assertStringContainsString('Trung tâm điều hành Pharma', $view);
        $this->assertStringContainsString('Tổng quan vận hành', $view);
        $this->assertStringContainsString('Giá trị tồn kho khả dụng', $view);
        $this->assertStringContainsString("available_stock_value", $view);
        $this->assertStringContainsString("unpriced_available_lots", $view);
        $this->assertStringContainsString('Việc cần xử lý', $view);
        $this->assertStringContainsString('Kho & cung ứng', $view);
        $this->assertStringContainsString('Đấu thầu & Commercial', $view);
        $this->assertStringContainsString('Không gian quản lý', $view);
        $this->assertStringContainsString("admin.pharma.inventory.commissions.index", $view);
        $this->assertStringContainsString("admin.pharma.inventory.issues.bid-sales.create", $view);
        $this->assertStringContainsString("\$cap['edit']?'admin.pharma.drug-bid-awards.review':'admin.pharma.drug-bid-awards.index'", $view);
    }

    public function test_dashboard_service_keeps_sections_failure_isolated(): void
    {
        $service=file_get_contents(base_path('Modules/Pharma/Services/PharmaDashboardService.php'));

        $this->assertStringContainsString("private function section(string \$section, callable \$resolver): array", $service);
        $this->assertStringContainsString("'available' => false", $service);
        $this->assertStringContainsString("Pharma Dashboard section is unavailable.", $service);
    }
}
