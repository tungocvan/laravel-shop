<?php

namespace Tests\Feature\ClientApps;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PharmaKqlcntAwardExcelContractTest extends TestCase
{
    #[Test]
    public function excel_routes_use_client_feature_and_action_authorization(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaKqlcntAwardExcelController.php'));
        foreach (['import/template', 'import/preview', 'import/confirm', '/kqlcnt-awards/export'] as $path) {
            $this->assertStringContainsString($path, $routes);
        }
        $this->assertSame(4, substr_count($routes, "[PharmaKqlcntAwardExcelController::class"));
        $this->assertStringContainsString("userCan(\$request->user('web'), 'client.pharma.kqlcnt-awards.'.\$action)", $controller);
        $this->assertStringContainsString("->preview(\$request->file('file'), 'web')", $controller);
        $this->assertStringContainsString("->confirm(\$request->string('token')->toString(), false, 'web')", $controller);
    }

    #[Test]
    public function import_service_isolates_preview_session_by_guard_and_user(): void
    {
        $service = file_get_contents(base_path('Modules/Muasamcong/Services/KqlcntAwardExcelImportService.php'));
        $this->assertStringContainsString("string \$guard = 'admin'", $service);
        $this->assertStringContainsString("'guard' => \$guard", $service);
        $this->assertStringContainsString("(\$batch['guard'] ?? 'admin') !== \$guard", $service);
        $this->assertStringContainsString("auth(\$guard)->id()", $service);
        $this->assertStringContainsString("lockForUpdate()", $service);
    }

    #[Test]
    public function export_uses_canonical_workbook_and_import_preview_has_confirm_gate(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaKqlcntAwardExcelController.php'));
        $preview = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/kqlcnt-award-import-preview.blade.php'));
        $index = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/kqlcnt-awards.blade.php'));
        $this->assertStringContainsString('new KqlcntAwardWorkbookExport(', $controller);
        $this->assertStringContainsString('new KqlcntAwardImportTemplateExport()', $controller);
        $this->assertStringContainsString('kqlcnt-awards.import.confirm', $preview);
        $this->assertStringContainsString('data-pwa-file-handoff-form', $index);
        $this->assertStringContainsString('navigator.share(', $index);
    }
    public function test_contract_duration_ui_prefers_canonical_period_over_legacy_text(): void
    {
        foreach ([
            'Modules/ClientPortal/resources/views/applications/pharma/kqlcnt-awards.blade.php',
            'Modules/ClientPortal/resources/views/applications/pharma/kqlcnt-award-detail.blade.php',
        ] as $path) {
            $blade = file_get_contents(base_path($path));
            $this->assertStringContainsString('$item->contract_period !== null', $blade);
            $this->assertStringContainsString('$item->contract_period_text', $blade);
        }
    }


}
