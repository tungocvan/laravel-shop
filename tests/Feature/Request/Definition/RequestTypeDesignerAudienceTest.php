<?php

namespace Tests\Feature\Request\Definition;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Request\Application\Services\CreateRequestGroup;
use Modules\Request\Application\Services\CreateRequestType;
use Modules\Request\Application\Services\RequestAudienceService;
use Modules\Request\Domain\Enums\AudienceCapability;
use Modules\Request\Livewire\Admin\TypeDesigner;
use Modules\Request\Models\RequestType;
use Modules\Request\Policies\RequestTypePolicy;
use Spatie\Permission\Models\Permission;

class RequestTypeDesignerAudienceTest extends RequestDefinitionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['auth.defaults.guard' => 'admin']);
        $this->app['view']->addNamespace('Request', base_path('Modules/Request/resources/views'));
        Gate::policy(RequestType::class, RequestTypePolicy::class);
        Permission::findOrCreate('request.type.update', 'admin');
        Permission::findOrCreate('request.type.audience.manage', 'admin');
    }

    public function test_authorized_manager_can_assign_active_users_without_editing_json(): void
    {
        [$manager, $type] = $this->designerFixture(['request.type.update', 'request.type.audience.manage']);
        $employee = User::factory()->create(['is_active' => true]);
        $draft = $type->activeDraft()->firstOrFail();
        $draft->audiences()->create([
            'actor_type' => 'role',
            'actor_id' => 99,
            'capability' => 'discover',
        ]);

        Livewire::actingAs($manager, 'admin')
            ->test(TypeDesigner::class, ['typePublicId' => $type->public_id])
            ->assertSee('Phân quyền tạo đề nghị theo người dùng')
            ->assertDontSee('Audience JSON')
            ->set('audienceUserIds', [$employee->id])
            ->call('save')
            ->assertHasNoErrors();

        $draft = $type->refresh()->activeDraft()->with('audiences')->firstOrFail();
        $this->assertTrue(app(RequestAudienceService::class)->can($draft, $employee->id, AudienceCapability::Create));
        $this->assertDatabaseHas('request_type_audiences', [
            'request_type_version_id' => $draft->id,
            'actor_type' => 'role',
            'actor_id' => 99,
            'capability' => 'discover',
        ]);
    }

    public function test_editor_without_audience_permission_cannot_change_assigned_users(): void
    {
        [$editor, $type] = $this->designerFixture(['request.type.update']);
        $currentEmployee = User::factory()->create(['is_active' => true]);
        $otherEmployee = User::factory()->create(['is_active' => true]);
        $draft = $type->activeDraft()->firstOrFail();
        $draft->audiences()->create([
            'actor_type' => 'user',
            'actor_id' => $currentEmployee->id,
            'capability' => 'create',
        ]);

        Livewire::actingAs($editor, 'admin')
            ->test(TypeDesigner::class, ['typePublicId' => $type->public_id])
            ->assertSee('Bạn chỉ có quyền xem danh sách này')
            ->set('audienceUserIds', [$otherEmployee->id])
            ->call('save')
            ->assertForbidden();

        $this->assertSame(
            [$currentEmployee->id],
            $draft->audiences()->where('actor_type', 'user')->where('capability', 'create')->pluck('actor_id')->all(),
        );
    }

    public function test_editor_without_audience_permission_can_save_other_fields_when_assignments_are_unchanged(): void
    {
        [$editor, $type] = $this->designerFixture(['request.type.update']);
        $employee = User::factory()->create(['is_active' => true]);
        $draft = $type->activeDraft()->firstOrFail();
        $draft->audiences()->create([
            'actor_type' => 'user',
            'actor_id' => $employee->id,
            'capability' => 'create',
        ]);

        Livewire::actingAs($editor, 'admin')
            ->test(TypeDesigner::class, ['typePublicId' => $type->public_id])
            ->set('title', 'Loại đề nghị đã cập nhật')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Loại đề nghị đã cập nhật', $draft->refresh()->title);
        $this->assertDatabaseHas('request_type_audiences', [
            'request_type_version_id' => $draft->id,
            'actor_type' => 'user',
            'actor_id' => $employee->id,
            'capability' => 'create',
        ]);
    }

    public function test_authorized_manager_can_remove_an_inactive_legacy_assignment(): void
    {
        [$manager, $type] = $this->designerFixture(['request.type.update', 'request.type.audience.manage']);
        $inactiveEmployee = User::factory()->create(['is_active' => false]);
        $draft = $type->activeDraft()->firstOrFail();
        $draft->audiences()->create([
            'actor_type' => 'user',
            'actor_id' => $inactiveEmployee->id,
            'capability' => 'create',
        ]);

        Livewire::actingAs($manager, 'admin')
            ->test(TypeDesigner::class, ['typePublicId' => $type->public_id])
            ->assertSee('Tài khoản không còn hoạt động')
            ->assertSee('Bỏ chọn để gỡ quyền cũ')
            ->set('audienceUserIds', [])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('request_type_audiences', [
            'request_type_version_id' => $draft->id,
            'actor_type' => 'user',
            'actor_id' => $inactiveEmployee->id,
            'capability' => 'create',
        ]);
    }

    /** @return array{0:User,1:RequestType} */
    private function designerFixture(array $permissions): array
    {
        $designer = User::factory()->create(['is_active' => true]);
        $designer->givePermissionTo($permissions);
        $group = app(CreateRequestGroup::class)->handle([
            'code' => 'AUDIENCE'.uniqid(),
            'name' => 'Audience',
        ], $designer->id);
        $type = app(CreateRequestType::class)->handle([
            'request_group_id' => $group->id,
            'code' => 'AUDIENCE_TYPE'.uniqid(),
            'name' => 'Audience request',
        ], $designer->id);

        return [$designer, $type];
    }
}
