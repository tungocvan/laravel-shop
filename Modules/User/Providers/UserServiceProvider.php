<?php

namespace Modules\User\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\User\Contracts\UserDirectory;
use Modules\User\Services\AuthUserDirectory;

class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(UserDirectory::class, AuthUserDirectory::class);
    }
}
