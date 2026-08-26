<?php

namespace Tests\Feature\Request\Architecture;

use Tests\TestCase;

class RequestWorkspaceNavigationContractTest extends TestCase
{
    public function test_workspace_navigation_is_permission_aware_and_production_focused(): void
    {
        $navigation = file_get_contents(base_path('Modules/Request/resources/views/partials/workspace-navigation.blade.php'));

        foreach ([
            'Tổng quan',
            'Tạo đề nghị',
            'Đề nghị của tôi',
            'Phê duyệt',
            'Quản trị',
            'Nhóm đề nghị',
            'Loại đề nghị',
            'Báo cáo',
            'Vận hành',
        ] as $label) {
            $this->assertStringContainsString($label, $navigation);
        }

        foreach ([
            'request.dashboard.view',
            'request.instance.create',
            'request.instance.view-own',
            'request.task.view',
            'request.group.view',
            'request.type.view',
            'request.report.view',
            'request.operation.view',
        ] as $permission) {
            $this->assertStringContainsString($permission, $navigation);
        }

        $this->assertStringContainsString("->can(\$item['permission'])", $navigation);
        $this->assertStringContainsString('aria-label="Điều hướng Đề nghị"', $navigation);
        $this->assertStringContainsString('aria-current="page"', $navigation);
        $this->assertStringContainsString('min-h-11', $navigation);
        $this->assertStringContainsString('overflow-x-auto', $navigation);

        $this->assertStringNotContainsString('hasRole(', $navigation);
        $this->assertStringNotContainsString('Super Admin', $navigation);
        $this->assertStringNotContainsString('UI-01', $navigation);
        $this->assertStringNotContainsString('REQUEST_UI_DEMO', $navigation);
    }

    public function test_primary_request_surfaces_mount_the_workspace_navigation(): void
    {
        foreach ([
            'Modules/Request/resources/views/dashboard.blade.php',
            'Modules/Request/resources/views/livewire/requester/catalog.blade.php',
            'Modules/Request/resources/views/livewire/requester/my-requests.blade.php',
            'Modules/Request/resources/views/livewire/approver/inbox.blade.php',
            'Modules/Request/resources/views/admin/reports.blade.php',
            'Modules/Request/resources/views/admin/operations.blade.php',
        ] as $view) {
            $contents = file_get_contents(base_path($view));

            $this->assertStringContainsString(
                "@include('Request::partials.workspace-navigation')",
                $contents,
                "Workspace navigation missing from {$view}."
            );
        }
    }
}
