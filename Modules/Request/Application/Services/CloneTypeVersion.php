<?php

namespace Modules\Request\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Request\Domain\Enums\RequestTypeVersionStatus;
use Modules\Request\Models\RequestType;
use Modules\Request\Models\RequestTypeVersion;

final class CloneTypeVersion
{
    public function __construct(private readonly RequestAuditAppender $audit) {}

    public function handle(RequestType $type, RequestTypeVersion $source, int $actorId): RequestTypeVersion
    {
        return DB::transaction(function () use ($type, $source, $actorId): RequestTypeVersion {
            $lockedType = RequestType::query()->lockForUpdate()->findOrFail($type->id);
            if ($lockedType->active_draft_version_id !== null || $source->request_type_id !== $lockedType->id) {
                throw ValidationException::withMessages(['version' => 'active_draft_exists_or_source_invalid']);
            }
            $source->load(['audiences', 'stages']);
            $draft = RequestTypeVersion::query()->create([
                'request_type_id' => $lockedType->id, 'version_number' => ((int) $lockedType->versions()->max('version_number')) + 1,
                'status' => RequestTypeVersionStatus::Draft, 'title' => $source->title, 'description' => $source->description,
                'requester_guidance' => $source->requester_guidance, 'form_schema_json' => $source->form_schema_json,
                'policy_json' => $source->policy_json, 'presentation_json' => $source->presentation_json,
                'schema_version' => $source->schema_version, 'created_from_version_id' => $source->id,
                'created_by' => $actorId, 'updated_by' => $actorId,
            ]);
            foreach ($source->audiences as $audience) {
                $draft->audiences()->create($audience->only(['actor_type', 'actor_id', 'capability']));
            }
            foreach ($source->stages as $stage) {
                $draft->stages()->create($stage->only(['stage_key', 'name', 'position', 'mode', 'resolver_key', 'resolver_config_json', 'instructions', 'allow_reassignment']));
            }
            $lockedType->forceFill(['active_draft_version_id' => $draft->id, 'lock_version' => $lockedType->lock_version + 1, 'updated_by' => $actorId])->save();
            $this->audit->append('request_type', $lockedType->public_id, 'request.type.version_cloned.v1', $actorId, (string) Str::uuid(), ['source_version' => $source->version_number, 'draft_version' => $draft->version_number]);

            return $draft->load(['audiences', 'stages']);
        });
    }
}
