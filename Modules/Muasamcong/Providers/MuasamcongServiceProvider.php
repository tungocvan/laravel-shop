<?php

namespace Modules\Muasamcong\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Muasamcong\Console\Commands\TestHsmtCommand;
use Modules\Muasamcong\Console\Commands\TestPricingCommand;

class MuasamcongServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/muasamcong.php', 'muasamcong');
    }

    public function boot(): void
    {
        $modulePath = dirname(__DIR__);

        $this->loadRoutesFrom($modulePath.'/routes/web.php');
        Route::prefix('api')->middleware('api')->group($modulePath.'/routes/api.php');
        $this->loadViewsFrom($modulePath.'/resources/views', 'Muasamcong');
        $this->registerLivewireComponents($modulePath.'/Livewire');

        if ($this->app->runningInConsole()) {
            $this->commands([
                TestHsmtCommand::class,
                TestPricingCommand::class,
            ]);

            $this->publishes([
                $modulePath.'/config/muasamcong.php' => config_path('muasamcong.php'),
            ], 'muasamcong-config');
        }
    }

    private function registerLivewireComponents(string $path): void
    {
        foreach (File::allFiles($path) as $file) {
            $relative = str_replace([$path.DIRECTORY_SEPARATOR, '.php'], '', $file->getPathname());
            $class = 'Modules\\Muasamcong\\Livewire\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $relative);

            if (class_exists($class)) {
                $alias = collect(explode(DIRECTORY_SEPARATOR, $relative))
                    ->map(fn (string $part) => Str::kebab($part))
                    ->implode('.');

                Livewire::component('muasamcong.'.$alias, $class);
            }
        }
    }
}
