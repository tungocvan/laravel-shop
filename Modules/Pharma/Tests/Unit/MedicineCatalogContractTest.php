<?php

namespace Modules\Pharma\Tests\Unit;

use Modules\Pharma\DTOs\MedicineCatalogItem;
use Modules\Pharma\Services\MedicineCatalog;
use Modules\Pharma\Services\MedicineCatalogUploadService;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

class MedicineCatalogContractTest extends TestCase
{
    #[Test]
    public function catalog_exposes_stable_read_contract_for_other_modules(): void
    {
        $catalog = new ReflectionClass(MedicineCatalog::class);

        foreach (['findBySku', 'findByRegistration', 'findVariant', 'search', 'resolve'] as $method) {
            $this->assertTrue($catalog->hasMethod($method), "Missing MedicineCatalog::{$method}");
            $this->assertTrue($catalog->getMethod($method)->isPublic());
        }

        $dto = new ReflectionClass(MedicineCatalogItem::class);
        $this->assertTrue($dto->isReadOnly());
        $this->assertTrue($dto->hasMethod('toArray'));
    }

    #[Test]
    public function upload_service_has_bounded_import_size(): void
    {
        $this->assertSame(10000, MedicineCatalogUploadService::MAX_ROWS);
    }

    #[Test]
    public function medicine_import_routes_keep_preview_and_commit_separate(): void
    {
        $routes = file_get_contents(base_path('Modules/Pharma/routes/web.php'));

        $this->assertStringContainsString("Route::prefix('medicines')->name('medicines.')", $routes);
        $this->assertStringContainsString("Route::get('/import', [MedicineCatalogImportController::class, 'index'])", $routes);
        $this->assertStringContainsString("->name('import.index')", $routes);
        $this->assertStringContainsString("Route::post('/import', [MedicineCatalogImportController::class, 'store'])", $routes);
        $this->assertStringContainsString("->name('import.store')", $routes);
        $this->assertStringContainsString("Route::put('/import/{batch}/selection', [MedicineCatalogImportController::class, 'selection'])", $routes);
        $this->assertStringContainsString("->name('import.selection')", $routes);
        $this->assertStringContainsString("Route::post('/import/{batch}/commit', [MedicineCatalogImportController::class, 'commit'])", $routes);
        $this->assertStringContainsString("->name('import.commit')", $routes);
    }
    #[Test]
    public function medicine_master_exposes_canonical_excel_export_with_code_and_sku(): void
    {
        $routes = file_get_contents(base_path('Modules/Pharma/routes/web.php'));
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/PharmaController.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/index.blade.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/MedicineService.php'));

        $this->assertStringContainsString("Route::get('/export', [PharmaController::class, 'export'])", $routes);
        $this->assertStringContainsString("->name('export')", $routes);
        $this->assertStringContainsString('Export danh mục thuốc chuẩn', $view);
        $this->assertStringContainsString("'Mã thuốc' => \$medicine->medicine_code", $controller);
        $this->assertStringContainsString("'SKU' => \$variant?->sku", $controller);
        $this->assertStringContainsString("'pharma_medicine_profiles.medicine_id'", $controller);
        $this->assertStringContainsString("'pharma_medicine_profiles.is_current'", $controller);
        $this->assertStringContainsString("'medicine_code' => 'MED-'.str_pad", $service);
        $this->assertStringNotContainsString("medicine_code' =>", substr($service, strpos($service, 'public function update('), strpos($service, 'public function verifyMaster(') - strpos($service, 'public function update(')));
    }

    #[Test]
    public function medicine_master_workspace_exposes_professional_catalog_controls(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/Medicine/Index.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/MedicineService.php'));
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/PharmaController.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/index.blade.php'));

        $this->assertStringContainsString('toggleImportExport', $component);
        $this->assertStringContainsString('medicine-supplier-filter-search', $component);
        $this->assertStringContainsString('supplierFilterCandidates', $service);
        $this->assertStringContainsString("'special_control' => Medicine::query()->where('is_special_control', true)->count()", $service);
        $this->assertStringContainsString('<x-select-search id="medicine-supplier-filter"', $view);
        $this->assertStringNotContainsString('<x-search-select', $view);
        $this->assertStringContainsString('Danh sách theo dõi', $view);
        $this->assertStringContainsString('Import / Export', $view);
        $this->assertStringContainsString('Export Excel đã chọn', $view);
        $this->assertStringContainsString("\$canDelete && \$filterDeletable === 'yes'", $view);
        $this->assertStringContainsString('thuốc KSĐB', $view);
        $this->assertStringContainsString("query('ids', '')", $controller);
        $this->assertStringContainsString('whereKey($selectedIds->all())', $controller);
    }

}
