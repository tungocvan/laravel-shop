<?php

namespace Modules\ClientPortal\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\ClientPortal\Support\ApplicationContext;

class ClientPortalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ApplicationContext::class);
    }
}
