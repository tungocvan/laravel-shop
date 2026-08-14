<?php

namespace Tests\Feature\System;

use App\Modules\ModuleLifecycleManager;
use App\Modules\ModulePermissionManager;
use App\Modules\ModuleStateRepository;
use Illuminate\Support\Facades\File;
use Modules\System\Services\SystemModuleControlService;
use Tests\TestCase;

class SystemModuleRuntimeGitCleanTest extends TestCase
{
    private string $fixtureRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureRoot = storage_path('framework/testing/runtime-git-clean-'.bin2hex(random_bytes(6)));
        File::ensureDirectoryExists($this->fixtureRoot.'/config');
        File::put($this->fixtureRoot.'/config/module.php', <<<'PHP'
<?php

return [
    'name' => 'Demo',
    'type' => 'domain',
    'enabled' => false,
    'depends' => [],
];
PHP);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->fixtureRoot);
        parent::tearDown();
    }

    public function test_successful_toggle_does_not_modify_module_manifest(): void
    {
        $manifestPath = $this->fixtureRoot.'/config/module.php';
        $before = File::get($manifestPath);

        config(['modules.registry.Demo' => [
            'name' => 'Demo',
            'type' => 'domain',
            'enabled' => false,
            'required' => false,
            'depends' => [],
            'path' => $this->fixtureRoot,
            'source' => 'manifest',
        ]]);

        $lifecycle = $this->mock(ModuleLifecycleManager::class);
        $permissions = $this->mock(ModulePermissionManager::class);
        $states = $this->mock(ModuleStateRepository::class);

        $lifecycle->shouldReceive('migrateIfNeeded')->once()->andReturn(['migrated' => false]);
        $permissions->shouldReceive('sync')->once()->andReturn(0);
        $states->shouldReceive('set')->once()->with('Demo', true);

        (new SystemModuleControlService($lifecycle, $permissions, $states))->toggle('Demo', 1);

        $this->assertSame($before, File::get($manifestPath));
        $this->assertFalse((bool) ((require $manifestPath)['enabled'] ?? true));
        $this->assertTrue(config('modules.registry.Demo.enabled'));
        $this->assertSame('runtime', config('modules.registry.Demo.source'));
    }
}
