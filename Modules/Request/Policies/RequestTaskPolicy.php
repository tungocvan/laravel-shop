<?php

namespace Modules\Request\Policies;

use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Domain\Enums\RunStatus;
use Modules\Request\Domain\Enums\TaskStatus;
use Modules\Request\Models\RequestTask;
use Modules\Request\Policies\Concerns\ChecksAdminPermission;

final class RequestTaskPolicy
{
    use ChecksAdminPermission;

    public function viewAny(mixed $user): bool
    {
        return $this->hasPermission($user, 'request.task.view');
    }

    public function view(mixed $user, RequestTask $task): bool
    {
        return $this->hasPermission($user, 'request.task.view') && $task->assignee_user_id === (int) $user->getAuthIdentifier();
    }

    public function decide(mixed $user, RequestTask $task): bool
    {
        $task->loadMissing('run.requestInstance');

        return $this->hasPermission($user, 'request.task.decide')
            && $task->assignee_user_id === (int) $user->getAuthIdentifier()
            && $task->status === TaskStatus::Active
            && $task->run->status === RunStatus::Active
            && $task->run->requestInstance->status === RequestStatus::Pending
            && $task->run->requestInstance->requester_id !== (int) $user->getAuthIdentifier();
    }
}
