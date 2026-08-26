<?php

namespace Tests\Feature\Request\Definition;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Request\Application\Services\CreateRequestGroup;
use Modules\Request\Application\Services\CreateRequestType;
use Modules\Request\Livewire\Admin\DefinitionIndex;
use Modules\Request\Models\RequestType;
use Modules\Request\Policies\RequestTypePolicy;
use Spatie\Permission\Models\Permission;

class RequestDefinitionIndexActionsTest extends RequestDefinitionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['auth.defaults.guard' => 'admin']);
        $this->app['view']->addNamespace('Request', base_path('Modules/Request/resources/views'));
        Gate::policy(RequestType::class, RequestTypePolicy::class);
        foreach (['request.type.view', 'request.type.create', 'request.type.update', 'request.type.audience.manage', 'request.type.delete'] as $permission) {
            Permission::findOrCreate($permission, 'admin');
        }
    }

    public function test_authorized_admin_can_submit_duplicate_form_and_is_redirected_to_the_new_draft(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->givePermissionTo(['request.type.view', 'request.type.create', 'request.type.update', 'request.type.audience.manage']);
        $group = app(CreateRequestGroup::class)->handle(['code' => 'DUPLICATE_UI', 'name' => 'Duplicate UI'], $admin->id);
        $source = app(CreateRequestType::class)->handle(['request_group_id' => $group->id, 'code' => 'DUPLICATE_UI_SOURCE', 'name' => 'Duplicate source'], $admin->id);

        $component = Livewire::actingAs($admin, 'admin')
            ->test(DefinitionIndex::class)
            ->call('prepareDuplicate', $source->public_id)
            ->assertSet('duplicateSourcePublicId', $source->public_id)
            ->assertSeeHtml('role="dialog"')
            ->assertSeeHtml('aria-modal="true"')
            ->set('duplicateCode', 'DUPLICATE_UI_COPY')
            ->set('duplicateName', 'Duplicate UI copy')
            ->call('duplicateType')
            ->assertHasNoErrors();

        $copy = RequestType::query()->where('code', 'DUPLICATE_UI_COPY')->firstOrFail();
        $component->assertRedirect(route('request.admin.types.designer', $copy->public_id));
        $this->assertNull($copy->current_published_version_id);
        $this->assertNotNull($copy->active_draft_version_id);
    }

    public function test_duplicate_form_shows_validation_instead_of_silently_ignoring_conflicting_code(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->givePermissionTo(['request.type.view', 'request.type.create', 'request.type.update']);
        $group = app(CreateRequestGroup::class)->handle(['code' => 'DUPLICATE_ERROR', 'name' => 'Duplicate error'], $admin->id);
        $source = app(CreateRequestType::class)->handle(['request_group_id' => $group->id, 'code' => 'EXISTING_CODE', 'name' => 'Existing'], $admin->id);

        Livewire::actingAs($admin, 'admin')
            ->test(DefinitionIndex::class)
            ->call('prepareDuplicate', $source->public_id)
            ->set('duplicateCode', 'EXISTING_CODE')
            ->call('duplicateType')
            ->assertHasErrors(['duplicateCode' => 'unique'])
            ->assertSee('Không thể tạo bản sao');
    }
}
