<?php

namespace Tests\Feature\Request\Architecture;

use Tests\TestCase;

class RequestProductionDashboardContractTest extends TestCase
{
    public function test_dashboard_is_production_focused_and_role_aware(): void
    {
        $controller = file_get_contents(base_path('Modules/Request/Http/Controllers/RequestDashboardController.php'));
        $query = file_get_contents(base_path('Modules/Request/Application/Queries/RequestDashboardQuery.php'));
        $view = file_get_contents(base_path('Modules/Request/resources/views/dashboard.blade.php'));

        $this->assertStringContainsString('RequestDashboardQuery', $controller);
        $this->assertStringNotContainsString('DB::table', $controller);
        $this->assertStringNotContainsString('REQUEST_UI_DEMO', $controller);
        $this->assertStringNotContainsString('DEMO-DRAFT-001', $controller);
        $this->assertStringNotContainsString('DEMO-PENDING-001', $controller);

        foreach ([
            'request.instance.view-own',
            'request.instance.create',
            'request.task.view',
            'request.group.view',
            'request.type.view',
            'request.report.view',
            'request.operation.view',
        ] as $permission) {
            $this->assertStringContainsString($permission, $query);
        }

        foreach ([
            'Tổng quan Đề nghị',
            'Tạo đề nghị',
            'Đề nghị đang xử lý',
            'Cần bạn bổ sung',
            'Chờ bạn duyệt',
            'SLA cần chú ý',
            'Việc cần bạn xử lý',
            'Đề nghị gần đây của tôi',
            'Quản trị Đề nghị',
        ] as $copy) {
            $this->assertStringContainsString($copy, $view);
        }

        $this->assertStringContainsString("@include('Request::partials.workspace-navigation')", $view);
        $this->assertStringContainsString("timezone(config('app.timezone'))", $view);
        $this->assertStringNotContainsString('UI-01', $view);
        $this->assertStringNotContainsString('UI-07', $view);
        $this->assertStringNotContainsString('REQUEST_UI_DEMO', $view);
        $this->assertStringNotContainsString('RequestDemoSeeder', $view);
        $this->assertStringNotContainsString('hasRole(', $view);
        $this->assertStringNotContainsString('Super Admin', $view);
    }
}
