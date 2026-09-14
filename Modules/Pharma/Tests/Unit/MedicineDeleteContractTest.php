<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MedicineDeleteContractTest extends TestCase
{
    #[Test]
    public function imported_catalog_children_do_not_block_delete_but_external_references_do(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/MedicineService.php'));
        $canonicalMigration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_14_100000_create_canonical_medicine_catalog_tables.php'));
        $sourceMigration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_05_011000_create_medicine_sources_table.php'));
        $workspace = file_get_contents(base_path('Modules/Pharma/Livewire/Medicine/Index.php'));

        $this->assertIsString($service);
        $this->assertStringContainsString('$medicine->profiles()->exists() || $medicine->drugBidAwards()->exists()', $service);
        $this->assertStringNotContainsString('$medicine->variants()->exists()', $service);
        $this->assertStringNotContainsString('$medicine->sources()->exists()', $service);
        $this->assertStringContainsString("constrained('pharma_medicines')->cascadeOnDelete()", $canonicalMigration);
        $this->assertStringContainsString("constrained('pharma_medicine_variants')->cascadeOnDelete()", $canonicalMigration);
        $this->assertStringContainsString("constrained('pharma_medicines')->cascadeOnDelete()", $sourceMigration);
        $this->assertStringContainsString('$protected++', $workspace);
        $this->assertStringContainsString('$deleted++', $workspace);
    }
}
