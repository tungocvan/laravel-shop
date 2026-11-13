<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\ClientPortal\Applications\Pharma\Services\KqlcntAwardVisibility;
use Symfony\Component\HttpFoundation\Response;

final class AuthorizeKqlcntAwardItem
{
    public function __construct(private readonly KqlcntAwardVisibility $visibility) {}

    public function handle(Request $request, Closure $next): Response
    {
        $awardItem = $request->route('awardItem');
        abort_unless(is_numeric($awardItem), 404);
        $this->visibility->authorizeItem($request, (int) $awardItem);

        return $next($request);
    }
}
