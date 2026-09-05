<?php

namespace Tests\Feature\Pharma;

use Tests\TestCase;

class PharmaDrugBidAwardWorkspaceTest extends TestCase
{
    public function test_workspace_uses_bounded_page_sizes_and_page_scoped_selection(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/DrugBidAward/Index.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/drug-bid-award/index.blade.php'));

        $this->assertStringContainsString('private const PER_PAGE_OPTIONS = [10, 25, 50, 100];', $component);
        $this->assertStringNotContainsString("'All'", $component);
        $this->assertStringNotContainsString('999999', $component);
        $this->assertStringContainsString('public bool $selectPage = false;', $component);
        $this->assertStringContainsString('$this->selectedIds = $value ? $this->currentPageIds() : [];', $component);
        $this->assertStringContainsString("array_intersect(array_map('strval', \$this->selectedIds), \$pageIds)", $component);
        $this->assertStringContainsString('@foreach ($perPageOptions as $option)', $view);
        $this->assertStringNotContainsString('Hiển thị tất cả', $view);
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
        $this->assertStringContainsString("with(['medicine', 'sources'])", $service);
        $this->assertStringContainsString("orWhereHas('sources'", $service);
        $this->assertStringContainsString('Bổ sung từ HSSP', $view);
        $this->assertStringContainsString('lineage', $view);
        $this->assertStringContainsString('Mua sắm công', $view);
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

    public function test_large_dimension_filters_do_not_load_distinct_option_collections(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/DrugBidAward/Index.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardService.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/drug-bid-award/index.blade.php'));

        $this->assertStringNotContainsString('getUniqueInvestors', $component);
        $this->assertStringNotContainsString('getUniqueCompanies', $component);
        $this->assertStringNotContainsString('getUniqueInvestors', $service);
        $this->assertStringNotContainsString('getUniqueCompanies', $service);
        $this->assertStringContainsString('wire:model.live.debounce.300ms="filterInvestor"', $view);
        $this->assertStringContainsString('wire:model.live.debounce.300ms="filterCompany"', $view);
        $this->assertStringContainsString("where('investor_name', 'like'", $service);
        $this->assertStringContainsString("where('winning_company_name', 'like'", $service);
    }

    public function test_export_controls_preserve_selected_all_and_intelligence_filters(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/drug-bid-award/index.blade.php'));
        $export = file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardImportExport.php'));

        $this->assertStringContainsString("\$canEdit = \$admin?->can('edit_pharma') ?? false;", $view);
        $this->assertStringContainsString("'permission' => 'edit_pharma'", $view);
        $this->assertStringContainsString("'selected_ids' => \$selectedIds", $view);
        $this->assertStringContainsString("'medicine_match_status' => \$filterMatchStatus", $view);
        $this->assertStringContainsString('$selectedIds = $this->selectedIds($filters);', $export);
        $this->assertStringContainsString("when(\$filters['medicine_match_status'] ?? null", $export);
        $this->assertStringContainsString("'Nguồn dữ liệu' => \$model->source_type", $export);
        $this->assertStringNotContainsString('raw_payload', $export);
    }
}
