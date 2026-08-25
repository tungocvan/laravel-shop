<?php

namespace Modules\Request\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Request\Domain\Enums\RequestTypeStatus;
use Modules\Request\Domain\Enums\RequestTypeVersionStatus;
use Modules\Request\Domain\Forms\DefinitionCanonicalizer;
use Modules\Request\Domain\Forms\RequestTypeDraftValidator;
use Modules\Request\Models\RequestType;
use Modules\Request\Models\RequestTypeVersion;

final class PublishTypeVersion
{
    public function __construct(private readonly RequestTypeDraftValidator $validator, private readonly DefinitionCanonicalizer $canonicalizer, private readonly RequestAuditAppender $audit, private readonly RequestOutboxAppender $outbox) {}

    public function handle(RequestType $type, int $actorId, int $expectedVersion, ?string $correlationId = null): RequestTypeVersion
    {
        return DB::transaction(function () use ($type, $actorId, $expectedVersion, $correlationId): RequestTypeVersion {
            $lockedType = RequestType::query()->lockForUpdate()->findOrFail($type->id);
            if ($lockedType->lock_version !== $expectedVersion) {
                throw ValidationException::withMessages(['lock_version' => 'stale_version']);
            }
            $draft = RequestTypeVersion::query()->lockForUpdate()->findOrFail($lockedType->active_draft_version_id);
            $draft->load(['audiences' => fn ($query) => $query->orderBy('id'), 'stages' => fn ($query) => $query->orderBy('id')]);
            $errors = $this->validator->errors($draft);
            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            $definition = [
                'schema' => $draft->form_schema_json,
                'policy' => $draft->policy_json,
                'presentation' => $draft->presentation_json,
                'audiences' => $draft->audiences->map->only(['actor_type', 'actor_id', 'capability'])->all(),
                'stages' => $draft->stages->map->only([
                    'stage_key', 'name', 'position', 'mode', 'resolver_key', 'resolver_config_json', 'allow_reassignment',
                    'sla_minutes', 'warning_minutes_before', 'grace_minutes', 'timeout_action',
                    'email_on_assignment', 'email_on_decision', 'email_on_sla_warning',
                ])->all(),
            ];
            $now = now('UTC');
            $correlationId ??= (string) Str::uuid();
            if ($lockedType->current_published_version_id) {
                DB::table('request_type_versions')
                    ->where('id', $lockedType->current_published_version_id)
                    ->update(['status' => RequestTypeVersionStatus::Superseded->value, 'updated_at' => $now]);
            }
            $draft->update(['status' => RequestTypeVersionStatus::Published, 'canonical_checksum' => $this->canonicalizer->checksum($definition), 'published_by' => $actorId, 'published_at' => $now, 'updated_by' => $actorId]);
            $lockedType->forceFill(['status' => RequestTypeStatus::Published, 'current_published_version_id' => $draft->id, 'active_draft_version_id' => null, 'lock_version' => $lockedType->lock_version + 1, 'updated_by' => $actorId])->save();
            $this->audit->append('request_type', $lockedType->public_id, 'request.type.published.v1', $actorId, $correlationId, ['version' => $draft->version_number, 'checksum' => $draft->canonical_checksum]);
            $this->outbox->append('request.type.published.v1', 'request_type', $lockedType->public_id, $correlationId, ['version' => $draft->version_number]);

            return $draft->refresh();
        }, 3);
    }
}
