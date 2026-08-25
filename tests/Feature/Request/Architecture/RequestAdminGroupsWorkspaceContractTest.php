<?php

namespace Tests\Feature\Request\Architecture;

use Tests\TestCase;

class RequestAdminGroupsWorkspaceContractTest extends TestCase
{
    public function test_admin_groups_workspace_is_ordered_status_aware_and_navigable(): void
    {
        $controller = file_get_contents(base_path('Modules/Request/Http/Controllers/RequestDefinitionController.php'));
        $model = file_get_contents(base_path('Modules/Request/Models/RequestGroup.php'));
        $view = file_get_contents(base_path('Modules/Request/resources/views/admin/groups.blade.php'));

        foreach (['Quản lý nhóm đề nghị', 'Thứ tự hiển thị', 'Trạng thái', 'Số loại đề nghị', 'Loại đề nghị trong nhóm'] as $label) {
            $this->assertStringContainsString($label, $view);
        }

        foreach (['Đang hoạt động', 'Ngừng sử dụng', 'Chưa có loại đề nghị'] as $label) {
            $this->assertStringContainsString($label, $view);
        }

        $this->assertStringContainsString('Đi tới quản lý loại đề nghị', $view);
        $this->assertStringContainsString('min-h-11', $view);
        $this->assertStringContainsString("with(['types:id,request_group_id,public_id,code,name,status'])", $controller);
        $this->assertStringContainsString("withCount('types')", $controller);
        $this->assertStringContainsString("orderBy('sort_order')", $controller);
        $this->assertStringContainsString("orderBy('name')", $controller);
        $this->assertStringContainsString("paginate(25)", $controller);
        $this->assertStringContainsString("Gate::authorize('viewAny', RequestGroup::class)", $controller);

        foreach (['sort_order', 'is_active', 'archived_at'] as $capability) {
            $this->assertStringContainsString($capability, $model);
        }

        $this->assertStringNotContainsString('wire:model', $view);
        $this->assertStringNotContainsString('wire:click', $view);
        $this->assertStringNotContainsString('App\\Models\\User', $controller.$model.$view);
    }
}
