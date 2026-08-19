<?php

namespace Modules\ClientPortal\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Support\ApplicationContext;
use Symfony\Component\HttpFoundation\Response;

class EnsureApplicationAccess
{
    public function handle(Request $request, Closure $next, string $application): Response
    {
        $registry = app(ApplicationRegistry::class);
        $manifest = $registry->find($application);

        abort_if($manifest === null, 404);

        $user = $request->user('web');
        abort_if($user === null, 401);

        $permission = $manifest['permission'] ?? null;
        abort_if($permission !== null && ! $registry->userCan($user, $permission), 403);

        app(ApplicationContext::class)->set($manifest);

        return $next($request);
    }
}
