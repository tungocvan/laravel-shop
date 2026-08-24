<?php

namespace Modules\Request\Providers;

use Illuminate\Support\ServiceProvider;

class RequestServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Request-owned contracts and registries are introduced with their owning slices.
    }
}
