<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PharmaLegalLookupMigrationBoundaryTest extends TestCase
{
    #[Test]
    public function optional_pharma_schema_is_not_migrated_from_global_directory(): void
    {
        $name = '2026_10_21_000001_create_pharma_official_source_legal_lookups_table.php';

        $this->assertFileDoesNotExist(database_path('migrations/'.$name));
        $this->assertFileExists(base_path('Modules/Pharma/database/migrations/'.$name));
    }

    #[Test]
    public function legal_lookup_migration_runs_after_its_pharma_parent_migration(): void
    {
        $directory = base_path('Modules/Pharma/database/migrations');
        $parent = '2026_09_06_081000_create_official_source_facilities_table.php';
        $child = '2026_10_21_000001_create_pharma_official_source_legal_lookups_table.php';

        $this->assertFileExists($directory.'/'.$parent);
        $this->assertLessThan(0, strcmp($parent, $child));
        $this->assertStringContainsString(
            "constrained('pharma_official_source_facilities')",
            file_get_contents($directory.'/'.$child)
        );
    }
}
