<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Modules\ClientPortal\Http\Middleware\EnsureApplicationAccess;
use Modules\ClientPortal\Http\Middleware\EnsureFeatureAccess;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO |
                Request::HEADER_X_FORWARDED_AWS_ELB
        );
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'client.application' => EnsureApplicationAccess::class,
            'client.feature' => EnsureFeatureAccess::class,
        ]);
        $middleware->web(append: [
            // \Modules\Website\Http\Middleware\TrackAffiliate::class,
            // \Modules\Website\Http\Middleware\ShareWishlistData::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->routeIs('admin.*')) {
                return redirect()->guest(route('admin.login'));
            }

            if ($request->routeIs('client.*') || $request->is('my-apps') || $request->is('apps/*')) {
                return redirect()->guest(route('client.apps.login'));
            }

            return redirect()->guest(route('login'));
        });

        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->routeIs('admin.*') && View::exists('Admin::errors.404')) {
                return response()->view('Admin::errors.404', [], 404);
            }

            if (View::exists('Website::errors.404')) {
                return response()->view('Website::errors.404', [], 404);
            }

            return response('Not Found', 404);
        });
    })->create();
