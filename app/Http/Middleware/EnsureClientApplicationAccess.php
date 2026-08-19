<?php

namespace App\Http\Middleware;

use App\Services\ClientApplicationRegistry;
use App\Support\ClientApplicationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientApplicationAccess
{
    public function handle(
        Request $request,
        Closure $next,
        string $application,
    ): Response {
        $registry = app(ClientApplicationRegistry::class);
        $manifest = $registry->find($application);

        abort_if($manifest === null, 404);

        $user = $request->user('web');
        abort_if($user === null, 401);

        $permission = $manifest['permission'] ?? null;
        abort_if($permission !== null && ! $registry->userCan($user, $permission), 403);

        app(ClientApplicationContext::class)->set($manifest);

        return $next($request);
    }
}
