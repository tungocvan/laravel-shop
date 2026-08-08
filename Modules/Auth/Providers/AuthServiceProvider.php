<?php

namespace Modules\Auth\Providers;

use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/config/social.php', 'auth.social');

        $google = config('auth.social.google', []);
        $redirect = trim((string) ($google['redirect'] ?? ''));

        if ($redirect === '' || str_starts_with($redirect, '/')) {
            $redirect = rtrim((string) config('app.url'), '/').'/'.ltrim($redirect ?: 'auth/google/callback', '/');
        }

        $google['redirect'] = $redirect;
        config(['services.google' => $google]);
    }
}
