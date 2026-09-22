<?php

namespace Tests\Feature\Pharma;

use Tests\TestCase;

class PharmaDrugAwardCommercialPolicyContractTest extends TestCase
{
    public function test_commercial_setup_keeps_separate_route_and_permissions(): void
    {
        $routes = file_get_contents(base_path('Modules/Pharma/routes/web.php'));
        $permissions = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_21_211000_add_drug_award_commercial_policy_permissions.php'));

        $this->assertStringContainsString("name('commercial-policy')", $routes);
        $this->assertStringContainsString('can:view_pharma_commercial_policies', $routes);
        $this->assertStringContainsString('view_pharma_commercial_policies', $permissions);
        $this->assertStringContainsString('manage_pharma_commercial_policies', $permissions);
    }

    public function test_schema_separates_product_percentage_from_hospital_user_assignment(): void
    {
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_21_220000_create_drug_bid_award_commercial_setup_tables.php'));

        $this->assertStringContainsString('pharma_drug_bid_award_product_policies', $migration);
        $this->assertStringContainsString("'drug_bid_award_id'", $migration);
        $this->assertStringContainsString("'commission_percentage'", $migration);
        $this->assertStringContainsString('pharma_drug_bid_award_management_assignments', $migration);
        $this->assertStringContainsString("'partner_id'", $migration);
        $this->assertStringContainsString("'user_id'", $migration);
        $this->assertStringContainsString("unique(['drug_bid_award_id', 'partner_id']", $migration);
        $this->assertStringNotContainsString('share_percentage', $migration);
    }

    public function test_assignment_is_limited_to_products_allocated_to_selected_hospital(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardCommercialPolicyService.php'));

        $this->assertStringContainsString("where('partner_id', \$partnerId)", $service);
        $this->assertStringContainsString("where('status', DrugBidAwardAllocation::STATUS_ACTIVE)", $service);
        $this->assertStringContainsString('Sản phẩm chưa được phân bổ cho bệnh viện đã chọn.', $service);
        $this->assertStringContainsString('updateOrCreate', $service);
    }

    public function test_workspace_is_two_step_setup_without_commission_calculation_or_activation(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/DrugBidAward/CommercialPolicyWorkspace.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/drug-bid-award/commercial-policy-workspace.blade.php'));

        $this->assertStringContainsString('saveProductPolicies', $component);
        $this->assertStringContainsString('assignManager', $component);
        $this->assertStringContainsString('Bước 1', $view);
        $this->assertStringContainsString('Thiết lập chính sách theo sản phẩm', $view);
        $this->assertStringContainsString('Bước 2', $view);
        $this->assertStringContainsString('Thiết lập User quản lý bệnh viện', $view);
        $this->assertStringContainsString('Áp dụng cho đã chọn', $view);
        $this->assertStringContainsString('không thực hiện tính hoa hồng', $view);
        $this->assertStringNotContainsString('Kích hoạt chính sách', $view);
        $this->assertStringNotContainsString('sharePercentage', $component);
        $this->assertStringNotContainsString('activationIssues', $component);
    }

    public function test_workspace_supports_tbmt_wide_quick_manager_assignment_without_fake_allocations(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/DrugBidAward/CommercialPolicyWorkspace.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/drug-bid-award/commercial-policy-workspace.blade.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardCommercialPolicyService.php'));

        $this->assertStringContainsString('assignManagerToAll', $component);
        $this->assertStringContainsString('removeManagerFromAll', $component);
        $this->assertStringContainsString('Gán nhanh User cho toàn bộ TBMT', $view);
        $this->assertStringContainsString('Áp dụng cho tất cả', $view);
        $this->assertStringContainsString('Gỡ tất cả của User này', $view);
        $this->assertStringContainsString('assignManagerToAllAllocations', $service);
        $this->assertStringContainsString('removeManagerFromAllAllocations', $service);
        $this->assertStringContainsString("where('status', DrugBidAwardAllocation::STATUS_ACTIVE)", $service);
        $this->assertStringContainsString("get(['drug_bid_award_id', 'partner_id'])", $service);
    }

    public function test_workspace_supports_select_all_bulk_management_winning_price_and_excel_round_trip(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/DrugBidAward/CommercialPolicyWorkspace.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/drug-bid-award/commercial-policy-workspace.blade.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardCommercialPolicyService.php'));

        $this->assertStringContainsString('selectAllPolicies', $component);
        $this->assertStringContainsString('selectAllManagement', $component);
        $this->assertStringContainsString('assignSelectedManagers', $component);
        $this->assertStringContainsString('removeSelectedManagers', $component);
        $this->assertStringContainsString('exportExcel', $component);
        $this->assertStringContainsString('importExcel', $component);
        $this->assertStringContainsString('Excel::download', $component);
        $this->assertStringContainsString('Excel::toArray', $component);
        $this->assertStringContainsString("'Số lượng phân bổ'=>\$allocation === null ? null : (float)\$allocation->allocated_quantity", $component);
        $this->assertStringContainsString("'Mã thuốc chuẩn'=>\$product->medicine?->medicine_code", $component);
        $this->assertStringContainsString("with(['medicine','allocations'", $component);
        $this->assertStringContainsString("'assigned'=>\$assignmentRows->count()", $component);
        $this->assertStringContainsString("'total'=>\$activeAllocationCount", $component);
        $this->assertStringContainsString('Đã phân công', $view);
        $this->assertStringContainsString('Đã phân công đầy đủ', $view);
        $this->assertStringContainsString('Bệnh viện đã có User', $view);
        $this->assertStringContainsString('Đơn giá trúng', $view);
        $this->assertStringContainsString('Export Excel', $view);
        $this->assertStringContainsString('Import Excel', $view);
        $this->assertStringContainsString('Gán User cho đã chọn', $view);
        $this->assertStringContainsString('Gỡ User đã chọn', $view);
        $this->assertStringContainsString('assignManagers', $service);
        $this->assertStringContainsString('removeManagers', $service);
    }
}
