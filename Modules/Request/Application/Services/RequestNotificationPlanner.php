<?php

namespace Modules\Request\Application\Services;

use Modules\Request\Data\NotificationPlan;
use Modules\Request\Domain\Enums\TaskStatus;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestAttachment;
use Modules\Request\Models\RequestComment;
use Modules\Request\Models\RequestOutboxMessage;
use Modules\Request\Models\RequestRun;
use Modules\Request\Models\RequestStageDefinition;
use Modules\Request\Models\RequestTask;

final class RequestNotificationPlanner
{
    /** @return list<NotificationPlan> */
    public function plans(RequestOutboxMessage $outbox): array
    {
        [$request, $recipients, $template, $channels] = match ($outbox->event_key) {
            'request.run.stage_activated.v1' => $this->stageActivated($outbox),
            'request.task.reassigned.v1' => $this->taskReassigned($outbox),
            'request.task.sla_warning.v1' => $this->slaWarning($outbox),
            'request.task.decided.v1' => $this->requesterForTask($outbox, 'request_decision_recorded'),
            'request.approved.v1' => $this->requester($outbox, 'request_approved'),
            'request.rejected.v1' => $this->requester($outbox, 'request_rejected'),
            'request.returned.v1' => $this->requester($outbox, 'request_returned'),
            'request.cancelled.v1' => $this->requester($outbox, 'request_cancelled'),
            'request.comment.created.v1' => $this->collaboration($outbox, true),
            'request.attachment.created.v1' => $this->collaboration($outbox, false),
            default => [null, [], '', ['database', 'email']],
        };

        if (! $request instanceof InternalRequest || $template === '') {
            return [];
        }

        return collect($recipients)
            ->filter(fn (mixed $id): bool => is_int($id) && $id > 0)
            ->unique()->sort()
            ->map(fn (int $recipientId): NotificationPlan => new NotificationPlan(
                recipientId: $recipientId,
                templateKey: $template,
                requestPublicId: $request->public_id,
                requestNumber: $request->request_number,
                requestTitle: $request->title_snapshot,
                status: $request->status->value,
                channels: $channels,
            ))->values()->all();
    }

    private function stageActivated(RequestOutboxMessage $outbox): array
    {
        $runPublicId = $outbox->payload_json['run_public_id'] ?? null;
        $position = $outbox->payload_json['stage_position'] ?? null;
        if (! is_string($runPublicId) || ! is_int($position)) {
            return [null, [], '', ['database']];
        }
        $run = RequestRun::query()->with('requestInstance')->where('public_id', $runPublicId)->first();
        if (! $run) {
            return [null, [], '', ['database']];
        }
        $stage = RequestStageDefinition::query()->where('request_type_version_id', $run->request_type_version_id)->where('position', $position)->first();
        $recipients = $run->tasks()->where('stage_position', $position)->where('status', TaskStatus::Active)->pluck('assignee_user_id')->map(fn (mixed $id): int => (int) $id)->all();

        return [$run->requestInstance, $recipients, 'approval_action_required', $this->channels((bool) ($stage?->email_on_assignment ?? true))];
    }

    private function taskReassigned(RequestOutboxMessage $outbox): array
    {
        $target = $outbox->payload_json['target_user_id'] ?? null;
        $task = RequestTask::query()->with(['run.requestInstance', 'stageDefinition'])->where('public_id', $outbox->aggregate_public_id)->first();

        return [$task?->run?->requestInstance, is_int($target) ? [$target] : [], 'approval_reassigned', $this->channels((bool) ($task?->stageDefinition?->email_on_assignment ?? true))];
    }

    private function slaWarning(RequestOutboxMessage $outbox): array
    {
        $task = RequestTask::query()->with(['run.requestInstance', 'stageDefinition'])->where('public_id', $outbox->aggregate_public_id)->first();
        $request = $task?->run?->requestInstance;
        $recipients = $task && $task->assignee_user_id ? [(int) $task->assignee_user_id] : [];

        return [$request, $recipients, 'approval_sla_warning', $this->channels((bool) ($task?->stageDefinition?->email_on_sla_warning ?? true))];
    }

    private function requesterForTask(RequestOutboxMessage $outbox, string $template): array
    {
        $task = RequestTask::query()->with(['run.requestInstance', 'stageDefinition'])->where('public_id', $outbox->aggregate_public_id)->first();
        $request = $task?->run?->requestInstance;

        return [$request, $request ? [$request->requester_id] : [], $template, $this->channels((bool) ($task?->stageDefinition?->email_on_decision ?? true))];
    }

    private function requester(RequestOutboxMessage $outbox, string $template): array
    {
        $request = InternalRequest::query()->where('public_id', $outbox->aggregate_public_id)->first();

        return [$request, $request ? [$request->requester_id] : [], $template, ['database', 'email']];
    }

    private function collaboration(RequestOutboxMessage $outbox, bool $comment): array
    {
        $request = InternalRequest::query()->where('public_id', $outbox->aggregate_public_id)->first();
        if (! $request) {
            return [null, [], '', ['database', 'email']];
        }
        $publicId = $comment ? ($outbox->payload_json['comment_public_id'] ?? null) : ($outbox->payload_json['attachment_public_id'] ?? null);
        $record = is_string($publicId) ? ($comment ? RequestComment::query()->where('public_id', $publicId)->first() : RequestAttachment::query()->where('public_id', $publicId)->first()) : null;
        $actorId = $comment ? $record?->author_id : $record?->uploaded_by;
        $recipients = $request->runs()->whereHas('tasks', fn ($tasks) => $tasks->where('status', TaskStatus::Active))->with('tasks')->get()->flatMap(fn (RequestRun $run) => $run->tasks->where('status', TaskStatus::Active)->pluck('assignee_user_id'))->push($request->requester_id)->reject(fn (mixed $id): bool => (int) $id === (int) $actorId)->map(fn (mixed $id): int => (int) $id)->all();

        return [$request, $recipients, $comment ? 'request_comment_added' : 'request_attachment_added', ['database', 'email']];
    }

    private function channels(bool $email): array
    {
        return $email ? ['database', 'email'] : ['database'];
    }
}
