<?php

namespace Modules\Pharma\Tests\Unit;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class OfficialFacilityImportTemplateContractTest extends TestCase
{
    public function test_template_download_route_is_registered_with_view_permission(): void
    {
        $route = collect(Route::getRoutes())->first(fn ($route) => $route->getName() === 'admin.pharma.official-facilities.template');

        $this->assertNotNull($route);
        $this->assertContains('permission:view_pharma_official_facilities', $route->gatherMiddleware());
    }

    public function test_workspace_exposes_excel_template_download_action(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/pages/official-facilities/import.blade.php'));

        $this->assertStringContainsString("admin.pharma.official-facilities.template", $view);
        $this->assertStringContainsString('Tải file mẫu Excel', $view);
    }
}
