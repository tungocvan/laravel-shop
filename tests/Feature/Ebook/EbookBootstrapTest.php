<?php

namespace Tests\Feature\Ebook;

use App\Modules\ModuleStateRepository;
use Illuminate\Support\Facades\Route;
use Mockery;
use Modules\ModuleServiceProvider;
use ReflectionMethod;
use Tests\TestCase;

class EbookBootstrapTest extends TestCase
{
    public function test_manifest_matches_approved_bootstrap_contract(): void
    {
        $manifest = require base_path('Modules/Ebook/config/module.php');

        $this->assertSame('Ebook', $manifest['name']);
        $this->assertSame('domain', $manifest['type']);
        $this->assertTrue($manifest['default_enabled']);
        $this->assertSame([], $manifest['depends']);
        $this->assertSame([
            'ebook.view',
            'ebook.create',
            'ebook.update',
            'ebook.delete',
            'ebook.upload',
            'ebook.sync',
        ], $manifest['permissions']);
    }

    public function test_admin_routes_are_registered_with_auth_and_view_permission(): void
    {
        $routes = [
            'admin.ebook.index' => 'admin/ebook',
            'admin.ebook.document.show' => 'admin/ebook/document/{document}',
            'admin.ebook.asset' => 'admin/ebook/document/{document}/asset',
        ];

        foreach ($routes as $name => $uri) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, "Route {$name} is not registered.");
            $this->assertContains('web', $route->gatherMiddleware());
            $this->assertContains('auth:admin', $route->gatherMiddleware());
            $this->assertContains('permission:ebook.view,admin', $route->gatherMiddleware());
            $this->assertSame($uri, $route->uri());
        }
    }

    public function test_ebook_view_namespace_is_registered(): void
    {
        $this->assertTrue(view()->exists('Ebook::pages.ebook.index'));
        $this->assertTrue(view()->exists('Ebook::pages.ebook.show'));
        $this->assertTrue(view()->exists('Ebook::livewire.ebook-viewer'));
    }

    public function test_root_provider_respects_runtime_disabled_override(): void
    {
        $states = Mockery::mock(ModuleStateRepository::class);
        $states->shouldReceive('get')->with('Ebook')->once()->andReturn(false);
        $this->app->instance(ModuleStateRepository::class, $states);

        $provider = new ModuleServiceProvider($this->app);
        $method = new ReflectionMethod($provider, 'resolveModuleManifest');
        $method->setAccessible(true);

        $module = $method->invoke($provider, base_path('Modules/Ebook'));

        $this->assertSame('Ebook', $module['name']);
        $this->assertSame('domain', $module['type']);
        $this->assertFalse($module['required']);
        $this->assertFalse($module['enabled']);
        $this->assertSame('runtime', $module['source']);
    }
}
