<?php

namespace Modules\Request\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Request\Domain\Enums\RequestTypeVersionStatus;
use Modules\Request\Models\RequestType;
use Modules\Request\Models\RequestTypeVersion;

final class SaveTypeDraft
{
    public function __construct(private readonly RequestAuditAppender $audit) {}

    public function handle(RequestType $type, array $data, int $actorId, int $expectedVersion): RequestTypeVersion
    {
        return DB::transaction(function () use ($type, $data, $actorId, $expectedVersion): RequestTypeVersion {
            $lockedType = RequestType::query()->lockForUpdate()->findOrFail($type->id);
            if ($lockedType->lock_version !== $expectedVersion) {
                throw ValidationException::withMessages(['lock_version' => 'stale_version']);
            }
            $draft = RequestTypeVersion::query()->lockForUpdate()->findOrFail($lockedType->active_draft_version_id);
            if ($draft->status !== RequestTypeVersionStatus::Draft) {
                throw ValidationException::withMessages(['version' => 'draft_required']);
            }

            $draft->update([
                'title' => $data['title'], 'description' => $data['description'] ?? null,
                'requester_guidance' => $data['requester_guidance'] ?? null,
                'form_schema_json' => $data['form_schema_json'], 'policy_json' => $data['policy_json'] ?? [],
                'presentation_json' => $data['presentation_json'] ?? [], 'updated_by' => $actorId,
            ]);
            $draft->audiences()->delete();
            foreach ((array) ($data['audiences'] ?? []) as $audience) {
                $draft->audiences()->create([
                    'actor_type' => $audience['actor_type'],
                    'actor_id' => $audience['actor_id'],
                    'capability' => $audience['capability'],
                ]);
            }
            $draft->stages()->delete();
            foreach ((array) ($data['stages'] ?? []) as $stage) {
                $draft->stages()->create([
                    'stage_key' => $stage['stage_key'],
                    'name' => $stage['name'],
                    'position' => $stage['position'],
                    'mode' => $stage['mode'],
                    'resolver_key' => $stage['resolver_key'],
                    'resolver_config_json' => $stage['resolver_config_json'],
                    'instructions' => $stage['instructions'] ?? null,
                    'allow_reassignment' => $stage['allow_reassignment'] ?? false,
                ]);
            }
            $lockedType->increment('lock_version');
            $lockedType->update(['updated_by' => $actorId]);
            $this->audit->append('request_type', $lockedType->public_id, 'request.type.draft_saved.v1', $actorId, (string) Str::uuid());

            return $draft->load(['audiences', 'stages']);
        });
    }
}
