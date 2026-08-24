<?php

namespace Modules\Request\Application\Queries;

use Modules\Request\Models\RequestAttachment;

final class RequestAttachmentQuery
{
    public function findVisible(string $requestPublicId, string $attachmentPublicId, mixed $user): RequestAttachment
    {
        $actorId = (int) $user->getAuthIdentifier();
        $viewAll = method_exists($user, 'checkPermissionTo') && $user->checkPermissionTo('request.instance.view-all', 'admin');
        $participant = method_exists($user, 'checkPermissionTo') && $user->checkPermissionTo('request.instance.view-participant', 'admin');

        return RequestAttachment::query()->with('requestInstance')->where('public_id', $attachmentPublicId)->whereHas('requestInstance', function ($query) use ($requestPublicId, $actorId, $viewAll, $participant): void {
            $query->where('public_id', $requestPublicId);
            if (! $viewAll) {
                $query->where(function ($scope) use ($actorId, $participant): void {
                    $scope->where('requester_id', $actorId);
                    if ($participant) {
                        $scope->orWhereHas('runs.tasks', fn ($tasks) => $tasks->where('assignee_user_id', $actorId));
                    }
                });
            }
        })->firstOrFail();
    }
}
