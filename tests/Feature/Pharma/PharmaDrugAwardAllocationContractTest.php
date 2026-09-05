<?php

namespace Tests\Feature\Pharma;

use Tests\TestCase;

class PharmaDrugAwardAllocationContractTest extends TestCase
{
    public function test_hospital_master_is_reused_from_partner_and_not_duplicated_in_pharma(): void
    {
        $allocation = file_get_contents(base_path('Modules/Pharma/Models/DrugBidAwardAllocation.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardAllocationService.php'));

        $this->assertStringContainsString('Modules\\Partner\\Models\\Partner', $allocation);
        $this->assertStringContainsString("\$partner->legal_type !== 'hospital'", $service);
        $this->assertFileDoesNotExist(base_path('Modules/Pharma/Models/Hospital.php'));
    }

    public function test_allocation_schema_preserves_award_and_partner_integrity(): void
    {
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_05_021000_create_drug_bid_award_allocations_table.php'));
        $quantityMigration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_05_020000_normalize_drug_bid_award_quantity_precision.php'));

        $this->assertStringContainsString("decimal('quantity', 20, 4)", $quantityMigration);
        $this->assertStringContainsString("decimal('allocated_quantity', 20, 4)", $migration);
        $this->assertStringContainsString("constrained('pharma_drug_bid_awards')->restrictOnDelete()", $migration);
        $this->assertStringContainsString("constrained('partners')->restrictOnDelete()", $migration);
        $this->assertStringContainsString("unique(['drug_bid_award_id', 'partner_id']", $migration);
    }

    public function test_allocation_mutations_lock_award_and_recalculate_active_sum(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardAllocationService.php'));

        $this->assertStringContainsString('DrugBidAward::query()->lockForUpdate()->findOrFail($awardId)', $service);
        $this->assertStringContainsString("->where('status', DrugBidAwardAllocation::STATUS_ACTIVE)", $service);
        $this->assertStringContainsString("->sum('allocated_quantity')", $service);
        $this->assertStringContainsString('Tổng phân bổ không được vượt số lượng trúng thầu còn lại.', $service);
        $this->assertStringContainsString('Không thể giảm phân bổ thấp hơn số lượng hợp đồng đã cam kết.', $service);
        $this->assertStringContainsString("->where('partner_id', \$partnerId)", $service);
    }

    public function test_contract_schema_and_service_support_one_allocation_to_many_contracts(): void
    {
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_05_022000_create_drug_bid_award_contracts_table.php'));
        $model = file_get_contents(base_path('Modules/Pharma/Models/DrugBidAwardContract.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardContractService.php'));

        $this->assertStringContainsString("decimal('contract_quantity', 20, 4)", $migration);
        $this->assertStringContainsString("unique(['drug_bid_award_allocation_id', 'contract_number']", $migration);
        $this->assertStringContainsString('COMMITTED_STATUSES', $model);
        $this->assertStringContainsString('DrugBidAwardAllocation::query()->lockForUpdate()->findOrFail($allocationId)', $service);
        $this->assertStringContainsString('Tổng số lượng hợp đồng hiệu lực không được vượt số lượng đã phân bổ.', $service);
        $this->assertStringContainsString('Không thể hủy trực tiếp hợp đồng đã hoàn thành.', $service);
    }

    public function test_summary_keeps_overallocated_as_diagnostic_state_and_does_not_clamp_remaining(): void
    {
        $summary = file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardAllocationSummaryService.php'));

        $this->assertStringContainsString("public const OVER_ALLOCATED = 'OVER_ALLOCATED';", $summary);
        $this->assertStringContainsString('$remaining = round($winning - $allocated, 4);', $summary);
        $this->assertStringNotContainsString('max(0', $summary);
    }

    public function test_workspace_has_separate_permissions_bounded_pagination_and_page_scoped_selection(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/DrugBidAward/AllocationWorkspace.php'));
        $route = file_get_contents(base_path('Modules/Pharma/routes/web.php'));
        $index = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/drug-bid-award/index.blade.php'));

        $this->assertStringContainsString('private const PER_PAGE_OPTIONS = [10, 25, 50, 100];', $component);
        $this->assertStringContainsString('$this->selectedIds = $value ? $this->currentPageIds() : [];', $component);
        $this->assertStringContainsString('can:view_pharma_allocations', $route);
        $this->assertStringContainsString("can('view_pharma_allocations')", $index);
        $this->assertStringContainsString('>Phân bổ</a>', $index);
        $this->assertStringNotContainsString('Hiển thị tất cả', $component);
    }

    public function test_pharma_uses_partner_routes_for_hospital_master_management(): void
    {
        $allocationPage = file_get_contents(base_path('Modules/Pharma/resources/views/pages/drug-bid-award/allocations.blade.php'));
        $partnerIndex = file_get_contents(base_path('Modules/Partner/resources/views/pages/index.blade.php'));
        $partnerCreate = file_get_contents(base_path('Modules/Partner/resources/views/pages/create.blade.php'));
        $partnerForm = file_get_contents(base_path('Modules/Partner/Livewire/Partner/Form.php'));

        $this->assertStringContainsString("route('admin.partner.partners.index', ['legalType' => 'hospital'])", $allocationPage);
        $this->assertStringContainsString("route('admin.partner.partners.create', ['legal_type' => 'hospital'])", $allocationPage);
        $this->assertStringContainsString("request()->query('legalType', '')", $partnerIndex);
        $this->assertStringContainsString("request()->query('legal_type', 'company')", $partnerCreate);
        $this->assertStringContainsString('array_key_exists($legal_type, Partner::LEGAL_TYPES)', $partnerForm);
    }

    public function test_partner_hospital_import_requires_only_name_and_legal_type_and_keeps_ui_bounded(): void
    {
        $component = file_get_contents(base_path('Modules/Partner/Livewire/Partner/Index.php'));
        $view = file_get_contents(base_path('Modules/Partner/resources/views/livewire/partner/index.blade.php'));

        $this->assertStringContainsString('name và legal_type là hai trường bắt buộc.', $component);
        $this->assertStringContainsString("['name' => \$name, 'legal_type' => \$legalType]", $component);
        $this->assertStringContainsString("\$legalType === 'hospital' ? 'customer' : 'supplier'", $component);
        $this->assertStringContainsString('downloadTemplate', $component);
        $this->assertStringContainsString('private const PER_PAGE_OPTIONS = [10, 25, 50, 100];', $component);
        $this->assertStringContainsString("'legal_type' => 'hospital'", $view);
        $this->assertStringContainsString('Tải file mẫu', $view);
        $this->assertStringContainsString('Export đã chọn', $view);
        $this->assertStringNotContainsString('<option value="All">', $view);
    }

    public function test_export_contract_is_selected_when_selected_and_filtered_all_otherwise(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/DrugBidAward/AllocationWorkspace.php'));

        $this->assertStringContainsString('$this->updatedSelectedIds();', $component);
        $this->assertStringContainsString('if ($this->selectedIds !== [])', $component);
        $this->assertStringContainsString("\$query->whereIn('id', array_map('intval', \$this->selectedIds));", $component);
        $this->assertStringContainsString('exportAllocations(): StreamedResponse', $component);
        $this->assertStringContainsString('exportContracts(): StreamedResponse', $component);
    }

    public function test_cancellation_is_soft_lifecycle_with_reason_and_audit_metadata(): void
    {
        $allocationService = file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardAllocationService.php'));
        $contractService = file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardContractService.php'));
        $panel = file_get_contents(base_path('Modules/Pharma/Livewire/DrugBidAward/AllocationCancellationPanel.php'));

        $this->assertStringContainsString("'cancelled_at' => now()", $allocationService);
        $this->assertStringContainsString("'cancellation_reason' => trim(\$reason)", $allocationService);
        $this->assertStringContainsString("'cancelled_at' => now()", $contractService);
        $this->assertStringContainsString("'cancellation_reason' => trim(\$reason)", $contractService);
        $this->assertStringContainsString("authorizePermission('cancel_pharma_allocations')", $panel);
        $this->assertStringContainsString("authorizePermission('cancel_pharma_contracts')", $panel);
    }
}
