<?php

declare(strict_types=1);

namespace Modules\System\Tests\Feature;

use Modules\System\Services\Database\CanonicalModuleSnapshotService;
use Modules\System\Services\Database\ModuleSnapshotService;
use Tests\TestCase;

class ModuleSnapshotCanonicalCompatibilityContractTest extends TestCase
{
    public function test_snapshot_service_resolves_to_canonical_validator(): void
    {
        self::assertInstanceOf(
            CanonicalModuleSnapshotService::class,
            app(ModuleSnapshotService::class),
        );
    }

    public function test_legacy_fallback_keeps_parent_security_validation_and_is_read_only(): void
    {
        $source = file_get_contents(base_path('Modules/System/Services/Database/CanonicalModuleSnapshotService.php'));
        self::assertIsString($source);
        self::assertStringContainsString('parent::validatePackage($path, $module, enforceSchema: false)', $source);
        self::assertStringContainsString("'compatibility_basis' =", str_replace('[', ' =', $source));
        self::assertStringContainsString('legacySqlMatchesCurrent', $source);
        self::assertStringContainsString("'canonical_schema'", $source);
        self::assertStringContainsString("'raw_fingerprint'", $source);
        self::assertStringNotContainsString('DB::statement(', $source);
        self::assertStringNotContainsString('DROP TABLE', $source);
        self::assertStringNotContainsString('ALTER TABLE', $source);
    }

    public function test_canonicalizer_compares_columns_indexes_and_foreign_keys_without_ddl(): void
    {
        $source = file_get_contents(base_path('Modules/System/Services/Database/ModuleSchemaCanonicalizer.php'));
        self::assertIsString($source);
        self::assertStringContainsString("'columns' =>", $source);
        self::assertStringContainsString("'indexes' =>", $source);
        self::assertStringContainsString("'foreign_keys' =>", $source);
        self::assertStringContainsString('SHOW FULL COLUMNS', $source);
        self::assertStringContainsString('SHOW INDEX', $source);
        self::assertStringContainsString('REFERENCED_TABLE_NAME IS NOT NULL', $source);
        self::assertStringNotContainsString('DB::statement(', $source);
        self::assertStringNotContainsString('->unprepared(', $source);
    }
}
