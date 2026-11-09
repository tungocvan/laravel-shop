<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class PharmaMedicineStagingReviewContractTest extends TestCase
{
    public function test_review_endpoint_requires_batch_ownership_capabilities_and_row_scoping(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaMedicineExcelController.php'));

        $this->assertStringContainsString("name('products.import.review.resolve')", $routes);
        $this->assertStringContainsString('public function resolveReview(', $controller);
        $this->assertStringContainsString("client.pharma.products.import", $controller);
        $this->assertStringContainsString("client.pharma.products.create-variant", $controller);
        $this->assertStringContainsString("(int) \$row->batch_id === (int) \$batch->id", $controller);
        $this->assertStringContainsString("in:link,create", $controller);
        $this->assertStringContainsString("['accepted']", $controller);
    }

    public function test_review_resolution_preserves_medicine_master_and_requires_revalidation(): void
    {
        $resolver = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Services/ClientMedicineImportReviewResolver.php'));
        $creator = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Services/ClientMedicineImportCreator.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/import-batch.blade.php'));

        $this->assertStringContainsString('DB::transaction(', $resolver);
        $this->assertStringContainsString('lockForUpdate()', $resolver);
        $this->assertStringContainsString('MedicineVariant::query()->find($variantId)', $resolver);
        $this->assertStringContainsString("client_review_linked_existing", $resolver);
        $this->assertStringContainsString("roundtrip_reference_not_found", $resolver);
        $this->assertStringContainsString("where('registration_number'", $resolver);
        $this->assertStringContainsString("where('variant_identity_key'", $resolver);
        $this->assertStringContainsString("client_review_approved_new", $resolver);
        $this->assertStringNotContainsString('Medicine::query()->create(', $resolver);
        $this->assertStringContainsString('MedicineVariant::query()->where(', $creator);
        $this->assertStringContainsString('products.import.review.resolve', $view);
        $this->assertStringContainsString('Đã kiểm tra — xác nhận thuốc mới', $view);
    }
}
