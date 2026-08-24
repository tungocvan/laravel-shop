<?php

namespace Modules\Request\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Request\Domain\Enums\IdempotencyStatus;
use Modules\Request\Domain\Forms\DefinitionCanonicalizer;
use Modules\Request\Models\RequestIdempotencyKey;

final class IdempotentCommandExecutor
{
    public function __construct(private readonly DefinitionCanonicalizer $canonicalizer) {}

    public function execute(int $actorId, string $command, string $aggregatePublicId, string $key, array $fingerprint, callable $operation): array
    {
        $key = trim($key);
        if (strlen($key) < 8 || strlen($key) > 200) {
            throw ValidationException::withMessages(['idempotency_key' => ['invalid_idempotency_key']]);
        }

        return DB::transaction(function () use ($actorId, $command, $aggregatePublicId, $key, $fingerprint, $operation): array {
            $keyHash = hash_hmac('sha256', $key, (string) config('app.key'));
            $fingerprintHash = $this->canonicalizer->checksum($fingerprint);
            $correlationId = (string) Str::uuid();
            $record = RequestIdempotencyKey::query()->createOrFirst(
                ['actor_id' => $actorId, 'command_key' => $command, 'aggregate_public_id' => $aggregatePublicId, 'key_hash' => $keyHash],
                ['request_fingerprint_hash' => $fingerprintHash, 'status' => IdempotencyStatus::Processing, 'correlation_id' => $correlationId, 'locked_at' => now('UTC'), 'expires_at' => now('UTC')->addDay()],
            );
            $created = $record->wasRecentlyCreated;
            $record = RequestIdempotencyKey::query()->lockForUpdate()->findOrFail($record->id);

            if (! hash_equals($record->request_fingerprint_hash, $fingerprintHash)) {
                throw ValidationException::withMessages(['idempotency_key' => ['idempotency_conflict']]);
            }
            if ($record->status === IdempotencyStatus::Completed) {
                return (array) $record->response_reference_json;
            }
            if (! $created && $record->status === IdempotencyStatus::Processing) {
                throw ValidationException::withMessages(['idempotency_key' => ['idempotency_in_progress']]);
            }

            $response = $operation($correlationId, $keyHash);
            $record->update(['status' => IdempotencyStatus::Completed, 'response_code' => 200, 'response_reference_json' => $response, 'completed_at' => now('UTC')]);

            return $response;
        }, 3);
    }
}
