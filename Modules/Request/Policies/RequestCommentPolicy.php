<?php

namespace Modules\Request\Policies;

use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestComment;
use Modules\Request\Policies\Concerns\ChecksAdminPermission;

final class RequestCommentPolicy
{
    use ChecksAdminPermission;

    public function view(mixed $user, RequestComment $comment): bool
    {
        return $this->visible($user, $comment->requestInstance);
    }

    public function create(mixed $user, InternalRequest $request): bool
    {
        return $this->hasPermission($user, 'request.comment.create')
            && ! $request->archived_at
            && ! in_array($request->status, [RequestStatus::Approved, RequestStatus::Rejected, RequestStatus::Cancelled], true)
            && $this->visible($user, $request);
    }

    private function visible(mixed $user, InternalRequest $request): bool
    {
        $policy = new InternalRequestPolicy;

        return $policy->view($user, $request);
    }
}
