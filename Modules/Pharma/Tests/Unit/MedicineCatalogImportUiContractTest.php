<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MedicineCatalogImportUiContractTest extends TestCase
{
    #[Test]
    public function medicine_master_uses_staged_import_workspace_instead_of_legacy_direct_import_panel(): void
    {
        $masterView = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/index.blade.php'));
        $importView = file_get_contents(base_path('Modules/Pharma/resources/views/pages/medicine-import/index.blade.php'));

        $this->assertIsString($masterView);
        $this->assertIsString($importView);
        $this->assertStringContainsString("route('admin.pharma.medicines.import.index')", $masterView);
        $this->assertStringContainsString('Upload chỉ tạo staging', $importView);
        $this->assertStringContainsString('Preview staging', $importView);
        $this->assertStringNotContainsString("@livewire('shared.import-export.panel'", $masterView);
    }

    #[Test]
    public function import_workspace_has_template_auto_filters_timestamped_history_and_safe_clear_action(): void
    {
        $routes = file_get_contents(base_path('Modules/Pharma/routes/web.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/pages/medicine-import/index.blade.php'));
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/MedicineCatalogImportController.php'));
        $export = file_get_contents(base_path('Modules/Pharma/Exports/MedicineCatalogTemplateExport.php'));

        $this->assertStringContainsString("name('import.template')", $routes);
        $this->assertStringContainsString("name('import.history.clear')", $routes);
        $this->assertStringContainsString("route('admin.pharma.medicines.import.template')", $view);
        $this->assertStringContainsString("route('admin.pharma.medicines.import.history.clear')", $view);
        $this->assertStringContainsString('Tải template danh mục thuốc (.xlsx)', $view);
        $this->assertStringContainsString('Clear lịch sử import', $view);
        $this->assertStringContainsString('onchange="this.form.submit()"', $view);
        $this->assertStringNotContainsString('Áp dụng bộ lọc', $view);
        $this->assertStringContainsString("format('d/m/Y H:i')", $view);
        $this->assertStringContainsString('MedicineImportRow::query()->delete()', $controller);
        $this->assertStringContainsString('MedicineImportBatch::query()->delete()', $controller);
        $this->assertStringContainsString('Medicine Master đã đồng bộ không bị thay đổi', $controller);
        $this->assertStringContainsString("'Tên hoạt chất'", $export);
        $this->assertStringContainsString("'Nồng độ - Hàm lượng'", $export);
        $this->assertStringContainsString("'Tên biệt dược'", $export);
    }
}
