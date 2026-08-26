<?php

namespace Tests\Feature\Request\Notifications;

use App\Modules\ModuleStateRepository;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\Request\Application\Services\CreateInternalRequest;
use Modules\Request\Application\Services\RequestNotificationPlanner;
use Modules\Request\Application\Services\RequestOutboxAppender;
use Modules\Request\Application\Services\RequestOutboxDispatcher;
use Modules\Request\Application\Services\SaveRequestDraft;
use Modules\Request\Application\Services\SubmitInternalRequest;
use Modules\Request\Jobs\DeliverRequestNotification;
use Modules\Request\Models\RequestNotificationDelivery;
use Modules\Request\Models\RequestOutboxMessage;
use Modules\Request\Models\RequestTask;
use Tests\Feature\Request\Draft\RequestDraftTestCase;

class RequestNotificationPreferenceTest extends RequestDraftTestCase
{
    public function test_disabled_stage_email_preferences_keep_assignment_decision_and_warning_database_only(): void
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
            'grace_minutes' => 0,
            'timeout_action' => 'notify_only',
            'email_on_assignment' => false,
            'email_on_decision' => false,
            'email_on_sla_warning' => false,
        ]]);
        $request = app(CreateInternalRequest::class)->handle($type, $requesterId, (string) Str::uuid());
        app(SaveRequestDraft::class)->handle($request, ['subject' => 'No email'], $requesterId, 1, (string) Str::uuid());
        app(SubmitInternalRequest::class)->handle($request, $requesterId, 2, (string) Str::uuid());

        $task = RequestTask::query()->firstOrFail();
        $this->assertFalse($task->stageDefinition->email_on_assignment);
        $this->assertFalse($task->stageDefinition->email_on_decision);
        $this->assertFalse($task->stageDefinition->email_on_sla_warning);

        $planner = app(RequestNotificationPlanner::class);
        $assignment = RequestOutboxMessage::query()->where('event_key', 'request.run.stage_activated.v1')->firstOrFail();
        $assignmentPlans = $planner->plans($assignment);

        $this->assertCount(1, $assignmentPlans);
        $this->assertSame($approverId, $assignmentPlans[0]->recipientId);
        $this->assertSame('approval_action_required', $assignmentPlans[0]->templateKey);
        $this->assertSame(['database'], $assignmentPlans[0]->channels);

        $decision = app(RequestOutboxAppender::class)->append(
            'request.task.decided.v1',
            'request_task',
            $task->public_id,
            (string) Str::uuid(),
            ['decision' => 'approve'],
        );
        $decisionPlans = $planner->plans($decision);

        $this->assertCount(1, $decisionPlans);
        $this->assertSame($requesterId, $decisionPlans[0]->recipientId);
        $this->assertSame('request_decision_recorded', $decisionPlans[0]->templateKey);
        $this->assertSame(['database'], $decisionPlans[0]->channels);

        $warning = app(RequestOutboxAppender::class)->append(
            'request.task.sla_warning.v1',
            'request_task',
            $task->public_id,
            (string) Str::uuid(),
            ['due_at' => $task->due_at?->toIso8601String()],
        );
        $warningPlans = $planner->plans($warning);

        $this->assertCount(1, $warningPlans);
        $this->assertSame($approverId, $warningPlans[0]->recipientId);
        $this->assertSame('approval_sla_warning', $warningPlans[0]->templateKey);
        $this->assertSame(['database'], $warningPlans[0]->channels);

        Queue::fake();
        $states = $this->mock(ModuleStateRepository::class);
        $states->shouldReceive('get')->with('Request')->andReturn(true);
        $dispatcher = app(RequestOutboxDispatcher::class);
        foreach ([$assignment, $decision, $warning] as $outbox) {
            $this->assertTrue($dispatcher->dispatchOne($outbox->public_id));
        }

        $this->assertSame(3, RequestNotificationDelivery::query()->where('channel', 'database')->count());
        $this->assertSame(0, RequestNotificationDelivery::query()->where('channel', 'email')->count());
        Queue::assertPushed(DeliverRequestNotification::class, 3);
    }
}
