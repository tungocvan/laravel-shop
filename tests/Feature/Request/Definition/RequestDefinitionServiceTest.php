<?php

namespace Tests\Feature\Request\Definition;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;
use Modules\Request\Application\Services\CloneTypeVersion;
use Modules\Request\Application\Services\CompareTypeVersions;
use Modules\Request\Application\Services\CreateRequestGroup;
use Modules\Request\Application\Services\CreateRequestType;
use Modules\Request\Application\Services\DeleteUnpublishedRequestType;
use Modules\Request\Application\Services\DuplicateRequestType;
use Modules\Request\Application\Services\PublishTypeVersion;
use Modules\Request\Application\Services\RetireRequestType;
use Modules\Request\Application\Services\SaveTypeDraft;
use Modules\Request\Domain\Enums\RequestTypeStatus;
use Modules\Request\Domain\Enums\RequestTypeVersionStatus;
use Modules\Request\Models\RequestAuditEvent;
use Modules\Request\Models\RequestOutboxMessage;

class RequestDefinitionServiceTest extends RequestDefinitionTestCase
{
    public function test_authorized_service_path_creates_saves_and_publishes_atomically(): void
    {
        $actorId = $this->user('Publisher');
        $group = app(CreateRequestGroup::class)->handle(['code' => 'GENERAL', 'name' => 'General'], $actorId);
        $type = app(CreateRequestType::class)->handle(['request_group_id' => $group->id, 'code' => 'LEAVE', 'name' => 'Leave request'], $actorId);

        app(SaveTypeDraft::class)->handle($type, $this->validDraft($actorId), $actorId, 1);
        $published = app(PublishTypeVersion::class)->handle($type->refresh(), $actorId, 2, 'test-correlation');

        $this->assertSame(RequestTypeVersionStatus::Published, $published->status);
        $this->assertSame(64, strlen($published->canonical_checksum));
        $this->assertSame(RequestTypeStatus::Published, $type->refresh()->status);
        $this->assertNull($type->active_draft_version_id);
        $this->assertSame($published->id, $type->current_published_version_id);
        $this->assertDatabaseHas('request_audit_events', ['event_key' => 'request.type.published.v1', 'aggregate_public_id' => $type->public_id]);
        $this->assertDatabaseHas('request_outbox_messages', ['event_key' => 'request.type.published.v1', 'aggregate_public_id' => $type->public_id]);
        $this->assertSame(1, RequestAuditEvent::query()->where('event_key', 'request.type.published.v1')->count());
        $this->assertSame(1, RequestOutboxMessage::query()->where('event_key', 'request.type.published.v1')->count());
    }

    public function test_invalid_draft_rolls_back_publication_without_audit_or_outbox(): void
    {
        $actorId = $this->user('Publisher');
        $group = app(CreateRequestGroup::class)->handle(['code' => 'INVALID', 'name' => 'Invalid'], $actorId);
        $type = app(CreateRequestType::class)->handle(['request_group_id' => $group->id, 'code' => 'INVALID_TYPE', 'name' => 'Invalid type'], $actorId);

        try {
            app(PublishTypeVersion::class)->handle($type, $actorId, 1);
            $this->fail('Invalid draft must not publish.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('stages', $exception->errors());
        }

        $this->assertSame(RequestTypeVersionStatus::Draft, $type->activeDraft->refresh()->status);
        $this->assertDatabaseMissing('request_outbox_messages', ['aggregate_public_id' => $type->public_id]);
    }

    public function test_published_version_is_immutable_and_clone_creates_independent_draft(): void
    {
        [$actorId, $type, $published] = $this->publishedType();
        $draft = app(CloneTypeVersion::class)->handle($type->refresh(), $published, $actorId);

        $this->assertSame(2, $draft->version_number);
        $this->assertSame($published->id, $draft->created_from_version_id);
        $this->assertSame(RequestTypeVersionStatus::Draft, $draft->status);
        $this->assertFalse(app(CompareTypeVersions::class)->handle($published, $draft)['changed']);

        $draft->stages()->firstOrFail()->update(['name' => 'Changed approval']);
        $this->assertTrue(app(CompareTypeVersions::class)->handle($published, $draft)['changed']);

        $this->expectException(LogicException::class);
        $published->update(['title' => 'Mutated']);
    }

    public function test_type_duplication_creates_an_independent_v1_draft_with_optional_audience_copy(): void
    {
        $actorId = $this->user('Duplicator');
        $group = app(CreateRequestGroup::class)->handle(['code' => 'DUPLICATE', 'name' => 'Duplicate'], $actorId);
        $source = app(CreateRequestType::class)->handle(['request_group_id' => $group->id, 'code' => 'SOURCE', 'name' => 'Source'], $actorId);
        app(SaveTypeDraft::class)->handle($source, $this->validDraft($actorId), $actorId, 1);

        $copy = app(DuplicateRequestType::class)->handle($source->refresh(), [
            'request_group_id' => $group->id, 'code' => 'SOURCE_COPY', 'name' => 'Source copy',
        ], $actorId, true);

        $this->assertSame(RequestTypeStatus::Draft, $copy->status);
        $this->assertNull($copy->current_published_version_id);
        $this->assertSame(1, $copy->activeDraft->version_number);
        $this->assertSame('Source copy', $copy->activeDraft->title);
        $this->assertSame(1, $copy->activeDraft->audiences()->count());
        $this->assertSame(1, $copy->activeDraft->stages()->count());
        $this->assertDatabaseHas('request_audit_events', ['event_key' => 'request.type.duplicated.v1', 'aggregate_public_id' => $copy->public_id]);
    }

    public function test_only_never_published_unused_type_can_be_deleted(): void
    {
        $actorId = $this->user('Definition deleter');
        $group = app(CreateRequestGroup::class)->handle(['code' => 'DELETE', 'name' => 'Delete'], $actorId);
        $draft = app(CreateRequestType::class)->handle(['request_group_id' => $group->id, 'code' => 'DELETE_ME', 'name' => 'Delete me'], $actorId);
        $publicId = $draft->public_id;

        app(DeleteUnpublishedRequestType::class)->handle($draft, $actorId);

        $this->assertDatabaseMissing('request_types', ['public_id' => $publicId]);
        $this->assertDatabaseHas('request_audit_events', ['event_key' => 'request.type.deleted.v1', 'aggregate_public_id' => $publicId]);

        [, $publishedType] = $this->publishedType();
        $this->expectException(ValidationException::class);
        app(DeleteUnpublishedRequestType::class)->handle($publishedType, $actorId);
    }

    public function test_stale_version_is_rejected_and_retirement_preserves_history(): void
    {
        [$actorId, $type, $published] = $this->publishedType();

        try {
            app(PublishTypeVersion::class)->handle($type->refresh(), $actorId, 1);
            $this->fail('Stale publication must fail.');
        } catch (ValidationException $exception) {
            $this->assertSame(['stale_version'], $exception->errors()['lock_version']);
        }

        $retired = app(RetireRequestType::class)->handle($type->refresh(), $actorId);
        $this->assertSame(RequestTypeStatus::Retired, $retired->status);
        $this->assertDatabaseHas('request_type_versions', ['id' => $published->id, 'status' => 'published']);
        $this->assertDatabaseHas('request_outbox_messages', ['event_key' => 'request.type.retired.v1']);
    }

    public function test_publish_repairs_multiple_published_versions_left_by_external_data_mutation(): void
    {
        [$actorId, $type, $first] = $this->publishedType();
        app(CloneTypeVersion::class)->handle($type->refresh(), $first, $actorId);
        $second = app(PublishTypeVersion::class)->handle($type->refresh(), $actorId, $type->refresh()->lock_version);
        $thirdDraft = app(CloneTypeVersion::class)->handle($type->refresh(), $second, $actorId);

        DB::table('request_type_versions')->where('id', $first->id)->update(['status' => RequestTypeVersionStatus::Published->value]);
        DB::table('request_types')->where('id', $type->id)->update(['current_published_version_id' => $first->id]);

        $published = app(PublishTypeVersion::class)->handle($type->refresh(), $actorId, $type->refresh()->lock_version);

        $this->assertSame($thirdDraft->id, $published->id);
        $this->assertSame($published->id, $type->refresh()->current_published_version_id);
        $this->assertNull($type->active_draft_version_id);
        $this->assertSame(1, $type->versions()->where('status', RequestTypeVersionStatus::Published->value)->count());
        $this->assertDatabaseHas('request_type_versions', ['id' => $first->id, 'status' => RequestTypeVersionStatus::Superseded->value]);
        $this->assertDatabaseHas('request_type_versions', ['id' => $second->id, 'status' => RequestTypeVersionStatus::Superseded->value]);
        $this->assertDatabaseHas('request_type_versions', ['id' => $thirdDraft->id, 'status' => RequestTypeVersionStatus::Published->value]);
    }

    public function test_save_draft_rejects_unsafe_sla_combinations_at_the_service_boundary(): void
    {
        $actorId = $this->user('SLA publisher');
        $invalidStages = [
            ['sla_minutes' => 525601, 'warning_minutes_before' => null, 'grace_minutes' => 0, 'timeout_action' => 'notify_only'],
            ['sla_minutes' => 60, 'warning_minutes_before' => 61, 'grace_minutes' => 0, 'timeout_action' => 'notify_only'],
            ['sla_minutes' => 60, 'warning_minutes_before' => 15, 'grace_minutes' => 30, 'timeout_action' => 'notify_only'],
            ['sla_minutes' => null, 'warning_minutes_before' => null, 'grace_minutes' => 0, 'timeout_action' => 'suspend'],
        ];

        foreach ($invalidStages as $index => $invalidSla) {
            $group = app(CreateRequestGroup::class)->handle(['code' => 'SLA'.$index.uniqid(), 'name' => 'SLA'], $actorId);
            $type = app(CreateRequestType::class)->handle(['request_group_id' => $group->id, 'code' => 'SLA_TYPE'.$index.uniqid(), 'name' => 'SLA type'], $actorId);
            $draft = $this->validDraft($actorId);
            $draft['stages'][0] += $invalidSla;

            try {
                app(SaveTypeDraft::class)->handle($type, $draft, $actorId, 1);
                $this->fail('Unsafe SLA configuration must be rejected.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('stages', $exception->errors());
            }

            $this->assertSame(0, $type->activeDraft->stages()->count());
            $this->assertSame(1, $type->refresh()->lock_version);
        }
    }

    private function publishedType(): array
    {
        $actorId = $this->user('Publisher '.uniqid());
        $group = app(CreateRequestGroup::class)->handle(['code' => 'G'.uniqid(), 'name' => 'Group'], $actorId);
        $type = app(CreateRequestType::class)->handle(['request_group_id' => $group->id, 'code' => 'T'.uniqid(), 'name' => 'Type'], $actorId);
        app(SaveTypeDraft::class)->handle($type, $this->validDraft($actorId), $actorId, 1);
        $published = app(PublishTypeVersion::class)->handle($type->refresh(), $actorId, 2);

        return [$actorId, $type->refresh(), $published];
    }

    private function validDraft(int $actorId): array
    {
        return [
            'title' => 'Leave request',
            'form_schema_json' => ['schema_version' => 1, 'sections' => [['key' => 'details', 'fields' => [['key' => 'reason', 'type' => 'textarea']]]]],
            'policy_json' => [], 'presentation_json' => [],
            'audiences' => [['actor_type' => 'user', 'actor_id' => $actorId, 'capability' => 'create']],
            'stages' => [['stage_key' => 'approval', 'name' => 'Approval', 'position' => 1, 'mode' => 'single', 'resolver_key' => 'fixed_users', 'resolver_config_json' => ['user_ids' => [$actorId]], 'allow_reassignment' => false]],
        ];
    }

    private function user(string $name): int
    {
        return (int) DB::table('users')->insertGetId(['name' => $name, 'email' => uniqid().'@example.test', 'is_active' => true, 'password' => null, 'created_at' => now(), 'updated_at' => now()]);
    }
}
