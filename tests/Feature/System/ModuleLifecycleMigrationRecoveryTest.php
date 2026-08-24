<?php

namespace Tests\Feature\System;

use App\Modules\ModuleLifecycleManager;
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
        File::deleteDirectory($this->modulePath);

        parent::tearDown();
    }

    public function test_partial_module_database_fails_with_recovery_guidance_instead_of_replaying_create_migrations(): void
    {
        Schema::create('lifecycle_existing', function ($table): void {
            $table->id();
        });

        $manager = app(ModuleLifecycleManager::class);
        $module = [
            'name' => 'LifecycleFixture',
            'path' => $this->modulePath,
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('lifecycle_missing');
        $this->expectExceptionMessage('migration');

        $manager->migrateIfNeeded($module);

        $this->assertTrue(Schema::hasTable('lifecycle_existing'));
        $this->assertFalse(Schema::hasTable('lifecycle_missing'));
    }
}
