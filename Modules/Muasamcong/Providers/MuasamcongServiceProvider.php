<?php

namespace Modules\Muasamcong\Providers;

use Illuminate\Support\ServiceProvider;

class MuasamcongServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/muasamcong.php', 'muasamcong');
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/muasamcong.php' => config_path('muasamcong.php'),
        ], 'muasamcong-config');
    }
}
