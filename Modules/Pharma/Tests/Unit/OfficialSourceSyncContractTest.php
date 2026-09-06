<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OfficialSourceSyncContractTest extends TestCase
{
    #[Test]
    public function source_sync_has_separate_permission_queue_job_and_status_route(): void
    {
        $routes = file_get_contents(base_path('Modules/Pharma/routes/web.php'));
        $config = file_get_contents(base_path('Modules/Pharma/config/module.php'));
        $job = file_get_contents(base_path('Modules/Pharma/Jobs/PersistOfficialSourceSnapshotJob.php'));
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/OfficialSourceSyncController.php'));
        $bhxhView = file_get_contents(base_path('Modules/Pharma/resources/views/pages/official-facilities/bhxh.blade.php'));

        $this->assertStringContainsString('sync_pharma_official_facilities', $routes);
        $this->assertStringContainsString('sync_pharma_official_facilities', $config);
        $this->assertStringContainsString('implements ShouldQueue', $job);
        $this->assertStringContainsString("official-facilities.source.sync-status", $routes);
        $this->assertStringContainsString('public function status(OfficialSourceSyncBatch $batch)', $controller);
        $this->assertStringContainsString('pollSyncStatus', $bhxhView);
        $this->assertStringContainsString('Đồng bộ hoàn tất', $bhxhView);
    }

    #[Test]
    public function source_mirror_is_not_allowed_to_write_partner(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/OfficialFacilityImport/OfficialSourceMirrorService.php'));

        $this->assertStringNotContainsString('Modules\\Partner', $service);
        $this->assertStringNotContainsString('Partner::', $service);
        $this->assertStringContainsString('OfficialSourceFacility', $service);
    }

    #[Test]
    public function workspace_uses_shared_search_live_filters_and_keeps_bounded_pagination(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/pages/official-facilities/source.blade.php'));
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/OfficialSourceSyncController.php'));
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_06_081000_create_official_source_facilities_table.php'));

        $this->assertStringContainsString('<x-search', $view);
        $this->assertStringContainsString('name="search"', $view);
        $this->assertStringContainsString('data-live-filter-form', $view);
        $this->assertStringContainsString('data-live-search', $view);
        $this->assertStringContainsString('data-live-filter', $view);
        $this->assertStringContainsString("filter.addEventListener('change', submitFilters)", $view);
        $this->assertStringContainsString('window.setTimeout(submitFilters, 450)', $view);
        $this->assertStringNotContainsString('>Lọc</button>', $view);
        $this->assertStringContainsString('[10, 25, 50, 100]', $view);
        $this->assertStringNotContainsString('>All<', $view);
        $this->assertStringContainsString("->orWhere('province_name', 'like', \$like)", $controller);
        $this->assertStringContainsString("->orWhere('district_name', 'like', \$like)", $controller);
        $this->assertStringContainsString('->withQueryString()', $controller);
        $this->assertStringContainsString("['source', 'external_id']", $migration);
        $this->assertStringContainsString('source_details', $migration);
    }
}
