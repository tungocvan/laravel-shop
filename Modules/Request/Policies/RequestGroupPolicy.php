<?php

namespace Modules\Request\Policies;

use Modules\Request\Models\RequestGroup;

final class RequestGroupPolicy
{
    public function viewAny(mixed $user): bool
    {
        return $user->can('request.group.view');
    }

    public function create(mixed $user): bool
    {
        return $user->can('request.group.create');
    }

    public function update(mixed $user, RequestGroup $group): bool
    {
        return $group->archived_at === null && $user->can('request.group.update');
    }

    public function archive(mixed $user, RequestGroup $group): bool
    {
        return $group->archived_at === null && $user->can('request.group.archive');
    }
}
