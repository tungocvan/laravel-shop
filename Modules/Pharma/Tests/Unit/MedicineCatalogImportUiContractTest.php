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
    public function import_workspace_has_template_auto_filters_and_timestamped_history(): void
    {
        $routes = file_get_contents(base_path('Modules/Pharma/routes/web.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/pages/medicine-import/index.blade.php'));
        $export = file_get_contents(base_path('Modules/Pharma/Exports/MedicineCatalogTemplateExport.php'));

        $this->assertStringContainsString("name('import.template')", $routes);
        $this->assertStringContainsString("route('admin.pharma.medicines.import.template')", $view);
        $this->assertStringContainsString('Tải template danh mục thuốc (.xlsx)', $view);
        $this->assertStringContainsString('onchange="this.form.submit()"', $view);
        $this->assertStringNotContainsString('Áp dụng bộ lọc', $view);
        $this->assertStringContainsString("format('d/m/Y H:i')", $view);
        $this->assertStringContainsString("'Tên hoạt chất'", $export);
        $this->assertStringContainsString("'Nồng độ - Hàm lượng'", $export);
        $this->assertStringContainsString("'Tên biệt dược'", $export);
    }
}
