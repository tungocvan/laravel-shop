<?php

namespace Modules\Pharma\Tests\Unit;

use Tests\TestCase;

class CommissionContractTest extends TestCase
{
    public function test_bid_commission_is_snapshotted_when_issue_is_posted(): void
    {
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $service=file_get_contents(base_path('Modules/Pharma/Services/DrugBidCommissionService.php'));
        $migration=file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_24_123000_create_pharma_inventory_issue_commissions_table.php'));

        $this->assertStringContainsString('snapshotPostedIssue', $controller);
        $this->assertStringContainsString('DrugBidAwardManagementAssignment::query()', $service);
        $this->assertStringContainsString('DrugBidAwardProductPolicy::query()', $service);
        $this->assertStringContainsString('round($revenue*$percentage/100,2)', $service);
        $this->assertStringContainsString("'calculated_at'=>\$issue->posted_at ?? now()", $service);
        $this->assertStringContainsString("unique(['issue_item_id','entry_type'],'ph_inv_issue_comm_item_type_unique')", $migration);
        $this->assertStringNotContainsString("foreignId('issue_item_id')", $migration);
    }

    public function test_commission_reversal_preserves_audit_history(): void
    {
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $service=file_get_contents(base_path('Modules/Pharma/Services/DrugBidCommissionService.php'));

        $this->assertStringContainsString('reverseIssue', $controller);
        $this->assertStringContainsString('TYPE_REVERSAL', $service);
        $this->assertStringContainsString("'commission_amount'=>-\$row->commission_amount", $service);
        $this->assertStringContainsString("'status'=>InventoryIssueCommission::STATUS_REVERSED", $service);
    }

    public function test_commission_route_only_aggregates_snapshot_ledger(): void
    {
        $routes=file_get_contents(base_path('Modules/Pharma/routes/web.php'));
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $view=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/commissions.blade.php'));

        $this->assertStringContainsString("Route::get('/commissions'", $routes);
        $this->assertStringContainsString("InventoryIssueCommission::query()", $controller);
        $this->assertStringContainsString("SUM(revenue_amount)", $controller);
        $this->assertStringContainsString("SUM(commission_amount)", $controller);
        $this->assertStringContainsString('Hoa hồng kinh doanh', $view);
        $this->assertStringContainsString('Số lượng thực xuất × Đơn giá trúng thầu × Chính sách %', $view);
        $this->assertStringContainsString('Chưa đủ chính sách/phân công', $view);
    }
}
