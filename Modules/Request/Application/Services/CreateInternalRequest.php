<?php

namespace Modules\Request\Application\Services;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Request\Domain\Enums\AudienceCapability;
use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Domain\Enums\RequestTypeStatus;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestType;
use Modules\User\Contracts\UserDirectory;

final class CreateInternalRequest
{
    public function __construct(private readonly RequestAudienceService $audience, private readonly UserDirectory $users, private readonly RequestNumberGenerator $numbers, private readonly IdempotentCommandExecutor $idempotency, private readonly RequestAuditAppender $audit, private readonly RequestOutboxAppender $outbox) {}

    public function handle(RequestType $type, int $actorId, string $idempotencyKey): InternalRequest
    {
        $response = $this->idempotency->execute($actorId, 'request.draft.create', $type->public_id, $idempotencyKey, ['type' => $type->public_id], function (string $correlationId, string $keyHash) use ($type, $actorId): array {
            $lockedType = RequestType::query()->with('currentPublishedVersion')->lockForUpdate()->findOrFail($type->id);
            $version = $lockedType->currentPublishedVersion;
            $now = now('UTC');
            if ($lockedType->status !== RequestTypeStatus::Published || ! $version || ($lockedType->available_from && $lockedType->available_from->isFuture()) || ($lockedType->available_until && $lockedType->available_until->lte($now)) || ! $this->audience->can($version, $actorId, AudienceCapability::Create)) {
                throw ValidationException::withMessages(['type' => ['request_type_unavailable']]);
            }
            $identity = $this->users->findActive($actorId);
            if (! $identity) {
                throw ValidationException::withMessages(['requester' => ['requester_unavailable']]);
            }

            $temporaryId = (string) Str::ulid();
            $request = InternalRequest::query()->create([
                'request_number' => $this->numbers->temporary($temporaryId),
                'request_type_id' => $lockedType->id,
                'request_type_version_id' => $version->id,
                'requester_id' => $actorId,
                'status' => RequestStatus::Draft,
                'title_snapshot' => $version->title,
                'requester_snapshot_json' => ['id' => $identity->id, 'display_name' => $identity->displayName],
                'lock_version' => 1,
            ]);
            $request->update(['request_number' => $this->numbers->forId($request->id)]);
            $this->audit->append('request_instance', $request->public_id, 'request.draft.created.v1', $actorId, $correlationId, ['type_public_id' => $lockedType->public_id], $keyHash);
            $this->outbox->append('request.draft.created.v1', 'request_instance', $request->public_id, $correlationId);

            return ['request_public_id' => $request->public_id];
        });

        return InternalRequest::query()->where('public_id', $response['request_public_id'])->firstOrFail();
    }
}
