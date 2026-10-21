<?php

namespace Tests\Feature\Pharma;

use Tests\TestCase;

class OfficialSourceLegalImportContractTest extends TestCase
{
    public function test_legal_import_has_distinct_preview_and_confirmation_paths(): void
    {
        $routes = file_get_contents(base_path('Modules/Pharma/routes/web.php'));
        $this->assertStringContainsString("source/legal-import/preview", $routes);
        $this->assertStringContainsString("source/legal-import/confirm", $routes);
        $this->assertStringContainsString("can:import_pharma_official_facilities", $routes);

        $blade = file_get_contents(base_path('Modules/Pharma/resources/views/pages/official-facilities/source.blade.php'));
        $this->assertStringContainsString('data-legal-import-form', $blade);
        $this->assertStringContainsString('data-legal-import-confirm', $blade);
        $this->assertStringContainsString('Import thông tin pháp lý', $blade);
    }

    public function test_import_requires_preview_token_and_preserves_bhxh_and_partner(): void
    {
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/OfficialSourceLegalImportController.php'));
        $this->assertStringContainsString('hash_equals(', $controller);
        $this->assertStringContainsString('lockForUpdate()', $controller);
        $this->assertStringContainsString("'source', 'bhxh'", $controller);
        $this->assertStringContainsString("'external_id', $code", $controller);
        $this->assertStringContainsString("['reviewed', 'queued', 'running']", $controller);
        $this->assertStringContainsString("'source' => 'excel_import'", $controller);
        $this->assertStringContainsString("session()->forget(self::SESSION_KEY)", $controller);
        $this->assertStringNotContainsString("OfficialSourceFacility::query()->update(", $controller);
        $this->assertStringNotContainsString("Modules\\Partner\\Models", $controller);
    }
}
