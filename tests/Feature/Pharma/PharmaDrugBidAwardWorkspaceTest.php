<?php

namespace Tests\Feature\Pharma;

use Tests\TestCase;

class PharmaDrugBidAwardWorkspaceTest extends TestCase
{
    public function test_workspace_groups_by_bidding_notice_and_drills_into_products_before_allocation(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/DrugBidAward/Index.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardService.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/drug-bid-award/index.blade.php'));
        $routes = file_get_contents(base_path('Modules/Pharma/routes/web.php'));
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/DrugBidAwardController.php'));
        $products = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/drug-bid-award/product-workspace.blade.php'));

        $this->assertStringContainsString('private const PER_PAGE_OPTIONS = [10, 25, 50, 100];', $component);
        $this->assertStringContainsString('getResultGroupsPaginated(', $component);
        $this->assertStringContainsString("COALESCE(NULLIF(bidding_notice_code, ''), CONCAT('award-', id))", $service);
        $this->assertStringContainsString("COUNT(*) as product_count", $service);
        $this->assertStringContainsString('Mỗi dòng là một mã thông báo mời thầu', $view);
        $this->assertStringContainsString('Xem sản phẩm / Phân bổ', $view);
        $this->assertStringNotContainsString('wire:model.live="selectedIds"', $view);
        $this->assertStringContainsString("name('allocation-detail')", $routes);
        $this->assertStringContainsString("view('Pharma::pages.drug-bid-award.products'", $controller);
        $productComponent = file_get_contents(base_path('Modules/Pharma/Livewire/DrugBidAward/ProductWorkspace.php'));
        $this->assertStringContainsString("->when(\$result->bidding_notice_code", $productComponent);
        $this->assertStringContainsString("route('admin.pharma.drug-bid-awards.allocation-detail'", $products);
        $this->assertStringContainsString('Đã phân bổ', $products);
        $this->assertStringContainsString('Còn lại', $products);
    }

    public function test_workspace_exposes_multi_source_provenance_and_hssp_enrichment(): void
    {
        $model = file_get_contents(base_path('Modules/Pharma/Models/DrugBidAward.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardService.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/drug-bid-award/index.blade.php'));

        $this->assertStringContainsString("public const SOURCE_MANUAL = 'manual';", $model);
        $this->assertStringContainsString("public const SOURCE_MUASAMCONG = 'muasamcong';", $model);
        $this->assertStringContainsString('effectiveMedicineAttribute', $model);
        $this->assertStringContainsString("'origin' => 'hssp'", $model);
        $this->assertStringContainsString("Schema::hasTable('pharma_drug_bid_award_sources')", $service);
        $this->assertStringContainsString("->with('medicine')", $service);
        $this->assertStringContainsString("->with('sources')", $service);
        $this->assertStringContainsString("orWhereHas('sources'", $service);
        $this->assertStringContainsString('Đối soát HSSP', $view);
    }

    public function test_kqlcnt_sync_is_explicit_bounded_and_permission_guarded(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/DrugBidAward/Index.php'));
        $syncService = file_get_contents(base_path('Modules/Pharma/Integrations/Muasamcong/MuasamcongDrugAwardSyncService.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/drug-bid-award/index.blade.php'));

        $this->assertStringContainsString('syncMuasamcong(MuasamcongDrugAwardSyncService $syncService)', $component);
        $this->assertStringContainsString('$this->authorizePharmaEdit();', $component);
        $this->assertStringContainsString('$syncService->sync($this->syncAfterId, 250)', $component);
        $this->assertStringContainsString('public function sync(?int $afterId = null, int $limit = 250): array', $syncService);
        $this->assertStringContainsString('$limit = max(1, min($limit, 1000));', $syncService);
        $this->assertStringContainsString('->limit($limit)', $syncService);
        $this->assertStringContainsString('Schema::hasTable', $syncService);
        $this->assertStringContainsString('Đồng bộ KQLCNT', $view);
        $this->assertStringContainsString('wire:loading.attr="disabled"', $view);
    }

    public function test_source_identity_migration_is_additive_and_unique(): void
    {
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_08_30_010000_add_source_identity_to_drug_bid_awards_table.php'));
        $lineageMigration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_05_013000_create_drug_bid_award_sources_table.php'));

        $this->assertStringContainsString("->default('manual')", $migration);
        $this->assertStringContainsString("\$table->uuid('source_id')", $migration);
        $this->assertStringContainsString("\$table->unique(['source_type', 'source_id'], 'drug_bid_awards_source_identity_unique')", $migration);
        $this->assertStringContainsString("['source_system', 'source_record_type', 'source_record_key']", $lineageMigration);
    }

    public function test_medicine_lookup_is_bounded_and_keeps_snapshot_fields_independent(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/DrugBidAward/Form.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/drug-bid-award/form.blade.php'));

        $this->assertStringContainsString('private const MEDICINE_RESULT_LIMIT = 25;', $component);
        $this->assertStringContainsString('->limit(self::MEDICINE_RESULT_LIMIT)->get()', $component);
        $this->assertStringNotContainsString('Medicine::query()->latest()->get()', $component);
        $this->assertStringContainsString('registration_number', $component);
        $this->assertStringContainsString('active_ingredients', $component);
        $this->assertStringContainsString('wire:model.live.debounce.300ms="medicineSearch"', $view);
        $this->assertStringContainsString('Chưa liên kết HSSP', $view);
        $this->assertStringContainsString('snapshot', $view);
    }

    public function test_searchable_dimension_filters_use_bounded_distinct_option_collections(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/DrugBidAward/Index.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardService.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/drug-bid-award/index.blade.php'));
        $selectSearch = file_get_contents(base_path('resources/views/components/select-search.blade.php'));

        $this->assertStringContainsString("'tbmtOptions' => \$this->distinctOptions('bidding_notice_code')", $component);
        $this->assertStringContainsString("'investorOptions' => \$this->distinctOptions('investor_name')", $component);
        $this->assertStringContainsString("'medicineOptions' => \$this->distinctOptions('medicine_name')", $component);
        $this->assertStringContainsString('->distinct()', $component);
        $this->assertStringContainsString('->limit(500)', $component);
        $this->assertStringContainsString('<x-select-search', $view);
        $this->assertStringContainsString('new TomSelect', $selectSearch);
        $this->assertStringContainsString('drug-award-filter-tbmt', $view);
        $this->assertStringContainsString('drug-award-filter-investor', $view);
        $this->assertStringNotContainsString('drug-award-filter-company', $view);
        $this->assertStringContainsString('wire:model.live="valueSort"', $view);
        $this->assertStringContainsString('Cao nhất → thấp nhất', $view);
        $this->assertStringContainsString('Thấp nhất → cao nhất', $view);
        $this->assertStringContainsString("where('investor_name', 'like'", $service);
        $this->assertStringContainsString("where('winning_company_name', 'like'", $service);
    }

    public function test_export_controls_preserve_detail_schema_and_intelligence_filters(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/drug-bid-award/index.blade.php'));
        $export = file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardImportExport.php'));

        $this->assertStringContainsString("\$canEdit = \$admin?->can('edit_pharma') ?? false;", $view);
        $this->assertStringContainsString("'permission' => 'edit_pharma'", $view);
        $this->assertStringNotContainsString("'selected_ids' => \$selectedIds", $view);
        $this->assertStringContainsString("'medicine_match_status' => \$filterMatchStatus", $view);
        $this->assertStringContainsString('$selectedIds = $this->selectedIds($filters);', $export);
        $this->assertStringContainsString("when(\$filters['medicine_match_status'] ?? null", $export);
        $this->assertStringContainsString("'Nguồn dữ liệu' => \$model->source_type", $export);
        $this->assertStringNotContainsString('raw_payload', $export);
    }

    public function test_drug_award_pages_use_canonical_full_width_admin_container(): void
    {
        foreach (['index.blade.php', 'products.blade.php', 'allocations.blade.php'] as $page) {
            $view = file_get_contents(base_path('Modules/Pharma/resources/views/pages/drug-bid-award/'.$page));
            $this->assertStringContainsString("@section('admin_container', 'full')", $view);
        }
    }


    public function test_award_index_is_a_management_dashboard_with_setup_status_and_collapsible_tools(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/DrugBidAward/Index.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardService.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/drug-bid-award/index.blade.php'));

        $this->assertStringContainsString('dashboardMetrics()', $component);
        $this->assertStringContainsString('showImportExport', $component);
        $this->assertStringContainsString('showFilters', $component);
        $this->assertStringContainsString('Tổng TBMT', $view);
        $this->assertStringContainsString('Tổng giá trị', $view);
        $this->assertStringContainsString('Cần hoàn thiện', $view);
        $this->assertStringContainsString('Thời gian HĐ', $view);
        $this->assertStringContainsString('Trạng thái thiết lập', $view);
        $this->assertStringContainsString('Phân bổ:', $view);
        $this->assertStringContainsString('CSKD:', $view);
        $this->assertStringNotContainsString('<th class="px-4 py-3">Nhà thầu trúng</th>', $view);
        $this->assertStringNotContainsString('<th class="px-4 py-3 text-right">Tổng SL</th>', $view);
        $this->assertStringContainsString('contract_duration_months', $service);
        $this->assertStringContainsString('management_assignment_count', $service);
        $this->assertStringContainsString("valueSort === 'desc'", $service);
    }

    public function test_award_distribution_scope_is_shared_by_products_and_restricts_hospitals(): void
    {
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_21_160000_create_drug_bid_award_distribution_scopes.php'));
        $productComponent = file_get_contents(base_path('Modules/Pharma/Livewire/DrugBidAward/ProductWorkspace.php'));
        $productView = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/drug-bid-award/product-workspace.blade.php'));
        $allocationComponent = file_get_contents(base_path('Modules/Pharma/Livewire/DrugBidAward/AllocationWorkspace.php'));
        $allocationView = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/drug-bid-award/allocation-workspace.blade.php'));
        $allocationService = file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardAllocationService.php'));
        $distributionScopeService = file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardDistributionScopeService.php'));

        $this->assertStringContainsString('pharma_drug_bid_award_distribution_scopes', $migration);
        $this->assertStringContainsString('pharma_drug_bid_award_distribution_scope_partners', $migration);
        $this->assertStringContainsString('public array $selectedProvinces', $productComponent);
        $this->assertStringContainsString('public string $facilityProvince', $productComponent);
        $this->assertStringContainsString('public array $selectedFacilityIds', $productComponent);
        $this->assertStringContainsString('saveDistributionScope', $productComponent);
        $this->assertStringContainsString("public function updatedSelectedProvinces(): void", $productComponent);
        $this->assertStringNotContainsString("\$this->selectedFacilityIds = [];", $productComponent);
        $this->assertStringContainsString('Phạm vi & hiệu lực phân bổ', $productView);
        $this->assertStringContainsString('Tỉnh/Thành trúng thầu', $productView);
        $this->assertStringContainsString('Cơ sở KCB được phân bổ', $productView);
        $this->assertStringContainsString('wire:key="award-facility-{{ $facility->id }}"', $productView);
        $this->assertStringContainsString('wire:model.live="selectedFacilityIds"', $productView);
        $this->assertStringContainsString('removeSelectedFacility', $productComponent);
        $this->assertStringContainsString("'selectedFacilities' => \$selectedFacilities", $productComponent);
        $this->assertStringContainsString('Bước 3 · Kiểm tra trước khi lưu', $productView);
        $this->assertStringContainsString('Cơ sở KCB đã chọn', $productView);
        $this->assertStringContainsString('wire:click="removeSelectedFacility({{ $facility->id }})"', $productView);
        $this->assertStringContainsString('OfficialSourceFacility::query()', $productComponent);
        $this->assertStringContainsString("where('province_name', \$this->facilityProvince)", $productComponent);
        $this->assertStringContainsString('OfficialSourceFacility::query()', $productComponent);
        $this->assertStringContainsString('OfficialSourceFacility::query()', $distributionScopeService);
        $this->assertStringContainsString("whereIn('province_name', \$provinceNames)", $distributionScopeService);
        $this->assertStringContainsString('pharma_drug_bid_award_distribution_scope_provinces', $distributionScopeService);
        $this->assertStringContainsString('wire:model.live="selectedProvinces"', $productView);
        $this->assertStringContainsString('public string $provinceSearch', $productComponent);
        $this->assertStringContainsString('wire:model.live.debounce.250ms="provinceSearch"', $productView);
        $this->assertStringContainsString('Tìm Tỉnh/Thành...', $productView);
        $this->assertStringContainsString("where('province_name', 'like'", $productComponent);
        $this->assertStringContainsString('wire:model.live="facilityProvince"', $productView);
        $this->assertStringContainsString("PartnerSourceReference::query()", $distributionScopeService);
        $this->assertStringContainsString("'facility_ids' => \$data['selectedFacilityIds']", $productComponent);
        $this->assertStringContainsString('Cơ sở KCB được phân bổ', $productView);
        $this->assertStringContainsString("whereIn('id', \$allowedPartnerIds)", $allocationComponent);
        $this->assertStringNotContainsString('wire:model="effectiveFrom"', $allocationView);
        $this->assertStringNotContainsString('wire:model="effectiveUntil"', $allocationView);
        $this->assertStringContainsString('Hiệu lực chung', $allocationView);
        $this->assertStringContainsString('Bệnh viện chưa nằm trong phạm vi phân bổ', $allocationService);
        $this->assertStringContainsString("'effective_from' => \$scope->effective_from", $allocationService);
        $this->assertStringContainsString("'effective_until' => \$scope->effective_until", $allocationService);
    }

}
