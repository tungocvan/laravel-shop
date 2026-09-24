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
        $this->assertStringContainsString('Quy cách / SKU', $view);
        $this->assertStringContainsString('Giá kê khai', $view);
        $this->assertStringContainsString('SKU:', $view);
        $this->assertStringContainsString('catalogVariant?->declared_price', $view);
        $this->assertStringNotContainsString('variants_count > 1', $view);
        $this->assertStringNotContainsString('variant->presentation_text', $view);
    }

    public function test_variant_identity_remains_package_sensitive_but_price_independent(): void
    {
        $sku = file_get_contents(base_path('Modules/Pharma/Services/MedicineSkuGenerator.php'));

        $this->assertStringContainsString("identityPart(\$attributes['presentation_text'] ?? null)", $sku);
        $this->assertStringNotContainsString("identityPart(\$attributes['declared_price']", $sku);
    }
    public function test_different_packaging_creates_different_medicine_identity_without_using_price(): void
    {
        $resolver = new \Modules\Pharma\Services\MedicineIdentityResolver;

        $base = [
            'name' => 'AMBROXOL-H',
            'registration_number' => 'VD-30742-18',
            'active_ingredients' => 'Ambroxol',
            'concentration' => '30mg/5ml',
            'dosage_form' => 'Siro thuốc',
            'manufacturing_company' => 'Dược TW2',
        ];

        $pack50 = $resolver->canonicalMedicineIdentity($base + [
            'packaging_specification' => 'Hộp 1 chai x 50ml',
            'declared_price' => 30000,
        ]);
        $pack90 = $resolver->canonicalMedicineIdentity($base + [
            'packaging_specification' => 'Hộp 1 chai x 90ml',
            'declared_price' => 65000,
        ]);
        $samePackNewPrice = $resolver->canonicalMedicineIdentity($base + [
            'packaging_specification' => 'Hộp 1 chai x 50ml',
            'declared_price' => 32000,
        ]);

        $this->assertNotNull($pack50);
        $this->assertNotSame($pack50, $pack90);
        $this->assertSame($pack50, $samePackNewPrice);
    }

    public function test_import_stager_and_committer_protect_package_level_med_codes(): void
    {
        $stager = file_get_contents(base_path('Modules/Pharma/Services/MedicineCatalogImportStager.php'));
        $committer = file_get_contents(base_path('Modules/Pharma/Services/MedicineCatalogImportCommitter.php'));

        $this->assertStringContainsString('samePackaging', $stager);
        $this->assertStringContainsString('legacy_medicine_same_packaging', $stager);
        $this->assertStringContainsString('samePackaging', $committer);
        $this->assertStringContainsString('Packaging is', $committer);
        $this->assertStringContainsString('own MED code', $committer);
    }

}
