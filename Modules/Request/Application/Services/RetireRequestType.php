<?php

namespace Modules\Request\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Request\Domain\Enums\RequestTypeStatus;
use Modules\Request\Models\RequestType;

final class RetireRequestType
{
    public function __construct(private readonly RequestAuditAppender $audit, private readonly RequestOutboxAppender $outbox) {}

    public function handle(RequestType $type, int $actorId): RequestType
    {
        return DB::transaction(function () use ($type, $actorId): RequestType {
            $locked = RequestType::query()->lockForUpdate()->findOrFail($type->id);
            $correlationId = (string) Str::uuid();
            $locked->update(['status' => RequestTypeStatus::Retired, 'retired_by' => $actorId, 'retired_at' => now('UTC'), 'updated_by' => $actorId, 'lock_version' => $locked->lock_version + 1]);
            $this->audit->append('request_type', $locked->public_id, 'request.type.retired.v1', $actorId, $correlationId);
            $this->outbox->append('request.type.retired.v1', 'request_type', $locked->public_id, $correlationId);

            return $locked;
        });
    }
}
