<?php

namespace Tests\Feature\Request\Architecture;

use Tests\TestCase;

class RequestEmployeeWorkspaceContractTest extends TestCase
{
    public function test_employee_workspace_exposes_action_oriented_states(): void
    {
        $component = file_get_contents(base_path('Modules/Request/Livewire/Requester/MyRequests.php'));
        $query = file_get_contents(base_path('Modules/Request/Application/Queries/MyRequestsQuery.php'));
        $view = file_get_contents(base_path('Modules/Request/resources/views/livewire/requester/my-requests.blade.php'));

        foreach (['all', 'draft', 'processing', 'returned', 'completed'] as $workspace) {
            $this->assertStringContainsString("'{$workspace}'", $component);
        }

        foreach (['Tất cả', 'Bản nháp', 'Đang xử lý', 'Cần bổ sung', 'Hoàn tất'] as $label) {
            $this->assertStringContainsString($label, $view);
        }

        foreach (['Tiếp tục', 'Bổ sung ngay', 'Theo dõi', 'Xem kết quả', 'Xem lý do', 'Xem lịch sử'] as $action) {
            $this->assertStringContainsString($action, $view);
        }

        $this->assertStringContainsString('workspaceCounts', $component);
        $this->assertStringContainsString("where('requester_id', \$userId)", $query);
        $this->assertStringContainsString("whereIn('status', ['approved', 'rejected', 'cancelled'])", $query);
        $this->assertStringContainsString('aria-label="Trạng thái đề nghị của tôi"', $view);
        $this->assertStringContainsString('min-h-11', $view);
        $this->assertStringNotContainsString('REQUEST_UI_DEMO', $view);
        $this->assertStringNotContainsString('App\\Models\\User', $component.$query);
    }

    public function test_employee_workspace_counts_use_a_strict_sql_safe_aggregate_projection(): void
    {
        $query = file_get_contents(base_path('Modules/Request/Application/Queries/MyRequestsQuery.php'));
        $start = strpos($query, 'public function workspaceCounts');
        $end = strpos($query, 'public function findVisible');

        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        $workspaceCounts = substr($query, $start, $end - $start);

        $this->assertStringContainsString('InternalRequest::query()', $workspaceCounts);
        $this->assertStringContainsString("selectRaw('status, COUNT(*) as aggregate')", $workspaceCounts);
        $this->assertStringContainsString("groupBy('status')", $workspaceCounts);
        $this->assertStringNotContainsString('$this->baseQuery(', $workspaceCounts);
        $this->assertStringNotContainsString("select(['id'", $workspaceCounts);
    }
}
