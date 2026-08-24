<?php

namespace Tests\Feature\System;

use App\Modules\ModuleMigrationLedgerRepairer;
use App\Modules\ModuleMigrationRecoveryAssessment;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class ModuleMigrationLedgerRepairerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('migrations');
        Schema::create('migrations', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('migration');
            $table->integer('batch');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('migrations');

        parent::tearDown();
    }

    public function test_recoverable_assessment_repairs_missing_ledger_with_next_batch(): void
    {
        DB::table('migrations')->insert([
            'migration' => '2026_01_01_000001_existing',
            'batch' => 4,
        ]);

        $repaired = app(ModuleMigrationLedgerRepairer::class)->repair(
            new ModuleMigrationRecoveryAssessment(
                'RECOVERABLE',
                ['2026_01_01_000002_verified'],
            ),
        );

        $this->assertSame(['2026_01_01_000002_verified'], $repaired);
        $this->assertDatabaseHas('migrations', [
            'migration' => '2026_01_01_000002_verified',
            'batch' => 5,
        ]);
    }

    public function test_repair_is_idempotent_when_record_already_exists(): void
    {
        DB::table('migrations')->insert([
            'migration' => '2026_01_01_000002_verified',
            'batch' => 3,
        ]);

        $repaired = app(ModuleMigrationLedgerRepairer::class)->repair(
            new ModuleMigrationRecoveryAssessment(
                'RECOVERABLE',
                ['2026_01_01_000002_verified'],
            ),
        );

        $this->assertSame([], $repaired);
        $this->assertSame(1, DB::table('migrations')->where('migration', '2026_01_01_000002_verified')->count());
    }

    public function test_blocked_assessment_cannot_write_ledger(): void
    {
        $this->expectException(RuntimeException::class);

        try {
            app(ModuleMigrationLedgerRepairer::class)->repair(
                new ModuleMigrationRecoveryAssessment(
                    'BLOCKED',
                    ['2026_01_01_000002_verified'],
                    ['schema' => ['missing_tables' => ['fixture']]],
                ),
            );
        } finally {
            $this->assertDatabaseMissing('migrations', [
                'migration' => '2026_01_01_000002_verified',
            ]);
        }
    }

    public function test_ready_assessment_cannot_write_ledger(): void
    {
        $this->expectException(RuntimeException::class);

        app(ModuleMigrationLedgerRepairer::class)->repair(
            new ModuleMigrationRecoveryAssessment('READY'),
        );
    }
}
