<?php

namespace Modules\Pharma\Tests\Unit;

use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Services\MedicineCatalogImportMapper;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MedicineProductTypeContractTest extends TestCase
{
    #[Test]
    public function product_types_are_stable_and_default_to_modern_medicine(): void
    {
        $this->assertSame([
            'tan_duoc' => 'Tân dược',
            'dong_duoc' => 'Đông dược',
            'thuc_pham_chuc_nang' => 'Thực phẩm chức năng',
        ], Medicine::productTypeOptions());

        $medicine = new Medicine();
        $this->assertSame(Medicine::PRODUCT_TYPE_MODERN, $medicine->product_type);
        $this->assertContains('product_type', $medicine->getFillable());
    }

    #[Test]
    public function admin_catalog_and_editor_expose_the_same_product_type(): void
    {
        $form = file_get_contents(base_path('Modules/Pharma/Livewire/Medicine/Form.php'));
        $editor = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/form.blade.php'));
        $catalog = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/index.blade.php'));
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_10_08_120000_add_product_type_to_pharma_medicines_table.php'));

        $this->assertStringContainsString("Rule::in(array_keys(Medicine::productTypeOptions()))", $form);
        $this->assertStringContainsString('wire:model="product_type"', $editor);
        $this->assertStringContainsString('Loại sản phẩm', $catalog);
        $this->assertStringContainsString("->default('tan_duoc')", $migration);
    }
    #[Test]
    public function import_mapper_accepts_export_labels_and_preserves_absent_type(): void
    {
        $mapper = new MedicineCatalogImportMapper();

        foreach (Medicine::productTypeOptions() as $code => $label) {
            $mapped = $mapper->map([
                'Tên thuốc' => 'Thuốc mẫu',
                'Loại sản phẩm' => $label,
                'GPLH' => 'VN-123',
                'Quy cách' => 'Hộp 10 viên',
            ]);
            $this->assertSame($code, $mapped['product_type']);
            $this->assertSame('VN-123', $mapped['registration_number']);
            $this->assertSame('Hộp 10 viên', $mapped['packaging_specification']);
        }

        $this->assertNull($mapper->map(['Tên thuốc' => 'Thuốc cũ'])['product_type']);
        $this->assertSame('__invalid_product_type__', $mapper->map(['Loại sản phẩm' => 'Sai loại'])['product_type']);
    }

    #[Test]
    public function staged_import_commits_type_only_when_provided(): void
    {
        $committer = file_get_contents(base_path('Modules/Pharma/Services/MedicineCatalogImportCommitter.php'));
        $stager = file_get_contents(base_path('Modules/Pharma/Services/MedicineCatalogImportStager.php'));
        $export = file_get_contents(base_path('Modules/Pharma/Http/Controllers/PharmaController.php'));

        $this->assertStringContainsString("'product_type' => \$data['product_type'] ?? Medicine::PRODUCT_TYPE_MODERN", $committer);
        $this->assertStringContainsString("'product_type' => \$data['product_type'] ?? null", $committer);
        $this->assertStringContainsString("'invalid_product_type'", $stager);
        $this->assertStringContainsString("'Loại sản phẩm' => Medicine::productTypeOptions()", $export);
    }

    #[Test]
    public function admin_catalog_filter_supports_all_product_types_and_reset(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/Medicine/Index.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/MedicineService.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/index.blade.php'));

        $this->assertStringContainsString("public string \$filterProductType = ''", $component);
        $this->assertStringContainsString('updatedFilterProductType', $component);
        $this->assertStringContainsString("'filterProductType', 'filterCircularGroup'", $component);
        $this->assertStringContainsString("'productTypeOptions' => Medicine::productTypeOptions()", $component);
        $this->assertStringContainsString("where('product_type', \$productType)", $service);
        $this->assertStringContainsString('wire:model.live="filterProductType"', $view);
        $this->assertStringContainsString('Tất cả loại sản phẩm', $view);
    }
    #[Test]
    public function desktop_catalog_columns_use_compact_widths_and_ellipsis(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/index.blade.php'));

        foreach (['w-[200px]', 'w-[190px]', 'w-[100px]', 'w-[80px]'] as $width) {
            $this->assertStringContainsString($width, $view);
        }

        $this->assertStringContainsString('w-[176px] truncate font-medium', $view);
        $this->assertStringContainsString('w-[166px] truncate font-medium', $view);
        $this->assertStringContainsString('title="{{ $medicine->active_ingredients', $view);
    }
    #[Test]
    public function medicine_filters_are_grouped_without_losing_livewire_bindings(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/index.blade.php'));

        foreach (['Tra cứu &amp; phân loại', 'Kiểm soát chất lượng dữ liệu', 'Bộ lọc bổ sung &amp; hiển thị'] as $heading) {
            $this->assertStringContainsString($heading, $view);
        }

        foreach (['search', 'filterProductType', 'filterSupplier', 'filterHssp', 'filterProfileStatus', 'filterRegistration', 'filterDeletable', 'filterCircularGroup', 'filterSpecialControl', 'perPage'] as $binding) {
            $this->assertStringContainsString('"'.$binding.'"', $view);
        }

        $this->assertStringContainsString('wire:click="resetFilters"', $view);
        $this->assertStringContainsString('md:grid-cols-2 xl:grid-cols-12', $view);
    }
}
