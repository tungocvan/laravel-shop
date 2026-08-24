<?php

namespace Tests\Feature\Request\Lifecycle;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Request\Application\Services\CancelInternalRequest;
use Modules\Request\Application\Services\CreateInternalRequest;
use Modules\Request\Application\Services\DecideRequestTask;
use Modules\Request\Application\Services\ReassignRequestTask;
use Modules\Request\Application\Services\ResubmitInternalRequest;
use Modules\Request\Application\Services\RetryStageActivation;
use Modules\Request\Application\Services\SaveRequestDraft;
use Modules\Request\Application\Services\SubmitInternalRequest;
use Modules\Request\Domain\Enums\DecisionType;
use Modules\Request\Domain\Enums\PayloadSource;
use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Domain\Enums\RequestTypeStatus;
use Modules\Request\Domain\Enums\RunStatus;
use Modules\Request\Domain\Enums\TaskStatus;
use Modules\Request\Models\RequestDecision;
use Modules\Request\Models\RequestRun;
use Modules\Request\Models\RequestTask;
use Tests\Feature\Request\Draft\RequestDraftTestCase;

class RequestParallelRecoveryLifecycleTest extends RequestDraftTestCase
{
    public function test_parallel_all_waits_for_all_and_activates_next_stage_once(): void
    {
        [$requester, $approvers, $request] = $this->submitted('parallel_all', 2, true);
        $tasks = RequestTask::query()->where('stage_position', 1)->orderBy('id')->get();

        app(DecideRequestTask::class)->approve($tasks[0], $approvers[0], 3, 1, (string) Str::uuid());
        $this->assertSame(RequestStatus::Pending, $request->refresh()->status);
        $this->assertDatabaseMissing('request_tasks', ['stage_position' => 2]);

        app(DecideRequestTask::class)->approve($tasks[1], $approvers[1], 4, 1, (string) Str::uuid());
        $this->assertSame(1, RequestTask::query()->where('stage_position', 2)->count());
        $this->assertSame(5, $request->refresh()->lock_version);
        $this->assertNotSame($requester, RequestTask::query()->where('stage_position', 2)->value('assignee_user_id'));
    }

    public function test_parallel_any_approve_skips_peers_and_reject_waits_until_the_last_candidate(): void
    {
        [, $approvers, $request] = $this->submitted('parallel_any', 3);
        $tasks = RequestTask::query()->where('request_run_id', $request->current_run_id)->orderBy('id')->get();
        app(DecideRequestTask::class)->handle($tasks[0], DecisionType::Reject, 'Not suitable', $approvers[0], 3, 1, (string) Str::uuid());
        $this->assertSame(RequestStatus::Pending, $request->refresh()->status);

        app(DecideRequestTask::class)->approve($tasks[1], $approvers[1], 4, 1, (string) Str::uuid());
        $this->assertSame(RequestStatus::Approved, $request->refresh()->status);
        $this->assertSame(TaskStatus::Skipped, $tasks[2]->refresh()->status);
    }

    public function test_parallel_any_all_rejects_and_parallel_all_first_reject_cancels_peers(): void
    {
        [, $approvers, $request] = $this->submitted('parallel_any', 2);
        $tasks = RequestTask::query()->where('request_run_id', $request->current_run_id)->orderBy('id')->get();
        app(DecideRequestTask::class)->handle($tasks[0], DecisionType::Reject, 'No', $approvers[0], 3, 1, (string) Str::uuid());
        app(DecideRequestTask::class)->handle($tasks[1], DecisionType::Reject, 'Still no', $approvers[1], 4, 1, (string) Str::uuid());
        $this->assertSame(RequestStatus::Rejected, $request->refresh()->status);

        [, $approvers, $request] = $this->submitted('parallel_all', 2);
        $tasks = RequestTask::query()->where('request_run_id', $request->current_run_id)->orderBy('id')->get();
        app(DecideRequestTask::class)->handle($tasks[0], DecisionType::Reject, 'Stop', $approvers[0], 3, 1, (string) Str::uuid());
        $this->assertSame(RequestStatus::Rejected, $request->refresh()->status);
        $this->assertSame(TaskStatus::Cancelled, $tasks[1]->refresh()->status);
    }

    public function test_parallel_return_requires_reason_cancels_peers_and_resubmit_preserves_history_and_pinned_version(): void
    {
        [, $approvers, $request] = $this->submitted('parallel_all', 2);
        $task = RequestTask::query()->firstOrFail();
        try {
            app(DecideRequestTask::class)->handle($task, DecisionType::Return, '', $approvers[0], 3, 1, (string) Str::uuid());
            $this->fail('Return without a reason must fail.');
        } catch (ValidationException $exception) {
            $this->assertSame(['reason_required'], $exception->errors()['reason']);
        }
        app(DecideRequestTask::class)->handle($task, DecisionType::Return, 'Please revise', $approvers[0], 3, 1, (string) Str::uuid());
        $oldRunId = $request->refresh()->current_run_id;
        $pinnedVersion = $request->request_type_version_id;
        $this->assertSame(RequestStatus::Returned, $request->status);
        $this->assertSame(1, RequestTask::query()->where('status', TaskStatus::Cancelled)->count());

        $request->type()->update(['status' => RequestTypeStatus::Retired]);
        app(ResubmitInternalRequest::class)->handle($request, ['subject' => 'Revised'], $request->requester_id, 4, (string) Str::uuid());
        $request->refresh();
        $this->assertSame(RequestStatus::Pending, $request->status);
        $this->assertSame($pinnedVersion, $request->request_type_version_id);
        $this->assertNotSame($oldRunId, $request->current_run_id);
        $this->assertSame(2, RequestRun::query()->count());
        $this->assertSame(PayloadSource::Resubmit, $request->currentPayloadRevision->source);
        $this->assertSame(1, RequestDecision::query()->count());
    }

    public function test_reassignment_creates_linked_replacement_and_old_candidate_cannot_decide(): void
    {
        [, $approvers, $request] = $this->submitted('single', 1, false, true);
        $replacementUser = User::factory()->create(['is_active' => true]);
        $old = RequestTask::query()->firstOrFail();
        $replacement = app(ReassignRequestTask::class)->handle($old, $replacementUser->id, 'Approver unavailable', $approvers[0], 3, 1, (string) Str::uuid());

        $this->assertSame(TaskStatus::Reassigned, $old->refresh()->status);
        $this->assertSame($replacement->id, $old->replaced_by_task_id);
        $this->assertSame($old->id, $replacement->replaces_task_id);
        $this->assertFalse((bool) $old->candidates()->value('is_effective'));
        $this->expectException(ValidationException::class);
        app(DecideRequestTask::class)->approve($old, $approvers[0], $request->refresh()->lock_version, 2, (string) Str::uuid());
    }

    public function test_later_stage_activation_failure_is_visible_and_retry_activates_only_real_user(): void
    {
        $requester = $this->activeUser('Requester');
        $first = $this->activeUser('First');
        $later = User::factory()->create(['is_active' => true]);
        $type = $this->publishedType($requester, $this->simpleSchema(), $requester, [$this->stage(1, 'single', [$first]), $this->stage(2, 'single', [$later->id])]);
        $later->update(['is_active' => false]);
        $request = app(CreateInternalRequest::class)->handle($type, $requester, (string) Str::uuid());
        app(SaveRequestDraft::class)->handle($request, ['subject' => 'Recovery'], $requester, 1, (string) Str::uuid());
        app(SubmitInternalRequest::class)->handle($request, $requester, 2, (string) Str::uuid());
        app(DecideRequestTask::class)->approve(RequestTask::query()->firstOrFail(), $first, 3, 1, (string) Str::uuid());

        $this->assertSame(RunStatus::FailedActivation, $request->refresh()->currentRun->status);
        $this->assertDatabaseMissing('request_tasks', ['stage_position' => 2]);
        $later->update(['is_active' => true]);
        app(RetryStageActivation::class)->handle($request, $first, 4, (string) Str::uuid());
        $this->assertSame(RunStatus::Active, $request->refresh()->currentRun->status);
        $this->assertSame($later->id, RequestTask::query()->where('stage_position', 2)->value('assignee_user_id'));
    }

    public function test_pending_cancel_any_requires_reason_and_closes_run_and_tasks(): void
    {
        [, , $request] = $this->submitted('single', 1);
        $operator = $this->activeUser('Operator');
        $this->expectException(ValidationException::class);
        app(CancelInternalRequest::class)->handle($request, $operator, 3, (string) Str::uuid(), '', true);
    }

    public function test_pending_cancel_any_with_reason_closes_run_and_tasks(): void
    {
        [, , $request] = $this->submitted('single', 1);
        $operator = $this->activeUser('Operator');
        app(CancelInternalRequest::class)->handle($request, $operator, 3, (string) Str::uuid(), 'Operational cancellation', true);

        $this->assertSame(RequestStatus::Cancelled, $request->refresh()->status);
        $this->assertSame(RunStatus::Cancelled, $request->currentRun->status);
        $this->assertSame(TaskStatus::Cancelled, $request->currentRun->tasks()->firstOrFail()->status);
    }

    private function submitted(string $mode, int $approverCount, bool $nextStage = false, bool $allowReassignment = false): array
    {
        $requester = $this->activeUser('Requester');
        $approvers = collect(range(1, $approverCount))->map(fn (int $index): int => $this->activeUser('Approver '.$index))->all();
        $stages = [$this->stage(1, $mode, $approvers, $allowReassignment)];
        if ($nextStage) {
            $stages[] = $this->stage(2, 'single', [$this->activeUser('Next approver')]);
        }
        $type = $this->publishedType($requester, $this->simpleSchema(), $requester, $stages);
        $request = app(CreateInternalRequest::class)->handle($type, $requester, (string) Str::uuid());
        app(SaveRequestDraft::class)->handle($request, ['subject' => 'Lifecycle'], $requester, 1, (string) Str::uuid());
        app(SubmitInternalRequest::class)->handle($request, $requester, 2, (string) Str::uuid());

        return [$requester, $approvers, $request->refresh()];
    }

    private function stage(int $position, string $mode, array $users, bool $allowReassignment = false): array
    {
        return ['stage_key' => 'stage_'.$position, 'name' => 'Stage '.$position, 'position' => $position, 'mode' => $mode, 'resolver_key' => 'fixed_users', 'resolver_config_json' => ['user_ids' => $users], 'allow_reassignment' => $allowReassignment];
    }
}
