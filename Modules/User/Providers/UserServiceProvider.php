<?php

namespace Modules\User\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\User\Contracts\UserDirectory;
use Modules\User\Contracts\UserMailGateway;
use Modules\User\Contracts\UserNotifier;
use Modules\User\Services\AuthUserDirectory;
use Modules\User\Services\AuthUserMailGateway;
use Modules\User\Services\AuthUserNotifier;

class UserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(UserDirectory::class, AuthUserDirectory::class);
        $this->app->singleton(UserMailGateway::class, AuthUserMailGateway::class);
        $this->app->singleton(UserNotifier::class, AuthUserNotifier::class);
    }
}
