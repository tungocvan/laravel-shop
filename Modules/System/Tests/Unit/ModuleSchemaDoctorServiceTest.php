<?php

declare(strict_types=1);

namespace Modules\System\Tests\Unit;

use Modules\System\Services\Database\ModuleSchemaDoctorService;
use Modules\System\Services\Database\ModuleSnapshotService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ModuleSchemaDoctorServiceTest extends TestCase
{
    public function test_legacy_incompatible_snapshot_requires_review_instead_of_force_repair(): void
    {
        $snapshots = $this->createMock(ModuleSnapshotService::class);
        $snapshots->method('resolveLocalReference')->with('ref', 'Pharma')->willReturn(['absolute_path' => '/tmp/pharma.zip']);
        $snapshots->method('validatePackage')->willReturn(['compatibility' => 'BLOCKED', 'manifest' => ['module' => 'Pharma', 'tables' => ['pharma_medicines']]]);

        $result = (new ModuleSchemaDoctorService($snapshots))->diagnose('ref', 'Pharma');

        self::assertSame('REVIEW', $result['verdict']);
        self::assertFalse($result['auto_repair_available']);
        self::assertFalse($result['restore_unlocked']);
        self::assertSame('legacy_snapshot', $result['issues'][0]['type']);
    }

    public function test_compatible_snapshot_is_safe_without_repair(): void
    {
        $snapshots = $this->createMock(ModuleSnapshotService::class);
        $snapshots->method('resolveLocalReference')->willReturn(['absolute_path' => '/tmp/pharma.zip']);
        $snapshots->method('validatePackage')->willReturn(['compatibility' => 'COMPATIBLE', 'manifest' => []]);

        $result = (new ModuleSchemaDoctorService($snapshots))->diagnose('ref', 'Pharma');

        self::assertSame('SAFE', $result['verdict']);
        self::assertTrue($result['restore_unlocked']);
    }

    public function test_same_named_index_is_equal_after_canonicalization(): void
    {
        $doctor = new ModuleSchemaDoctorService($this->createMock(ModuleSnapshotService::class));
        $method = new ReflectionMethod($doctor, 'namedStructureDifferences');
        $method->setAccessible(true);

        $snapshot = [
            'pharma_drug_bid_awards_medicine_id_foreign' => [
                ['column' => 'medicine_id', 'sequence' => 1, 'unique' => false],
            ],
        ];
        $mysql = [
            'pharma_drug_bid_awards_medicine_id_foreign' => [
                ['unique' => false, 'sequence' => 1, 'column' => 'medicine_id'],
            ],
        ];

        self::assertSame([], $method->invoke($doctor, $snapshot, $mysql, 'indexes'));
    }

    public function test_foreign_key_is_compared_separately_from_supporting_index(): void
    {
        $doctor = new ModuleSchemaDoctorService($this->createMock(ModuleSnapshotService::class));
        $method = new ReflectionMethod($doctor, 'namedStructureDifferences');
        $method->setAccessible(true);

        $foreignKey = [
            'pharma_drug_bid_awards_medicine_id_foreign' => [[
                'column' => 'medicine_id',
                'referenced_table' => 'pharma_medicines',
                'referenced_column' => 'id',
            ]],
        ];

        self::assertSame([], $method->invoke($doctor, $foreignKey, $foreignKey, 'foreign_keys'));
    }
}
