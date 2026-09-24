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
        $this->assertStringContainsString('function()use($issue,$data,$inventory,$commissions)', $controller);
        $this->assertStringContainsString('DrugBidAwardManagementAssignment::query()', $service);
        $this->assertStringContainsString('DrugBidAwardProductPolicy::query()', $service);
        $this->assertStringContainsString('round($revenue*$percentage/100,2)', $service);
        $this->assertStringContainsString("'calculated_at'=>\$issue->posted_at ?? now()", $service);
        $this->assertStringContainsString("unique(['issue_item_id','entry_type'],'ph_inv_issue_comm_item_type_unique')", $migration);
        $this->assertStringNotContainsString("foreignId('issue_item_id')", $migration);
    }

    public function test_reverting_bid_issue_removes_commission_cost(): void
    {
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $service=file_get_contents(base_path('Modules/Pharma/Services/DrugBidCommissionService.php'));

        $this->assertStringContainsString('reverseIssue', $controller);
        $this->assertStringContainsString('Phiếu đã từng ghi sổ và phát sinh nhật ký hoa hồng', $controller);
        $this->assertStringContainsString("->where('issue_id',\$issue->id)", $service);
        $this->assertStringContainsString('->lockForUpdate()', $service);
        $this->assertStringContainsString('->delete();', $service);
        $this->assertStringNotContainsString('TYPE_REVERSAL', $service);
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
        $this->assertStringContainsString("->when(\$partnerId>0", $controller);
        $this->assertStringContainsString("->when(\$medicineId>0", $controller);
        $this->assertStringContainsString("DrugBidAwardManagementAssignment::query()", $controller);
        $this->assertStringContainsString("name=\"partner_id\"", $view);
        $this->assertStringContainsString("name=\"medicine_id\"", $view);
        $this->assertStringContainsString("commission-user-filter", $view);
        $this->assertStringContainsString("commission-partner-filter", $view);
        $this->assertStringContainsString("partner.value=''", $view);
        $this->assertStringContainsString("medicine.value=''", $view);
    }
}
