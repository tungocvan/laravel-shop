<?php

namespace Modules\Request\Policies;

use Modules\Request\Domain\Enums\RequestTypeStatus;
use Modules\Request\Models\RequestType;

final class RequestTypePolicy
{
    public function viewAny(mixed $user): bool
    {
        return $user->can('request.type.view');
    }

    public function view(mixed $user, RequestType $type): bool
    {
        return $user->can('request.type.view');
    }

    public function create(mixed $user): bool
    {
        return $user->can('request.type.create');
    }

    public function update(mixed $user, RequestType $type): bool
    {
        return $type->status !== RequestTypeStatus::Retired && $user->can('request.type.update');
    }

    public function publish(mixed $user, RequestType $type): bool
    {
        return $type->active_draft_version_id !== null && $user->can('request.type.publish');
    }

    public function retire(mixed $user, RequestType $type): bool
    {
        return $type->status !== RequestTypeStatus::Retired && $user->can('request.type.retire');
    }
}
