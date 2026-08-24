<?php

namespace Modules\Request\Policies;

use Modules\Request\Models\RequestGroup;
use Modules\Request\Policies\Concerns\ChecksAdminPermission;

final class RequestGroupPolicy
{
    use ChecksAdminPermission;

    public function viewAny(mixed $user): bool
    {
        return $this->hasPermission($user, 'request.group.view');
    }

    public function create(mixed $user): bool
    {
        return $this->hasPermission($user, 'request.group.create');
    }

    public function update(mixed $user, RequestGroup $group): bool
    {
        return $group->archived_at === null && $this->hasPermission($user, 'request.group.update');
    }

    public function archive(mixed $user, RequestGroup $group): bool
    {
        return $group->archived_at === null && $this->hasPermission($user, 'request.group.archive');
    }
}
