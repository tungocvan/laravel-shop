<?php

namespace Tests\Feature\System;

use App\Modules\ModuleStateRepository;
use Illuminate\Support\Facades\File;
use Mockery;
use Modules\ModuleServiceProvider;
use ReflectionMethod;
use Tests\TestCase;

class ModuleBootstrapRuntimeStateTest extends TestCase
{
    private string $fixtureRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtureRoot = storage_path('framework/testing/module-bootstrap-runtime');
        File::deleteDirectory($this->fixtureRoot);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->fixtureRoot);
        parent::tearDown();
    }

    public function test_bootstrap_uses_runtime_override_for_manifest_enabled_state(): void
    {
        $modulePath = $this->writeModule('Demo', [
            'name' => 'Demo',
            'type' => 'domain',
            'enabled' => true,
            'depends' => [],
        ]);

        $states = Mockery::mock(ModuleStateRepository::class);
        $states->shouldReceive('get')->with('Demo')->once()->andReturn(false);
        $this->app->instance(ModuleStateRepository::class, $states);

        $module = $this->resolveManifest($modulePath);

        $this->assertFalse($module['enabled']);
        $this->assertSame('runtime', $module['source']);
    }

    public function test_bootstrap_prefers_default_enabled_over_legacy_enabled(): void
    {
        $modulePath = $this->writeModule('Demo', [
            'name' => 'Demo',
            'type' => 'domain',
            'default_enabled' => false,
            'enabled' => true,
            'depends' => [],
        ]);

        $states = Mockery::mock(ModuleStateRepository::class);
        $states->shouldReceive('get')->with('Demo')->once()->andReturnNull();
        $this->app->instance(ModuleStateRepository::class, $states);

        $module = $this->resolveManifest($modulePath);

        $this->assertFalse($module['enabled']);
        $this->assertSame('manifest', $module['source']);
    }

    public function test_bootstrap_keeps_shell_module_enabled_even_when_runtime_override_is_false(): void
    {
        $modulePath = $this->writeModule('ShellDemo', [
            'name' => 'ShellDemo',
            'type' => 'shell',
            'enabled' => false,
            'depends' => [],
        ]);

        $states = Mockery::mock(ModuleStateRepository::class);
        $states->shouldReceive('get')->with('ShellDemo')->once()->andReturn(false);
        $this->app->instance(ModuleStateRepository::class, $states);

        $module = $this->resolveManifest($modulePath);

        $this->assertTrue($module['enabled']);
        $this->assertTrue($module['required']);
    }

    private function resolveManifest(string $modulePath): array
    {
        $provider = new ModuleServiceProvider($this->app);
        $method = new ReflectionMethod($provider, 'resolveModuleManifest');
        $method->setAccessible(true);

        return $method->invoke($provider, $modulePath);
    }

    private function writeModule(string $name, array $manifest): string
    {
        $modulePath = $this->fixtureRoot.'/'.$name;
        File::ensureDirectoryExists($modulePath.'/config');
        File::put(
            $modulePath.'/config/module.php',
            "<?php\n\nreturn ".var_export($manifest, true).";\n"
        );

        return $modulePath;
    }
}
