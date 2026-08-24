<?php

namespace Modules\Role\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Role\Contracts\RoleDirectory;
use Modules\Role\Services\SpatieRoleDirectory;

class RoleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RoleDirectory::class, SpatieRoleDirectory::class);
    }
}
