<?php

namespace Modules\Pharma\Tests\Unit;

use Modules\Pharma\Http\Controllers\OfficialFacilityImportTemplateController;
use Tests\TestCase;

class OfficialFacilityImportTemplateDownloadTest extends TestCase
{
    public function test_template_controller_returns_xlsx_attachment(): void
    {
        $response = app(OfficialFacilityImportTemplateController::class)();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('official-facility-import-template.xlsx', (string) $response->headers->get('Content-Disposition'));
        $this->assertNotEmpty($response->getContent());
    }
}
