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

    public function test_workspace_exposes_source_provenance_without_coupling_pharma_to_muasamcong_model(): void
    {
        $model = file_get_contents(base_path('Modules/Pharma/Models/DrugBidAward.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardService.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/drug-bid-award/index.blade.php'));

        $this->assertStringContainsString("public const SOURCE_MANUAL = 'manual';", $model);
        $this->assertStringContainsString("public const SOURCE_MUASAMCONG = 'muasamcong';", $model);
        $this->assertStringContainsString('projectFromSource(DrugBidAwardSourceData $source)', $service);
        $this->assertStringContainsString("'source_type' => \$source->sourceType", $service);
        $this->assertStringContainsString("'source_id' => \$source->sourceId", $service);
        $this->assertStringNotContainsString('Modules\\Muasamcong', $service);
        $this->assertStringContainsString('Nguồn dữ liệu', $view);
        $this->assertStringContainsString('Mua sắm công', $view);
    }

    public function test_source_identity_migration_is_additive_and_unique(): void
    {
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_08_30_010000_add_source_identity_to_drug_bid_awards_table.php'));

        $this->assertStringContainsString("->default('manual')", $migration);
        $this->assertStringContainsString("\$table->uuid('source_id')", $migration);
        $this->assertStringContainsString("\$table->timestamp('source_synced_at')", $migration);
        $this->assertStringContainsString("\$table->char('source_payload_hash', 64)", $migration);
        $this->assertStringContainsString("\$table->unique(['source_type', 'source_id'], 'drug_bid_awards_source_identity_unique')", $migration);
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

    public function test_destructive_and_import_export_controls_are_permission_aware(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/drug-bid-award/index.blade.php'));

        $this->assertStringContainsString("\$canCreate = \$admin?->can('create_pharma') ?? false;", $view);
        $this->assertStringContainsString("\$canEdit = \$admin?->can('edit_pharma') ?? false;", $view);
        $this->assertStringContainsString("\$canDelete = \$admin?->can('delete_pharma') ?? false;", $view);
        $this->assertStringContainsString("'permission' => 'edit_pharma'", $view);
        $this->assertStringContainsString('wire:click="confirmBulkDelete"', $view);
        $this->assertStringContainsString('role="dialog"', $view);
        $this->assertStringContainsString('trang hiện tại', $view);
        $this->assertStringContainsString('wire:loading.attr="disabled"', $view);
    }
}
