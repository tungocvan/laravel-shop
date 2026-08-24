<?php

namespace Tests\Feature\Request\Draft;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Request\Application\Services\CreateInternalRequest;
use Modules\Request\Livewire\Requester\AttachmentManager;
use Modules\Request\Livewire\Requester\CommentComposer;
use Modules\Request\Livewire\Requester\RequestDetail;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Policies\InternalRequestPolicy;
use Spatie\Permission\Models\Permission;

class RequestDraftLivewireTest extends RequestDraftTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app['view']->addNamespace('Request', base_path('Modules/Request/resources/views'));
        Livewire::component('request.requester.attachment-manager', AttachmentManager::class);
        Livewire::component('request.requester.comment-composer', CommentComposer::class);
        Gate::policy(InternalRequest::class, InternalRequestPolicy::class);
        Route::get('/request-test-mine', fn () => 'mine')->name('request.mine');
    }

    public function test_owner_saves_through_livewire_while_other_user_gets_hidden_not_found(): void
    {
        config(['auth.defaults.guard' => 'admin']);
        foreach (['request.instance.view-own', 'request.instance.update-own', 'request.instance.cancel-own'] as $name) {
            Permission::findOrCreate($name, 'admin');
        }
        $owner = User::factory()->create(['is_active' => true]);
        $other = User::factory()->create(['is_active' => true]);
        $owner->givePermissionTo(['request.instance.view-own', 'request.instance.update-own', 'request.instance.cancel-own']);
        $other->givePermissionTo(['request.instance.view-own', 'request.instance.update-own', 'request.instance.cancel-own']);
        $type = $this->publishedType($owner->id, $this->simpleSchema());
        $request = app(CreateInternalRequest::class)->handle($type, $owner->id, (string) Str::uuid());

        Livewire::actingAs($owner, 'admin')->test(RequestDetail::class, ['requestPublicId' => $request->public_id])
            ->set('values.subject', 'Livewire draft')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('values.subject', 'Livewire draft');
        $this->assertSame('Livewire draft', $request->payloadRevisions()->firstOrFail()->payload_json['subject']);

        $this->expectException(ModelNotFoundException::class);
        Livewire::actingAs($other, 'admin')->test(RequestDetail::class, ['requestPublicId' => $request->public_id]);
    }
}
