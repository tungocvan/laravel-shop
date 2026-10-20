<?php

namespace Modules\Muasamcong\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Muasamcong\Services\MuasamcongSettingsRepository;

class MuasamcongServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/muasamcong.php', 'muasamcong');
    }

    public function boot(): void
    {
        // Module-owned overrides are applied only while this provider is booted.
        // Legacy .env-backed defaults remain readable during migration.
        try {
            $this->app->make(MuasamcongSettingsRepository::class)->apply();
        } catch (\Illuminate\Database\QueryException $e) {
            // Database can be unavailable during initial installation or migrations.
            report($e);
        }

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/muasamcong.php' => config_path('muasamcong.php'),
        ], 'muasamcong-config');
    }
}
