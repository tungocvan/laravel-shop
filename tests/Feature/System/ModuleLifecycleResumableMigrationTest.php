<?php

namespace Tests\Feature\System;

use App\Modules\ModuleLifecycleManager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ModuleLifecycleResumableMigrationTest extends TestCase
{
    private string $modulePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modulePath = storage_path('framework/testing/module-resumable-'.uniqid());
        File::ensureDirectoryExists($this->modulePath.'/config');
        File::ensureDirectoryExists($this->modulePath.'/database/migrations');

        File::put($this->modulePath.'/config/module.php', <<<'PHP'
<?php

return [
    'name' => 'ResumableFixture',
    'tables' => ['resumable_first', 'resumable_second'],
];
PHP);

        File::put($this->modulePath.'/database/migrations/2026_01_01_000001_create_resumable_first_table.php', <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resumable_first', function (Blueprint $table): void {
            $table->id();
        });
    }
};
PHP);

        File::put($this->modulePath.'/database/migrations/2026_01_01_000002_create_resumable_second_table.php', <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resumable_second', function (Blueprint $table): void {
            $table->id();
        });
    }
};
PHP);

        Artisan::call('migrate:install');
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('resumable_second');
        Schema::dropIfExists('resumable_first');

        if (Schema::hasTable('migrations')) {
            DB::table('migrations')
                ->whereIn('migration', [
                    '2026_01_01_000001_create_resumable_first_table',
                    '2026_01_01_000002_create_resumable_second_table',
                ])
                ->delete();
        }

        File::deleteDirectory($this->modulePath);

        parent::tearDown();
    }

    public function test_consistent_partial_schema_is_resumable_and_finishes_pending_migrations(): void
    {
        Schema::create('resumable_first', function (Blueprint $table): void {
            $table->id();
        });
        DB::table('migrations')->insert([
            'migration' => '2026_01_01_000001_create_resumable_first_table',
            'batch' => 1,
        ]);

        $manager = app(ModuleLifecycleManager::class);
        $before = $manager->migrationDiagnosis($this->module());

        $this->assertTrue($before->isResumable());
        $this->assertFalse($before->needsRecovery());

        $result = $manager->migrateIfNeeded($this->module());
        $after = $manager->migrationDiagnosis($this->module());

        $this->assertTrue($result['migrated']);
        $this->assertTrue(Schema::hasTable('resumable_first'));
        $this->assertTrue(Schema::hasTable('resumable_second'));
        $this->assertTrue($after->isReady());
        $this->assertSame(2, DB::table('migrations')
            ->whereIn('migration', $after->migrationFiles)
            ->count());
    }

    public function test_unrecorded_table_from_pending_migration_still_requires_recovery(): void
    {
        Schema::create('resumable_first', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('resumable_second', function (Blueprint $table): void {
            $table->id();
        });
        DB::table('migrations')->insert([
            'migration' => '2026_01_01_000001_create_resumable_first_table',
            'batch' => 1,
        ]);

        $diagnosis = app(ModuleLifecycleManager::class)->migrationDiagnosis($this->module());

        $this->assertFalse($diagnosis->isResumable());
        $this->assertTrue($diagnosis->needsRecovery());
    }

    private function module(): array
    {
        return [
            'name' => 'ResumableFixture',
            'path' => $this->modulePath,
        ];
    }
}
