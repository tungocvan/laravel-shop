<?php

namespace Modules\Request\Policies\Concerns;

use Modules\Request\Authorization\RequestAuthorizationContext;

trait ChecksRequestPermission
{
    private function hasPermission(mixed $user, string $permission): bool
    {
        if (method_exists($user, 'checkPermissionTo')) {
            $guard = app(RequestAuthorizationContext::class)->guard() ?? 'admin';

            return $user->checkPermissionTo($permission, $guard);
        }

        return method_exists($user, 'can') && $user->can($permission);
    }
}
