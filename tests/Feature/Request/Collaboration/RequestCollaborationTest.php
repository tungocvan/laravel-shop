<?php

namespace Tests\Feature\Request\Collaboration;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Modules\Request\Application\Services\AddRequestComment;
use Modules\Request\Application\Services\CreateInternalRequest;
use Modules\Request\Application\Services\SaveRequestDraft;
use Modules\Request\Application\Services\SubmitInternalRequest;
use Modules\Request\Application\Services\UploadRequestAttachment;
use Modules\Request\Domain\Enums\AttachmentClassification;
use Modules\Request\Domain\Enums\AttachmentScanStatus;
use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Domain\Forms\FormPayloadValidator;
use Modules\Request\Http\Controllers\RequestAttachmentController;
use Modules\Request\Livewire\Requester\AttachmentManager;
use Modules\Request\Models\RequestAuditEvent;
use Modules\Request\Models\RequestComment;
use Modules\Request\Providers\RequestServiceProvider;
use Modules\Request\Support\LaravelPrivateRequestFileStore;
use Spatie\Permission\Models\Permission;
use Tests\Feature\Request\Draft\RequestDraftTestCase;
use ZipArchive;

class RequestCollaborationTest extends RequestDraftTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'auth.defaults.guard' => 'admin',
            'request.files' => array_replace(require base_path('Modules/Request/config/files.php'), ['disk' => 'local', 'scan_driver' => 'none']),
        ]);
        Storage::fake('local');
        $this->app->register(RequestServiceProvider::class);
        $this->app['view']->addNamespace('Request', base_path('Modules/Request/resources/views'));
        Livewire::component('request.requester.attachment-manager', AttachmentManager::class);
        Route::middleware('web')->group(base_path('Modules/Request/routes/web.php'));
        Route::prefix('api')->middleware('api')->group(base_path('Modules/Request/routes/api.php'));
        Route::get('/request-collaboration-test/{requestPublicId}/attachments/{attachmentPublicId}', RequestAttachmentController::class)->name('request.attachments.download');

        foreach (['request.instance.view-own', 'request.instance.view-participant', 'request.instance.view-all', 'request.instance.submit', 'request.comment.create', 'request.attachment.upload', 'request.attachment.download', 'request.audit.view'] as $permission) {
            Permission::findOrCreate($permission, 'admin');
        }
    }

    public function test_co_01_owner_and_participant_can_comment_but_outsider_and_terminal_request_are_denied(): void
    {
        [$owner, $participant, $request] = $this->pendingRequest();
        $owner->givePermissionTo(['request.instance.view-own', 'request.comment.create']);
        $participant->givePermissionTo(['request.instance.view-participant', 'request.comment.create']);
        $outsider = User::factory()->create(['is_active' => true]);
        $outsider->givePermissionTo(['request.instance.view-participant', 'request.comment.create']);

        $this->assertTrue(Gate::forUser($owner)->allows('create', [RequestComment::class, $request]));
        $this->assertTrue(Gate::forUser($participant)->allows('create', [RequestComment::class, $request]));
        $this->assertFalse(Gate::forUser($outsider)->allows('create', [RequestComment::class, $request]));
        app(AddRequestComment::class)->handle($request, 'Owner note', $owner->id, $request->lock_version, (string) Str::uuid());
        $comment = app(AddRequestComment::class)->handle($request->refresh(), 'Participant note', $participant->id, $request->lock_version, (string) Str::uuid());

        $this->assertSame(2, $request->comments()->count());
        $request->update(['status' => RequestStatus::Approved]);
        $this->assertFalse(Gate::forUser($owner)->allows('create', [RequestComment::class, $request->refresh()]));
        $this->expectException(ValidationException::class);
        app(AddRequestComment::class)->handle($request, 'Late note', $owner->id, $request->lock_version, (string) Str::uuid());
    }

    public function test_co_02_comment_text_is_plain_escaped_immutable_and_redactable(): void
    {
        [$owner, , $request] = $this->draftRequest();
        $owner->givePermissionTo(['request.instance.view-own', 'request.comment.create']);
        $body = '<script>alert("x")</script><img src=x onerror=alert(1)>';
        $comment = app(AddRequestComment::class)->handle($request, $body, $owner->id, $request->lock_version, (string) Str::uuid());

        $this->actingAs($owner, 'admin');
        $html = view('Request::livewire.requester.comment-composer', [
            'request' => $request->refresh(),
            'comments' => $request->comments()->paginate(10),
            'errors' => new ViewErrorBag,
        ])->render();
        $this->assertStringNotContainsString($body, $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertSame('plain', $comment->body_format);
        $comment->update(['redacted_at' => now(), 'redacted_by' => $owner->id, 'redaction_reason' => 'Privacy request']);
        $redactedHtml = view('Request::livewire.requester.comment-composer', [
            'request' => $request->refresh(),
            'comments' => $request->comments()->paginate(10),
            'errors' => new ViewErrorBag,
        ])->render();
        $this->assertStringNotContainsString('&lt;script&gt;', $redactedHtml);
        $this->assertStringContainsString(__('Request::request.comment_redacted'), $redactedHtml);
        $this->expectException(\LogicException::class);
        $comment->update(['body' => 'rewritten']);
    }

    public function test_co_03_private_upload_download_has_safe_metadata_headers_and_audit(): void
    {
        [$owner, , $request] = $this->draftRequest();
        $owner->givePermissionTo(['request.instance.view-own', 'request.attachment.upload', 'request.attachment.download']);
        $file = UploadedFile::fake()->createWithContent('../../unsafe.pdf', "%PDF-1.7\nprivate evidence");
        $idempotencyKey = (string) Str::uuid();

        $attachment = app(UploadRequestAttachment::class)->handle($request, $file, $owner->id, $request->lock_version, $idempotencyKey, 'evidence');
        $replayed = app(UploadRequestAttachment::class)->handle($request, $file, $owner->id, $request->lock_version, $idempotencyKey, 'evidence');

        $this->assertSame($attachment->id, $replayed->id);
        $this->assertDatabaseCount('request_attachments', 1);
        $this->assertSame(AttachmentScanStatus::Clean, $attachment->scan_status);
        $this->assertSame(AttachmentClassification::Confidential, $attachment->classification);
        $this->assertSame('unsafe.pdf', $attachment->original_filename);
        $this->assertStringStartsWith('request/attachments/'.$request->public_id.'/', $attachment->storage_path);
        $this->assertStringNotContainsString('unsafe', $attachment->generated_filename);
        Storage::disk('local')->assertExists($attachment->storage_path);
        $downloadUrl = '/request-collaboration-test/'.$request->public_id.'/attachments/'.$attachment->public_id;
        $response = $this->actingAs($owner, 'admin')->get($downloadUrl)
            ->assertOk()
            ->assertHeader('cache-control', 'max-age=0, no-store, private')
            ->assertHeader('pragma', 'no-cache')
            ->assertHeader('x-content-type-options', 'nosniff');
        $this->assertStringStartsWith('attachment;', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('unsafe.pdf', (string) $response->headers->get('content-disposition'));
        $this->assertDatabaseHas('request_audit_events', ['request_instance_id' => $request->id, 'event_key' => 'request.attachment.created.v1']);
        $this->assertDatabaseHas('request_audit_events', ['request_instance_id' => $request->id, 'event_key' => 'request.attachment.downloaded.v1']);
    }

    public function test_upload_removes_private_object_when_transactional_evidence_append_fails(): void
    {
        [$owner, , $request] = $this->draftRequest();
        DB::statement("CREATE TRIGGER request_outbox_upload_failure BEFORE INSERT ON request_outbox_messages WHEN NEW.event_key = 'request.attachment.created.v1' BEGIN SELECT RAISE(FAIL, 'forced outbox failure'); END");

        try {
            app(UploadRequestAttachment::class)->handle($request, UploadedFile::fake()->createWithContent('rollback.pdf', "%PDF-1.7\nrollback"), $owner->id, $request->lock_version, (string) Str::uuid());
            $this->fail('Forced outbox failure must roll back the attachment.');
        } catch (\Throwable $exception) {
            $this->assertStringContainsString('forced outbox failure', $exception->getMessage());
        }

        $this->assertDatabaseCount('request_attachments', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_field_attachment_manager_accepts_multiple_files_in_one_selection(): void
    {
        [$owner, , $request] = $this->draftRequest();
        $owner->givePermissionTo(['request.instance.view-own', 'request.attachment.upload']);

        Livewire::actingAs($owner, 'admin')
            ->test(AttachmentManager::class, [
                'requestPublicId' => $request->public_id,
                'requestVersion' => $request->lock_version,
                'fieldKey' => 'evidence',
            ])
            ->set('uploads', [
                UploadedFile::fake()->createWithContent('quote.pdf', "%PDF-1.7\nquote"),
                UploadedFile::fake()->createWithContent('plan.pdf', "%PDF-1.7\nplan"),
            ])
            ->call('store')
            ->assertHasNoErrors();

        $this->assertSame(2, $request->attachments()->where('payload_field_key', 'evidence')->count());
    }

    public function test_private_file_store_converts_an_unwritable_disk_into_a_validation_error(): void
    {
        $fileRoot = tempnam(sys_get_temp_dir(), 'request-unwritable-');
        config([
            'request.files.disk' => 'request_unwritable',
            'filesystems.disks.request_unwritable' => [
                'driver' => 'local',
                'root' => $fileRoot,
                'throw' => true,
            ],
        ]);

        try {
            app(LaravelPrivateRequestFileStore::class)->put(
                UploadedFile::fake()->createWithContent('quote.pdf', "%PDF-1.7\nquote"),
                'request/attachments/example',
                'quote.pdf',
            );
            $this->fail('An unwritable private disk must not leak a filesystem exception.');
        } catch (ValidationException $exception) {
            $this->assertSame(['attachment_storage_unavailable'], $exception->errors()['attachment']);
        } finally {
            if (is_string($fileRoot) && is_file($fileRoot)) {
                unlink($fileRoot);
            }
        }
    }

    public function test_co_04_file_attack_corpus_is_rejected_without_persistence(): void
    {
        [$owner, , $request] = $this->draftRequest();
        $owner->givePermissionTo(['request.instance.view-own', 'request.attachment.upload']);
        $service = app(UploadRequestAttachment::class);

        foreach ([
            UploadedFile::fake()->createWithContent('payload.php', '<?php echo 1;'),
            UploadedFile::fake()->createWithContent('confused.pdf', 'not a pdf'),
            UploadedFile::fake()->createWithContent('image.jpg', "%PDF-1.7\nwrong signature"),
            $this->maliciousOfficeArchive(),
        ] as $index => $file) {
            try {
                $service->handle($request->refresh(), $file, $owner->id, $request->lock_version, (string) Str::uuid());
                $this->fail("Attack corpus item {$index} was accepted.");
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }

        config(['request.files.max_bytes' => 5]);
        try {
            $service->handle($request->refresh(), UploadedFile::fake()->createWithContent('large.pdf', "%PDF-1.7\nlarge"), $owner->id, $request->lock_version, (string) Str::uuid());
            $this->fail('Oversized file was accepted.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
        $this->assertDatabaseCount('request_attachments', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_co_05_cross_request_idor_public_storage_and_quarantined_download_fail(): void
    {
        [$owner, , $request] = $this->draftRequest();
        [, , $otherRequest] = $this->draftRequest();
        $owner->givePermissionTo(['request.instance.view-own', 'request.attachment.upload', 'request.attachment.download']);
        $attachment = app(UploadRequestAttachment::class)->handle($request, UploadedFile::fake()->createWithContent('safe.pdf', "%PDF-1.7\nsafe"), $owner->id, $request->lock_version, (string) Str::uuid());

        $this->actingAs($owner, 'admin')->get('/request-collaboration-test/'.$otherRequest->public_id.'/attachments/'.$attachment->public_id)->assertNotFound();
        $this->assertContains($this->get('/storage/'.$attachment->storage_path)->getStatusCode(), [403, 404]);
        Storage::disk('local')->put($attachment->storage_path, "%PDF-1.7\ntampered");
        $this->actingAs($owner, 'admin')->get('/request-collaboration-test/'.$request->public_id.'/attachments/'.$attachment->public_id)->assertConflict();
        $attachment->update(['scan_status' => AttachmentScanStatus::Quarantined, 'quarantined_at' => now()]);
        $this->actingAs($owner, 'admin')->get('/request-collaboration-test/'.$request->public_id.'/attachments/'.$attachment->public_id)->assertForbidden();
    }

    public function test_rf_05_attachment_reference_must_match_request_requester_field_and_clean_state(): void
    {
        [$owner, , $request] = $this->draftRequest();
        $owner->givePermissionTo(['request.instance.view-own', 'request.attachment.upload']);
        $attachment = app(UploadRequestAttachment::class)->handle($request, UploadedFile::fake()->createWithContent('safe.pdf', "%PDF-1.7\nsafe"), $owner->id, $request->lock_version, (string) Str::uuid(), 'evidence');
        $schema = $request->typeVersion->form_schema_json;
        $validator = app(FormPayloadValidator::class);

        $this->assertSame([], $validator->validate($schema, ['subject' => 'Valid', 'evidence' => [$attachment->public_id]], false, $request->refresh())['errors']);
        $attachment->update(['scan_status' => AttachmentScanStatus::Quarantined]);
        $this->assertSame(['attachment_not_owned_or_clean'], $validator->validate($schema, ['subject' => 'Invalid', 'evidence' => [$attachment->public_id]], false, $request->refresh())['errors']['payload.evidence']);
    }

    public function test_audit_policy_scopes_each_event_to_a_visible_request(): void
    {
        [$owner, , $request] = $this->draftRequest();
        $outsider = User::factory()->create(['is_active' => true]);
        $owner->givePermissionTo(['request.instance.view-own', 'request.audit.view']);
        $outsider->givePermissionTo(['request.instance.view-participant', 'request.audit.view']);
        $event = RequestAuditEvent::query()->where('aggregate_public_id', $request->public_id)->firstOrFail();

        $this->assertTrue(Gate::forUser($owner)->allows('view', $event));
        $this->assertFalse(Gate::forUser($outsider)->allows('view', $event));
    }

    public function test_api_comment_and_upload_use_same_private_services(): void
    {
        [$owner, , $request] = $this->draftRequest();
        $owner->givePermissionTo(['request.instance.view-own', 'request.comment.create', 'request.attachment.upload']);
        Sanctum::actingAs($owner);

        $this->postJson('/api/request/v1/requests/'.$request->public_id.'/comments', ['body' => 'API note', 'expected_version' => $request->lock_version], ['Idempotency-Key' => (string) Str::uuid()])->assertCreated()->assertJsonPath('data.body', 'API note');
        $request->refresh();
        $this->post('/api/request/v1/requests/'.$request->public_id.'/attachments', ['attachment' => UploadedFile::fake()->createWithContent('api.pdf', "%PDF-1.7\napi"), 'expected_version' => $request->lock_version], ['Idempotency-Key' => (string) Str::uuid(), 'Accept' => 'application/json'])->assertCreated()->assertJsonPath('data.filename', 'api.pdf');
    }

    private function draftRequest(): array
    {
        $owner = User::factory()->create(['is_active' => true]);
        $participant = User::factory()->create(['is_active' => true]);
        $schema = ['schema_version' => 1, 'sections' => [['key' => 'details', 'label' => 'Details', 'fields' => [
            ['key' => 'subject', 'type' => 'text', 'label' => 'Subject', 'required' => true, 'validation' => ['max_length' => 100]],
            ['key' => 'evidence', 'type' => 'attachment', 'label' => 'Evidence', 'required' => false, 'classification' => 'confidential', 'validation' => ['max_count' => 2]],
        ]]]];
        $stages = [['stage_key' => 'approval', 'name' => 'Approval', 'position' => 1, 'mode' => 'single', 'resolver_key' => 'fixed_users', 'resolver_config_json' => ['user_ids' => [$participant->id]], 'allow_reassignment' => false]];
        $type = $this->publishedType($owner->id, $schema, $owner->id, $stages);
        $request = app(CreateInternalRequest::class)->handle($type, $owner->id, (string) Str::uuid());
        app(SaveRequestDraft::class)->handle($request, ['subject' => 'Collaboration', 'evidence' => []], $owner->id, 1, (string) Str::uuid());

        return [$owner, $participant, $request->refresh()];
    }

    private function pendingRequest(): array
    {
        [$owner, $participant, $request] = $this->draftRequest();
        app(SubmitInternalRequest::class)->handle($request, $owner->id, $request->lock_version, (string) Str::uuid(), ['subject' => 'Collaboration', 'evidence' => []]);

        return [$owner, $participant, $request->refresh()];
    }

    private function maliciousOfficeArchive(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'request-archive-');
        $archive = new ZipArchive;
        $archive->open($path, ZipArchive::OVERWRITE);
        $archive->addFromString('word/document.xml', '<document/>');
        $archive->addFromString('../payload.php', '<?php echo 1;');
        $archive->close();

        return new UploadedFile($path, 'traversal.docx', 'application/zip', null, true);
    }
}
