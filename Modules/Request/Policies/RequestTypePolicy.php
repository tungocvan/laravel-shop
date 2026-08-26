<?php

namespace Modules\Request\Policies;

use Modules\Request\Domain\Enums\RequestTypeStatus;
use Modules\Request\Models\RequestType;
use Modules\Request\Policies\Concerns\ChecksAdminPermission;

final class RequestTypePolicy
{
    use ChecksAdminPermission;

    public function viewAny(mixed $user): bool
    {
        return $this->hasPermission($user, 'request.type.view');
    }

    public function view(mixed $user, RequestType $type): bool
    {
        return $this->hasPermission($user, 'request.type.view');
    }

    public function create(mixed $user): bool
    {
        return $this->hasPermission($user, 'request.type.create');
    }

    public function delete(mixed $user, RequestType $type): bool
    {
        return $type->current_published_version_id === null
            && $this->hasPermission($user, 'request.type.delete');
    }

    public function update(mixed $user, RequestType $type): bool
    {
        return $type->status !== RequestTypeStatus::Retired && $this->hasPermission($user, 'request.type.update');
    }

    public function manageAudience(mixed $user, RequestType $type): bool
    {
        return $type->status !== RequestTypeStatus::Retired
            && $this->hasPermission($user, 'request.type.audience.manage');
    }

    public function publish(mixed $user, RequestType $type): bool
    {
        return $type->active_draft_version_id !== null && $this->hasPermission($user, 'request.type.publish');
    }

    public function retire(mixed $user, RequestType $type): bool
    {
        return $type->status !== RequestTypeStatus::Retired && $this->hasPermission($user, 'request.type.retire');
    }

    public function exportDefinition(mixed $user, RequestType $type): bool
    {
        return $this->hasPermission($user, 'request.type.export');
    }

    public function importDefinition(mixed $user, RequestType $type): bool
    {
        return $type->status !== RequestTypeStatus::Retired && $this->hasPermission($user, 'request.type.import');
    }
}
