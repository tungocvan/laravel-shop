<?php

namespace Modules\Request\Policies;

use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Policies\Concerns\ChecksAdminPermission;

final class InternalRequestPolicy
{
    use ChecksAdminPermission;

    public function viewAny(mixed $user): bool
    {
        return $this->hasPermission($user, 'request.instance.view-own') || $this->hasPermission($user, 'request.instance.view-all');
    }

    public function create(mixed $user): bool
    {
        return $this->hasPermission($user, 'request.instance.create');
    }

    public function view(mixed $user, InternalRequest $request): bool
    {
        return $this->hasPermission($user, 'request.instance.view-all')
            || ($request->requester_id === (int) $user->getAuthIdentifier() && $this->hasPermission($user, 'request.instance.view-own'))
            || ($this->hasPermission($user, 'request.instance.view-participant') && $request->runs()->whereHas('tasks', fn ($query) => $query->where('assignee_user_id', (int) $user->getAuthIdentifier()))->exists());
    }

    public function submit(mixed $user, InternalRequest $request): bool
    {
        return in_array($request->status, [RequestStatus::Draft, RequestStatus::Returned], true) && $request->requester_id === (int) $user->getAuthIdentifier() && $this->hasPermission($user, 'request.instance.submit');
    }

    public function update(mixed $user, InternalRequest $request): bool
    {
        return in_array($request->status, [RequestStatus::Draft, RequestStatus::Returned], true) && $request->requester_id === (int) $user->getAuthIdentifier() && $this->hasPermission($user, 'request.instance.update-own');
    }

    public function cancel(mixed $user, InternalRequest $request): bool
    {
        return ($request->requester_id === (int) $user->getAuthIdentifier() && in_array($request->status, [RequestStatus::Draft, RequestStatus::Returned], true) && $this->hasPermission($user, 'request.instance.cancel-own'))
            || ($request->status === RequestStatus::Pending && $this->hasPermission($user, 'request.instance.cancel-any'));
    }

    public function retryActivation(mixed $user, InternalRequest $request): bool
    {
        return $request->status === RequestStatus::Pending && $this->hasPermission($user, 'request.operation.retry');
    }
}
