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
        Permission::findOrCreate('request.type.audience.manage', 'admin');
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

    public function test_designer_persists_required_as_boolean_and_date_today_layout_options(): void
    {
        [$designer, , $type] = $this->designerFixture();

        Livewire::actingAs($designer, 'admin')
            ->test(TypeDesigner::class, ['typePublicId' => $type->public_id])
            ->call('addSection')
            ->call('addField', 0)
            ->set('sections.0.fields.0.type', 'date')
            ->set('sections.0.fields.0.required', false)
            ->set('sections.0.fields.0.default_today', true)
            ->set('sections.0.fields.0.width', 'third')
            ->call('save')
            ->assertHasNoErrors();

        $field = $type->activeDraft()->firstOrFail()->form_schema_json['sections'][0]['fields'][0];
        $this->assertFalse($field['required']);
        $this->assertSame('today', $field['default']);
        $this->assertSame('third', $field['width']);
        $this->assertArrayNotHasKey('default_today', $field);

        Livewire::actingAs($designer, 'admin')
            ->test(TypeDesigner::class, ['typePublicId' => $type->public_id])
            ->assertSet('sections.0.fields.0.required', false)
            ->assertSet('sections.0.fields.0.default_today', true)
            ->assertSet('sections.0.fields.0.width', 'third');
    }

    public function test_designer_manages_select_options_and_grouped_integer_display_without_raw_schema_edits(): void
    {
        [$designer, , $type] = $this->designerFixture();

        Livewire::actingAs($designer, 'admin')
            ->test(TypeDesigner::class, ['typePublicId' => $type->public_id])
            ->call('addSection')
            ->call('addField', 0)
            ->set('sections.0.fields.0.key', 'advance_amount')
            ->set('sections.0.fields.0.type', 'integer')
            ->set('sections.0.fields.0.grouped_integer', true)
            ->assertSee('Phân cách hàng nghìn, không có số lẻ')
            ->call('addField', 0)
            ->set('sections.0.fields.1.key', 'expense_category')
            ->set('sections.0.fields.1.type', 'select')
            ->call('addFieldOption', 0, 1)
            ->set('sections.0.fields.1.options.0.key', 'customer_care')
            ->set('sections.0.fields.1.options.0.label', 'Chăm sóc khách hàng')
            ->call('addFieldOption', 0, 1)
            ->set('sections.0.fields.1.options.1.key', 'market_research')
            ->set('sections.0.fields.1.options.1.label', 'Nghiên cứu thị trường')
            ->call('moveFieldOption', 0, 1, 1, -1)
            ->assertSee('Danh sách lựa chọn')
            ->call('save')
            ->assertHasNoErrors();

        $fields = collect($type->activeDraft()->firstOrFail()->form_schema_json['sections'][0]['fields'])->keyBy('key');
        $this->assertSame('grouped_integer', $fields['advance_amount']['display_format']);
        $this->assertSame([
            ['key' => 'market_research', 'label' => 'Nghiên cứu thị trường'],
            ['key' => 'customer_care', 'label' => 'Chăm sóc khách hàng'],
        ], $fields['expense_category']['options']);

        $component = Livewire::actingAs($designer, 'admin')
            ->test(TypeDesigner::class, ['typePublicId' => $type->public_id])
            ->assertSet('sections.0.fields.0.grouped_integer', true)
            ->assertSet('sections.0.fields.1.options.0.key', 'market_research');

        $component
            ->set('sections.0.fields.1.options.1.key', 'market_research')
            ->call('publish')
            ->assertSet('showValidationModal', true)
            ->assertSee('Biểu mẫu còn trường hoặc cấu trúc chưa hợp lệ.');
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
