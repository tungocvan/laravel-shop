<?php

namespace App\Http\Middleware;

use App\Services\ClientApplicationRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientFeatureAccess
{
    public function handle(
        Request $request,
        Closure $next,
        string $application,
        string $feature,
    ): Response {
        $registry = app(ClientApplicationRegistry::class);
        $manifest = $registry->find($application);

        abort_if($manifest === null, 404);

        $featureManifest = collect($manifest['features'] ?? [])->first(
            fn (array $item): bool => ($item['key'] ?? null) === $feature
        );

        abort_if($featureManifest === null, 404);

        $user = $request->user('web');
        abort_if($user === null, 401);

        $permission = $featureManifest['permission'] ?? null;
        abort_if($permission !== null && ! $registry->userCan($user, $permission), 403);

        $request->attributes->set('client_feature', $featureManifest);

        return $next($request);
    }
}
