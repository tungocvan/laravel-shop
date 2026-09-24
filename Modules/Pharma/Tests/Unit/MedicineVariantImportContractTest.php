<?php

namespace Modules\Pharma\Tests\Unit;

use Tests\TestCase;

class MedicineVariantImportContractTest extends TestCase
{
    public function test_catalog_import_keeps_package_and_price_at_variant_level(): void
    {
        $committer = file_get_contents(base_path('Modules/Pharma/Services/MedicineCatalogImportCommitter.php'));
        $variant = file_get_contents(base_path('Modules/Pharma/Models/MedicineVariant.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/MedicineService.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/medicine/index.blade.php'));
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_24_150500_add_declared_price_to_pharma_medicine_variants.php'));

        $this->assertStringContainsString("'declared_price' => \$data['declared_price'] ?? null", $committer);
        $this->assertStringContainsString("forceFill(['declared_price' => \$data['declared_price']])", $committer);
        $this->assertStringContainsString("'declared_price'", $variant);
        $this->assertStringContainsString("decimal('declared_price', 18, 2)", $migration);
        $this->assertStringContainsString('presentation_text,declared_price,status,is_default', $service);
        $this->assertStringContainsString('variants_count > 1', $view);
        $this->assertStringContainsString('quy cách / SKU', $view);
        $this->assertStringContainsString('variant->declared_price', $view);
    }

    public function test_variant_identity_remains_package_sensitive_but_price_independent(): void
    {
        $sku = file_get_contents(base_path('Modules/Pharma/Services/MedicineSkuGenerator.php'));

        $this->assertStringContainsString("identityPart(\$attributes['presentation_text'] ?? null)", $sku);
        $this->assertStringNotContainsString("identityPart(\$attributes['declared_price']", $sku);
    }
}
