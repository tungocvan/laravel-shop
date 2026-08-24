<?php

namespace Modules\Request\Policies;

use Modules\Request\Domain\Enums\RequestTypeVersionStatus;
use Modules\Request\Models\RequestTypeVersion;

final class RequestTypeVersionPolicy
{
    public function view(mixed $user, RequestTypeVersion $version): bool
    {
        return $user->can('request.type.view');
    }

    public function update(mixed $user, RequestTypeVersion $version): bool
    {
        return $version->status === RequestTypeVersionStatus::Draft && $user->can('request.type.update');
    }

    public function publish(mixed $user, RequestTypeVersion $version): bool
    {
        return $version->status === RequestTypeVersionStatus::Draft && $user->can('request.type.publish');
    }
}
