<?php

namespace Tests\Feature\Modules;

use App\Modules\ModuleLifecycleManager;
use App\Modules\ModulePermissionManager;
use App\Modules\ModuleStateRepository;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mockery;
use Modules\System\Services\SystemModuleControlService;
use Tests\TestCase;

class ModuleRuntimeStateToggleTest extends TestCase
{
    public function test_fresh_module_migration_uses_supported_artisan_options(): void
    {
        $root = storage_path('framework/testing/module-lifecycle-'.Str::uuid());
        $migrationPath = $root.'/database/migrations';
        File::ensureDirectoryExists($migrationPath);
        File::ensureDirectoryExists($root.'/config');

        File::put($root.'/config/module.php', "<?php\n\nreturn ['tables' => ['fixture_requests']];\n");
        File::put($migrationPath.'/2026_08_27_000000_create_fixture_requests_table.php', "<?php\n");

        $module = [
            'name' => 'Fixture',
            'path' => $root,
        ];

        Schema::shouldReceive('hasTable')
            ->with('fixture_requests')
            ->andReturn(false, false, true);
        Schema::shouldReceive('hasTable')
            ->with('migrations')
            ->once()
            ->andReturn(false);

        $relativePath = str_replace('\\', '/', ltrim(str_replace(base_path(), '', $migrationPath), '/\\'));

        Artisan::shouldReceive('call')
            ->once()
            ->with('migrate', [
                '--path' => $relativePath,
                '--force' => true,
            ])
            ->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn('Migrated fixture module.');

        try {
            $result = (new ModuleLifecycleManager())->migrateIfNeeded($module);

            $this->assertTrue($result['migrated']);
            $this->assertSame([], $result['missing_tables']);
        } finally {
            File::deleteDirectory($root);
        }
    }

    public function test_toggle_persists_runtime_state_without_mutating_manifest(): void
    {
        $root = storage_path('framework/testing/module-toggle-'.Str::uuid());
        File::ensureDirectoryExists($root.'/config');
        $manifestPath = $root.'/config/module.php';
        File::put($manifestPath, "<?php\n\nreturn ['enabled' => false, 'tables' => []];\n");
        $manifestBefore = File::get($manifestPath);

        $module = [
            'name' => 'Fixture',
            'type' => 'domain',
            'enabled' => false,
            'required' => false,
            'depends' => [],
            'path' => $root,
            'source' => 'manifest',
        ];

        config(['modules.registry' => ['Fixture' => $module]]);

        $lifecycle = Mockery::mock(ModuleLifecycleManager::class);
        $lifecycle->shouldReceive('migrateIfNeeded')
            ->once()
            ->with($module)
            ->andReturn(['migrated' => false]);

        $permissions = Mockery::mock(ModulePermissionManager::class);
        $permissions->shouldReceive('sync')
            ->once()
            ->with($module)
            ->andReturn(0);

        $states = Mockery::mock(ModuleStateRepository::class);
        $states->shouldReceive('set')
            ->once()
            ->with('Fixture', true);

        $service = new SystemModuleControlService($lifecycle, $permissions, $states);

        try {
            $service->toggle('Fixture');

            $this->assertTrue(config('modules.registry.Fixture.enabled'));
            $this->assertSame($manifestBefore, File::get($manifestPath));
        } finally {
            File::deleteDirectory($root);
        }
    }
}
