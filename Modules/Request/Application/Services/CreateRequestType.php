<?php

namespace Modules\Request\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Request\Domain\Enums\RequestTypeStatus;
use Modules\Request\Domain\Enums\RequestTypeVersionStatus;
use Modules\Request\Models\RequestType;
use Modules\Request\Models\RequestTypeVersion;

final class CreateRequestType
{
    public function __construct(private readonly RequestAuditAppender $audit) {}

    public function handle(array $data, int $actorId): RequestType
    {
        return DB::transaction(function () use ($data, $actorId): RequestType {
            $type = RequestType::query()->create([
                'request_group_id' => $data['request_group_id'], 'code' => $data['code'], 'name' => $data['name'],
                'summary' => $data['summary'] ?? null, 'status' => RequestTypeStatus::Draft,
                'sort_order' => $data['sort_order'] ?? 0, 'created_by' => $actorId, 'updated_by' => $actorId,
            ]);
            $draft = RequestTypeVersion::query()->create([
                'request_type_id' => $type->id, 'version_number' => 1, 'status' => RequestTypeVersionStatus::Draft,
                'title' => $data['name'], 'form_schema_json' => ['schema_version' => 1, 'sections' => []],
                'policy_json' => [], 'presentation_json' => [], 'created_by' => $actorId, 'updated_by' => $actorId,
            ]);
            $type->forceFill(['active_draft_version_id' => $draft->id])->save();
            $this->audit->append('request_type', $type->public_id, 'request.type.created.v1', $actorId, (string) Str::uuid());

            return $type->load('activeDraft');
        });
    }
}
