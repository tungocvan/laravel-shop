<?php

namespace Tests\Feature\System;

use App\Modules\ModuleMigrationDiagnosis;
use App\Modules\ModuleMigrationRecoveryAssessor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ModuleMigrationRecoveryAssessorTest extends TestCase
{
    private string $modulePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modulePath = storage_path('framework/testing/module-recovery-assessor-'.uniqid());
        File::ensureDirectoryExists($this->modulePath.'/config');
        File::put($this->modulePath.'/config/migration_ownership.php', <<<'PHP'
<?php

return [
    '2026_01_01_000001_create_fixture' => [
        'tables' => ['recovery_assessor_fixture'],
    ],
];
PHP);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('recovery_assessor_fixture');
        File::deleteDirectory($this->modulePath);

        parent::tearDown();
    }

    public function test_fresh_diagnosis_is_fresh(): void
    {
        $assessment = $this->assess(new ModuleMigrationDiagnosis(
            ['recovery_assessor_fixture'],
            [],
            ['recovery_assessor_fixture'],
            ['2026_01_01_000001_create_fixture'],
            [],
            ['2026_01_01_000001_create_fixture'],
        ));

        $this->assertSame('FRESH', $assessment->status);
        $this->assertFalse($assessment->isRecoverable());
    }

    public function test_complete_schema_and_ledger_are_ready(): void
    {
        $this->createFixtureTable();

        $assessment = $this->assess(new ModuleMigrationDiagnosis(
            ['recovery_assessor_fixture'],
            ['recovery_assessor_fixture'],
            [],
            ['2026_01_01_000001_create_fixture'],
            ['2026_01_01_000001_create_fixture'],
            [],
        ));

        $this->assertSame('READY', $assessment->status);
        $this->assertFalse($assessment->isRecoverable());
    }

    public function test_missing_ledger_with_verified_schema_is_recoverable(): void
    {
        $this->createFixtureTable();

        $assessment = $this->assess(new ModuleMigrationDiagnosis(
            ['recovery_assessor_fixture'],
            ['recovery_assessor_fixture'],
            [],
            ['2026_01_01_000001_create_fixture'],
            [],
            ['2026_01_01_000001_create_fixture'],
        ));

        $this->assertSame('RECOVERABLE', $assessment->status);
        $this->assertTrue($assessment->isRecoverable());
        $this->assertSame(['2026_01_01_000001_create_fixture'], $assessment->recoverableMigrations);
        $this->assertSame([], $assessment->blockedMigrations);
    }

    public function test_missing_schema_blocks_recovery(): void
    {
        $assessment = $this->assess(new ModuleMigrationDiagnosis(
            ['recovery_assessor_fixture', 'another_missing_table'],
            ['recovery_assessor_fixture'],
            ['another_missing_table'],
            ['2026_01_01_000001_create_fixture'],
            [],
            ['2026_01_01_000001_create_fixture'],
        ));

        $this->assertSame('BLOCKED', $assessment->status);
        $this->assertTrue($assessment->isBlocked());
        $this->assertArrayHasKey('schema', $assessment->blockedMigrations);
    }

    public function test_missing_ownership_contract_blocks_recovery(): void
    {
        $this->createFixtureTable();

        $assessment = $this->assess(new ModuleMigrationDiagnosis(
            ['recovery_assessor_fixture'],
            ['recovery_assessor_fixture'],
            [],
            ['2026_01_01_000099_unknown'],
            [],
            ['2026_01_01_000099_unknown'],
        ));

        $this->assertSame('BLOCKED', $assessment->status);
        $this->assertTrue($assessment->isBlocked());
        $this->assertSame(
            ['reason' => 'missing_ownership_contract'],
            $assessment->blockedMigrations['2026_01_01_000099_unknown'],
        );
    }

    private function assess(ModuleMigrationDiagnosis $diagnosis)
    {
        return app(ModuleMigrationRecoveryAssessor::class)->assess([
            'name' => 'RecoveryAssessorFixture',
            'path' => $this->modulePath,
        ], $diagnosis);
    }

    private function createFixtureTable(): void
    {
        Schema::create('recovery_assessor_fixture', function (Blueprint $table): void {
            $table->id();
        });
    }
}
