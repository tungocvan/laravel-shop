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

        $this->assertStringContainsString("name('medicines.import.index')", $routes);
        $this->assertStringContainsString("name('medicines.import.store')", $routes);
        $this->assertStringContainsString("name('medicines.import.selection')", $routes);
        $this->assertStringContainsString("name('medicines.import.commit')", $routes);
    }
}
