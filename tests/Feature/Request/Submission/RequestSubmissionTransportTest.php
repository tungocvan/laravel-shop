<?php

namespace Tests\Feature\Request\Submission;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Modules\Request\Application\Services\CreateInternalRequest;
use Modules\Request\Application\Services\SaveRequestDraft;
use Modules\Request\Livewire\Approver\DecisionPanel;
use Modules\Request\Livewire\Requester\RequestDetail;
use Modules\Request\Models\RequestTask;
use Modules\Request\Providers\RequestServiceProvider;
use Spatie\Permission\Models\Permission;
use Tests\Feature\Request\Draft\RequestDraftTestCase;

class RequestSubmissionTransportTest extends RequestDraftTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['auth.defaults.guard' => 'admin']);
        $this->app->register(RequestServiceProvider::class);
        $this->app['view']->addNamespace('Request', base_path('Modules/Request/resources/views'));
        Route::get('/request-test-inbox', fn () => 'inbox')->name('request.inbox');
        Route::get('/request-test-mine', fn () => 'mine')->name('request.mine');
        Route::get('/request-test/{requestPublicId}', fn () => 'detail')->name('request.show');
        Route::prefix('api')->middleware('api')->group(base_path('Modules/Request/routes/api.php'));
        foreach (['request.instance.view-own', 'request.instance.view-participant', 'request.instance.update-own', 'request.instance.cancel-own', 'request.instance.submit', 'request.task.view', 'request.task.decide'] as $permission) {
            Permission::findOrCreate($permission, 'admin');
        }
    }

    public function test_livewire_submit_and_decision_use_the_same_services(): void
    {
        [$requester, $approver, $request] = $this->draftForTransport();
        $requester->givePermissionTo(['request.instance.view-own', 'request.instance.update-own', 'request.instance.cancel-own', 'request.instance.submit']);
        $approver->givePermissionTo(['request.instance.view-participant', 'request.task.view', 'request.task.decide']);

        Livewire::actingAs($requester, 'admin')->test(RequestDetail::class, ['requestPublicId' => $request->public_id])
            ->set('values.subject', 'Current unsaved Livewire value')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('request.show', ['requestPublicId' => $request->public_id]));
        $this->assertSame('Current unsaved Livewire value', $request->refresh()->currentPayloadRevision->payload_json['subject']);
        $task = RequestTask::query()->firstOrFail();
        $request->refresh();
        Livewire::actingAs($approver, 'admin')->test(DecisionPanel::class, ['taskPublicId' => $task->public_id, 'requestVersion' => $request->lock_version, 'taskVersion' => $task->lock_version])
            ->call('approve')
            ->assertHasNoErrors()
            ->assertRedirect(route('request.inbox'));

        $this->assertSame('approved', $request->refresh()->status->value);
    }

    public function test_sanctum_submit_inbox_and_decision_have_admin_permission_parity(): void
    {
        [$requester, $approver, $request] = $this->draftForTransport();
        $requester->givePermissionTo(['request.instance.view-own', 'request.instance.submit']);
        $approver->givePermissionTo(['request.instance.view-participant', 'request.task.view', 'request.task.decide']);

        Sanctum::actingAs($requester);
        $this->postJson('/api/request/v1/requests/'.$request->public_id.'/submit', ['expected_version' => 2, 'payload' => ['subject' => 'Current API value']], ['Idempotency-Key' => (string) Str::uuid()])
            ->assertOk()
            ->assertJsonPath('data.status', 'pending');
        $this->assertSame('Current API value', $request->refresh()->currentPayloadRevision->payload_json['subject']);
        $task = RequestTask::query()->firstOrFail();
        $request->refresh();

        Sanctum::actingAs($approver);
        $this->getJson('/api/request/v1/inbox')->assertOk()->assertJsonPath('data.0.public_id', $task->public_id);
        $this->postJson('/api/request/v1/tasks/'.$task->public_id.'/decisions', ['decision' => 'approve', 'expected_request_version' => $request->lock_version, 'expected_task_version' => $task->lock_version], ['Idempotency-Key' => (string) Str::uuid()])
            ->assertOk()
            ->assertJsonPath('data.request.status', 'approved');
    }

    public function test_api_denies_missing_permission_and_hides_another_users_records(): void
    {
        [, , $request] = $this->draftForTransport();
        $outsider = User::factory()->create(['is_active' => true]);
        Sanctum::actingAs($outsider);

        $this->postJson('/api/request/v1/requests/'.$request->public_id.'/submit', ['expected_version' => 2], ['Idempotency-Key' => (string) Str::uuid()])->assertNotFound();
        $this->getJson('/api/request/v1/inbox')->assertForbidden();
    }

    public function test_api_reject_uses_the_general_decision_service_contract(): void
    {
        [$requester, $approver, $request] = $this->draftForTransport();
        $requester->givePermissionTo(['request.instance.view-own', 'request.instance.submit']);
        $approver->givePermissionTo(['request.instance.view-participant', 'request.task.view', 'request.task.decide']);
        Sanctum::actingAs($requester);
        $this->postJson('/api/request/v1/requests/'.$request->public_id.'/submit', ['expected_version' => 2], ['Idempotency-Key' => (string) Str::uuid()])->assertOk();
        $task = RequestTask::query()->firstOrFail();

        Sanctum::actingAs($approver);
        $this->postJson('/api/request/v1/tasks/'.$task->public_id.'/decisions', ['decision' => 'reject', 'reason' => 'Policy mismatch', 'expected_request_version' => 3, 'expected_task_version' => 1], ['Idempotency-Key' => (string) Str::uuid()])
            ->assertOk()
            ->assertJsonPath('data.decision', 'reject')
            ->assertJsonPath('data.request.status', 'rejected');
    }

    private function draftForTransport(): array
    {
        $requester = User::factory()->create(['is_active' => true]);
        $approver = User::factory()->create(['is_active' => true]);
        $stages = [['stage_key' => 'approval', 'name' => 'Approval', 'position' => 1, 'mode' => 'single', 'resolver_key' => 'fixed_users', 'resolver_config_json' => ['user_ids' => [$approver->id]], 'allow_reassignment' => false]];
        $type = $this->publishedType($requester->id, $this->simpleSchema(), $requester->id, $stages);
        $request = app(CreateInternalRequest::class)->handle($type, $requester->id, (string) Str::uuid());
        app(SaveRequestDraft::class)->handle($request, ['subject' => 'Transport parity'], $requester->id, 1, (string) Str::uuid());

        return [$requester, $approver, $request->refresh()];
    }
}
