<?php

declare(strict_types=1);

namespace Modules\System\Tests\Feature;

use Tests\TestCase;

class ModuleSchemaDoctorContractTest extends TestCase
{
    public function test_schema_doctor_contract_is_non_destructive_and_actionable(): void
    {
        $source = file_get_contents(base_path('Modules/System/Services/Database/ModuleSchemaDoctorService.php'));
        self::assertIsString($source);
        self::assertStringContainsString("'SAFE'", $source);
        self::assertStringContainsString("'REVIEW'", $source);
        self::assertStringContainsString("'BLOCKED'", $source);
        self::assertStringContainsString("'auto_repair_available' => false", $source);
        self::assertStringContainsString("'restore_unlocked' => \$verdict === 'SAFE'", $source);
        self::assertStringContainsString('Không tự DROP cột', $source);
        self::assertStringNotContainsString("DB::statement('DROP", $source);
        self::assertStringNotContainsString('->unprepared(', $source);
    }

    public function test_detailed_manifest_boundary_is_backward_compatible(): void
    {
        $source = file_get_contents(base_path('Modules/System/Services/Database/ModuleSnapshotSchemaManifest.php'));
        self::assertIsString($source);
        self::assertStringContainsString("schema_manifest'] =", $source);
        self::assertStringNotContainsString("format_version'] =", $source);
    }
}
