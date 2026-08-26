<?php

namespace Modules\Request\Policies;

use Modules\Request\Policies\Concerns\ChecksAdminPermission;

final class RequestOperationPolicy
{
    use ChecksAdminPermission;

    public function view(mixed $user): bool
    {
        return $this->hasPermission($user, 'request.operation.view');
    }

    public function retry(mixed $user): bool
    {
        return $this->hasPermission($user, 'request.operation.retry');
    }

    public function delete(mixed $user): bool
    {
        return $this->hasPermission($user, 'request.operation.delete');
    }
}
