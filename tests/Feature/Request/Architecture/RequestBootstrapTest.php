<?php

namespace Tests\Feature\Request\Architecture;

use App\Modules\ModuleStateRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use LogicException;
use Mockery;
use Modules\ModuleServiceProvider;
use Modules\Request\Providers\RequestServiceProvider;
use ReflectionMethod;
use Tests\TestCase;

class RequestBootstrapTest extends TestCase
{
    public function test_runtime_enable_registers_provider_config_routes_and_translations_once(): void
    {
        $states = Mockery::mock(ModuleStateRepository::class);
        $states->shouldReceive('get')->with('Request')->once()->andReturn(true);
        $this->app->instance(ModuleStateRepository::class, $states);

        $provider = new ModuleServiceProvider($this->app);
        $module = $this->invoke($provider, 'resolveModuleManifest', [base_path('Modules/Request')]);

        $this->invoke($provider, 'registerModule', [$module]);
        $this->invoke($provider, 'registerModule', [$module]);
        Route::getRoutes()->refreshNameLookups();

        $this->assertTrue($module['enabled']);
        $this->assertSame('runtime', $module['source']);
        $this->assertCount(1, $this->app->getProviders(RequestServiceProvider::class));
        $this->assertSame([10, 25, 50, 100], config('request.settings.page_sizes'));
        $this->assertSame('Requests', trans('Request::request.module_name', locale: 'en'));
        $this->assertNull(Route::getRoutes()->getByName('request.index'));
        $this->assertSame([
            'request.admin.groups',
            'request.admin.types',
            'request.admin.types.designer',
            'request.admin.types.versions',
            'request.api.v1.inbox',
            'request.api.v1.requests.submit',
            'request.api.v1.tasks.decide',
            'request.catalog',
            'request.create',
            'request.inbox',
            'request.mine',
            'request.show',
        ], collect(Route::getRoutes())->filter(fn ($route): bool => str_starts_with((string) $route->getName(), 'request.'))->pluck('action.as')->unique()->sort()->values()->all());

        foreach (['request.admin.groups', 'request.admin.types', 'request.admin.types.designer', 'request.admin.types.versions', 'request.catalog', 'request.create', 'request.inbox', 'request.mine', 'request.show'] as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route);
            $this->assertContains('auth:admin', $route->gatherMiddleware());
        }
    }

    public function test_enabled_module_fails_safely_for_missing_or_disabled_dependencies(): void
    {
        $provider = new ModuleServiceProvider($this->app);

        foreach (['missing', 'disabled'] as $case) {
            try {
                $modules = $this->dependencyFixture($case);
                $this->invoke($provider, 'validateModuleGraph', [$modules]);
                $this->fail("The {$case} dependency case must fail.");
            } catch (LogicException $exception) {
                $this->assertStringContainsString($case === 'missing' ? 'missing module' : 'disabled module', $exception->getMessage());
            }
        }
    }

    private function dependencyFixture(string $case): Collection
    {
        $request = [
            'name' => 'Request',
            'type' => 'domain',
            'enabled' => true,
            'required' => false,
            'depends' => ['User'],
        ];

        if ($case === 'missing') {
            return collect([$request]);
        }

        return collect([$request, [
            'name' => 'User',
            'type' => 'shell',
            'enabled' => false,
            'required' => false,
            'depends' => [],
        ]]);
    }

    private function invoke(object $target, string $method, array $arguments): mixed
    {
        $reflection = new ReflectionMethod($target, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($target, $arguments);
    }
}
