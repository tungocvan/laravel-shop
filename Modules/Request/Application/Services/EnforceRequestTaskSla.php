<?php

namespace Modules\Request\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Request\Domain\Enums\TaskStatus;
use Modules\Request\Models\RequestTask;

final class EnforceRequestTaskSla
{
    public function __construct(
        private readonly RequestAuditAppender $audit,
        private readonly RequestOutboxAppender $outbox,
    ) {}

    /**
     * @return array{warned:int,overdue:int,suspended:int}
     */
    public function handle(): array
    {
        $counts = ['warned' => 0, 'overdue' => 0, 'suspended' => 0];
        $now = now('UTC');

        RequestTask::query()
            ->where('status', TaskStatus::Active)
            ->whereNotNull('sla_snapshot_json')
            ->orderBy('id')
            ->chunkById(100, function ($tasks) use (&$counts, $now): void {
                foreach ($tasks as $task) {
                    DB::transaction(function () use ($task, &$counts, $now): void {
                        $target = RequestTask::query()->with('run.requestInstance')->lockForUpdate()->find($task->id);
                        if (! $target || $target->status !== TaskStatus::Active || $target->decided_at || $target->closed_at) {
                            return;
                        }

                        $request = $target->run?->requestInstance;
                        if (! $request) {
                            return;
                        }

                        $snapshot = (array) $target->sla_snapshot_json;
                        $timeoutAction = (string) ($snapshot['timeout_action'] ?? 'notify_only');

                        if ($target->warning_at
                            && $target->warning_at->lte($now)
                            && (! $target->due_at || $now->lt($target->due_at))
                            && empty($snapshot['warning_emitted_at'])) {
                            $snapshot['warning_emitted_at'] = $now->toIso8601String();
                            $counts['warned']++;
                            $this->emit($target, $request->id, 'request.task.sla_warning.v1', $now, ['due_at' => $target->due_at?->toIso8601String()]);
                        }

                        if ($target->due_at && $target->due_at->lte($now) && ! $target->overdue_at) {
                            $target->overdue_at = $now;
                            $counts['overdue']++;
                            $this->emit($target, $request->id, 'request.task.overdue.v1', $now, ['due_at' => $target->due_at->toIso8601String()]);
                        }

                        if ($timeoutAction === 'suspend' && $target->grace_expires_at && $target->grace_expires_at->lte($now) && ! $target->suspended_at) {
                            $target->suspended_at = $now;
                            $counts['suspended']++;
                            $this->emit($target, $request->id, 'request.task.suspended.v1', $now, ['grace_expires_at' => $target->grace_expires_at->toIso8601String(), 'reason' => 'sla_grace_expired']);
                        }

                        $target->sla_snapshot_json = $snapshot;
                        $target->save();
                    }, 3);
                }
            });

        return $counts;
    }

    private function emit(RequestTask $task, int $requestId, string $event, mixed $now, array $payload): void
    {
        $correlationId = 'sla:'.$task->public_id.':'.$event.':'.$now->timestamp;
        $payload += ['task_public_id' => $task->public_id, 'stage_key' => $task->stage_key_snapshot];

        $this->audit->append('request_task', $task->public_id, $event, null, $correlationId, $payload, null, $requestId);
        $this->outbox->append($event, 'request_task', $task->public_id, $correlationId, $payload);
    }
}
