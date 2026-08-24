<?php

namespace Modules\Request\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Request\Domain\Approval\ApprovalStageActivator;
use Modules\Request\Domain\Enums\PayloadSource;
use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Domain\Enums\RunStatus;
use Modules\Request\Domain\Forms\DefinitionCanonicalizer;
use Modules\Request\Domain\Forms\FormPayloadValidator;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestPayloadRevision;
use Modules\Request\Models\RequestRun;

final class ResubmitInternalRequest
{
    public function __construct(private readonly FormPayloadValidator $payloads, private readonly DefinitionCanonicalizer $canonicalizer, private readonly ApprovalStageActivator $activator, private readonly IdempotentCommandExecutor $idempotency, private readonly RequestAuditAppender $audit, private readonly RequestOutboxAppender $outbox) {}

    public function handle(InternalRequest $request, array $payload, int $actorId, int $expectedVersion, string $idempotencyKey): InternalRequest
    {
        $response = DB::transaction(function () use ($request, $payload, $actorId, $expectedVersion, $idempotencyKey): array {
            $locked = InternalRequest::query()->with('typeVersion')->lockForUpdate()->findOrFail($request->id);

            return $this->idempotency->execute($actorId, 'request.resubmit', $locked->public_id, $idempotencyKey, ['expected_version' => $expectedVersion, 'payload' => $payload], function (string $correlationId, string $keyHash) use ($locked, $payload, $actorId, $expectedVersion): array {
                if ($locked->requester_id !== $actorId || $locked->status !== RequestStatus::Returned) {
                    throw ValidationException::withMessages(['request' => ['request_not_resubmittable']]);
                }
                if ($locked->lock_version !== $expectedVersion) {
                    throw ValidationException::withMessages(['lock_version' => ['stale_version']]);
                }
                $validated = $this->payloads->validate((array) $locked->typeVersion->form_schema_json, $payload, true, $locked);
                if ($validated['errors'] !== []) {
                    throw ValidationException::withMessages($validated['errors']);
                }
                $now = now('UTC');
                $revision = RequestPayloadRevision::query()->create(['request_instance_id' => $locked->id, 'revision_number' => ((int) $locked->payloadRevisions()->max('revision_number')) + 1, 'request_type_version_id' => $locked->request_type_version_id, 'payload_json' => $validated['payload'], 'display_snapshot_json' => $validated['display'], 'payload_checksum' => $this->canonicalizer->checksum($validated['payload']), 'schema_version' => $locked->typeVersion->schema_version, 'source' => PayloadSource::Resubmit, 'created_by' => $actorId]);
                $run = RequestRun::query()->create(['request_instance_id' => $locked->id, 'sequence_number' => ((int) $locked->runs()->max('sequence_number')) + 1, 'request_type_version_id' => $locked->request_type_version_id, 'request_payload_revision_id' => $revision->id, 'status' => RunStatus::Active, 'started_by' => $actorId, 'started_at' => $now]);
                $this->activator->activate($locked, $run, 1, $validated['payload'], $actorId, $correlationId, $keyHash);
                $locked->update(['status' => RequestStatus::Pending, 'current_payload_revision_id' => $revision->id, 'current_run_id' => $run->id, 'submitted_at' => $now, 'returned_at' => null, 'lock_version' => $locked->lock_version + 1]);
                $this->audit->append('request_instance', $locked->public_id, 'request.resubmitted.v1', $actorId, $correlationId, ['run_public_id' => $run->public_id, 'run_sequence' => $run->sequence_number], $keyHash, $locked->id);
                $this->outbox->append('request.resubmitted.v1', 'request_instance', $locked->public_id, $correlationId, ['run_public_id' => $run->public_id, 'run_sequence' => $run->sequence_number]);

                return ['request_public_id' => $locked->public_id];
            });
        }, 3);

        return InternalRequest::query()->where('public_id', $response['request_public_id'])->firstOrFail();
    }
}
