<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OfficialSourceSyncContractTest extends TestCase
{
    #[Test]
    public function source_sync_has_separate_permission_and_queue_job(): void
    {
        $routes = file_get_contents(base_path('Modules/Pharma/routes/web.php'));
        $config = file_get_contents(base_path('Modules/Pharma/config/module.php'));
        $job = file_get_contents(base_path('Modules/Pharma/Jobs/PersistOfficialSourceSnapshotJob.php'));

        $this->assertStringContainsString('sync_pharma_official_facilities', $routes);
        $this->assertStringContainsString('sync_pharma_official_facilities', $config);
        $this->assertStringContainsString('implements ShouldQueue', $job);
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
    public function workspace_keeps_bounded_pagination_and_source_identity(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/pages/official-facilities/source.blade.php'));
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_06_081000_create_official_source_facilities_table.php'));

        $this->assertStringContainsString('[10,25,50,100]', $view);
        $this->assertStringNotContainsString('>All<', $view);
        $this->assertStringContainsString("['source', 'external_id']", $migration);
        $this->assertStringContainsString('source_details', $migration);
    }
}
