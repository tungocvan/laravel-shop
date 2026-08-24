<?php

namespace Tests\Feature\System;

use App\Modules\ModuleLifecycleManager;
use App\Modules\ModuleMigrationDiagnosis;
use App\Modules\ModuleMigrationLedgerRepairer;
use App\Modules\ModuleMigrationRecoveryAssessment;
use App\Modules\ModuleMigrationRecoveryAssessor;
use Mockery;
use Tests\TestCase;

class RecoverModuleMigrationsCommandTest extends TestCase
{
    private ModuleMigrationDiagnosis $diagnosis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->diagnosis = new ModuleMigrationDiagnosis(
            expectedTables: ['fixture'],
            existingTables: ['fixture'],
            missingTables: [],
            migrationFiles: ['2026_01_01_000001_fixture'],
            recordedMigrations: [],
            missingMigrationRecords: ['2026_01_01_000001_fixture'],
        );
    }

    public function test_dry_run_never_calls_ledger_repairer(): void
    {
        $this->bindAssessment(new ModuleMigrationRecoveryAssessment(
            'RECOVERABLE',
            ['2026_01_01_000001_fixture'],
        ));

        $repairer = Mockery::mock(ModuleMigrationLedgerRepairer::class);
        $repairer->shouldNotReceive('repair');
        $this->app->instance(ModuleMigrationLedgerRepairer::class, $repairer);

        $this->artisan('module:migration-recover Request')
            ->expectsOutputToContain('RECOVERABLE')
            ->expectsOutputToContain('DRY-RUN')
            ->assertSuccessful();
    }

    public function test_apply_cancelled_by_operator_never_writes_ledger(): void
    {
        $this->bindAssessment(new ModuleMigrationRecoveryAssessment(
            'RECOVERABLE',
            ['2026_01_01_000001_fixture'],
        ));

        $repairer = Mockery::mock(ModuleMigrationLedgerRepairer::class);
        $repairer->shouldNotReceive('repair');
        $this->app->instance(ModuleMigrationLedgerRepairer::class, $repairer);

        $this->artisan('module:migration-recover Request --apply')
            ->expectsConfirmation('Xác nhận chỉ ghi các migration ledger record VERIFIED ở trên?', 'no')
            ->expectsOutputToContain('Đã hủy')
            ->assertSuccessful();
    }

    public function test_confirmed_recoverable_apply_calls_repairer_once(): void
    {
        $assessment = new ModuleMigrationRecoveryAssessment(
            'RECOVERABLE',
            ['2026_01_01_000001_fixture'],
        );
        $this->bindAssessment($assessment);

        $repairer = Mockery::mock(ModuleMigrationLedgerRepairer::class);
        $repairer->shouldReceive('repair')
            ->once()
            ->with($assessment)
            ->andReturn(['2026_01_01_000001_fixture']);
        $this->app->instance(ModuleMigrationLedgerRepairer::class, $repairer);

        $this->artisan('module:migration-recover Request --apply')
            ->expectsConfirmation('Xác nhận chỉ ghi các migration ledger record VERIFIED ở trên?', 'yes')
            ->expectsOutputToContain('Đã phục hồi migration ledger an toàn')
            ->assertSuccessful();
    }

    public function test_blocked_apply_fails_without_confirmation_or_repair(): void
    {
        $this->bindAssessment(new ModuleMigrationRecoveryAssessment(
            'BLOCKED',
            [],
            ['schema' => ['missing_tables' => ['fixture']]],
        ));

        $repairer = Mockery::mock(ModuleMigrationLedgerRepairer::class);
        $repairer->shouldNotReceive('repair');
        $this->app->instance(ModuleMigrationLedgerRepairer::class, $repairer);

        $this->artisan('module:migration-recover Request --apply')
            ->expectsOutputToContain('BLOCKED')
            ->assertFailed();
    }

    public function test_ready_apply_is_a_no_op(): void
    {
        $this->bindAssessment(new ModuleMigrationRecoveryAssessment('READY'));

        $repairer = Mockery::mock(ModuleMigrationLedgerRepairer::class);
        $repairer->shouldNotReceive('repair');
        $this->app->instance(ModuleMigrationLedgerRepairer::class, $repairer);

        $this->artisan('module:migration-recover Request --apply')
            ->expectsOutputToContain('READY')
            ->assertSuccessful();
    }

    private function bindAssessment(ModuleMigrationRecoveryAssessment $assessment): void
    {
        $manager = Mockery::mock(ModuleLifecycleManager::class);
        $manager->shouldReceive('migrationDiagnosis')
            ->once()
            ->andReturn($this->diagnosis);
        $this->app->instance(ModuleLifecycleManager::class, $manager);

        $assessor = Mockery::mock(ModuleMigrationRecoveryAssessor::class);
        $assessor->shouldReceive('assess')
            ->once()
            ->andReturn($assessment);
        $this->app->instance(ModuleMigrationRecoveryAssessor::class, $assessor);
    }
}
