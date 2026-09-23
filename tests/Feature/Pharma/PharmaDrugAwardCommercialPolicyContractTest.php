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
        $this->assertStringContainsString('Phân công User quản lý', $view);
        $this->assertStringContainsString('Áp dụng cho đã chọn', $view);
        $this->assertStringContainsString('không thực hiện tính hoa hồng', $view);
        $this->assertStringNotContainsString('Kích hoạt chính sách', $view);
        $this->assertStringNotContainsString('sharePercentage', $component);
        $this->assertStringNotContainsString('activationIssues', $component);
    }

    public function test_workspace_assigns_managers_by_selected_products_without_fake_allocations(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/DrugBidAward/CommercialPolicyWorkspace.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/drug-bid-award/commercial-policy-workspace.blade.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardCommercialPolicyService.php'));

        $this->assertStringContainsString('assignManagerToSelectedProducts', $component);
        $this->assertStringContainsString('selectAllPoliciesForManagement', $component);
        $this->assertStringContainsString('Phân công User theo sản phẩm', $view);
        $this->assertStringContainsString('Gán User cho {{ count($selectedManagementAwardIds) }} sản phẩm đã chọn', $view);
        $this->assertStringContainsString('Số bệnh viện có phân bổ', $view);
        $this->assertStringContainsString('unassignedProducts', $component);
        $this->assertStringContainsString('Tất cả sản phẩm đã được phân công User quản lý đầy đủ.', $view);
        $this->assertStringContainsString('Điều chỉnh phân công theo bệnh viện', $view);
        $this->assertStringContainsString('<details class="group" @if($selectedPartnerId) open @endif>', $view);
        $this->assertStringContainsString('Chỉ sử dụng khi cần thay User quản lý cho một hoặc một số sản phẩm tại một bệnh viện cụ thể.', $view);
        $this->assertStringContainsString("{{ \$group['products'] }} sản phẩm · {{ \$group['hospitals'] }} bệnh viện", $view);
        $this->assertStringContainsString('assignManagerToProductAllocations', $service);
        $this->assertStringContainsString("where('status', DrugBidAwardAllocation::STATUS_ACTIVE)", $service);
        $this->assertStringContainsString("get(['drug_bid_award_id', 'partner_id'])", $service);
        $this->assertStringNotContainsString('Gán cho toàn bộ TBMT', $view);
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
        $this->assertStringContainsString("'Mã thuốc chuẩn'=>\$product->medicine?->medicine_code ?? \$product->canonicalMatch?->medicine?->medicine_code", $component);
        $this->assertStringContainsString("'canonicalMatch.medicine'", $component);
        $this->assertStringContainsString("with(['medicine','canonicalMatch.medicine','allocations'", $component);
        $this->assertStringContainsString("'assigned'=>\$assignmentRows->count()", $component);
        $this->assertStringContainsString("'total'=>\$activeAllocationCount", $component);
        $this->assertStringContainsString('Đã phân công đầy đủ', $view);
        $this->assertStringContainsString('Số lượng Bệnh viện', $view);
        $this->assertStringContainsString('<x-select-search id="commercial-policy-product-user" wire:model.live="selectedUserId"', $view);
        $this->assertStringContainsString('<x-select-search id="commercial-policy-hospital-user" wire:model.live="selectedUserId"', $view);
        $this->assertStringContainsString('Đơn giá trúng', $view);
        $this->assertStringContainsString('Sản phẩm / Mã sản phẩm', $view);
        $this->assertStringContainsString("\$product->medicine?->medicine_code ?? \$product->canonicalMatch?->medicine?->medicine_code ?? 'Chưa có mã sản phẩm'", $view);
        $this->assertStringContainsString("with(['medicine','canonicalMatch.medicine','allocations'", $component);
        $this->assertStringContainsString('Tên / mã sản phẩm...', $view);
        $this->assertStringNotContainsString('Chưa có mã hàng', $view);
        $this->assertStringNotContainsString('Sản phẩm / Mã hàng', $view);
        $this->assertStringContainsString('Export Excel', $view);
        $this->assertStringContainsString('Import Excel', $view);
        $this->assertStringContainsString('applyBulkPercentageToAll', $component);
        $this->assertStringContainsString('updatedProductPolicies', $component);
        $this->assertStringContainsString('wire:model.blur="productPolicies.', $view);
        $this->assertStringContainsString('Áp dụng tất cả', $view);
        $this->assertStringContainsString('Chính sách từng sản phẩm tự lưu', $view);
        $this->assertStringNotContainsString('wire:click="saveProductPolicies"', $view);
        $this->assertStringContainsString('Gán User cho đã chọn', $view);
        $this->assertStringContainsString('Gỡ User đã chọn', $view);
        $this->assertStringContainsString('assignManagers', $service);
        $this->assertStringContainsString('removeManagers', $service);
    }
}
