<?php

namespace Tests\Feature\Request\Lifecycle;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Request\Application\Services\CreateInternalRequest;
use Modules\Request\Application\Services\DecideRequestTask;
use Modules\Request\Application\Services\EnforceRequestTaskSla;
use Modules\Request\Application\Services\SaveRequestDraft;
use Modules\Request\Application\Services\SubmitInternalRequest;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestAuditEvent;
use Modules\Request\Models\RequestOutboxMessage;
use Modules\Request\Models\RequestTask;
use Tests\Feature\Request\Draft\RequestDraftTestCase;

class RequestTaskSlaTest extends RequestDraftTestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_sla_snapshot_warns_overdues_and_suspends_once_at_utc_boundaries(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-26 00:00:00', 'UTC'));
        [$approverId, $request, $task] = $this->submittedSlaRequest();

        $this->assertSame('2026-08-26T00:45:00+00:00', $task->warning_at?->toIso8601String());
        $this->assertSame('2026-08-26T01:00:00+00:00', $task->due_at?->toIso8601String());
        $this->assertSame('2026-08-26T01:30:00+00:00', $task->grace_expires_at?->toIso8601String());
        $this->assertSame([
            'sla_minutes' => 60,
            'warning_minutes_before' => 15,
            'grace_minutes' => 30,
            'timeout_action' => 'suspend',
        ], $task->sla_snapshot_json);

        Carbon::setTestNow(Carbon::parse('2026-08-26 00:45:00', 'UTC'));
        $this->assertSame(['warned' => 1, 'overdue' => 0, 'suspended' => 0], app(EnforceRequestTaskSla::class)->handle());
        $this->assertSame(['warned' => 0, 'overdue' => 0, 'suspended' => 0], app(EnforceRequestTaskSla::class)->handle());

        Carbon::setTestNow(Carbon::parse('2026-08-26 01:00:00', 'UTC'));
        $this->assertSame(['warned' => 0, 'overdue' => 1, 'suspended' => 0], app(EnforceRequestTaskSla::class)->handle());

        Carbon::setTestNow(Carbon::parse('2026-08-26 01:30:00', 'UTC'));
        $this->assertSame(['warned' => 0, 'overdue' => 0, 'suspended' => 1], app(EnforceRequestTaskSla::class)->handle());
        $this->assertSame(['warned' => 0, 'overdue' => 0, 'suspended' => 0], app(EnforceRequestTaskSla::class)->handle());

        $task->refresh();
        $this->assertNotNull($task->overdue_at);
        $this->assertNotNull($task->suspended_at);
        foreach (['request.task.sla_warning.v1', 'request.task.overdue.v1', 'request.task.suspended.v1'] as $eventKey) {
            $this->assertSame(1, RequestAuditEvent::query()->where('event_key', $eventKey)->where('aggregate_public_id', $task->public_id)->count());
            $this->assertSame(1, RequestOutboxMessage::query()->where('event_key', $eventKey)->where('aggregate_public_id', $task->public_id)->count());
        }

        try {
            app(DecideRequestTask::class)->approve(
                $task,
                $approverId,
                $request->refresh()->lock_version,
                $task->lock_version,
                (string) Str::uuid(),
            );
            $this->fail('A suspended task must not be actionable.');
        } catch (ValidationException $exception) {
            $this->assertSame(['task_suspended'], $exception->errors()['task']);
        }
    }

    public function test_first_enforcement_after_due_skips_stale_warning_and_only_marks_overdue(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-26 00:00:00', 'UTC'));
        [, , $task] = $this->submittedSlaRequest();

        Carbon::setTestNow(Carbon::parse('2026-08-26 01:01:00', 'UTC'));
        $this->assertSame(['warned' => 0, 'overdue' => 1, 'suspended' => 0], app(EnforceRequestTaskSla::class)->handle());
        $this->assertSame(0, RequestOutboxMessage::query()->where('event_key', 'request.task.sla_warning.v1')->where('aggregate_public_id', $task->public_id)->count());
        $this->assertSame(1, RequestOutboxMessage::query()->where('event_key', 'request.task.overdue.v1')->where('aggregate_public_id', $task->public_id)->count());
    }

    /** @return array{0:int,1:InternalRequest,2:RequestTask} */
    private function submittedSlaRequest(): array
    {
        $requesterId = $this->activeUser('Requester');
        $approverId = $this->activeUser('Approver');
        $type = $this->publishedType($requesterId, $this->simpleSchema(), $requesterId, [[
            'stage_key' => 'approval',
            'name' => 'Approval',
            'position' => 1,
            'mode' => 'single',
            'resolver_key' => 'fixed_users',
            'resolver_config_json' => ['user_ids' => [$approverId]],
            'allow_reassignment' => false,
            'sla_minutes' => 60,
            'warning_minutes_before' => 15,
            'grace_minutes' => 30,
            'timeout_action' => 'suspend',
        ]]);
        $request = app(CreateInternalRequest::class)->handle($type, $requesterId, (string) Str::uuid());
        app(SaveRequestDraft::class)->handle($request, ['subject' => 'SLA'], $requesterId, 1, (string) Str::uuid());
        app(SubmitInternalRequest::class)->handle($request, $requesterId, 2, (string) Str::uuid());

        return [$approverId, $request->refresh(), RequestTask::query()->firstOrFail()];
    }
}
