<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

/**
 * Import danh mục trúng thầu is a canonical Muasamcong Excel update flow,
 * not the legacy Pharma manual-source creation workflow.
 */
class PharmaKqlcntImportedCatalogBoundaryContractTest extends TestCase
{
    public function test_excel_import_uses_canonical_service_and_requires_web_permission(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaKqlcntAwardExcelController.php'));
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $this->assertNotFalse($controller);
        $this->assertNotFalse($routes);
        $this->assertStringContainsString('KqlcntAwardExcelImportService', $controller);
        $this->assertStringContainsString("authorizeAction(\$request, \$registry, 'import')", $controller);
        $this->assertStringContainsString("->preview(\$request->file('file'), 'web')", $controller);
        $this->assertStringContainsString("->confirm(\$request->string('token')->toString(), false, 'web')", $controller);
        $this->assertStringContainsString("Route::post('/kqlcnt-awards/import/preview'", $routes);
        $this->assertStringContainsString("Route::post('/kqlcnt-awards/import/confirm'", $routes);
    }

    public function test_import_does_not_create_new_canonical_records_implicitly(): void
    {
        $source = file_get_contents(base_path('Modules/Muasamcong/Services/KqlcntAwardExcelImportService.php'));
        $this->assertNotFalse($source);
        $this->assertStringContainsString('Thuốc chưa có bản ghi canonical; thêm mới cần quy trình riêng', $source);
        $this->assertStringContainsString('Không tìm thấy bản ghi tương ứng; không tự tạo mới từ Excel cũ', $source);
        $this->assertStringContainsString('Dòng mới chưa có ID: cần quy trình thêm mới có kiểm soát', $source);
        $this->assertStringContainsString("if (\$existing && \$existing->synced_from_catalog_at === null)", $source);
        $this->assertStringContainsString('DB::transaction(', $source);
    }

    public function test_imported_canonical_rows_still_require_verified_pharma_provenance(): void
    {
        $bridge = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Services/KqlcntLegacyWorkflowBridge.php'));
        $this->assertNotFalse($bridge);
        $this->assertStringContainsString('verifiedProvenanceProductIds($awardItemId)', $bridge);
        $this->assertStringContainsString("where('source_reference', 'muasamcong_kqlcnt_award_items:'.\$line->id)", $bridge);
        $this->assertStringContainsString('verifiedMappingCandidateIds(int $awardItemId)', $bridge);
        $this->assertStringContainsString("->whereDoesntHave('sources')", $bridge);
        $this->assertStringContainsString('if ($ids !== $legacyIds)', $bridge);
    }

    public function test_existing_bid_awards_routes_are_not_removed(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $this->assertStringContainsString("'/bid-awards'", $routes);
        $this->assertStringContainsString("'/bid-awards/{scope}/allocation'", $routes);
        $this->assertStringContainsString("'/bid-awards/{scope}/commercial-policy'", $routes);
    }
}
