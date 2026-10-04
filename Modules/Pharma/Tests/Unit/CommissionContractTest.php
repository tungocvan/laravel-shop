<?php

namespace Modules\Pharma\Tests\Unit;

use Tests\TestCase;

class CommissionContractTest extends TestCase
{
    public function test_bid_commission_is_snapshotted_when_issue_is_posted(): void
    {
        $inventoryService=file_get_contents(base_path('Modules/Pharma/Services/InventoryService.php'));
        $service=file_get_contents(base_path('Modules/Pharma/Services/DrugBidCommissionService.php'));
        $migration=file_get_contents(base_path('Modules/Pharma/database/migrations/2026_10_03_140000_make_issue_commission_ledger_append_only.php'));

        $this->assertStringContainsString('private readonly DrugBidCommissionService $commissions', $inventoryService);
        $this->assertStringContainsString("\$this->commissions->snapshotPostedIssue(\$issue->fresh(['items','deferredSupplies']),\$userId)", $inventoryService);
        $this->assertStringContainsString("\$deferredMedicineIds=\$issue->deferredSupplies->pluck('medicine_id')", $service);
        $this->assertStringContainsString('hasActiveEarnedSnapshot', $service);
        $this->assertStringContainsString("'entry_type'=>InventoryIssueCommission::TYPE_EARNED", $service);
        $this->assertStringContainsString('InventoryIssueCommission::query()->create', $service);
        $this->assertStringNotContainsString('updateOrCreate(', $service);
        $this->assertStringContainsString("dropUnique('ph_inv_issue_comm_item_type_unique')", $migration);
        $this->assertStringContainsString("unique('original_commission_id','ph_inv_issue_comm_original_unique')", $migration);
    }

    public function test_reverting_issue_appends_a_linked_negative_commission_entry(): void
    {
        $inventoryService=file_get_contents(base_path('Modules/Pharma/Services/InventoryService.php'));
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $service=file_get_contents(base_path('Modules/Pharma/Services/DrugBidCommissionService.php'));

        $this->assertStringContainsString('$this->commissions->reverseIssue($issue->fresh(),$userId)', $inventoryService);
        $this->assertStringContainsString('Phiếu đã từng ghi sổ và phát sinh nhật ký hoa hồng', $controller);
        $this->assertStringContainsString("'original_commission_id'=>\$earned->id", $service);
        $this->assertStringContainsString("'quantity'=>-(float)\$earned->quantity", $service);
        $this->assertStringContainsString("'revenue_amount'=>-(float)\$earned->revenue_amount", $service);
        $this->assertStringContainsString("'commission_amount'=>-(float)\$earned->commission_amount", $service);
        $this->assertStringContainsString("'entry_type'=>InventoryIssueCommission::TYPE_REVERSAL", $service);
        $this->assertStringContainsString("\$earned->update(['status'=>InventoryIssueCommission::STATUS_REVERSED])", $service);
        $this->assertStringNotContainsString('->delete();', $service);
    }

    public function test_commission_report_and_export_use_canonical_query_service(): void
    {
        $routes=file_get_contents(base_path('Modules/Pharma/routes/web.php'));
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $queryService=file_get_contents(base_path('Modules/Pharma/Services/CommissionQueryService.php'));
        $view=file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/commissions.blade.php'));

        $this->assertStringContainsString("Route::get('/commissions'", $routes);
        $this->assertStringContainsString('CommissionQueryService $commissions', $controller);
        $this->assertStringContainsString('$commissions->adminQuery([', $controller);
        $this->assertStringContainsString("InventoryIssueCommission::query()->where('user_id',\$userId)", $queryService);
        $this->assertStringContainsString("SUM(revenue_amount)", $controller);
        $this->assertStringContainsString("SUM(commission_amount)", $controller);
        $this->assertStringContainsString('Trung tâm hoa hồng', $view);
        $this->assertStringContainsString('Dữ liệu chính sách được snapshot tại thời điểm ghi sổ.', $view);
        $this->assertStringContainsString('Giá trị thu · bảng giá', $view);
        $this->assertStringContainsString('Hoa hồng phát sinh', $view);
        $this->assertStringContainsString('Chưa đủ dữ liệu', $view);
        $this->assertStringContainsString("name=\"partner_id\"", $view);
        $this->assertStringContainsString("name=\"medicine_id\"", $view);
        $this->assertStringContainsString("Route::get('/commissions/export'", $routes);
        $this->assertStringContainsString("'ids'=>'nullable|array|max:500'", $controller);
        $this->assertStringContainsString("'commission'=>(float)\$row->commission_amount", $controller);
        $this->assertStringContainsString("'commission'=>['label'=>'Hoa hồng'", file_get_contents(base_path('Modules/Pharma/Services/CommissionExportProfileService.php')));
        $this->assertStringContainsString('Xóa bộ lọc', $view);
        $this->assertStringContainsString('Xuất Excel theo bộ lọc', $view);
        $this->assertStringContainsString('Export Excel đã chọn', $view);
    }

    public function test_client_portal_commission_scope_is_exposed_through_guarded_pwa_route(): void
    {
        $queryService=file_get_contents(base_path('Modules/Pharma/Services/CommissionQueryService.php'));
        $clientRoutes=file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $manifest=file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/manifest.php'));

        $this->assertStringContainsString('public function userQuery(int $userId', $queryService);
        $this->assertStringContainsString("->where('user_id',\$userId)", $queryService);
        $this->assertStringContainsString("'client.pharma.commissions.view'", $manifest);
        $this->assertStringContainsString("Route::get('/commissions'", $clientRoutes);
        $this->assertStringContainsString("client.feature:pharma,commissions", $clientRoutes);
        $this->assertStringContainsString("->name('commissions')", $clientRoutes);
    }
}
