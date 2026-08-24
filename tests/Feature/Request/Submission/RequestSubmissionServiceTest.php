<?php

namespace Tests\Feature\Request\Submission;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Request\Application\Queries\ApproverInboxQuery;
use Modules\Request\Application\Services\CreateInternalRequest;
use Modules\Request\Application\Services\DecideRequestTask;
use Modules\Request\Application\Services\SaveRequestDraft;
use Modules\Request\Application\Services\SubmitInternalRequest;
use Modules\Request\Domain\Enums\PayloadSource;
use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Domain\Enums\RunStatus;
use Modules\Request\Domain\Enums\TaskStatus;
use Modules\Request\Models\RequestDecision;
use Modules\Request\Models\RequestPayloadRevision;
use Modules\Request\Models\RequestRun;
use Modules\Request\Models\RequestTask;
use Tests\Feature\Request\Draft\RequestDraftTestCase;

class RequestSubmissionServiceTest extends RequestDraftTestCase
{
    public function test_submit_is_atomic_idempotent_and_activates_first_single_stage(): void
    {
        [$requesterId, $approverId, $request] = $this->savedDraft();
        $key = (string) Str::uuid();
        $service = app(SubmitInternalRequest::class);

        $submitted = $service->handle($request, $requesterId, 2, $key);
        $replayed = $service->handle($request, $requesterId, 2, $key);

        $this->assertSame($submitted->id, $replayed->id);
        $this->assertSame(RequestStatus::Pending, $submitted->status);
        $this->assertSame(3, $submitted->lock_version);
        $this->assertNotNull($submitted->current_payload_revision_id);
        $this->assertNotNull($submitted->current_run_id);
        $this->assertSame(1, RequestRun::query()->count());
        $this->assertSame(1, RequestTask::query()->count());
        $this->assertSame($approverId, RequestTask::query()->value('assignee_user_id'));
        $this->assertSame(PayloadSource::Submit, RequestPayloadRevision::query()->latest('id')->firstOrFail()->source);
        $this->assertDatabaseHas('request_audit_events', ['request_instance_id' => $request->id, 'event_key' => 'request.instance.submitted.v1']);
        $this->assertDatabaseHas('request_outbox_messages', ['aggregate_public_id' => $request->public_id, 'event_key' => 'request.instance.submitted.v1']);
    }

    public function test_submit_validation_rolls_back_schema_self_candidate_and_stale_failures(): void
    {
        $requesterId = $this->activeUser('Requester');
        $type = $this->publishedType($requesterId, $this->simpleSchema(), $requesterId, [$this->fixedStage(1, $requesterId)]);
        $request = app(CreateInternalRequest::class)->handle($type, $requesterId, (string) Str::uuid());
        app(SaveRequestDraft::class)->handle($request, ['subject' => 'Ready'], $requesterId, 1, (string) Str::uuid());

        foreach ([1, 2] as $expectedVersion) {
            try {
                app(SubmitInternalRequest::class)->handle($request, $requesterId, $expectedVersion, (string) Str::uuid());
                $this->fail('Submission must fail safely.');
            } catch (ValidationException $exception) {
                $this->assertNotEmpty($exception->errors());
            }
        }

        $this->assertSame(0, RequestRun::query()->count());
        $this->assertSame(0, RequestTask::query()->count());
        $this->assertSame(1, RequestPayloadRevision::query()->count());
        $this->assertSame(RequestStatus::Draft, $request->refresh()->status);
    }

    public function test_same_submit_key_with_different_fingerprint_conflicts(): void
    {
        [$requesterId, , $request] = $this->savedDraft();
        $key = (string) Str::uuid();
        app(SubmitInternalRequest::class)->handle($request, $requesterId, 2, $key);

        try {
            app(SubmitInternalRequest::class)->handle($request, $requesterId, 99, $key);
            $this->fail('Different fingerprint must conflict.');
        } catch (ValidationException $exception) {
            $this->assertSame(['idempotency_conflict'], $exception->errors()['idempotency_key']);
        }
        $this->assertSame(1, RequestRun::query()->count());
    }

    public function test_two_sequential_single_stages_approve_in_order_and_snapshot_late_role_membership(): void
    {
        $requesterId = $this->activeUser('Requester');
        $firstApproverId = $this->activeUser('First approver');
        $lateApproverId = $this->activeUser('Late role approver');
        $roleId = $this->adminRole('Late approvers');
        $type = $this->publishedType($requesterId, $this->simpleSchema(), $requesterId, [$this->fixedStage(1, $firstApproverId), ['stage_key' => 'finance', 'name' => 'Finance', 'position' => 2, 'mode' => 'single', 'resolver_key' => 'role_members', 'resolver_config_json' => ['role_id' => $roleId], 'allow_reassignment' => false]]);
        $request = app(CreateInternalRequest::class)->handle($type, $requesterId, (string) Str::uuid());
        app(SaveRequestDraft::class)->handle($request, ['subject' => 'Sequential'], $requesterId, 1, (string) Str::uuid());
        app(SubmitInternalRequest::class)->handle($request, $requesterId, 2, (string) Str::uuid());
        $this->assertDatabaseMissing('request_tasks', ['stage_position' => 2]);

        DB::table('model_has_roles')->insert(['role_id' => $roleId, 'model_type' => User::class, 'model_id' => $lateApproverId]);
        $firstTask = RequestTask::query()->where('stage_position', 1)->firstOrFail();
        app(DecideRequestTask::class)->approve($firstTask, $firstApproverId, 3, 1, (string) Str::uuid());
        $secondTask = RequestTask::query()->where('stage_position', 2)->firstOrFail();

        $this->assertSame($lateApproverId, $secondTask->assignee_user_id);
        $this->assertSame($lateApproverId, $secondTask->candidates()->value('user_id'));
        $this->assertSame(RequestStatus::Pending, $request->refresh()->status);
        app(DecideRequestTask::class)->approve($secondTask, $lateApproverId, 4, 1, (string) Str::uuid());

        $this->assertSame(RequestStatus::Approved, $request->refresh()->status);
        $this->assertSame(RunStatus::Approved, $request->currentRun->status);
        $this->assertSame(2, RequestDecision::query()->count());
        $this->assertSame(2, RequestTask::query()->where('status', TaskStatus::Approved)->count());
    }

    public function test_non_candidate_requester_wrong_stage_and_duplicate_decision_fail_safely(): void
    {
        [$requesterId, $approverId, $request] = $this->savedDraft();
        app(SubmitInternalRequest::class)->handle($request, $requesterId, 2, (string) Str::uuid());
        $task = RequestTask::query()->firstOrFail();

        foreach ([$requesterId, $this->activeUser('Outsider')] as $actorId) {
            try {
                app(DecideRequestTask::class)->approve($task, $actorId, 3, 1, (string) Str::uuid());
                $this->fail('Unauthorized actor must fail.');
            } catch (ValidationException $exception) {
                $this->assertSame(['task_not_actionable'], $exception->errors()['task']);
            }
        }

        $key = (string) Str::uuid();
        $first = app(DecideRequestTask::class)->approve($task, $approverId, 3, 1, $key);
        $replayed = app(DecideRequestTask::class)->approve($task, $approverId, 3, 1, $key);
        $this->assertSame($first->id, $replayed->id);
        $this->assertSame(1, RequestDecision::query()->count());

        $this->expectException(ValidationException::class);
        app(DecideRequestTask::class)->approve($task, $approverId, 3, 1, (string) Str::uuid());
    }

    public function test_form_user_field_resolver_uses_only_validated_payload_identity(): void
    {
        $requesterId = $this->activeUser('Requester');
        $approverId = $this->activeUser('Selected approver');
        $schema = ['schema_version' => 1, 'sections' => [[
            'key' => 'details',
            'fields' => [
                ['key' => 'subject', 'type' => 'text', 'required' => true],
                ['key' => 'approver', 'type' => 'user', 'required' => true],
            ],
        ]]];
        $stage = ['stage_key' => 'selected', 'name' => 'Selected approver', 'position' => 1, 'mode' => 'single', 'resolver_key' => 'form_user_field', 'resolver_config_json' => ['field_key' => 'approver'], 'allow_reassignment' => false];
        $type = $this->publishedType($requesterId, $schema, $requesterId, [$stage]);
        $request = app(CreateInternalRequest::class)->handle($type, $requesterId, (string) Str::uuid());
        app(SaveRequestDraft::class)->handle($request, ['subject' => 'Select', 'approver' => $approverId], $requesterId, 1, (string) Str::uuid());
        app(SubmitInternalRequest::class)->handle($request, $requesterId, 2, (string) Str::uuid());

        $this->assertSame($approverId, RequestTask::query()->value('assignee_user_id'));
        $this->assertDatabaseHas('request_task_candidates', ['user_id' => $approverId, 'source_type' => 'form_user_field', 'source_reference' => 'approver']);
    }

    public function test_inbox_query_count_is_bounded_for_twenty_five_tasks(): void
    {
        $requesterId = $this->activeUser('Requester');
        $approverId = $this->activeUser('Approver');
        $type = $this->publishedType($requesterId, $this->simpleSchema(), $requesterId, [$this->fixedStage(1, $approverId)]);
        foreach (range(1, 25) as $index) {
            $request = app(CreateInternalRequest::class)->handle($type, $requesterId, (string) Str::uuid());
            app(SaveRequestDraft::class)->handle($request, ['subject' => 'Request '.$index], $requesterId, 1, (string) Str::uuid());
            app(SubmitInternalRequest::class)->handle($request, $requesterId, 2, (string) Str::uuid());
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $tasks = app(ApproverInboxQuery::class)->paginate($approverId, '', 25);
        $tasks->items();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertCount(25, $tasks);
        $this->assertLessThanOrEqual(15, $queryCount);
    }

    private function savedDraft(): array
    {
        $requesterId = $this->activeUser('Requester');
        $approverId = $this->activeUser('Approver');
        $type = $this->publishedType($requesterId, $this->simpleSchema(), $requesterId, [$this->fixedStage(1, $approverId)]);
        $request = app(CreateInternalRequest::class)->handle($type, $requesterId, (string) Str::uuid());
        app(SaveRequestDraft::class)->handle($request, ['subject' => 'Ready'], $requesterId, 1, (string) Str::uuid());

        return [$requesterId, $approverId, $request->refresh()];
    }

    private function fixedStage(int $position, int $userId): array
    {
        return ['stage_key' => 'approval_'.$position, 'name' => 'Approval '.$position, 'position' => $position, 'mode' => 'single', 'resolver_key' => 'fixed_users', 'resolver_config_json' => ['user_ids' => [$userId]], 'allow_reassignment' => false];
    }
}
