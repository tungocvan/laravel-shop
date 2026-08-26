<?php

namespace Modules\Request\Policies\Concerns;

use Modules\Request\Authorization\RequestAuthorizationContext;

trait ChecksRequestPermission
{
    private function hasPermission(mixed $user, string $permission): bool
    {
        $guard = app(RequestAuthorizationContext::class)->guard();

        if ($guard === null || ! method_exists($user, 'checkPermissionTo')) {
            return false;
        }

        return $user->checkPermissionTo($permission, $guard);
    }
}
