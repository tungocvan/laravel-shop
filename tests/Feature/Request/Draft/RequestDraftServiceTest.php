<?php

namespace Tests\Feature\Request\Draft;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;
use Modules\Request\Application\Services\CancelInternalRequest;
use Modules\Request\Application\Services\CreateInternalRequest;
use Modules\Request\Application\Services\RetireRequestType;
use Modules\Request\Application\Services\SaveRequestDraft;
use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestAuditEvent;
use Modules\Request\Models\RequestOutboxMessage;
use Modules\Request\Models\RequestPayloadRevision;

class RequestDraftServiceTest extends RequestDraftTestCase
{
    public function test_eligible_user_creates_a_pinned_numbered_idempotent_draft(): void
    {
        $actorId = $this->activeUser();
        $type = $this->publishedType($actorId, $this->simpleSchema());
        $service = app(CreateInternalRequest::class);
        $key = (string) Str::uuid();

        $first = $service->handle($type, $actorId, $key);
        $replayed = $service->handle($type, $actorId, $key);

        $this->assertSame($first->id, $replayed->id);
        $this->assertMatchesRegularExpression('/^REQ-\d{4}-\d{8}$/', $first->request_number);
        $this->assertSame($type->current_published_version_id, $first->request_type_version_id);
        $this->assertSame(RequestStatus::Draft, $first->status);
        $this->assertSame(1, InternalRequest::query()->count());
        $this->assertDatabaseHas('request_audit_events', ['aggregate_public_id' => $first->public_id, 'event_key' => 'request.draft.created.v1']);
        $this->assertDatabaseHas('request_outbox_messages', ['aggregate_public_id' => $first->public_id, 'event_key' => 'request.draft.created.v1']);
    }

    public function test_non_audience_user_cannot_create_by_direct_service_path(): void
    {
        $designerId = $this->activeUser('Designer');
        $allowedId = $this->activeUser('Allowed');
        $deniedId = $this->activeUser('Denied');
        $type = $this->publishedType($designerId, $this->simpleSchema(), $allowedId);

        $this->expectException(ValidationException::class);
        app(CreateInternalRequest::class)->handle($type, $deniedId, (string) Str::uuid());
    }

    public function test_every_initial_field_type_is_normalized_and_validated_server_side(): void
    {
        $actorId = $this->activeUser();
        $roleId = $this->adminRole();
        $schema = ['schema_version' => 1, 'sections' => [['key' => 'all', 'fields' => [
            ['key' => 'text', 'type' => 'text'], ['key' => 'memo', 'type' => 'textarea'], ['key' => 'count', 'type' => 'integer'],
            ['key' => 'decimal', 'type' => 'decimal'], ['key' => 'money', 'type' => 'currency'], ['key' => 'day', 'type' => 'date'],
            ['key' => 'moment', 'type' => 'datetime'], ['key' => 'flag', 'type' => 'boolean'],
            ['key' => 'choice', 'type' => 'select', 'options' => [['key' => 'a'], ['key' => 'b']]],
            ['key' => 'choices', 'type' => 'multiselect', 'options' => [['key' => 'a'], ['key' => 'b']]],
            ['key' => 'user', 'type' => 'user'], ['key' => 'role', 'type' => 'role'], ['key' => 'files', 'type' => 'attachment'],
            ['key' => 'computed', 'type' => 'computed_display'],
        ]]]];
        $type = $this->publishedType($actorId, $schema);
        $request = app(CreateInternalRequest::class)->handle($type, $actorId, (string) Str::uuid());
        $revision = app(SaveRequestDraft::class)->handle($request, [
            'text' => '  hello  ', 'memo' => ' note ', 'count' => '7', 'decimal' => '001.2300',
            'money' => ['amount' => '010.500', 'currency' => 'usd'], 'day' => '2026-08-24', 'moment' => '2026-08-24T10:00:00+07:00',
            'flag' => true, 'choice' => 'a', 'choices' => ['b', 'a', 'a'], 'user' => (string) $actorId, 'role' => (string) $roleId,
            'files' => [(string) Str::ulid()], 'computed' => 'browser value',
        ], $actorId, 1, (string) Str::uuid());

        $this->assertSame('hello', $revision->payload_json['text']);
        $this->assertSame(7, $revision->payload_json['count']);
        $this->assertSame('1.23', $revision->payload_json['decimal']);
        $this->assertSame(['amount' => '10.5', 'currency' => 'USD'], $revision->payload_json['money']);
        $this->assertSame('2026-08-24T03:00:00Z', $revision->payload_json['moment']);
        $this->assertSame(['b', 'a'], $revision->payload_json['choices']);
        $this->assertArrayNotHasKey('computed', $revision->payload_json);
        $this->assertSame(64, strlen($revision->payload_checksum));
    }

    public function test_unknown_and_invalid_values_cannot_enter_authoritative_payload(): void
    {
        $actorId = $this->activeUser();
        $type = $this->publishedType($actorId, $this->simpleSchema());
        $request = app(CreateInternalRequest::class)->handle($type, $actorId, (string) Str::uuid());

        try {
            app(SaveRequestDraft::class)->handle($request, ['subject' => ['not a string'], 'status' => 'approved'], $actorId, 1, (string) Str::uuid());
            $this->fail('Invalid payload must fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('payload.status', $exception->errors());
            $this->assertArrayHasKey('payload.subject', $exception->errors());
        }

        $this->assertSame(0, RequestPayloadRevision::query()->count());
        $this->assertSame(1, $request->refresh()->lock_version);
    }

    public function test_hidden_and_computed_browser_values_are_stripped(): void
    {
        $actorId = $this->activeUser();
        $schema = ['schema_version' => 1, 'sections' => [['key' => 'safe', 'fields' => [
            ['key' => 'show_secret', 'type' => 'boolean'],
            ['key' => 'secret', 'type' => 'text', 'visible_when' => ['field' => 'show_secret', 'operator' => 'equals', 'value' => true]],
            ['key' => 'computed', 'type' => 'computed_display'],
        ]]]];
        $type = $this->publishedType($actorId, $schema);
        $request = app(CreateInternalRequest::class)->handle($type, $actorId, (string) Str::uuid());
        $revision = app(SaveRequestDraft::class)->handle($request, ['show_secret' => false, 'secret' => 'browser secret', 'computed' => 'forged'], $actorId, 1, (string) Str::uuid());

        $this->assertSame(['show_secret' => false], $revision->payload_json);
    }

    public function test_save_is_idempotent_and_stale_two_tab_write_is_rejected(): void
    {
        $actorId = $this->activeUser();
        $type = $this->publishedType($actorId, $this->simpleSchema());
        $request = app(CreateInternalRequest::class)->handle($type, $actorId, (string) Str::uuid());
        $service = app(SaveRequestDraft::class);
        $key = (string) Str::uuid();
        $first = $service->handle($request, ['subject' => 'First'], $actorId, 1, $key);
        $replayed = $service->handle($request, ['subject' => 'First'], $actorId, 1, $key);
        $this->assertSame($first->id, $replayed->id);

        try {
            $service->handle($request, ['subject' => 'Stale'], $actorId, 1, (string) Str::uuid());
            $this->fail('A stale save must fail.');
        } catch (ValidationException $exception) {
            $this->assertSame(['stale_version'], $exception->errors()['lock_version']);
        }
        $this->assertSame(1, RequestPayloadRevision::query()->count());

        try {
            $service->handle($request, ['subject' => 'Different'], $actorId, 1, $key);
            $this->fail('Reusing a key with another fingerprint must fail.');
        } catch (ValidationException $exception) {
            $this->assertSame(['idempotency_conflict'], $exception->errors()['idempotency_key']);
        }
    }

    public function test_cancellation_is_terminal_idempotent_and_audited(): void
    {
        $actorId = $this->activeUser();
        $type = $this->publishedType($actorId, $this->simpleSchema());
        $request = app(CreateInternalRequest::class)->handle($type, $actorId, (string) Str::uuid());
        $key = (string) Str::uuid();
        $service = app(CancelInternalRequest::class);
        $cancelled = $service->handle($request, $actorId, 1, $key);
        $replayed = $service->handle($request, $actorId, 1, $key);

        $this->assertSame($cancelled->id, $replayed->id);
        $this->assertSame(RequestStatus::Cancelled, $cancelled->status);
        $this->assertSame(2, $cancelled->lock_version);
        $this->assertSame(1, RequestAuditEvent::query()->where('event_key', 'request.cancelled.v1')->count());
        $this->assertSame(1, RequestOutboxMessage::query()->where('event_key', 'request.cancelled.v1')->count());

        $this->expectException(LogicException::class);
        RequestPayloadRevision::factory()->create()->update(['payload_json' => ['changed' => true]]);
    }

    public function test_retired_type_blocks_an_unsubmitted_pinned_draft_save(): void
    {
        $actorId = $this->activeUser();
        $type = $this->publishedType($actorId, $this->simpleSchema());
        $request = app(CreateInternalRequest::class)->handle($type, $actorId, (string) Str::uuid());
        app(RetireRequestType::class)->handle($type, $actorId);

        $this->expectException(ValidationException::class);
        app(SaveRequestDraft::class)->handle($request, ['subject' => 'Blocked'], $actorId, 1, (string) Str::uuid());
    }
}
