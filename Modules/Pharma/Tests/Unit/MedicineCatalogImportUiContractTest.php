<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MedicineCatalogImportUiContractTest extends TestCase
{
    #[Test]
    public function medicine_master_uses_staged_import_workspace_instead_of_legacy_direct_import_panel(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/index.blade.php'));

        $this->assertIsString($view);
        $this->assertStringContainsString("route('admin.pharma.medicines.import.index')", $view);
        $this->assertStringContainsString('Import an toàn qua staging', $view);
        $this->assertStringNotContainsString("@livewire('shared.import-export.panel'", $view);
    }
}
