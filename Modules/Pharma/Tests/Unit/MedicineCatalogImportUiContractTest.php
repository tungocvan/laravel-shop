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
}
