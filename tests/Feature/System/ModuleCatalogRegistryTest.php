<?php

namespace Tests\Feature\System;

use App\Modules\ModuleCatalog;
use App\Modules\ModuleGraphValidator;
use App\Modules\ModulePermissionManager;
use App\Modules\ModuleRegistry;
use App\Modules\ModuleStateRepository;
use App\Modules\ModuleStateResolver;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class ModuleCatalogRegistryTest extends TestCase
{
    private string $fixtureRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureRoot = storage_path('framework/testing/module-catalog-'.Str::uuid());
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->fixtureRoot);

        parent::tearDown();
    }

    public function test_catalog_normalizes_manifests_runtime_state_and_boot_order(): void
    {
        $this->writeModule('FeatureDemo', 'config', [
            'type' => 'domain',
            'default_enabled' => true,
            'depends' => ['support_demo', 'SupportDemo'],
        ]);
        $this->writeModule('ShellDemo', 'config', [
            'type' => 'shell',
            'enabled' => false,
        ]);
        $this->writeModule('SupportDemo', 'Config', [
            'type' => 'support',
            'permissions_required' => false,
        ]);

        $states = Mockery::mock(ModuleStateRepository::class);
        $states->shouldReceive('get')
            ->once()
            ->with('FeatureDemo')
            ->andReturn(false);
        $states->shouldReceive('get')
            ->once()
            ->with('ShellDemo')
            ->andReturn(false);
        $states->shouldReceive('get')
            ->once()
            ->with('SupportDemo')
            ->andReturnNull();

        $modules = (new ModuleCatalog(new ModuleStateResolver($states)))->discover($this->fixtureRoot);

        $this->assertSame(['ShellDemo', 'SupportDemo', 'FeatureDemo'], $modules->pluck('name')->all());
        $this->assertTrue($modules->firstWhere('name', 'ShellDemo')['enabled']);
        $this->assertTrue($modules->firstWhere('name', 'ShellDemo')['required']);
        $this->assertFalse($modules->firstWhere('name', 'FeatureDemo')['enabled']);
        $this->assertSame('runtime', $modules->firstWhere('name', 'FeatureDemo')['source']);
        $this->assertSame(['SupportDemo'], $modules->firstWhere('name', 'FeatureDemo')['depends']);
        $this->assertFalse($modules->firstWhere('name', 'SupportDemo')['permissions_required']);
        $this->assertStringEndsWith('/Config/module.php', $modules->firstWhere('name', 'SupportDemo')['manifest_path']);
    }

    public function test_registry_publishes_only_the_compatible_projection(): void
    {
        $modules = collect([
            $this->descriptor('Admin', 'shell', true, true),
            $this->descriptor('Demo', 'domain', false, false, ['Admin']) + [
                'manifest' => ['permissions' => ['demo.view']],
            ],
        ]);
        $catalog = Mockery::mock(ModuleCatalog::class);
        $catalog->shouldReceive('discover')->once()->andReturn($modules);
        $registry = new ModuleRegistry($catalog, new ModuleGraphValidator);

        $booted = $registry->boot();

        $this->assertSame($modules, $booted);
        $this->assertSame([
            'name',
            'type',
            'enabled',
            'required',
            'depends',
            'path',
            'source',
        ], array_keys(config('modules.registry.Demo')));
        $this->assertArrayNotHasKey('manifest', config('modules.registry.Demo'));
        $this->assertSame(['Admin', 'Demo'], $registry->current()->pluck('name')->all());
    }

    public function test_permission_audit_consumes_the_canonical_catalog_descriptor(): void
    {
        $module = $this->descriptor('Demo', 'domain', false, false) + [
            'manifest_exists' => true,
            'manifest' => ['permissions' => ['demo.view']],
            'default_enabled' => true,
            'permissions_required' => true,
        ];
        $catalog = Mockery::mock(ModuleCatalog::class);
        $catalog->shouldReceive('discover')->once()->andReturn(collect([$module]));
        config(['modules.registry' => ['Demo' => $module]]);

        $audit = (new ModulePermissionManager($catalog))->discoverModules();

        $this->assertCount(1, $audit);
        $this->assertTrue($audit[0]['registered']);
        $this->assertTrue($audit[0]['manifest_enabled']);
        $this->assertSame(['demo.view'], $audit[0]['permissions']);
        $this->assertSame('ok', $audit[0]['status']);
    }

    private function writeModule(string $name, string $configDirectory, array $manifest): void
    {
        $directory = $this->fixtureRoot.'/'.$name.'/'.$configDirectory;
        File::ensureDirectoryExists($directory);
        File::put($directory.'/module.php', "<?php\n\nreturn ".var_export($manifest, true).";\n");
    }

    private function descriptor(
        string $name,
        string $type,
        bool $enabled,
        bool $required,
        array $depends = [],
    ): array {
        return [
            'name' => $name,
            'type' => $type,
            'enabled' => $enabled,
            'required' => $required,
            'depends' => $depends,
            'path' => base_path('Modules/'.$name),
            'source' => 'manifest',
        ];
    }
}
