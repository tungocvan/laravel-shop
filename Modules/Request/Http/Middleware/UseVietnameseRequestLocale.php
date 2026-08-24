<?php

namespace Modules\Request\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class UseVietnameseRequestLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale('vi');

        return $next($request);
    }
}
