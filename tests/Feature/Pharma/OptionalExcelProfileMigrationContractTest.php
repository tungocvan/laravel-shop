<?php

namespace Tests\Feature\Pharma;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OptionalExcelProfileMigrationContractTest extends TestCase
{
    #[Test]
    public function core_migration_skips_when_optional_pharma_table_is_absent(): void
    {
        $source = file_get_contents(database_path('migrations/2026_11_04_160000_expand_pharma_medicine_excel_profile_owner_guard.php'));

        $this->assertStringContainsString("Schema::hasTable('pharma_medicine_excel_profiles')", $source);
        $this->assertStringContainsString("Schema::hasColumn('pharma_medicine_excel_profiles', 'owner_guard')", $source);
    }

    #[Test]
    public function pharma_migration_enforces_full_guard_width_after_module_install(): void
    {
        $source = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_11_14_000001_ensure_medicine_excel_profile_owner_guard_width.php'));

        $this->assertStringContainsString("Schema::table('pharma_medicine_excel_profiles'", $source);
        $this->assertStringContainsString("->string('owner_guard', 64)->change()", $source);
    }
}
