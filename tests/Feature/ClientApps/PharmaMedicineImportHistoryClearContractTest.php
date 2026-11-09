<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class PharmaMedicineImportHistoryClearContractTest extends TestCase
{
    public function test_clear_history_is_confirmed_owner_scoped_and_does_not_touch_medicine_master(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaMedicineExcelController.php'));
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/import.blade.php'));

        $this->assertStringContainsString("name('products.import.clear-history')", $routes);
        $this->assertStringContainsString('public function clearImportHistory(', $controller);
        $this->assertStringContainsString("client.pharma.products.import", $controller);
        $this->assertStringContainsString("where('source', 'clientportal_pharma')", $controller);
        $this->assertStringContainsString("where('created_by', (int) \$user->id)", $controller);
        $this->assertStringContainsString("in:XOA LICH SU IMPORT", $controller);
        $this->assertStringContainsString('STATUS_READY', $controller);
        $this->assertStringContainsString('STATUS_FAILED', $controller);
        $this->assertStringContainsString('STATUS_STAGED', $controller);
        $this->assertStringContainsString('DB::transaction(', $controller);
        $this->assertStringContainsString('->rows()->delete()', $controller);
        $this->assertStringContainsString('products.import.clear-history', $view);
        $this->assertStringContainsString('Xóa lịch sử import', $view);
    }
}
