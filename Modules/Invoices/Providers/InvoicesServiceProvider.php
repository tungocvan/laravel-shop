<?php

namespace Modules\Invoices\Providers;

use Illuminate\Support\ServiceProvider;

class InvoicesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/invoices.php', 'invoices');
    }

    public function boot(): void
    {
        $modulePath = dirname(__DIR__);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                $modulePath.'/config/invoices.php' => config_path('invoices.php'),
            ], 'invoices-config');
        }
    }
}
