<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MedicineMigrationOrderContractTest extends TestCase
{
    #[Test]
    public function medicine_import_staging_runs_after_canonical_catalog(): void
    {
        $migrationDirectory = base_path('Modules/Pharma/database/migrations');
        $canonical = glob($migrationDirectory.'/*_create_canonical_medicine_catalog_tables.php');
        $staging = glob($migrationDirectory.'/*_create_medicine_import_staging_tables.php');

        $this->assertCount(1, $canonical, 'Expected exactly one canonical medicine catalog migration.');
        $this->assertCount(1, $staging, 'Expected exactly one medicine import staging migration.');
        $this->assertGreaterThan(
            basename($canonical[0]),
            basename($staging[0]),
            'Medicine import staging must run after the canonical catalog because it references pharma_medicine_variants.'
        );

        $canonicalMigration = file_get_contents($canonical[0]);
        $stagingMigration = file_get_contents($staging[0]);

        $this->assertStringContainsString("Schema::create('pharma_medicine_variants'", $canonicalMigration);
        $this->assertStringContainsString("constrained('pharma_medicine_variants')->nullOnDelete()", $stagingMigration);
    }
}
