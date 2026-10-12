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
        $this->assertStringContainsString("unset(\$normalized['title'], \$normalized['slug'])", $service);
        $this->assertStringContainsString("PharmaProductLink::query()->where('product_id', \$id)->exists()", $service);
        $this->assertStringContainsString("assertNotPharmaLinked([\$id], 'duplicate')", $service);
        $this->assertStringContainsString("assertNotPharmaLinked([\$id], 'delete')", $service);
        $this->assertStringContainsString("array_diff(\$existingIds, \$protectedIds)", $service);
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

    public function test_pharma_commercial_edit_keeps_identity_readonly(): void
    {
        $service = file_get_contents(base_path('Modules/Product/Services/ProductService.php'));
        $form = file_get_contents(base_path('Modules/Product/Livewire/Products/ProductForm.php'));
        $view = file_get_contents(base_path('Modules/Product/resources/views/livewire/products/product-form.blade.php'));

        $this->assertStringContainsString("unset(\$normalized['title'], \$normalized['slug'])", $service);
        $this->assertStringContainsString("PharmaProductLink::query()->where('product_id', \$id)->exists()", $service);
        $this->assertStringContainsString('isPharmaLinked', $form);
        $this->assertStringContainsString('@readonly($isPharmaLinked)', $view);
        $this->assertStringContainsString('Nguồn: Pharma.', $view);
    }

    public function test_admin_filters_and_excel_export_share_the_same_query(): void
    {
        $service = file_get_contents(base_path('Modules/Product/Services/ProductService.php'));
        $table = file_get_contents(base_path('Modules/Product/Livewire/Products/ProductTable.php'));
        $export = file_get_contents(base_path('Modules/Product/Exports/ProductsExport.php'));
        $view = file_get_contents(base_path('Modules/Product/resources/views/livewire/products/product-table.blade.php'));

        $this->assertStringContainsString("public \$status = 'active'", $table);
        $this->assertStringContainsString("public \$source = 'all'", $table);
        $this->assertStringContainsString('filteredAdminQuery($filters)', $service);
        $this->assertStringContainsString("whereHas('pharmaLink')", $service);
        $this->assertStringContainsString("whereDoesntHave('pharmaLink')", $service);
        $this->assertStringContainsString('new ProductsExport($ids, $this->filters())', $table);
        $this->assertStringContainsString('exportRows($this->ids, 5000, $this->filters)', $export);
        $this->assertStringContainsString('wire:model.live="status"', $view);
        $this->assertStringContainsString('wire:model.live="source"', $view);
    }

    public function test_pharma_product_status_is_commercial_and_filters_can_be_reset(): void
    {
        $service = file_get_contents(base_path('Modules/Product/Services/ProductService.php'));
        $table = file_get_contents(base_path('Modules/Product/Livewire/Products/ProductTable.php'));
        $view = file_get_contents(base_path('Modules/Product/resources/views/livewire/products/product-table.blade.php'));

        $this->assertStringNotContainsString("assertNotPharmaLinked([\$id], 'toggle status')", $service);
        $this->assertStringNotContainsString("assertNotPharmaLinked(\$ids, 'bulk status')", $service);
        $this->assertStringContainsString("public function clearFilters(): void", $table);
        $this->assertStringContainsString("wire:click=\"clearFilters\"", $view);
        $this->assertStringContainsString("Xóa bộ lọc", $view);
    }

    public function test_mixed_bulk_delete_preserves_pharma_links_and_reports_counts(): void
    {
        $service = file_get_contents(base_path('Modules/Product/Services/ProductService.php'));
        $table = file_get_contents(base_path('Modules/Product/Livewire/Products/ProductTable.php'));
        $view = file_get_contents(base_path('Modules/Product/resources/views/livewire/products/product-table.blade.php'));

        $this->assertStringContainsString("Schema::hasTable('pharma_product_links')", $service);
        $this->assertStringContainsString("array_diff(\$existingIds, \$protectedIds)", $service);
        $this->assertStringContainsString("'protected' => count(\$protectedIds)", $service);
        $this->assertStringContainsString('bulkDeleteNotice', $table);
        $this->assertStringContainsString('role="status"', $view);
    }

    public function test_pharma_excel_has_separate_guarded_export_and_template(): void
    {
        $table = file_get_contents(base_path('Modules/Product/Livewire/Products/ProductTable.php'));
        $view = file_get_contents(base_path('Modules/Product/resources/views/livewire/products/product-table.blade.php'));
        $export = file_get_contents(base_path('Modules/Product/Exports/PharmaProductsExport.php'));

        $this->assertStringContainsString('public function exportPharma()', $table);
        $this->assertStringContainsString('public function downloadPharmaTemplate()', $table);
        $this->assertStringContainsString('abort_unless($this->products->pharmaSyncAvailable(), 404)', $table);
        $this->assertStringContainsString("whereHas('pharmaLink')", $export);
        $this->assertStringContainsString('if ($this->template)', $export);
        $this->assertStringContainsString('Export Pharma Excel', $view);
        $this->assertStringContainsString('Tải Template Pharma', $view);
        $this->assertStringContainsString('Import Pharma Excel', $view);
    }

    public function test_pharma_excel_import_is_separate_previewed_and_commercial_only(): void
    {
        $table = file_get_contents(base_path('Modules/Product/Livewire/Products/ProductTable.php'));
        $view = file_get_contents(base_path('Modules/Product/resources/views/livewire/products/product-table.blade.php'));
        $service = file_get_contents(base_path('Modules/Product/Services/PharmaProductExcelImportService.php'));

        $this->assertStringContainsString('previewPharmaImport', $table);
        $this->assertStringContainsString('applyPharmaImport', $table);
        $this->assertStringContainsString('pharmaImportValidated', $table);
        $this->assertStringContainsString('Import Pharma Excel', $view);
        $this->assertStringContainsString('Xác nhận Import', $view);
        $this->assertStringContainsString("where('product_id', \$product->id)->exists()", $service);
        $this->assertStringContainsString("DB::transaction(function () use (\$sheets)", $service);
        $this->assertStringNotContainsString("'quantity' =>", $service);
        $this->assertStringNotContainsString('$changes[\'title\']', $service);
        $this->assertStringNotContainsString('$changes[\'slug\']', $service);
    }

    public function test_pharma_excel_accepts_null_gallery_and_tags_and_styles_file_picker(): void
    {
        $service = file_get_contents(base_path('Modules/Product/Services/PharmaProductExcelImportService.php'));
        $view = file_get_contents(base_path('Modules/Product/resources/views/livewire/products/product-table.blade.php'));

        $this->assertStringContainsString("\$normalized === 'null'", $service);
        $this->assertStringContainsString("json_decode((string) \$normalized, true)", $service);
        $this->assertStringContainsString("array_is_list(\$decoded)", $service);
        $this->assertStringContainsString('border border-gray-300', $view);
        $this->assertStringContainsString('wire:model="pharmaImportFile"', $view);
    }

    public function test_admin_currency_input_does_not_multiply_decimal_cast_values(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/resources/views/components/currency-input.blade.php'));

        $this->assertStringContainsString('const amount = Number(val);', $component);
        $this->assertStringContainsString('Math.round(amount).toString()', $component);
        $this->assertStringNotContainsString("val.toString().replace(/\\D/g, '')", $component);
    }

    public function test_manual_pharma_sync_projects_declared_price_and_exposes_action(): void
    {
        $sync = file_get_contents(base_path('Modules/Pharma/Services/PharmaProductCatalogSyncService.php'));
        $table = file_get_contents(base_path('Modules/Product/Livewire/Products/ProductTable.php'));
        $view = file_get_contents(base_path('Modules/Product/resources/views/livewire/products/product-table.blade.php'));

        $this->assertStringContainsString('$variant->declared_price ?? $medicine->declared_price', $sync);
        $this->assertStringContainsString("'regular_price' => $declaredPrice", $sync);
        $this->assertStringContainsString("if (\$declaredPrice !== null)", $sync);
        $this->assertStringContainsString('function syncFromPharma(', $table);
        $this->assertStringContainsString('Đồng bộ từ Pharma', $view);
    }

}
