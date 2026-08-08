<?php

namespace Modules;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Livewire;

class ModuleServiceProvider extends ServiceProvider
{
    private const MODULE_TYPES = ['shell', 'domain', 'support'];

    private const BOOT_ORDER = [
        'shell' => 0,
        'support' => 1,
        'domain' => 2,
    ];

    private const TYPE_FALLBACKS = [
        'Admin' => 'shell',
        'Auth' => 'support',
        'Role' => 'support',
        'Template' => 'support',
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $modules = $this->discoverModules();

        $this->validateModuleGraph($modules);

        config([
            'modules.registry' => $modules->mapWithKeys(fn(array $module) => [
                $module['name'] => [
                    'name' => $module['name'],
                    'type' => $module['type'],
                    'enabled' => $module['enabled'],
                    'required' => $module['required'],
                    'depends' => $module['depends'],
                    'path' => $module['path'],
                    'source' => $module['source'],
                ],
            ])->all(),
        ]);

        $enabledModules = $modules->filter(fn(array $module) => $module['enabled']);

        if (config('modules.log_discovery', false)) {
            Log::info('Enabled modules', ['modules' => $enabledModules->pluck('name')->values()->all()]);
        }

        $enabledModules->each(fn(array $module) => $this->registerModule($module));

        Gate::before(function ($user) {
            return $user->hasRole('Super Admin') ? true : null;
        });
    }

    private function discoverModules(): Collection
    {
        $path = base_path('Modules');

        if (! File::exists($path)) {
            return collect();
        }

        return collect(File::directories($path))
            ->map(fn(string $modulePath) => $this->resolveModuleManifest($modulePath))
            ->sort(function (array $left, array $right) {
                $leftOrder = self::BOOT_ORDER[$left['type']] ?? PHP_INT_MAX;
                $rightOrder = self::BOOT_ORDER[$right['type']] ?? PHP_INT_MAX;

                if ($leftOrder === $rightOrder) {
                    return strcmp($left['name'], $right['name']);
                }

                return $leftOrder <=> $rightOrder;
            })
            ->values();
    }

    private function resolveModuleManifest(string $modulePath): array
    {
        $module = basename($modulePath);
        $manifestPath = $this->modulePath($modulePath, ['config/module.php', 'Config/module.php']);
        $manifest = [];
        $source = 'fallback';

        if ($manifestPath !== null) {
            $loaded = require $manifestPath;

            if (is_array($loaded)) {
                $manifest = $loaded;
                $source = 'manifest';
            }
        }

        $type = $manifest['type'] ?? $this->inferModuleType($module);
        if (! in_array($type, self::MODULE_TYPES, true)) {
            $type = $this->inferModuleType($module);
            $source = 'fallback';
        }

        return [
            'name' => $module,
            'path' => $modulePath,
            'lower_name' => Str::lower($module),
            'type' => $type,
            'enabled' => (bool) ($manifest['enabled'] ?? true),
            'required' => $type === 'shell',
            'depends' => array_values(array_unique(array_map(
                static fn(mixed $dependency): string => Str::studly((string) $dependency),
                is_array($manifest['depends'] ?? null) ? $manifest['depends'] : []
            ))),
            'source' => $source,
        ];
    }

    private function validateModuleGraph(Collection $modules): void
    {
        $registry = $modules->keyBy('name');

        foreach ($modules as $module) {
            if ($module['required'] && ! $module['enabled']) {
                throw new \LogicException("Shell module [{$module['name']}] is required and cannot be disabled.");
            }

            if (! $module['enabled']) {
                continue;
            }

            foreach ($module['depends'] as $dependency) {
                if (! $registry->has($dependency)) {
                    throw new \LogicException("Module [{$module['name']}] requires missing module [{$dependency}].");
                }

                if (! $registry->get($dependency)['enabled']) {
                    throw new \LogicException("Module [{$module['name']}] requires disabled module [{$dependency}].");
                }

                if ($dependency === $module['name']) {
                    throw new \LogicException("Module [{$module['name']}] cannot depend on itself.");
                }
            }
        }

        $visiting = [];
        $visited = [];
        $visit = function (string $name) use (&$visit, &$visiting, &$visited, $registry): void {
            if (isset($visited[$name])) {
                return;
            }

            if (isset($visiting[$name])) {
                throw new \LogicException("Circular module dependency detected at [{$name}].");
            }

            $visiting[$name] = true;
            foreach ($registry->get($name)['depends'] as $dependency) {
                $visit($dependency);
            }
            unset($visiting[$name]);
            $visited[$name] = true;
        };

        foreach ($modules->where('enabled', true)->pluck('name') as $name) {
            $visit($name);
        }
    }

    private function inferModuleType(string $module): string
    {
        return self::TYPE_FALLBACKS[$module] ?? 'domain';
    }

    private function registerModule(array $module): void
    {
        $provider = 'Modules\\' . $module['name'] . '\\Providers\\' . $module['name'] . 'ServiceProvider';
        if (class_exists($provider)) {
            $this->app->register($provider);
        }

        $this->registerConfig($module);
        $this->registerRoutes($module);
        $this->registerResources($module);
        $this->registerHelpers($module);
        $this->registerMigrations($module);
        $this->registerLivewireComponents($module);
        $this->registerBladeComponents($module);
        $this->registerConsole($module);
    }

    private function registerConsole(array $module): void
    {
        // chỉ chạy khi artisan
        if (! $this->app->runningInConsole()) {
            return;
        }

        $consolePath = $this->modulePath($module['path'], ['Console']);

        if ($consolePath === null) {
            return;
        }

        $namespacePrefix = 'Modules\\' . $module['name'] . '\\Console';

        $commands = [];

        foreach (File::allFiles($consolePath) as $file) {
            $relativePath = str_replace($consolePath, '', $file->getPathname());
            $relativePath = str_replace('.php', '', $relativePath);
            $classPath = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

            $fullClass = $namespacePrefix . $classPath;

            if (! class_exists($fullClass)) {
                continue;
            }

            // chỉ lấy class extend Command
            if (is_subclass_of($fullClass, \Illuminate\Console\Command::class)) {
                $commands[] = $fullClass;
            }
        }

        if (! empty($commands)) {
            $this->commands($commands);
        }
    }

    private function registerConfig(array $module): void
    {
        $configPath = $this->modulePath($module['path'], ['config', 'Config']);

        if ($configPath === null) {
            return;
        }

        foreach (File::files($configPath) as $file) {
            if (Str::lower($file->getExtension()) !== 'php') {
                continue;
            }

            $configName = pathinfo($file->getFilename(), PATHINFO_FILENAME);

            $this->mergeConfigFrom(
                $file->getPathname(),
                $module['lower_name'] . '.' . $configName
            );
        }
    }

    private function registerRoutes(array $module): void
    {
        $routesPath = $this->modulePath($module['path'], ['routes', 'Routes']);

        if ($routesPath === null) {
            return;
        }

        $webRoutePath = $this->modulePath($routesPath, ['web.php']);
        if ($webRoutePath !== null) {
            $this->loadRoutesFrom($webRoutePath);
        }

        $apiRoutePath = $this->modulePath($routesPath, ['api.php']);
        if ($apiRoutePath !== null) {
            Route::prefix('api')
                ->middleware('api')
                ->group(function () use ($apiRoutePath) {
                    require $apiRoutePath;
                });
        }
    }

    private function registerResources(array $module): void
    {
        $resourcePath = $this->modulePath($module['path'], ['resources', 'Resources']);

        if ($resourcePath === null) {
            return;
        }

        $viewsPath = $this->modulePath($resourcePath, ['views']);
        if ($viewsPath !== null) {
            $this->loadViewsFrom($viewsPath, $module['name']);
            $this->loadViewsFrom($viewsPath, $module['lower_name']);
        }

        $langPath = $this->modulePath($resourcePath, ['lang']);
        if ($langPath !== null) {
            $this->loadTranslationsFrom($langPath, $module['name']);
            $this->loadJSONTranslationsFrom($langPath);
        }
    }

    private function registerHelpers(array $module): void
    {
        $helpersPath = $this->modulePath($module['path'], ['Helpers']);

        if ($helpersPath === null) {
            return;
        }

        foreach (File::allFiles($helpersPath) as $file) {
            require_once $file->getPathname();
        }
    }

    private function registerMigrations(array $module): void
    {
        $migrationsPath = $this->modulePath($module['path'], [
            'database/migrations',
            'Database/Migrations',
        ]);

        if ($migrationsPath !== null) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }

    private function registerLivewireComponents(array $module): void
    {
        $livewirePath = $this->modulePath($module['path'], ['Livewire']);

        if ($livewirePath === null) {
            return;
        }

        $namespacePrefix = 'Modules\\' . $module['name'] . '\\Livewire';

        foreach (File::allFiles($livewirePath) as $file) {
            $relativePath = str_replace($livewirePath, '', $file->getPathname());
            $relativePath = str_replace('.php', '', $relativePath);
            $classPath = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);
            $fullClass = $namespacePrefix . $classPath;

            if (! class_exists($fullClass)) {
                continue;
            }

            $alias = collect(explode(DIRECTORY_SEPARATOR, trim($relativePath, DIRECTORY_SEPARATOR)))
                ->map(fn(string $part) => Str::kebab($part))
                ->implode('.');

            Livewire::component($module['lower_name'] . '.' . $alias, $fullClass);
        }
    }

    private function registerBladeComponents(array $module): void
    {
        $classComponentPath = $this->modulePath($module['path'], ['View/Components', 'Http/Components']);

        if ($classComponentPath !== null) {
            $namespace = str_contains($classComponentPath, 'Http' . DIRECTORY_SEPARATOR . 'Components')
                ? 'Modules\\' . $module['name'] . '\\Http\\Components'
                : 'Modules\\' . $module['name'] . '\\View\\Components';

            Blade::componentNamespace($namespace, $module['lower_name']);
        }

        $resourcePath = $this->modulePath($module['path'], ['resources', 'Resources']);
        $anonymousComponentPath = $resourcePath !== null
            ? $this->modulePath($resourcePath, ['views/components'])
            : null;

        if ($anonymousComponentPath !== null) {
            Blade::anonymousComponentPath($anonymousComponentPath, $module['lower_name']);
        }
    }

    private function modulePath(string $basePath, array $segments): ?string
    {
        foreach ($segments as $segment) {
            $path = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $segment);

            if (File::exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
