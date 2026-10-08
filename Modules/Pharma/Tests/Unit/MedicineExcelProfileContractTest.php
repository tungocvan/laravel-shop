<?php

namespace Modules\Pharma\Tests\Unit;

use Modules\Pharma\Services\MedicineExcelProfileService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MedicineExcelProfileContractTest extends TestCase
{
    #[Test]
    public function medicine_excel_profile_has_independent_schema_and_product_type(): void
    {
        $service = new MedicineExcelProfileService();
        $defaults = $service->defaults();

        $this->assertContains('product_type', $defaults['columns']);
        $this->assertSame('Loại sản phẩm', MedicineExcelProfileService::COLUMNS['product_type']);
        $this->assertContains('medicine_code', $defaults['columns']);
        $this->assertContains('sku', $defaults['columns']);
        $this->assertSame('Hạn dùng sản phẩm', MedicineExcelProfileService::COLUMNS['shelf_life']);
        $this->assertSame('Times New Roman', $defaults['settings']['font_family']);
        $this->assertSame(12, $defaults['settings']['header_font_size']);
        $this->assertSame(11, $defaults['settings']['body_font_size']);
        $this->assertTrue($defaults['settings']['body_border']);
        $this->assertSame('A4', $defaults['settings']['paper_size']);
        $this->assertSame('landscape', $defaults['settings']['orientation']);
        $this->assertSame('pharma_medicine_excel_profiles', (new \Modules\Pharma\Models\MedicineExcelProfile())->getTable());
    }

    #[Test]
    public function configured_export_is_separate_from_legacy_import_export(): void
    {
        $routes = file_get_contents(base_path('Modules/Pharma/routes/web.php'));
        $catalog = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/index.blade.php'));
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/MedicineExcelController.php'));

        $this->assertStringContainsString("name('export-configured')", $routes);
        $this->assertStringContainsString("name('export')", $routes);
        $this->assertStringContainsString("name('import.index')", $routes);
        $this->assertStringContainsString("route('admin.pharma.medicines.export')", $catalog);
        $this->assertStringContainsString("route('admin.pharma.medicines.export-configured'", $catalog);
        $this->assertStringContainsString("pharma.medicine.excel-configurator", $catalog);
        $this->assertStringContainsString("'product_type' => Medicine::productTypeOptions()", $controller);
        $this->assertStringContainsString("->lazy(200)", $controller);
    }

    #[Test]
    public function configurator_supports_saved_profiles_and_custom_column_order(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/Medicine/ExcelConfigurator.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/excel-configurator.blade.php'));

        foreach (['openConfig', 'newProfile', 'move', 'reorderSelected', 'duplicateProfile', 'resetColumnOrder', 'selectAll', 'clearAll', 'save', 'deleteProfile'] as $method) {
            $this->assertStringContainsString('function '.$method.'(', $component);
        }
        $this->assertStringContainsString('wire:click="save"', $view);
        $this->assertStringContainsString('x-on:medicine-designer-save.window="$wire.saveDraft(payload())"', $view);
        $this->assertStringContainsString('wire:model="settings.paper_size"', $view);
        $this->assertStringContainsString('wire:model="settings.orientation"', $view);
        $this->assertStringContainsString('3. Thiết lập cột', $view);
        $this->assertStringContainsString('Đã lưu cấu hình Excel', $view);
        $this->assertStringContainsString('settings.font_family', $view);
        $this->assertStringContainsString('settings.header_fill', $view);
        $this->assertStringContainsString('medicine-designer-workspace', $view);
        $this->assertStringContainsString('grid-template-columns: 190px minmax(0,1fr)', $view);
        $this->assertStringContainsString('medicine-designer-columns', $view);
        $this->assertStringContainsString('draft.widths[editing]', $view);
        $this->assertStringContainsString('Chỉnh sửa tức thì trên trình duyệt', $view);
        $this->assertStringContainsString('wire:click="duplicateProfile"', $view);
        $this->assertStringContainsString('x-on:click="reset()"', $view);
        $this->assertStringContainsString('x-on:drop.prevent=', $view);
        $this->assertStringContainsString('Kho dữ liệu', $view);
        $this->assertStringContainsString('Cột sẽ xuất Excel', $view);
        $this->assertStringContainsString("['brand','1','Thương hiệu']", $view);
        $this->assertStringContainsString("['page','3','Trang in']", $view);
    }
    #[\PHPUnit\Framework\Attributes\Test]
    public function saving_older_profiles_defaults_alignment_for_new_columns(): void
    {
        $source = file_get_contents(base_path('Modules/Pharma/Services/MedicineExcelProfileService.php'));

        $this->assertStringContainsString("\$alignment = \$data['alignments'][\$key] ?? 'left';", $source);
        $this->assertStringContainsString("? \$alignment : 'left';", $source);
        $this->assertStringNotContainsString("? \$data['alignments'][\$key] : 'left';", $source);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function regulatory_fields_are_optional_and_exported_from_medicine(): void
    {
        $defaults = (new MedicineExcelProfileService())->defaults();
        $fields = [
            'circular_order_number' => 'STT thông tư',
            'visa_validity_date' => 'Hiệu lực Visa',
            'gmp_certification_date' => 'GMP cơ sở sản xuất',
            'is_special_control' => 'Thuốc kiểm soát đặc biệt (KSĐB)',
        ];
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/MedicineExcelController.php'));
        foreach ($fields as $key => $label) {
            $this->assertSame($label, MedicineExcelProfileService::COLUMNS[$key]);
            $this->assertNotContains($key, $defaults['columns']);
            $this->assertStringContainsString("'".$key."' =>", $controller);
        }
        $this->assertStringContainsString("visa_validity_date?->format('d/m/Y')", $controller);
        $this->assertStringContainsString("gmp_certification_date?->format('d/m/Y')", $controller);
        $this->assertStringContainsString("getRawOriginal('is_special_control') === null", $controller);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function inspector_selection_is_local_and_column_position_can_jump(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/excel-configurator.blade.php'));
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/Medicine/ExcelConfigurator.php'));

        $this->assertStringContainsString('draft: {', $view);
        $this->assertStringContainsString('columns: @js($selectedOrder->all())', $view);
        $this->assertStringContainsString('x-on:click="editing = key"', $view);
        $this->assertStringContainsString('x-model="draft.headers[editing]"', $view);
        $this->assertStringContainsString('x-on:drop.prevent=', $view);
        $this->assertStringContainsString('x-on:change="move(key, $event.target.value)"', $view);
        $this->assertStringNotContainsString("\$wire.entangle(", $view);
        $this->assertStringContainsString('function saveDraft(array $draft, MedicineExcelProfileService $service)', $component);
        $this->assertStringContainsString('$this->save($service);', $component);
    }

    #[Test]
    public function local_first_designer_survives_profile_actions_without_dom_morph_corruption(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/excel-configurator.blade.php'));
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/Medicine/ExcelConfigurator.php'));

        $this->assertStringContainsString('wire:ignore wire:key="medicine-excel-v4-{{ $designerRevision }}"', $view);
        $this->assertStringContainsString("this.draft.headers[key] = this.labels[key]", $view);
        $this->assertStringContainsString('public int $designerRevision = 0;', $component);
        $this->assertStringContainsString('$this->designerRevision++;', $component);
        $this->assertStringContainsString('service->delete((int) auth(\'admin\')->id(), $this->profileId);', $component);
        $this->assertStringContainsString('$this->profileId = null;', $component);
        $this->assertStringContainsString('x-on:click="add(@js($key))"', $view);
    }

}
