<?php

namespace Modules\Website\Livewire\Concerns;

trait AuthorizesAdminPermissions
{
    protected function authorizeAdminPermission(string $permission): void
    {
        $user = auth('admin')->user();

        abort_unless(
            $user && $user->hasPermissionTo($permission, 'admin'),
            403,
            'Bạn không có quyền thực hiện thao tác này.'
        );
    }
}
