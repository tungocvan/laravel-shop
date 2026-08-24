<?php

namespace Modules\Request\Policies;

use Modules\Request\Domain\Enums\RequestTypeVersionStatus;
use Modules\Request\Models\RequestTypeVersion;
use Modules\Request\Policies\Concerns\ChecksAdminPermission;

final class RequestTypeVersionPolicy
{
    use ChecksAdminPermission;

    public function view(mixed $user, RequestTypeVersion $version): bool
    {
        return $this->hasPermission($user, 'request.type.view');
    }

    public function update(mixed $user, RequestTypeVersion $version): bool
    {
        return $version->status === RequestTypeVersionStatus::Draft && $this->hasPermission($user, 'request.type.update');
    }

    public function publish(mixed $user, RequestTypeVersion $version): bool
    {
        return $version->status === RequestTypeVersionStatus::Draft && $this->hasPermission($user, 'request.type.publish');
    }
}
