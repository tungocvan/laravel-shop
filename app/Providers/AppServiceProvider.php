<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
