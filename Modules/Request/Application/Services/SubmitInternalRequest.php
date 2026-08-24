<?php

namespace Modules\Request\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Request\Domain\Approval\ApprovalStageActivator;
use Modules\Request\Domain\Enums\AudienceCapability;
use Modules\Request\Domain\Enums\PayloadSource;
use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Domain\Enums\RequestTypeStatus;
use Modules\Request\Domain\Enums\RunStatus;
use Modules\Request\Domain\Forms\DefinitionCanonicalizer;
use Modules\Request\Domain\Forms\FormPayloadValidator;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestPayloadRevision;
use Modules\Request\Models\RequestRun;

final class SubmitInternalRequest
{
    public function __construct(private readonly FormPayloadValidator $payloads, private readonly DefinitionCanonicalizer $canonicalizer, private readonly RequestAudienceService $audience, private readonly ApprovalStageActivator $activator, private readonly IdempotentCommandExecutor $idempotency, private readonly RequestAuditAppender $audit, private readonly RequestOutboxAppender $outbox) {}

    public function handle(InternalRequest $request, int $actorId, int $expectedVersion, string $idempotencyKey, ?array $payload = null): InternalRequest
    {
        $response = DB::transaction(function () use ($request, $actorId, $expectedVersion, $idempotencyKey, $payload): array {
            $locked = InternalRequest::query()->with(['type', 'typeVersion', 'latestPayloadRevision'])->lockForUpdate()->findOrFail($request->id);
            $fingerprintPayload = $payload === null ? ['payload_checksum' => $locked->latestPayloadRevision?->payload_checksum] : ['payload' => $payload];

            return $this->idempotency->execute($actorId, 'request.submit', $locked->public_id, $idempotencyKey, ['expected_version' => $expectedVersion] + $fingerprintPayload, function (string $correlationId, string $keyHash) use ($locked, $actorId, $expectedVersion, $payload): array {
                if ($locked->requester_id !== $actorId || $locked->status !== RequestStatus::Draft) {
                    throw ValidationException::withMessages(['request' => ['request_not_submittable']]);
                }
                if ($locked->lock_version !== $expectedVersion) {
                    throw ValidationException::withMessages(['lock_version' => ['stale_version']]);
                }
                $now = now('UTC');
                if ($locked->type->status !== RequestTypeStatus::Published || ($locked->type->available_from && $locked->type->available_from->isFuture()) || ($locked->type->available_until && $locked->type->available_until->lte($now)) || ! $this->audience->can($locked->typeVersion, $actorId, AudienceCapability::Create)) {
                    throw ValidationException::withMessages(['type' => ['request_type_unavailable']]);
                }
                $validated = $this->payloads->validate((array) $locked->typeVersion->form_schema_json, $payload ?? (array) ($locked->latestPayloadRevision?->payload_json ?? []), true, $locked);
                if ($validated['errors'] !== []) {
                    throw ValidationException::withMessages($validated['errors']);
                }

                $revision = RequestPayloadRevision::query()->create([
                    'request_instance_id' => $locked->id,
                    'revision_number' => ((int) $locked->payloadRevisions()->max('revision_number')) + 1,
                    'request_type_version_id' => $locked->request_type_version_id,
                    'payload_json' => $validated['payload'],
                    'display_snapshot_json' => $validated['display'],
                    'payload_checksum' => $this->canonicalizer->checksum($validated['payload']),
                    'schema_version' => $locked->typeVersion->schema_version,
                    'source' => PayloadSource::Submit,
                    'created_by' => $actorId,
                ]);
                $run = RequestRun::query()->create([
                    'request_instance_id' => $locked->id,
                    'sequence_number' => ((int) $locked->runs()->max('sequence_number')) + 1,
                    'request_type_version_id' => $locked->request_type_version_id,
                    'request_payload_revision_id' => $revision->id,
                    'status' => RunStatus::Active,
                    'started_by' => $actorId,
                    'started_at' => $now,
                ]);
                $this->activator->activate($locked, $run, 1, $validated['payload'], $actorId, $correlationId, $keyHash);
                $locked->update(['status' => RequestStatus::Pending, 'current_payload_revision_id' => $revision->id, 'current_run_id' => $run->id, 'submitted_at' => $now, 'lock_version' => $locked->lock_version + 1]);
                $this->audit->append('request_instance', $locked->public_id, 'request.instance.submitted.v1', $actorId, $correlationId, ['run_public_id' => $run->public_id, 'payload_revision_public_id' => $revision->public_id], $keyHash, $locked->id);
                $this->outbox->append('request.instance.submitted.v1', 'request_instance', $locked->public_id, $correlationId, ['run_public_id' => $run->public_id]);

                return ['request_public_id' => $locked->public_id, 'run_public_id' => $run->public_id, 'status' => RequestStatus::Pending->value];
            });
        }, 3);

        return InternalRequest::query()->where('public_id', $response['request_public_id'])->firstOrFail();
    }
}
