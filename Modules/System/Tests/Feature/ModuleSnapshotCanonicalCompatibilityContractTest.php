<?php

declare(strict_types=1);

namespace Modules\System\Tests\Feature;

use Modules\System\Services\Database\CanonicalModuleSnapshotService;
use Modules\System\Services\Database\ModuleSchemaCanonicalizer;
use Modules\System\Services\Database\ModuleSnapshotService;
use Tests\TestCase;

class ModuleSnapshotCanonicalCompatibilityContractTest extends TestCase
{
    public function test_snapshot_service_resolves_to_canonical_validator(): void
    {
        self::assertInstanceOf(CanonicalModuleSnapshotService::class, app(ModuleSnapshotService::class));
    }

    public function test_legacy_fallback_keeps_parent_security_validation_and_is_read_only(): void
    {
        $source = file_get_contents(base_path('Modules/System/Services/Database/CanonicalModuleSnapshotService.php'));
        self::assertIsString($source);
        self::assertStringContainsString('parent::validatePackage($path, $module, enforceSchema: false)', $source);
        self::assertStringContainsString('compatibility_basis', $source);
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
        self::assertStringContainsString('schemasEquivalent', $source);
        self::assertStringContainsString('allForeignKeysSupported', $source);
        self::assertStringContainsString('SHOW FULL COLUMNS', $source);
        self::assertStringContainsString('SHOW INDEX', $source);
        self::assertStringContainsString('REFERENCED_TABLE_NAME IS NOT NULL', $source);
        self::assertStringNotContainsString('DB::statement(', $source);
        self::assertStringNotContainsString('->unprepared(', $source);
    }

    public function test_fk_supporting_index_name_or_extra_trailing_columns_are_semantically_equivalent(): void
    {
        $canonicalizer = app(ModuleSchemaCanonicalizer::class);
        $columns = ['medicine_id' => ['type' => 'bigint unsigned', 'null' => 'YES', 'default' => null, 'extra' => '']];
        $fk = ['snapshot_fk_name' => [['column' => 'medicine_id', 'referenced_table' => 'pharma_medicines', 'referenced_column' => 'id']]];
        $currentFk = ['current_fk_name' => [['column' => 'medicine_id', 'referenced_table' => 'pharma_medicines', 'referenced_column' => 'id']]];
        $expected = ['pharma_drug_bid_awards' => [
            'columns' => $columns,
            'indexes' => ['snapshot_fk_name' => [['column' => 'medicine_id', 'sequence' => 1, 'unique' => false]]],
            'foreign_keys' => $fk,
        ]];
        $current = ['pharma_drug_bid_awards' => [
            'columns' => $columns,
            'indexes' => ['different_supporting_index' => [
                ['column' => 'medicine_id', 'sequence' => 1, 'unique' => false],
                ['column' => 'status', 'sequence' => 2, 'unique' => false],
            ]],
            'foreign_keys' => $currentFk,
        ]];

        self::assertTrue($canonicalizer->schemasEquivalent($expected, $current, ['pharma_drug_bid_awards']));
    }

    public function test_fk_without_supporting_index_is_not_equivalent(): void
    {
        $canonicalizer = app(ModuleSchemaCanonicalizer::class);
        $columns = ['medicine_id' => ['type' => 'bigint unsigned', 'null' => 'YES', 'default' => null, 'extra' => '']];
        $fk = ['fk' => [['column' => 'medicine_id', 'referenced_table' => 'pharma_medicines', 'referenced_column' => 'id']]];
        $expected = ['t' => ['columns' => $columns, 'indexes' => ['fk' => [['column' => 'medicine_id', 'sequence' => 1, 'unique' => false]]], 'foreign_keys' => $fk]];
        $current = ['t' => ['columns' => $columns, 'indexes' => [], 'foreign_keys' => $fk]];

        self::assertFalse($canonicalizer->schemasEquivalent($expected, $current, ['t']));
    }
}
