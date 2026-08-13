<?php

namespace App\Providers;

use App\Modules\FileModuleStateRepository;
use App\Modules\ModuleStateRepository;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use LogicException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ModuleStateRepository::class, function (): ModuleStateRepository {
            $driver = (string) config('modules.state.driver', 'file');

            if ($driver !== 'file') {
                throw new LogicException("Unsupported module state driver [{$driver}].");
            }

            return new FileModuleStateRepository(
                (string) config('modules.state.file', storage_path('app/system/module-state.json'))
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Livewire tạo signed upload URL từ scheme của request. Trên production,
        // luôn dùng HTTPS kể cả khi SSL kết thúc tại Cloudflare/reverse proxy.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
