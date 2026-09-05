<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OfficialFacilitySelectionContractTest extends TestCase
{
    #[Test]
    public function routes_keep_view_import_and_conflict_permissions_separate(): void
    {
        $routes = file_get_contents(base_path('Modules/Pharma/routes/web.php'));

        $this->assertStringContainsString('view_pharma_official_facilities', $routes);
        $this->assertStringContainsString('import_pharma_official_facilities', $routes);
        $this->assertStringContainsString('resolve_pharma_official_facility_conflicts', $routes);
    }

    #[Test]
    public function workspace_has_bounded_pagination_and_direct_page_scoped_import(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/pages/official-facilities/import.blade.php'));

        $this->assertStringContainsString('[10,25,50,100]', $view);
        $this->assertStringNotContainsString('>All<', $view);
        $this->assertStringContainsString('name="visible[]"', $view);
        $this->assertStringContainsString('name="selected[]"', $view);
        $this->assertStringContainsString('data-page-select', $view);
        $this->assertStringContainsString('data-selected-count', $view);
        $this->assertStringContainsString('admin.pharma.official-facilities.run', $view);
        $this->assertStringNotContainsString('Lưu lựa chọn', $view);
    }

    #[Test]
    public function direct_import_revalidates_selection_on_the_server(): void
    {
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/OfficialFacilityImportController.php'));

        $this->assertStringContainsString("'visible' => ['required', 'array', 'min:1']", $controller);
        $this->assertStringContainsString("'selected' => ['required', 'array', 'min:1']", $controller);
        $this->assertStringContainsString('intersect($visible)', $controller);
        $this->assertStringContainsString("whereNull('imported_at')->update(['is_selected' => false])", $controller);
    }

    #[Test]
    public function importer_requires_explicit_selection(): void
    {
        $importer = file_get_contents(base_path('Modules/Pharma/Services/OfficialFacilityImport/OfficialFacilityPartnerImporter.php'));

        $this->assertStringContainsString('if (! $row->is_selected)', $importer);
        $this->assertStringContainsString("['LIKELY_MATCH', 'CONFLICT']", $importer);
    }
}
