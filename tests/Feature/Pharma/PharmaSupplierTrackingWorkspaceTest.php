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

        $this->assertStringNotContainsString('public string $workingDateFrom', $component);
        $this->assertStringNotContainsString('public string $workingDateTo', $component);
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
        $this->assertStringContainsString('public ?int $medicine_id = null;', $form);
        $this->assertStringContainsString('Medicine::query()->find((int) $medicineId)', $form);
        $this->assertStringContainsString("\$this->form['unit'] = (string) (\$medicine->unit ?? '');", $form);
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


    public function test_commercial_workspace_uses_partner_facility_scope_and_document_uploads(): void
    {
        $medicineView = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/index.blade.php'));
        $form = file_get_contents(base_path('Modules/Pharma/Livewire/SupplierTrackings/Form.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/supplier-trackings/form.blade.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/SupplierTrackingService.php'));
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_21_101500_refactor_supplier_trackings_as_commercial_workspace.php'));

        $this->assertStringContainsString('Cập nhật NCC', $medicineView);
        $this->assertStringContainsString("['medicine_id' => \$medicine->id]", $medicineView);
        $this->assertStringContainsString('public ?int $partner_id = null;', $form);
        $this->assertStringContainsString('use WithFileUploads;', $form);
        $this->assertStringContainsString("'distribution_scope' => 'all'", $form);
        $this->assertStringContainsString('supplierCandidates(', $service);
        $this->assertStringContainsString("public string \$supplierSearch = '';", $form);
        $this->assertStringContainsString("supplierCandidates(\$this->supplierSearch, \$this->partner_id)", $form);
        $this->assertStringContainsString('search-event="supplier-search"', $view);
        $this->assertStringContainsString('options-wire="supplierOptions"', $view);
        $this->assertStringContainsString('Chọn hoặc tìm nhà cung cấp', $view);
        $this->assertStringContainsString("#[On('supplier-search')]", $form);
        $this->assertStringContainsString("public array \$supplierOptions = [];", $form);
        $this->assertStringContainsString('refreshSupplierOptions($service)', $form);
        $this->assertStringContainsString('$this->supplierOptions = $service->supplierCandidates', $form);
        $selectSearch = file_get_contents(base_path('resources/views/components/select-search.blade.php'));
        $this->assertStringContainsString("optionsWire: @js(\$attributes->get('options-wire'))", $selectSearch);
        $this->assertStringNotContainsString("optionsWire: '{{ \$attributes->get('options-wire') }}'", $selectSearch);
        $this->assertStringContainsString("->withPartnerType('supplier')", $service);
        $this->assertStringContainsString('facilityCandidates(', $service);
        $this->assertStringContainsString('Supplier Commercial Workspace', $view);
        $this->assertStringContainsString('Giá vốn NCC', $view);
        $this->assertStringContainsString('Theo vùng miền', $view);
        $this->assertStringContainsString('Tỉnh/Thành thuộc vùng miền', $view);
        $this->assertStringContainsString('wire:model.live="form.distribution_regions"', $view);
        $this->assertStringContainsString('distribution_provinces', $form);
        $this->assertStringContainsString('distributionProvincesByRegion()', $service);
        $this->assertStringContainsString('Chọn từng cơ sở', $view);
        $this->assertStringContainsString('Hợp đồng hai bên', $view);
        $this->assertStringContainsString('Biên bản / chứng từ cọc', $view);
        $this->assertStringContainsString('Đơn vị tính', $view);
        $this->assertStringNotContainsString('Tìm nhà cung cấp *', $view);
        $this->assertStringContainsString("filterSupplier", $medicineView);
        $this->assertStringContainsString('Nhà cung cấp', $medicineView);
        $this->assertStringContainsString('Khả năng xóa', $medicineView);
        $this->assertStringContainsString('Có thể xóa', $medicineView);
        $this->assertStringContainsString('x-teleport="body"', $medicineView);
        $this->assertStringContainsString('x-ref="trigger"', $medicineView);
        $this->assertStringContainsString('Điều kiện NCC', $medicineView);
        $this->assertStringContainsString('supplier_tracking_partner_business_key_unique', $migration);
        $this->assertStringContainsString('pharma_supplier_tracking_facilities', $migration);
    }



    public function test_supplier_candidates_use_shared_multi_role_partner_scope(): void
    {
        $partner = file_get_contents(base_path('Modules/Partner/Models/Partner.php'));
        $partnerService = file_get_contents(base_path('Modules/Partner/Services/PartnerService.php'));
        $supplierService = file_get_contents(base_path('Modules/Pharma/Services/SupplierTrackingService.php'));

        $this->assertStringContainsString('scopeWithPartnerType', $partner);
        $this->assertStringContainsString("whereJsonContains('partner_types', \$type)", $partner);
        $this->assertStringContainsString("->withPartnerType('supplier')", $supplierService);
        $this->assertStringContainsString("'partner_types' => \$partnerTypes", $partnerService);
    }

    public function test_minimal_supplier_commercial_record_only_requires_supplier_and_cost(): void
    {
        $form = file_get_contents(base_path('Modules/Pharma/Livewire/SupplierTrackings/Form.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/SupplierTrackingService.php'));

        $this->assertStringContainsString("'partner_id' => ['required', 'exists:partners,id']", $form);
        $this->assertStringContainsString("'form.import_price' => ['required', 'numeric', 'min:0']", $form);
        $this->assertStringNotContainsString('Chọn ít nhất một vùng được phép bán.', $form);
        $this->assertStringNotContainsString('Chọn ít nhất một Tỉnh/Thành thuộc vùng miền đã chọn.', $form);
        $this->assertStringNotContainsString('Chọn ít nhất một cơ sở khám chữa bệnh.', $form);
        $this->assertStringContainsString("A commercial condition is valid with Supplier + supplier cost only.", $service);
        $this->assertStringContainsString("\$data['working_date'] = \$data['working_date'] ?: null;", $service);
    }

    public function test_region_scope_persists_and_validates_province_selection(): void
    {
        $model = file_get_contents(base_path('Modules/Pharma/Models/SupplierTracking.php'));
        $form = file_get_contents(base_path('Modules/Pharma/Livewire/SupplierTrackings/Form.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/SupplierTrackingService.php'));
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_21_124500_add_distribution_provinces_to_supplier_trackings.php'));

        $this->assertStringContainsString("'distribution_provinces' => 'array'", $model);
        $this->assertStringContainsString("form.distribution_provinces", $form);
        $this->assertStringContainsString('updatedFormDistributionRegions($value = null, $key = null)', $form);
        $this->assertStringContainsString("array_intersect(", $form);
        $this->assertStringContainsString('$allowedProvinceCodes', $form);
        $this->assertStringNotContainsString('Chọn ít nhất một Tỉnh/Thành thuộc vùng miền đã chọn.', $form);
        $this->assertStringNotContainsString('Tỉnh/Thành đã chọn không thuộc vùng miền được phép bán.', $form);
        $this->assertStringContainsString("['distribution_provinces']", $service);
        $this->assertStringContainsString("json('distribution_provinces')", $migration);
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
    public function test_supplier_tracking_index_uses_searchable_supplier_and_product_filters_without_date_filter(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/SupplierTrackings/Index.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/SupplierTrackingService.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/supplier-trackings/index.blade.php'));
        $page = file_get_contents(base_path('Modules/Pharma/resources/views/pages/supplier-trackings/index.blade.php'));

        $this->assertStringContainsString("public string \$supplierId = '';", $component);
        $this->assertStringContainsString("public string \$medicineId = '';", $component);
        $this->assertStringContainsString("#[On('supplier-filter-search')]", $component);
        $this->assertStringContainsString("#[On('medicine-filter-search')]", $component);
        $this->assertStringContainsString("'partner_id' => \$this->supplierId", $component);
        $this->assertStringContainsString("'medicine_id' => \$this->medicineId", $component);
        $this->assertStringContainsString('supplierFilterCandidates(', $service);
        $this->assertStringContainsString('medicineFilterCandidates(', $service);
        $this->assertStringContainsString('search-event="supplier-filter-search"', $view);
        $this->assertStringContainsString('search-event="medicine-filter-search"', $view);
        $this->assertStringContainsString('<x-select-search id="supplier-filter"', $view);
        $this->assertStringContainsString('<x-select-search id="medicine-filter"', $view);
        $this->assertStringNotContainsString('supplier-date-from', $view);
        $this->assertStringNotContainsString('supplier-date-to', $view);
        $this->assertStringNotContainsString('LN thực tế', $view);
        $this->assertStringContainsString("@section('admin_container', 'full')", $page);
        $this->assertStringContainsString('<div class="w-full space-y-5">', $view);
    }
}
