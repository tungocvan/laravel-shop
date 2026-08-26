<?php

namespace Tests\Feature\Request\Definition;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Request\Application\Services\CreateRequestGroup;
use Modules\Request\Application\Services\CreateRequestType;
use Modules\Request\Livewire\Admin\TypeDesigner;
use Modules\Request\Models\RequestType;
use Modules\Request\Policies\RequestTypePolicy;
use Spatie\Permission\Models\Permission;

class RequestTypeDesignerSlaTest extends RequestDefinitionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['auth.defaults.guard' => 'admin']);
        $this->app['view']->addNamespace('Request', base_path('Modules/Request/resources/views'));
        Gate::policy(RequestType::class, RequestTypePolicy::class);
        Permission::findOrCreate('request.type.update', 'admin');
        Permission::findOrCreate('request.type.publish', 'admin');
    }

    public function test_designer_persists_real_timeout_and_email_preferences(): void
    {
        [$designer, $approver, $type] = $this->designerFixture();

        Livewire::actingAs($designer, 'admin')
            ->test(TypeDesigner::class, ['typePublicId' => $type->public_id])
            ->call('addStage')
            ->set('stages.0.resolver_user_ids', [$approver->id])
            ->set('stages.0.timeout_action', 'notify_only')
            ->set('stages.0.email_on_assignment', false)
            ->set('stages.0.email_on_decision', false)
            ->set('stages.0.email_on_sla_warning', false)
            ->call('save')
            ->assertHasNoErrors();

        $stage = $type->activeDraft->stages()->firstOrFail();
        $this->assertSame('notify_only', $stage->timeout_action);
        $this->assertSame(0, $stage->grace_minutes);
        $this->assertFalse($stage->email_on_assignment);
        $this->assertFalse($stage->email_on_decision);
        $this->assertFalse($stage->email_on_sla_warning);
    }

    public function test_designer_rejects_warning_after_deadline_and_unbounded_duration(): void
    {
        [$designer, $approver, $type] = $this->designerFixture();

        $component = Livewire::actingAs($designer, 'admin')
            ->test(TypeDesigner::class, ['typePublicId' => $type->public_id])
            ->call('addStage')
            ->set('stages.0.resolver_user_ids', [$approver->id])
            ->set('stages.0.sla_value', 1)
            ->set('stages.0.sla_unit', 'hours')
            ->set('stages.0.warning_value', 2)
            ->set('stages.0.warning_unit', 'hours')
            ->call('save')
            ->assertHasErrors(['stages.0.warning_value']);

        $component
            ->set('stages.0.warning_value', 0)
            ->set('stages.0.sla_value', 366)
            ->set('stages.0.sla_unit', 'days')
            ->call('save')
            ->assertHasErrors(['stages.0.sla_value']);

        $this->assertSame(0, $type->activeDraft->stages()->count());
    }

    public function test_publish_validation_opens_an_actionable_modal_without_publishing(): void
    {
        [$designer, $approver, $type] = $this->designerFixture();

        Livewire::actingAs($designer, 'admin')
            ->test(TypeDesigner::class, ['typePublicId' => $type->public_id])
            ->call('addStage')
            ->set('stages.0.resolver_user_ids', [$approver->id])
            ->set('stages.0.sla_value', '')
            ->call('publish')
            ->assertHasErrors(['stages.0.sla_value'])
            ->assertSet('showValidationModal', true)
            ->assertSet('validationModalTitle', 'Chưa thể phát hành phiên bản')
            ->assertSee('Cấp duyệt 1: Hãy nhập thời hạn xử lý SLA.')
            ->assertSee('Quay lại chỉnh sửa');

        $type->refresh();
        $this->assertNotNull($type->active_draft_version_id);
        $this->assertNull($type->current_published_version_id);
    }

    public function test_valid_publish_redirects_without_opening_the_validation_modal(): void
    {
        [$designer, $approver, $type] = $this->designerFixture();

        Livewire::actingAs($designer, 'admin')
            ->test(TypeDesigner::class, ['typePublicId' => $type->public_id])
            ->call('addStage')
            ->set('stages.0.resolver_user_ids', [$approver->id])
            ->call('publish')
            ->assertSet('showValidationModal', false)
            ->assertRedirect(route('request.admin.types.versions', $type->public_id));

        $type->refresh();
        $this->assertNull($type->active_draft_version_id);
        $this->assertNotNull($type->current_published_version_id);
    }

    /** @return array{0:User,1:User,2:RequestType} */
    private function designerFixture(): array
    {
        $designer = User::factory()->create(['is_active' => true]);
        $approver = User::factory()->create(['is_active' => true]);
        $designer->givePermissionTo(['request.type.update', 'request.type.publish']);
        $group = app(CreateRequestGroup::class)->handle(['code' => 'SLA'.uniqid(), 'name' => 'SLA'], $designer->id);
        $type = app(CreateRequestType::class)->handle([
            'request_group_id' => $group->id,
            'code' => 'SLA_TYPE'.uniqid(),
            'name' => 'SLA request',
        ], $designer->id);

        return [$designer, $approver, $type];
    }
}
