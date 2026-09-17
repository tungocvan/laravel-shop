<?php

declare(strict_types=1);

namespace Modules\System\Tests\Unit;

use Modules\System\Services\Database\ModuleSchemaDoctorService;
use Modules\System\Services\Database\ModuleSnapshotService;
use PHPUnit\Framework\TestCase;

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
}
