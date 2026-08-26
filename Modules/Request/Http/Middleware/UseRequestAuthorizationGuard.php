<?php

namespace Modules\Request\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Request\Authorization\RequestAuthorizationContext;
use Symfony\Component\HttpFoundation\Response;

final class UseRequestAuthorizationGuard
{
    public function __construct(private readonly RequestAuthorizationContext $context) {}

    public function handle(Request $request, Closure $next, string $guard): Response
    {
        $previous = $this->context->guard();
        $this->context->setGuard($guard);

        try {
            return $next($request);
        } finally {
            $this->context->restore($previous);
        }
    }
}
