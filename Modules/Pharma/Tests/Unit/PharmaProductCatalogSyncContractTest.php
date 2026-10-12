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
    public function product_crud_guards_pharma_linked_records(): void
    {
        $service = file_get_contents(base_path('Modules/Product/Services/ProductService.php'));
        $this->assertStringContainsString("assertNotPharmaLinked([\$id], 'edit')", $service);
        $this->assertStringContainsString("assertNotPharmaLinked([\$id], 'duplicate')", $service);
        $this->assertStringContainsString("assertNotPharmaLinked([\$id], 'toggle status')", $service);
        $this->assertStringContainsString("assertNotPharmaLinked([\$id], 'delete')", $service);
        $this->assertStringContainsString("assertNotPharmaLinked(\$ids, 'bulk delete')", $service);
    }

    #[Test]
    public function projection_keeps_product_commercial_values_out_of_pharma(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/PharmaProductCatalogSyncService.php'));

        $this->assertStringContainsString("'regular_price' => null", $service);
        $this->assertStringContainsString("'is_active' => false", $service);
        $this->assertStringContainsString("where('source_key', \$sourceKey)->lockForUpdate()", $service);
        $this->assertStringContainsString("['title' => \$title]", $service);
        $this->assertStringContainsString('lockForUpdate()->findOrFail', $service);
        $this->assertStringNotContainsString('declared_price', $service);
    }
    public function test_bulk_status_and_sync_panel_have_guards(): void
    {
        $service = file_get_contents(base_path('Modules/Product/Services/ProductService.php'));
        $table = file_get_contents(base_path('Modules/Product/Livewire/Products/ProductTable.php'));
        $view = file_get_contents(base_path('Modules/Product/resources/views/livewire/products/product-table.blade.php'));

        $this->assertStringContainsString("config('modules.registry.Pharma.enabled', false) === true", $service);
        $this->assertStringNotContainsString('Nwidart\\Modules\\Facades\\Module', $service);
        $this->assertStringContainsString("Schema::hasTable('pharma_product_links')", $service);
        $this->assertStringContainsString("DB::transaction(function () use (\$ids, \$active)", $service);
        $this->assertStringContainsString("assertNotPharmaLinked(\$ids, 'bulk status')", $service);
        $this->assertStringContainsString("authorizeAdmin('edit_product')", $table);
        $this->assertStringContainsString('Quản lý đồng bộ Pharma', $view);
        $this->assertStringContainsString('Bật hàng loạt', $view);
        $this->assertStringContainsString('Tắt hàng loạt', $view);
    }

    public function test_product_admin_listing_displays_pharma_origin_without_n_plus_one(): void
    {
        $model = file_get_contents(base_path('Modules/Product/Models/Product.php'));
        $service = file_get_contents(base_path('Modules/Product/Services/ProductService.php'));
        $view = file_get_contents(base_path('Modules/Product/resources/views/livewire/products/product-table.blade.php'));

        $this->assertStringContainsString('function pharmaLink(): HasOne', $model);
        $this->assertStringContainsString("->with('pharmaLink:id,product_id,medicine_id,sync_status,last_error,last_synced_at')", $service);
        $this->assertStringContainsString('pharmaSyncAvailable()', $service);
        $this->assertStringContainsString('Nguồn: Pharma', $view);
        $this->assertStringContainsString('Nguồn: Thủ công', $view);
        $this->assertStringContainsString('sync_status', $view);
    }

}
