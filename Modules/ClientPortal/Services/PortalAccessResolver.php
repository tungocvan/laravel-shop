<?php

namespace Modules\ClientPortal\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class PortalAccessResolver
{
    public function __construct(private readonly ApplicationRegistry $registry)
    {
    }

    public function applicationsFor(?User $user): Collection
    {
        return $this->registry->forUser($user);
    }

    public function can(?User $user, ?string $permission): bool
    {
        if ($user === null) {
            return false;
        }

        if ($permission === null || $permission === '') {
            return true;
        }

        return $this->registry->userCan($user, $permission);
    }
}
