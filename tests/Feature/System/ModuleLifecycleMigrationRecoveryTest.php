<?php

namespace Tests\Feature\System;

use App\Modules\ModuleLifecycleManager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ModuleLifecycleMigrationRecoveryTest extends TestCase
{
    private string $modulePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modulePath = storage_path('framework/testing/module-lifecycle-'.uniqid());
        File::ensureDirectoryExists($this->modulePath.'/config');
        File::ensureDirectoryExists($this->modulePath.'/database/migrations');

        File::put($this->modulePath.'/config/module.php', <<<'PHP'
<?php

return [
    'name' => 'LifecycleFixture',
    'tables' => ['lifecycle_existing', 'lifecycle_missing'],
];
PHP);

        File::put($this->modulePath.'/database/migrations/2026_01_01_000001_create_lifecycle_fixture_tables.php', <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lifecycle_existing', function (Blueprint $table): void {
            $table->id();
        });

        Schema::create('lifecycle_missing', function (Blueprint $table): void {
            $table->id();
        });
    }
};
PHP);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('lifecycle_missing');
        Schema::dropIfExists('lifecycle_existing');

        if (Schema::hasTable('migrations')) {
            DB::table('migrations')->where('migration', '2026_01_01_000001_create_lifecycle_fixture_tables')->delete();
        }

        File::deleteDirectory($this->modulePath);

        parent::tearDown();
    }

    public function test_fresh_module_database_runs_migrations_and_becomes_ready(): void
    {
        $result = app(ModuleLifecycleManager::class)->migrateIfNeeded($this->module());

        $this->assertTrue($result['ready']);
        $this->assertTrue($result['migrated']);
        $this->assertSame([], $result['missing_tables']);
        $this->assertTrue(Schema::hasTable('lifecycle_existing'));
        $this->assertTrue(Schema::hasTable('lifecycle_missing'));
        $this->assertDatabaseHas('migrations', [
            'migration' => '2026_01_01_000001_create_lifecycle_fixture_tables',
        ]);
    }

    public function test_ready_module_database_is_idempotent_and_does_not_replay_migrations(): void
    {
        $manager = app(ModuleLifecycleManager::class);
        $first = $manager->migrateIfNeeded($this->module());
        $second = $manager->migrateIfNeeded($this->module());

        $this->assertTrue($first['migrated']);
        $this->assertFalse($second['migrated']);
        $this->assertTrue($second['ready']);
        $this->assertSame('', $second['output']);
        $this->assertSame(1, DB::table('migrations')
            ->where('migration', '2026_01_01_000001_create_lifecycle_fixture_tables')
            ->count());
    }

    public function test_partial_module_database_fails_with_recovery_guidance_instead_of_replaying_create_migrations(): void
    {
        Artisan::call('migrate:install');

        Schema::create('lifecycle_existing', function (Blueprint $table): void {
            $table->id();
        });

        $manager = app(ModuleLifecycleManager::class);

        try {
            $manager->migrateIfNeeded($this->module());
            $this->fail('Expected partial module database to require recovery.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('lifecycle_missing', $exception->getMessage());
            $this->assertStringContainsString('migration', $exception->getMessage());
            $this->assertStringContainsString('ledger', $exception->getMessage());
        }

        $this->assertTrue(Schema::hasTable('lifecycle_existing'));
        $this->assertFalse(Schema::hasTable('lifecycle_missing'));
        $this->assertDatabaseMissing('migrations', [
            'migration' => '2026_01_01_000001_create_lifecycle_fixture_tables',
        ]);
    }

    public function test_complete_schema_with_missing_ledger_requires_recovery_instead_of_fast_path(): void
    {
        Artisan::call('migrate:install');

        Schema::create('lifecycle_existing', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('lifecycle_missing', function (Blueprint $table): void {
            $table->id();
        });

        try {
            app(ModuleLifecycleManager::class)->migrateIfNeeded($this->module());
            $this->fail('Expected complete schema with missing ledger to require recovery.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('migration ledger còn thiếu', $exception->getMessage());
            $this->assertStringContainsString('2026_01_01_000001_create_lifecycle_fixture_tables', $exception->getMessage());
        }

        $this->assertTrue(Schema::hasTable('lifecycle_existing'));
        $this->assertTrue(Schema::hasTable('lifecycle_missing'));
        $this->assertDatabaseMissing('migrations', [
            'migration' => '2026_01_01_000001_create_lifecycle_fixture_tables',
        ]);
    }

    private function module(): array
    {
        return [
            'name' => 'LifecycleFixture',
            'path' => $this->modulePath,
        ];
    }
}
