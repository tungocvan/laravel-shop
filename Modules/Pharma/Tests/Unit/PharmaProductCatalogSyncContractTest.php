<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PharmaProductCatalogSyncContractTest extends TestCase
{
    #[Test]
    public function catalog_sync_schema_has_unique_source_and_product_links(): void
    {
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_10_12_120000_create_pharma_product_links_table.php'));

        $this->assertStringContainsString("Schema::create('pharma_product_links'", $migration);
        $this->assertStringContainsString("->unique()->constrained('wp_products')", $migration);
        $this->assertStringContainsString("'source_key', 128)->unique()", $migration);
        $this->assertStringContainsString('restrictOnDelete()', $migration);
    }

    #[Test]
    public function projection_keeps_product_commercial_values_out_of_pharma(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/PharmaProductCatalogSyncService.php'));

        $this->assertStringContainsString("'regular_price' => null", $service);
        $this->assertStringContainsString("'is_active' => false", $service);
        $this->assertStringContainsString("where('source_key', \$sourceKey)->lockForUpdate()", $service);
        $this->assertStringContainsString("['title' => \$title]", $service);
        $this->assertStringNotContainsString('declared_price', $service);
    }
}
