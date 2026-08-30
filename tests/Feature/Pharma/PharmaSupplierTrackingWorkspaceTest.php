<?php

namespace Tests\Feature\Pharma;

use Modules\Pharma\Services\SupplierTrackingService;
use Tests\TestCase;

class PharmaSupplierTrackingWorkspaceTest extends TestCase
{
    public function test_workspace_uses_bounded_pagination_and_page_scoped_selection(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/SupplierTrackings/Index.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/supplier-trackings/index.blade.php'));

        $this->assertStringContainsString('private const PER_PAGE_OPTIONS = [10, 25, 50, 100];', $component);
        $this->assertStringContainsString('public bool $selectPage = false;', $component);
        $this->assertStringContainsString('$this->selectedIds = $value ? $this->currentPageIds() : [];', $component);
        $this->assertStringNotContainsString('getFilteredIds', $component);
        $this->assertStringContainsString('trang hiện tại', $view);
        $this->assertStringNotContainsString('999999', $component.$view);
    }

    public function test_workspace_exposes_status_date_filters_permissions_and_loading_states(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/SupplierTrackings/Index.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/supplier-trackings/index.blade.php'));

        $this->assertStringContainsString('public string $workingDateFrom', $component);
        $this->assertStringContainsString('public string $workingDateTo', $component);
        $this->assertStringContainsString("@can('create_pharma')", $view);
        $this->assertStringContainsString("@can('edit_pharma')", $view);
        $this->assertStringContainsString("@can('delete_pharma')", $view);
        $this->assertStringContainsString('wire:loading.attr="disabled"', $view);
        $this->assertStringContainsString('rel="noopener noreferrer"', $view);
    }

    public function test_medicine_lookup_is_server_side_and_bounded(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/SupplierTrackingService.php'));
        $form = file_get_contents(base_path('Modules/Pharma/Livewire/SupplierTrackings/Form.php'));

        $this->assertStringContainsString('medicineCandidates(', $service);
        $this->assertStringContainsString('min(25, $limit)', $service);
        $this->assertStringContainsString("->orWhere('active_ingredients', 'like'", $service);
        $this->assertStringContainsString('public string $medicineSearch', $form);
        $this->assertStringNotContainsString('medicinesForSelect()', $form);
    }

    public function test_business_key_is_normalized_and_protected_across_crud_import_and_database(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/SupplierTrackingService.php'));
        $importExport = file_get_contents(base_path('Modules/Pharma/Services/ImportExport.php'));
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_08_30_020000_add_business_key_to_supplier_trackings_table.php'));

        $this->assertStringContainsString("'supplier_name_normalized'", $service);
        $this->assertStringContainsString('guardBusinessKey(', $service);
        $this->assertStringContainsString('DuplicateSupplierTrackingException', $service);
        $this->assertStringContainsString('protected array $uniqueBy = [\'medicine_id\', \'supplier_name_normalized\', \'working_date\'];', $importExport);
        $this->assertStringContainsString('supplier_trackings_business_key_unique', $migration);
        $this->assertStringContainsString("->whereNotNull('working_date')", $migration);
        $this->assertStringContainsString('Resolve duplicate Medicine + Supplier + Working Date records', $migration);
    }

    public function test_financial_calculations_remain_server_owned_and_cover_edge_cases(): void
    {
        $service = app(SupplierTrackingService::class);

        $normal = $service->previewCalculate([
            'import_price' => 100,
            'invoice_price' => 200,
            'invoice_difference_percent' => 10,
            'selling_price' => 250,
        ]);

        $this->assertSame(100.0, $normal['invoice_difference_amount']);
        $this->assertSame(10.0, $normal['invoice_difference_fee']);
        $this->assertSame(110.0, $normal['cost_price']);
        $this->assertSame(56.0, $normal['gross_profit_percent']);

        $zeroSellingPrice = $service->previewCalculate([
            'import_price' => 100,
            'invoice_price' => 80,
            'invoice_difference_percent' => 10,
            'selling_price' => 0,
        ]);

        $this->assertSame(-20.0, $zeroSellingPrice['invoice_difference_amount']);
        $this->assertSame(-2.0, $zeroSellingPrice['invoice_difference_fee']);
        $this->assertSame(98.0, $zeroSellingPrice['cost_price']);
        $this->assertSame(0.0, $zeroSellingPrice['gross_profit_percent']);
    }

    public function test_demo_command_is_local_only_and_has_repeatable_dataset_scope(): void
    {
        $command = file_get_contents(base_path('Modules/Pharma/Console/Commands/ResetSupplierTrackingDemoCommand.php'));

        $this->assertStringContainsString('protected $signature = \'reset:pharma-supplier-tracking-demo\';', $command);
        $this->assertStringContainsString("app()->environment('local')", $command);
        $this->assertStringContainsString('for ($i = 1; $i <= 36; $i++)', $command);
        $this->assertStringContainsString("where('supplier_name', 'like', self::SUPPLIER_PREFIX.'%')->delete()", $command);
        $this->assertStringNotContainsString('truncate()', $command);
        $this->assertStringNotContainsString('migrate:fresh', $command);
    }
}
