<?php

namespace Modules\System\Livewire\Concerns;

trait AuthorizesSystemActions
{
    private function authorizePermission(string $permission): void
    {
        $user = auth('admin')->user() ?: auth()->user();

        abort_unless($user?->can($permission), 403);
    }
}
