<?php

namespace Modules\Request\Domain\Approval;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Modules\Request\Application\Services\RequestAuditAppender;
use Modules\Request\Application\Services\RequestOutboxAppender;
use Modules\Request\Domain\Enums\RunStatus;
use Modules\Request\Domain\Enums\TaskStatus;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestRun;
use Modules\Request\Models\RequestStageDefinition;

final class ApprovalStageActivator
{
    public function __construct(private readonly ActorResolverRegistry $resolvers, private readonly RequestAuditAppender $audit, private readonly RequestOutboxAppender $outbox) {}

    public function activate(InternalRequest $request, RequestRun $run, int $position, array $payload, int $actorId, string $correlationId, ?string $keyHash = null): Collection
    {
        $stage = RequestStageDefinition::query()->where('request_type_version_id', $request->request_type_version_id)->where('position', $position)->first();
        if (! $stage) {
            throw ValidationException::withMessages(['stage' => ['stage_not_found']]);
        }
        $resolved = $this->resolvers->resolve($stage->resolver_key)->resolve(new ActorResolutionContext($request, $stage, $payload));
        $users = collect($resolved->users)->reject(fn ($user): bool => $user->id === $request->requester_id)->unique('id')->sortBy('id')->values();
        if ($users->isEmpty()) {
            throw ValidationException::withMessages(['stage' => ['self_approval_only']]);
        }
        if ($stage->mode->value === 'single' && $users->count() !== 1) {
            throw ValidationException::withMessages(['stage' => ['single_requires_one_candidate']]);
        }

        $now = now();
        $slaMinutes = $stage->sla_minutes;
        $warningMinutes = $stage->warning_minutes_before;
        $graceMinutes = $stage->grace_minutes ?? 0;
        $dueAt = $slaMinutes ? $now->copy()->addMinutes($slaMinutes) : null;
        $warningAt = $dueAt && $warningMinutes !== null ? $dueAt->copy()->subMinutes($warningMinutes) : null;
        $graceExpiresAt = $dueAt ? $dueAt->copy()->addMinutes($graceMinutes) : null;
        $slaSnapshot = $slaMinutes ? [
            'sla_minutes' => $slaMinutes,
            'warning_minutes_before' => $warningMinutes,
            'grace_minutes' => $graceMinutes,
            'timeout_action' => $stage->timeout_action ?? 'notify_only',
        ] : null;

        $tasks = $users->map(function ($user) use ($run, $stage, $resolved, $now, $slaSnapshot, $warningAt, $dueAt, $graceExpiresAt) {
            $task = $run->tasks()->create([
                'request_stage_definition_id' => $stage->id,
                'stage_key_snapshot' => $stage->stage_key,
                'stage_name_snapshot' => $stage->name,
                'stage_position' => $stage->position,
                'stage_mode' => $stage->mode,
                'status' => TaskStatus::Active,
                'assignee_user_id' => $user->id,
                'resolver_key_snapshot' => $stage->resolver_key,
                'resolver_source_snapshot_json' => ['source' => $resolved->source->value, 'reference' => $resolved->sourceReference],
                'sla_snapshot_json' => $slaSnapshot,
                'activated_at' => $now,
                'warning_at' => $warningAt,
                'due_at' => $dueAt,
                'grace_expires_at' => $graceExpiresAt,
            ]);
            $task->candidates()->create(['user_id' => $user->id, 'source_type' => $resolved->source, 'source_reference' => $resolved->sourceReference, 'user_snapshot_json' => ['id' => $user->id, 'display_name' => $user->displayName], 'is_effective' => true, 'created_at' => $now]);

            return $task;
        });
        $run->update(['status' => RunStatus::Active, 'current_stage_position' => $position, 'activation_error_code' => null, 'activation_failed_at' => null, 'last_activation_correlation_id' => $correlationId]);
        $this->audit->append('request_instance', $request->public_id, 'request.run.stage_activated.v1', $actorId, $correlationId, ['run_public_id' => $run->public_id, 'stage_position' => $position, 'candidate_count' => $tasks->count()], $keyHash, $request->id);
        $this->outbox->append('request.run.stage_activated.v1', 'request_instance', $request->public_id, $correlationId, ['run_public_id' => $run->public_id, 'stage_position' => $position, 'candidate_count' => $tasks->count()]);

        return $tasks;
    }
}
