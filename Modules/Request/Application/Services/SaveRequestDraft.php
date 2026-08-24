<?php

namespace Modules\Request\Application\Services;

use Illuminate\Validation\ValidationException;
use Modules\Request\Domain\Enums\PayloadSource;
use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Domain\Enums\RequestTypeStatus;
use Modules\Request\Domain\Forms\DefinitionCanonicalizer;
use Modules\Request\Domain\Forms\FormPayloadValidator;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestPayloadRevision;

final class SaveRequestDraft
{
    public function __construct(private readonly FormPayloadValidator $payloads, private readonly DefinitionCanonicalizer $canonicalizer, private readonly IdempotentCommandExecutor $idempotency, private readonly RequestAuditAppender $audit, private readonly RequestOutboxAppender $outbox) {}

    public function handle(InternalRequest $request, array $payload, int $actorId, int $expectedVersion, string $idempotencyKey): RequestPayloadRevision
    {
        $response = $this->idempotency->execute($actorId, 'request.draft.save', $request->public_id, $idempotencyKey, ['payload' => $payload, 'expected_version' => $expectedVersion], function (string $correlationId, string $keyHash) use ($request, $payload, $actorId, $expectedVersion): array {
            $locked = InternalRequest::query()->with(['typeVersion', 'type'])->lockForUpdate()->findOrFail($request->id);
            if ($locked->requester_id !== $actorId || $locked->status !== RequestStatus::Draft) {
                throw ValidationException::withMessages(['request' => ['draft_not_editable']]);
            }
            if ($locked->lock_version !== $expectedVersion) {
                throw ValidationException::withMessages(['lock_version' => ['stale_version']]);
            }
            $now = now('UTC');
            if ($locked->type->status !== RequestTypeStatus::Published || ($locked->type->available_from && $locked->type->available_from->isFuture()) || ($locked->type->available_until && $locked->type->available_until->lte($now))) {
                throw ValidationException::withMessages(['type' => ['request_type_unavailable']]);
            }
            $validated = $this->payloads->validate((array) $locked->typeVersion->form_schema_json, $payload);
            if ($validated['errors'] !== []) {
                throw ValidationException::withMessages($validated['errors']);
            }

            $revisionNumber = ((int) $locked->payloadRevisions()->max('revision_number')) + 1;
            $revision = RequestPayloadRevision::query()->create([
                'request_instance_id' => $locked->id,
                'revision_number' => $revisionNumber,
                'request_type_version_id' => $locked->request_type_version_id,
                'payload_json' => $validated['payload'],
                'display_snapshot_json' => $validated['display'],
                'payload_checksum' => $this->canonicalizer->checksum($validated['payload']),
                'schema_version' => $locked->typeVersion->schema_version,
                'source' => PayloadSource::ServerDraft,
                'created_by' => $actorId,
            ]);
            $locked->update(['lock_version' => $locked->lock_version + 1]);
            $this->audit->append('request_instance', $locked->public_id, 'request.draft.saved.v1', $actorId, $correlationId, ['revision' => $revisionNumber, 'changed_keys' => array_keys($validated['payload'])], $keyHash);
            $this->outbox->append('request.draft.saved.v1', 'request_instance', $locked->public_id, $correlationId, ['revision' => $revisionNumber]);

            return ['request_public_id' => $locked->public_id, 'revision_public_id' => $revision->public_id];
        });

        return RequestPayloadRevision::query()->where('public_id', $response['revision_public_id'])->firstOrFail();
    }
}
