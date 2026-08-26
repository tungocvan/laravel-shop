<?php

namespace Modules\Request\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Request\Domain\Enums\RequestTypeStatus;
use Modules\Request\Domain\Enums\RequestTypeVersionStatus;
use Modules\Request\Models\RequestType;
use Modules\Request\Models\RequestTypeVersion;

final class DuplicateRequestType
{
    public function __construct(private readonly RequestAuditAppender $audit) {}

    public function handle(RequestType $sourceType, array $data, int $actorId, bool $copyAudience): RequestType
    {
        return DB::transaction(function () use ($sourceType, $data, $actorId, $copyAudience): RequestType {
            $sourceType = RequestType::query()->lockForUpdate()->findOrFail($sourceType->id);
            $source = $sourceType->activeDraft ?? $sourceType->currentPublishedVersion;
            if (! $source) {
                throw ValidationException::withMessages(['duplicateType' => 'Không tìm thấy phiên bản nguồn để nhân bản.']);
            }
            $source->load(['audiences', 'stages']);

            $type = RequestType::query()->create([
                'request_group_id' => $data['request_group_id'], 'code' => $data['code'], 'name' => $data['name'],
                'summary' => $sourceType->summary, 'status' => RequestTypeStatus::Draft,
                'sort_order' => $sourceType->sort_order, 'created_by' => $actorId, 'updated_by' => $actorId,
            ]);
            $draft = RequestTypeVersion::query()->create([
                'request_type_id' => $type->id, 'version_number' => 1, 'status' => RequestTypeVersionStatus::Draft,
                'title' => $data['name'], 'description' => $source->description,
                'requester_guidance' => $source->requester_guidance, 'form_schema_json' => $source->form_schema_json,
                'policy_json' => $source->policy_json, 'presentation_json' => $source->presentation_json,
                'schema_version' => $source->schema_version, 'created_by' => $actorId, 'updated_by' => $actorId,
            ]);
            if ($copyAudience) {
                foreach ($source->audiences as $audience) {
                    $draft->audiences()->create($audience->only(['actor_type', 'actor_id', 'capability']));
                }
            }
            foreach ($source->stages as $stage) {
                $draft->stages()->create($stage->only([
                    'stage_key', 'name', 'position', 'mode', 'resolver_key', 'resolver_config_json', 'instructions',
                    'allow_reassignment', 'sla_minutes', 'warning_minutes_before', 'grace_minutes', 'timeout_action',
                    'email_on_assignment', 'email_on_decision', 'email_on_sla_warning',
                ]));
            }
            $type->forceFill(['active_draft_version_id' => $draft->id])->save();
            $this->audit->append('request_type', $type->public_id, 'request.type.duplicated.v1', $actorId, (string) Str::uuid(), [
                'source_type_public_id' => $sourceType->public_id, 'source_version' => $source->version_number,
                'audience_copied' => $copyAudience,
            ]);

            return $type->load('activeDraft');
        });
    }
}
